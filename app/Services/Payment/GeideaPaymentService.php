<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Notifications\PaymentFailed;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeideaPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    public function __construct()
    {
        // Cast to string: an unconfigured gateway (CI, local, staging) must not
        // crash on construction — PaymentController injects this service, so a
        // TypeError here took down every payment endpoint, not just Geidea.
        $this->base_url = (string) config('services.geidea.base_url', '');
        $this->api_key = (string) config('services.geidea.api_key', '');
        $this->api_password = (string) config('services.geidea.api_password', '');

        $this->header = [
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.base64_encode("{$this->api_key}:{$this->api_password}"),
        ];
    }

    // -------------------------------------------------------------------------
    // Create a Geidea v2 session
    // POST /payment-intent/api/v2/direct/session
    //
    // Required fields (doc):
    //   amount, currency, timestamp, merchantReferenceId, signature,
    //   paymentOperation, callbackUrl, returnUrl, customer, initiatedBy
    // -------------------------------------------------------------------------

    public function sendPayment(Request|array $input): array
    {
        $data = $input instanceof Request ? $input->all() : $input;

        // --- Amount: must be a clean float, never a string or long decimal ---
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $currency = (string) ($data['currency'] ?? config('services.geidea.currency', 'SAR'));

        // --- merchantReferenceId ---
        $merchantReferenceId = (string) ($data['merchantReferenceId'] ?? '');

        // --- Timestamp + Signature (computed — not passed by caller) ---
        $timestamp = $this->geideaTimestamp();
        $signature = $this->generateSignature($amount, $currency, $merchantReferenceId, $timestamp);

        // --- Description: ASCII only ---
        $description = isset($data['description'])
            ? trim(preg_replace('/[^\x20-\x7E]/', '', $data['description'])) ?: 'Booking'
            : 'Booking';

        // --- URLs ---
        $appBase = rtrim(config('app.url'), '/');
        $callbackUrl = config('services.geidea.callback_url')
            ?: $appBase.'/api/geidea/payment/callback';
        $returnUrl = config('services.geidea.return_url')
            ?: $appBase.'/payment-complete';

        // --- Customer ---
        // v2 session: phoneNumber is the full international number e.g. "+966501231231"
        $phone = (string) ($data['customer']['phoneNumber'] ?? '');
        $phoneCountryCode = (string) ($data['customer']['phoneCountryCode'] ?? '+966');

        // Ensure phone starts with country code
        if ($phone && ! str_starts_with($phone, '+')) {
            $phone = $phoneCountryCode.ltrim($phone, '0');
        }

        // --- Build the exact v2 session payload from the doc ---
        // v2 session endpoint — requires timestamp + signature
        $v2payload = [
            'amount' => $amount,
            'currency' => $currency,
            'timestamp' => $timestamp,
            'merchantReferenceId' => $merchantReferenceId,
            'signature' => $signature,
            'paymentOperation' => 'Pay',
            'language' => 'en',
            'initiatedBy' => 'Internet',
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'description' => $description,
            'customer' => [
                'email' => (string) ($data['customer']['email'] ?? ''),
                'phoneNumber' => $phone,
                'phoneCountryCode' => $phoneCountryCode,
            ],
        ];

        // v1 eInvoice fallback payload — used when v2 session is not enabled on the merchant account
        $v1payload = [
            'amount' => $amount,
            'currency' => $currency,
            'merchantReferenceId' => $merchantReferenceId,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'description' => $description,
            'customer' => [
                'name' => (string) ($data['customer']['name'] ?? 'Customer'),
                'email' => (string) ($data['customer']['email'] ?? ''),
                'phoneNumber' => ltrim(str_replace($phoneCountryCode, '', $phone), '0'),
                'phoneCountryCode' => $phoneCountryCode,
            ],
            'eInvoiceDetails' => [
                'subtotal' => $amount,
                'grandTotal' => $amount,
                'extraChargesType' => 'Amount',
                'invoiceDiscountType' => 'Amount',
                'eInvoiceItems' => [[
                    'price' => $amount,
                    'quantity' => 1,
                    'total' => $amount,
                    'description' => $description,
                ]],
            ],
        ];

        // Try v2 first; fall back to v1 if merchant account does not have v2 enabled (code 013)
        $useV2 = config('services.geidea.use_v2_session', true);
        $payload = $useV2 ? $v2payload : $v1payload;
        $url = $useV2
            ? '/payment-intent/api/v2/direct/session'
            : '/payment-intent/api/v1/direct/eInvoice';

        $response = $this->buildRequest('POST', $url, $payload);
        $wrapper = $response->getData(true);
        $geideaBody = $wrapper['data'] ?? [];

        Log::info('Geidea session response', [
            'endpoint' => $url,
            'http_status' => $wrapper['status'] ?? null,
            'body' => $geideaBody,
        ]);

        // Error 013 = v2 session not enabled on this merchant account — retry with v1
        if ($useV2
            && ($geideaBody['detailedResponseCode'] ?? null) === '013'
        ) {
            Log::warning('Geidea v2 session not enabled (013) — retrying with v1 eInvoice');
            $response = $this->buildRequest('POST', '/payment-intent/api/v1/direct/eInvoice', $v1payload);
            $wrapper = $response->getData(true);
            $geideaBody = $wrapper['data'] ?? [];
            Log::info('Geidea v1 fallback response', ['http_status' => $wrapper['status'] ?? null, 'body' => $geideaBody]);
        }

        if ($wrapper['success'] ?? false) {
            $link = $geideaBody['session']['paymentUrl']
                 ?? $geideaBody['paymentIntent']['link']
                 ?? $geideaBody['paymentIntent']['paymentUrl']
                 ?? $geideaBody['paymentUrl']
                 ?? $geideaBody['redirectUrl']
                 ?? $geideaBody['url']
                 ?? null;

            $sessionId = $geideaBody['session']['id']
                      ?? $geideaBody['paymentIntent']['paymentIntentId']
                      ?? $geideaBody['id']
                      ?? null;

            if ($link) {
                return ['success' => true, 'url' => $link, 'item_id' => $sessionId];
            }

            Log::error('Geidea: no payment URL in response', ['body' => $geideaBody]);

            return ['success' => false, 'message' => __('lang.no_payment_url_in_response'), 'geidea_response' => $geideaBody];
        }

        $geideaMessage = $geideaBody['detailedResponseMessage']
            ?? $geideaBody['responseMessage']
            ?? json_encode($geideaBody);

        Log::error('Geidea v2 session failed', [
            'http_status' => $wrapper['status'] ?? null,
            'geidea_error' => $geideaBody,
            'payload_sent' => $payload,
        ]);

        return [
            'success' => false,
            'message' => $geideaMessage,
            'geidea_response' => $geideaBody,
        ];
    }

    // -------------------------------------------------------------------------
    // Webhook callback — Geidea POSTs here after payment completes
    // Payload: { merchantReferenceId, order: { orderId, status, detailedStatus } }
    // -------------------------------------------------------------------------

    public function callBack(Request $request): bool
    {
        $payload = $request->all();

        Log::info('Geidea callback raw payload', ['payload' => $payload]);

        if (! $this->verifyWebhookSignature($request)) {
            Log::warning('Geidea callback: invalid signature', ['ip' => $request->ip()]);

            return false;
        }

        // No webhook secret configured → the payload cannot be trusted as-is
        // (anyone could POST {"order":{"status":"Success","detailedStatus":"Paid"}}).
        // Re-verify the order status directly with Geidea's API instead.
        if (! config('services.geidea.webhook_secret')) {
            $orderId = $payload['order']['orderId'] ?? null;
            if (! $orderId) {
                Log::warning('Geidea callback: unsigned payload without orderId — ignored', ['ip' => $request->ip()]);

                return false;
            }

            return (bool) ($this->confirmByOrderId($orderId)['success'] ?? false);
        }

        $referenceId = $payload['merchantReferenceId'] ?? null;
        $gatewayOrderId = $payload['order']['orderId'] ?? null;
        $status = $payload['order']['status'] ?? null;
        $detailedStatus = $payload['order']['detailedStatus'] ?? null;

        Log::info('Geidea callback parsed', compact('referenceId', 'gatewayOrderId', 'status', 'detailedStatus'));

        if (! $referenceId) {
            Log::error('Geidea callback: missing merchantReferenceId', ['payload' => $payload]);

            return false;
        }

        $payment = Payment::where('reference_id', $referenceId)->first();

        if (! $payment) {
            Log::error('Geidea callback: Payment not found', ['reference_id' => $referenceId]);

            return false;
        }

        if ($payment->status === 'paid') {
            Log::info('Geidea callback: already processed', ['reference_id' => $referenceId]);

            return true;
        }

        if ($status === 'Success' && $detailedStatus === 'Paid') {
            return $this->handleSuccessfulPayment($payment, $gatewayOrderId);
        }

        return $this->handleFailedPayment($payment);
    }

    // -------------------------------------------------------------------------
    // Recovery: manually confirm by Geidea Order ID
    // POST /api/payments/confirm-by-order  { "order_id": "..." }
    // -------------------------------------------------------------------------

    public function confirmByOrderId(string $geideaOrderId): array
    {
        $details = $this->getPaymentDetails($geideaOrderId);

        if (! ($details['success'] ?? false)) {
            return ['success' => false, 'message' => __('lang.could_not_fetch_geidea_order'), 'data' => $details];
        }

        $order = $details['data'] ?? [];
        $status = $order['status'] ?? null;
        $detailedStatus = $order['detailedStatus'] ?? null;
        $referenceId = $order['merchantReferenceId'] ?? null;

        Log::info('Geidea confirmByOrderId', compact('geideaOrderId', 'status', 'detailedStatus', 'referenceId'));

        if (! $referenceId) {
            return ['success' => false, 'message' => __('lang.no_merchant_reference_in_response'), 'data' => $order];
        }

        $payment = Payment::where('reference_id', $referenceId)->first();

        if (! $payment) {
            return ['success' => false, 'message' => "No Payment found for reference {$referenceId}."];
        }

        if ($payment->status === 'paid') {
            return ['success' => true, 'message' => __('lang.payment_already_confirmed'), 'payment_id' => $payment->id];
        }

        if ($status === 'Success' && $detailedStatus === 'Paid') {
            $this->handleSuccessfulPayment($payment, $geideaOrderId);

            return ['success' => true, 'message' => __('lang.payment_confirmed_records_updated'), 'payment_id' => $payment->id];
        }

        return ['success' => false, 'message' => "Order status is '{$status}/{$detailedStatus}' — not paid.", 'data' => $order];
    }

    // -------------------------------------------------------------------------
    // Refund a paid Geidea order
    //
    // Endpoint: POST /payment-intent/api/v1/direct/order/{orderId}/refund
    //
    // Signed payload:
    //   amount + currency + merchantReferenceId (use original) + timestamp
    //
    // Geidea response on success:
    //   { "order": { "orderId": "...", "status": "Refunded", ... } }
    // -------------------------------------------------------------------------

    public function refund(string $geideaOrderId, ?float $amount = null, string $reason = 'Customer cancellation'): array
    {
        try {
            // Fetch the original order so we can read amount + currency + merchantReferenceId
            $details = $this->getPaymentDetails($geideaOrderId);

            if (! ($details['success'] ?? false)) {
                Log::error('Geidea refund: could not fetch order', ['order_id' => $geideaOrderId, 'details' => $details]);

                return ['success' => false, 'message' => __('lang.could_not_fetch_order_before_refund')];
            }

            $order = $details['data'] ?? [];
            $currency = $order['currency'] ?? config('services.geidea.currency', 'SAR');
            $merchantReferenceId = $order['merchantReferenceId'] ?? $geideaOrderId;

            // If no partial amount specified, refund the full order amount
            $refundAmount = $amount !== null
                ? round($amount, 2)
                : round((float) ($order['amount'] ?? 0), 2);

            $timestamp = $this->geideaTimestamp();
            $signature = $this->generateSignature($refundAmount, $currency, $merchantReferenceId, $timestamp);

            $payload = [
                'amount' => $refundAmount,
                'currency' => $currency,
                'timestamp' => $timestamp,
                'merchantReferenceId' => $merchantReferenceId,
                'signature' => $signature,
                'reason' => trim(preg_replace('/[^\x20-\x7E]/', '', $reason)) ?: 'Cancellation',
                'callbackUrl' => config('services.geidea.callback_url')
                    ?: rtrim(config('app.url'), '/').'/api/geidea/payment/callback',
            ];

            Log::info('Geidea refund request', ['order_id' => $geideaOrderId, 'payload' => $payload]);

            $url = rtrim($this->base_url, '/').'/payment-intent/api/v1/direct/order/'.urlencode($geideaOrderId).'/refund';
            $response = Http::withHeaders($this->header)->post($url, $payload);

            $body = $response->json();

            Log::info('Geidea refund response', ['status' => $response->status(), 'body' => $body]);

            if ($response->successful()) {
                $orderStatus = $body['order']['status'] ?? null;

                if (in_array($orderStatus, ['Refunded', 'PartiallyRefunded'], true)) {
                    return ['success' => true, 'data' => $body];
                }

                // HTTP 200 but status not yet Refunded — Geidea sometimes processes async
                Log::warning('Geidea refund: success HTTP but unexpected order status', ['body' => $body]);

                return ['success' => true, 'data' => $body];
            }

            $message = $body['responseMessage'] ?? $body['detailedResponseMessage'] ?? json_encode($body);
            Log::error('Geidea refund failed', ['order_id' => $geideaOrderId, 'body' => $body]);

            return ['success' => false, 'message' => $message, 'data' => $body];

        } catch (\Throwable $e) {
            Log::error('Geidea refund exception', ['order_id' => $geideaOrderId, 'message' => $e->getMessage()]);

            return ['success' => false, 'message' => \App\Support\ClientError::message($e)];
        }
    }

    // -------------------------------------------------------------------------
    // Query Geidea for a session/order by ID
    // -------------------------------------------------------------------------

    public function getPaymentDetails(string $payment_id): array
    {
        try {
            $url = rtrim($this->base_url, '/').'/payment-intent/api/v2/direct/session/'.urlencode($payment_id);
            $response = Http::withHeaders($this->header)->get($url);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Geidea getPaymentDetails error', ['message' => $e->getMessage()]);

            return ['success' => false, 'status' => 500, 'message' => \App\Support\ClientError::message($e)];
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function handleSuccessfulPayment(Payment $payment, ?string $gatewayOrderId): bool
    {
        $payment->update(['status' => 'paid', 'payment_id' => $gatewayOrderId ?? $payment->payment_id]);

        // Single source of truth for fulfillment — shared with Tabby/Tamara/Maysar.
        // Confirms the reservation, multi-booking group, viewing deposit or
        // subscription, and sends notifications outside any transaction so a
        // notification failure can never roll back a real payment confirmation.
        app(\App\Repositories\Payment\PaymentRepository::class)->handlePostPayment($payment->fresh());

        return true;
    }

    private function handleFailedPayment(Payment $payment): bool
    {
        $payment->update(['status' => 'failed']);
        $payment->user?->notify(new PaymentFailed($payment));
        Log::warning('Geidea callback: payment failed', ['reference_id' => $payment->reference_id]);

        return false;
    }

    private function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.geidea.webhook_secret');

        if (! $secret) {
            Log::warning('Geidea webhook secret not configured — skipping verification.');

            return true;
        }

        $signature = $request->header('X-Geidea-Signature');
        if (! $signature) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $request->getContent(), $secret),
            $signature
        );
    }
}
