<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The single way user uploads are written under public/.
 *
 * Why this exists: every repository used to do
 *     $name = time().'_'.$file->getClientOriginalName();
 *     $file->move(public_path($dir), $name);
 * which keeps the client's own filename — including its extension — inside
 * the web root. Ad media was validated only as ['file', 'max:51200'], so an
 * uploaded "shell.php" became a URL the web server would execute (RCE).
 * Other risks from the same pattern: stored XSS via .svg/.html, path
 * traversal via a directory built from a user-controlled name, and
 * collisions when two files with the same name arrive in the same second.
 *
 * This helper is deliberately independent of request validation, so a
 * missing or weakened rule elsewhere cannot re-open the hole:
 *   - the extension is derived from the file's CONTENT (MIME sniffing),
 *     never from the client-supplied filename;
 *   - only allow-listed, non-executable, non-scriptable types are accepted;
 *   - the stored name is a random UUID;
 *   - the target directory is normalised and cannot escape public/.
 */
class SafeUpload
{
    /** Images: raster only — SVG can carry script and is served same-origin. */
    public const IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public const VIDEOS = ['mp4', 'mov', 'webm', 'mkv', 'avi'];

    public const DOCUMENTS = ['pdf'];

    /**
     * Move an uploaded file into public/{$directory} and return the relative
     * path to persist in the database (e.g. "Ads/3f1c…e9.jpg").
     *
     * @param  string[]  $allowed  allow-listed extensions (see the constants)
     *
     * @throws ValidationException when the content type is not allowed
     */
    public static function store(UploadedFile $file, string $directory, array $allowed = self::IMAGES, string $field = 'file'): string
    {
        $extension = strtolower((string) $file->guessExtension());

        // guessExtension() reports JPEGs as "jpeg"; normalise for the allowlist.
        if ($extension === 'jpeg' && in_array('jpg', $allowed, true)) {
            $extension = 'jpg';
        }

        if ($extension === '' || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                $field => __('validation.mimes', ['attribute' => $field, 'values' => implode(', ', $allowed)]),
            ]);
        }

        $directory = self::sanitizeDirectory($directory);
        $absolute = public_path($directory);

        if (! is_dir($absolute)) {
            mkdir($absolute, 0755, true);
        }

        $name = Str::uuid()->toString().'.'.$extension;
        $file->move($absolute, $name);

        return $directory.'/'.$name;
    }

    /**
     * Strip anything that could climb out of public/ or inject odd segments.
     * Keeps letters (any script, so Arabic names still work), digits, _ - .
     */
    public static function sanitizeDirectory(string $directory): string
    {
        $segments = array_filter(
            explode('/', str_replace('\\', '/', $directory)),
            fn ($s) => $s !== '' && $s !== '.' && $s !== '..'
        );

        $segments = array_map(
            fn ($s) => trim(preg_replace('/[^\p{L}\p{N}_\-.]+/u', '_', $s), '.'),
            $segments
        );

        $clean = implode('/', array_filter($segments, fn ($s) => $s !== ''));

        if ($clean === '') {
            throw new \InvalidArgumentException('Upload directory resolved to an empty path.');
        }

        return $clean;
    }
}
