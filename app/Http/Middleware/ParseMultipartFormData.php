<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * ROOT-CAUSE FIX for: form-data fields silently failing to update on PUT/PATCH.
 *
 * PHP only populates $_POST / $_FILES from a multipart/form-data body when
 * the HTTP method is POST — this is a PHP SAPI-level limitation, not a
 * Laravel bug, and it applies regardless of framework. A genuine PUT or
 * PATCH request with a multipart/form-data body (e.g. Postman's "form-data"
 * body type, which many API clients default to for consistency with their
 * other requests) arrives at Laravel with an entirely empty $request->all()
 * for that body — every 'sometimes' validation rule silently treats the
 * field as "not sent" and skips it, with no error surfaced anywhere.
 *
 * This was the confirmed cause of profile updates (e.g. birth_date) not
 * persisting: PUT /api/profile only reads via $request->validate([...]),
 * and if the client sends a multipart PUT body, none of those fields are
 * ever visible to Laravel — not just birth_date, but the whole payload.
 *
 * This middleware manually parses the raw multipart body for PUT/PATCH/
 * DELETE requests and merges the result into $request->request (used by
 * ->input()/->validate()) and $request->files (used by ->file()), so
 * every existing route and FormRequest works correctly without any other
 * code changes. Registered globally on the 'api' middleware group in
 * app/Http/Kernel.php — it only activates for the specific method +
 * content-type combination that's actually broken; every other request
 * passes through completely untouched.
 */
class ParseMultipartFormData
{
    public function handle(Request $request, Closure $next)
    {
        $method = $request->method();
        $contentType = (string) $request->header('Content-Type');

        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)
            && str_contains($contentType, 'multipart/form-data')
        ) {
            $this->parseInto($request, $contentType);
        }

        return $next($request);
    }

    protected function parseInto(Request $request, string $contentType): void
    {
        $rawBody = $request->getContent();

        if ($rawBody === '' || $rawBody === null) {
            return;
        }

        if (! preg_match('/boundary=(.*)$/', $contentType, $boundaryMatch)) {
            return;
        }

        // The boundary value may be quoted per RFC 7578 — strip quotes if present.
        $boundary = trim($boundaryMatch[1], "\"; \t\r\n");

        $blocks = preg_split('/-+'.preg_quote($boundary, '/').'/', $rawBody);

        $fields = [];
        $files = [];

        foreach ($blocks as $block) {
            $block = ltrim($block, "\r\n");

            // Skip empty separators and the final "--" closing boundary marker.
            if ($block === '' || $block === '--' || $block === "--\r\n") {
                continue;
            }

            // Split headers from body on the first blank line (CRLF CRLF).
            $parts = preg_split("/\r\n\r\n/", $block, 2);
            if (count($parts) < 2) {
                continue;
            }

            [$headers, $content] = $parts;
            $content = preg_replace('/\r\n$/', '', $content);

            if (! preg_match('/name="([^"]*)"/', $headers, $nameMatch)) {
                continue;
            }
            $name = $nameMatch[1];

            // File part — has a filename= attribute on the Content-Disposition line.
            if (preg_match('/filename="([^"]*)"/', $headers, $filenameMatch)) {
                $filename = $filenameMatch[1];

                if ($filename === '') {
                    // An empty file input was submitted — nothing to attach.
                    continue;
                }

                preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $mimeMatch);
                $mimeType = isset($mimeMatch[1]) ? trim($mimeMatch[1]) : 'application/octet-stream';

                $tmpPath = tempnam(sys_get_temp_dir(), 'mpput_');
                file_put_contents($tmpPath, $content);

                $uploadedFile = new UploadedFile(
                    $tmpPath,
                    $filename,
                    $mimeType,
                    null,
                    true // "test" mode — bypasses is_uploaded_file(), required since this
                    // file didn't arrive through PHP's normal upload mechanism.
                );

                $this->setNested($files, $name, $uploadedFile);

                continue;
            }

            // Regular text field.
            $this->setNested($fields, $name, $content);
        }

        if (! empty($fields)) {
            $request->request->add($fields);
        }

        foreach ($files as $key => $value) {
            $request->files->set($key, $value);
        }
    }

    /**
     * Supports PHP-style bracket field names from multipart bodies, e.g.
     * "images[]", "keep_image_ids[]", "hall[max_capacity]", "slots[0][day]" —
     * mirrors how PHP itself parses these for a normal POST request.
     */
    protected function setNested(array &$target, string $name, mixed $value): void
    {
        if (! str_contains($name, '[')) {
            $target[$name] = $value;

            return;
        }

        preg_match('/^([^\[]+)((\[[^\]]*\])+)$/', $name, $matches);
        if (empty($matches)) {
            $target[$name] = $value;

            return;
        }

        $base = $matches[1];
        preg_match_all('/\[([^\]]*)\]/', $matches[2], $keyMatches);
        $keys = $keyMatches[1];

        $cursor = &$target[$base];
        if (! is_array($cursor)) {
            $cursor = [];
        }

        foreach ($keys as $i => $key) {
            $isLast = $i === count($keys) - 1;

            if ($key === '') {
                // Trailing [] — append.
                if ($isLast) {
                    $cursor[] = $value;

                    return;
                }
                $cursor[] = [];
                $cursor = &$cursor[array_key_last($cursor)];

                continue;
            }

            if ($isLast) {
                $cursor[$key] = $value;

                return;
            }

            if (! isset($cursor[$key]) || ! is_array($cursor[$key])) {
                $cursor[$key] = [];
            }
            $cursor = &$cursor[$key];
        }
    }
}
