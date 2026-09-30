<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Decides whether an exception's message is safe to return to an API client.
 *
 * Many catch blocks returned `$e->getMessage()` straight into JSON. That is
 * correct for deliberate domain errors — e.g. abort(422, __('lang.time_slot_conflict'))
 * — but it also forwarded internals:
 *   - QueryException extends PDOException extends RuntimeException, so even
 *     "narrow" catch (\RuntimeException) blocks sent raw SQL (table/column
 *     names, bound values) to the client;
 *   - HTTP client errors expose gateway hosts ("cURL error 6: could not
 *     resolve host api.merchant.geidea.net");
 *   - PHP runtime errors (TypeError, ErrorException…) expose file paths.
 *
 * Rule: pass through messages we raised on purpose; replace everything else
 * with a generic translated message and log the real one so nothing is lost
 * for debugging.
 */
class ClientError
{
    public static function message(\Throwable $e, ?string $fallback = null): string
    {
        if (self::isUserFacing($e) && trim($e->getMessage()) !== '') {
            return $e->getMessage();
        }

        Log::warning('Internal error hidden from API client', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        return $fallback ?? __('lang.unexpected_error_try_again');
    }

    public static function isUserFacing(\Throwable $e): bool
    {
        // Never user-facing, whatever they extend.
        if ($e instanceof \PDOException
            || $e instanceof \Illuminate\Http\Client\RequestException
            || $e instanceof \Illuminate\Http\Client\ConnectionException
            || (interface_exists(\GuzzleHttp\Exception\GuzzleException::class) && $e instanceof \GuzzleHttp\Exception\GuzzleException)) {
            return false;
        }

        // abort(4xx, __('lang.…')) — the app's standard way of raising a user error.
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode() < 500 || $e->getStatusCode() === 502;
        }

        if ($e instanceof ValidationException) {
            return true;
        }

        // Domain exceptions the repositories/services throw deliberately
        // (e.g. "Unknown payment method", gateway-declined messages).
        return $e instanceof \RuntimeException
            || $e instanceof \InvalidArgumentException
            || $e instanceof \DomainException;
    }
}
