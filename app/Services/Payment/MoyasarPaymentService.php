<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Moysar — Islamic BNPL for Saudi Arabia.
 * Docs: https://developers.moyasar.sa (check official docs for exact endpoint shapes)
 */
class MoyasarPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.maysar.api_key', '');
        $this->baseUrl = config('services.maysar.base_url', 'https://api.maysar.sa/v1');
    }

    public function sendPayment(Request|array $input): array
    {
        $data = is_array($input) ? $input : $input->all();
        $payment = isset($data['payment_id']) ? Payment::find($data['payment_id']) : null;

        $amount = (float) ($data['amount'] ?? $payment?->amount ?? 0);
        $reference = $data['merchantReferenceId'] ?? $payment?->reference_id ?? uniqid('ref_');
        $email = $data['customer']['email'] ?? $data['email'] ?? $payment?->user?->email ?? null;
        $phone = $data['customer']['phoneNumber'] ?? $data['phone'] ?? $payment?->phone ?? null;
        $name = $data['customer']['name'] ?? $data['name'] ?? 'Customer';
        $description = $data['description'] ?? 'Weekend booking';

        $payload = [
            'merchant_order_id' => $reference,
            'amount' => $amount,
            'currency' => 'SAR',
            'customer' => ['email' => $email, 'phone' => $phone, 'name' => $name],
            'description' => $description,
            'callback_url' => url('/api/maysar/callback'),
            'success_url' => url('/payment-complete'),
            'failure_url' => url('/payment/failed'),
        ];

        try {
            $r = Http::withHeaders(['X-Api-Key' => $this->apiKey, 'Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/checkout/session", $payload);
            $body = $r->json();
            Log::info('Maysar session created', ['session_id' => $body['session_id'] ?? null, 'reference' => $reference]);

            if ($r->successful() && isset($body['redirect_url'])) {
                if ($payment) {
                    $payment->update(['payment_id' => $body['session_id'] ?? null]);
                }

                return ['success' => true, 'payment_url' => $body['redirect_url'], 'item_id' => $body['session_id'] ?? null];
            }

            return ['success' => false, 'message' => $body['message'] ?? 'Maysar session creation failed.'];
        } catch (\Throwable $e) {
            Log::error('Maysar sendPayment error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function callBack(Request $request): bool
    {
        $sessionId = $request->session_id ?? $request->input('session_id');
        $status = $request->status ?? $request->input('status');
        if (! $sessionId || $status !== 'paid') {
            return false;
        }

        $payment = Payment::where('payment_id', $sessionId)->first();
        if (! $payment) {
            return false;
        }

        $payment->update(['status' => 'paid']);
        (new \App\Repositories\Payment\PaymentRepository)->handlePostPayment($payment);

        return true;
    }

    public function confirmByOrderId(string $orderId): array
    {
        try {
            $r = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/checkout/session/{$orderId}");

            return ['success' => $r->successful(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPaymentDetails(string $paymentId): array
    {
        try {
            $r = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/checkout/session/{$paymentId}");

            return $r->json();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function refund(string $orderId, ?float $amount = null, string $reason = 'Customer cancellation'): array
    {
        try {
            $r = Http::withHeaders(['X-Api-Key' => $this->apiKey, 'Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/refunds", ['session_id' => $orderId, 'amount' => $amount, 'reason' => $reason]);

            return ['success' => $r->successful(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
