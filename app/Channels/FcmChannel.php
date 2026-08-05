<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const CACHE_KEY = 'fcm_v1_access_token';

    // ── Entry point ──────────────────────────────────────────────────────────

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $token = $notifiable->routeNotificationFor('fcm', $notification);

        if (empty($token)) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        try {
            $accessToken = $this->getAccessToken();
            $projectId = config('services.fcm.project_id');

            if (empty($projectId)) {
                Log::warning('FCM v1: FCM_PROJECT_ID not set in .env');

                return;
            }

            $this->dispatch($accessToken, $projectId, $token, $payload, $notifiable);

        } catch (\Throwable $e) {
            Log::error('FCM v1 channel error: '.$e->getMessage(), [
                'user_id' => $notifiable->id ?? null,
            ]);
        }
    }

    // ── OAuth 2.0 access token (cached 55 min) ───────────────────────────────

    /**
     * Returns a valid Bearer token, fetching and caching one if needed.
     */
    private function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(55), function () {
            $sa = $this->loadServiceAccount();
            $jwt = $this->buildJwt($sa);

            return $this->exchangeJwt($jwt);
        });
    }

    /**
     * Load and decode the service account JSON file.
     *
     * @throws \RuntimeException if file is missing or invalid
     */
    private function loadServiceAccount(): array
    {
        $path = storage_path('app/firebase/service-account.json');

        if (! file_exists($path)) {
            throw new \RuntimeException(
                'FCM service account not found at storage/app/firebase/service-account.json'
            );
        }

        $data = json_decode(file_get_contents($path), true);

        if (! is_array($data) || empty($data['private_key']) || empty($data['client_email'])) {
            throw new \RuntimeException(
                'FCM service account JSON is invalid or missing private_key / client_email'
            );
        }

        return $data;
    }

    /**
     * Build a signed JWT for the Google OAuth 2.0 token endpoint.
     *
     * Spec: https://developers.google.com/identity/protocols/oauth2/service-account#jwt-auth
     */
    private function buildJwt(array $sa): string
    {
        $now = time();

        // ── Header ────────────────────────────────────────────────────────────
        $header = $this->base64url(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        // ── Claims ────────────────────────────────────────────────────────────
        $claims = $this->base64url(json_encode([
            'iss' => $sa['client_email'],
            'sub' => $sa['client_email'],
            'aud' => self::TOKEN_URL,
            'scope' => self::SCOPE,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        // ── RS256 Signature ───────────────────────────────────────────────────
        $unsigned = $header.'.'.$claims;

        $privateKey = openssl_pkey_get_private($sa['private_key']);

        if (! $privateKey) {
            throw new \RuntimeException('FCM: could not load private key from service account.');
        }

        $signature = '';
        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('FCM: openssl_sign failed — '.openssl_error_string());
        }

        return $unsigned.'.'.$this->base64url($signature);
    }

    /**
     * Exchange a signed JWT for a short-lived OAuth 2.0 access token.
     */
    private function exchangeJwt(string $jwt): string
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'FCM token exchange failed: '.$response->body()
            );
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new \RuntimeException('FCM token exchange returned no access_token');
        }

        return $data['access_token'];
    }

    // ── Send the message ─────────────────────────────────────────────────────

    private function dispatch(
        string $accessToken,
        string $projectId,
        string $deviceToken,
        array $payload,
        mixed $notifiable
    ): void {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // FCM v1 requires all data values to be strings
        $data = collect($payload['data'] ?? [])
            ->map(fn ($v) => (string) $v)
            ->all();

        $body = [
            'message' => [
                'token' => $deviceToken,

                'notification' => [
                    'title' => $payload['title'] ?? 'Weekend',
                    'body' => $payload['body'] ?? '',
                ],

                // Android-specific config
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],

                // APNs (iOS) config
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'content-available' => 1,
                        ],
                    ],
                ],

                // Data payload (available in onMessage / onBackgroundMessage)
                'data' => $data,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $body);

        if ($response->successful()) {
            Log::info('FCM v1: message sent', [
                'name' => $response->json('name'),  // projects/{id}/messages/{msg_id}
                'user_id' => $notifiable->id ?? null,
            ]);

            return;
        }

        $error = $response->json('error') ?? [];
        $status = $error['status'] ?? '';
        $httpStatus = $response->status();

        // Extract the FCM-specific errorCode from the details array
        // e.g. {"status":"NOT_FOUND","details":[{"errorCode":"UNREGISTERED"}]}
        $errorCode = '';
        foreach ($error['details'] ?? [] as $detail) {
            if (isset($detail['errorCode'])) {
                $errorCode = $detail['errorCode'];
                break;
            }
        }

        // Stale / invalid token — clear it so we stop retrying this token
        $isStaleToken = in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true)
                     || in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)
                     || $httpStatus === 404;

        if ($isStaleToken) {
            Log::warning('FCM v1: stale token cleared', [
                'http_status' => $httpStatus,
                'status' => $status,
                'error_code' => $errorCode,
                'user_id' => $notifiable->id ?? null,
            ]);
            optional($notifiable)->forceFill(['fcm_token' => null])->save();

            return;
        }

        // Quota / server error — don't clear the token, just log
        Log::error('FCM v1: send failed', [
            'http_status' => $httpStatus,
            'error' => $error,
            'user_id' => $notifiable->id ?? null,
        ]);

        // Expired access token (401) — bust cache so next call gets a fresh one
        if ($httpStatus === 401) {
            Cache::forget(self::CACHE_KEY);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** URL-safe base64 without padding (RFC 4648 §5) */
    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
