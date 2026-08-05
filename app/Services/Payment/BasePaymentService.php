<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BasePaymentService
{
    protected string $base_url;

    protected string $api_key;

    protected string $api_password;

    protected array $header = [];

    // -------------------------------------------------------------------------
    // Geidea v2 session signature
    //
    // Concat: amount (2-decimal string) + currency + merchantReferenceId + timestamp
    // Hash:   HMAC-SHA256 with api_password as key, return Base64
    // -------------------------------------------------------------------------

    protected function generateSignature(
        float $amount,
        string $currency,
        string $merchantReferenceId,
        string $timestamp
    ): string {
        // Format amount exactly as Geidea expects: plain decimal, no trailing zeros beyond 2dp
        $amountStr = number_format($amount, 2, '.', '');

        $plain = $amountStr.$currency.$merchantReferenceId.$timestamp;

        return base64_encode(hash_hmac('sha256', $plain, $this->api_password, true));
    }

    // Geidea timestamp format: "M/D/YYYY H:MM:SS AM/PM" UTC  e.g. "2/21/2024 5:16:48 AM"
    protected function geideaTimestamp(): string
    {
        return now('UTC')->format('n/j/Y g:i:s A');
    }

    // -------------------------------------------------------------------------
    // Generic HTTP helper
    // -------------------------------------------------------------------------

    protected function buildRequest(
        string $method,
        string $url,
        array $payload
    ): \Illuminate\Http\JsonResponse {
        try {
            Log::debug('Geidea request', [
                'url' => $this->base_url.$url,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($this->header)
                ->send($method, $this->base_url.$url, ['json' => $payload]);

            Log::debug('Geidea response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Geidea HTTP exception', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
