<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tappy (Tap Payments) gateway.
 * Docs: https://tap.company/docs
 */
class TabbyPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    private string $secretKey;

    private string $publicKey;

    private string $baseUrl;

    private string $merchantCode;

    public function __construct()
    {
        $this->publicKey = env('TABBY_PUBLIC_KEY', env('TAPPY_PUBLIC_KEY', ''));
        $this->secretKey = env('TABBY_SECRET_KEY', env('TAPPY_SECRET_KEY', ''));
        $this->baseUrl = env('TABBY_BASE_URL', env('TAPPY_BASE_URL', 'https://api.tabby.sa/api/v2/'));
        $this->merchantCode = env('TABBY_MERCHANT_CODE', 'Naqiappsau');
    }

    public function sendPayment(Request|array $input): array
    {
        $data = is_array($input) ? $input : $input->all();
        // Accept both calling conventions:
        // 1) Reservation flow: full data array with amount, description, customer, etc.
        // 2) Standalone: ['payment_id' => N]
        $payment = isset($data['merchantReferenceId'])
            ? Payment::where('reference_id', $data['merchantReferenceId'])->first()
            : null;

        $amount = (float) ($data['amount'] ?? $payment?->amount ?? 0);
        $reference = $data['merchantReferenceId'] ?? $payment?->reference_id ?? uniqid('ref_');
        $email = $data['customer']['email'] ?? $data['email'] ?? $payment?->user?->email ?? null;
        $phone = $data['customer']['phoneNumber'] ?? $data['phone'] ?? $payment?->phone ?? null;
        $name = $data['customer']['name'] ?? $data['name'] ?? 'Customer';
        $description = $data['description'] ?? 'Weekend booking';
        $currency = 'SAR';
        $returnUrl = url('/payment-complete');

        // Phone: strip country code — Tabby expects local 9-digit format e.g. "500000001"
        $localPhone = preg_replace('/^(\+?966|0)/', '', $phone ?? '500000001');
        $localPhone = preg_replace('/\D/', '', $localPhone);

        $payload = [
            'payment' => [
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => 'SAR',
                'description' => $description,

                'buyer' => [
                    'phone' => $localPhone,
                    'email' => $email ?? 'card.success@tabby.ai',
                    'name' => $name,
                    'dob' => $data['dob'] ?? '1990-01-01',
                ],

                'buyer_history' => [
                    'registered_since' => now()->subYear()->toIso8601String(),
                    'loyalty_level' => 0,
                    'wishlist_count' => 0,
                    'is_social_networks_connected' => false,
                    'is_phone_number_verified' => true,
                    'is_email_verified' => true,
                ],

                'shipping_address' => [
                    'city' => $data['city'] ?? 'Riyadh',
                    'address' => $data['country'] ?? 'Saudi Arabia',
                    'zip' => $data['shipping_address']['zip'] ?? '00000',
                ],

                'order' => [
                    'reference_id' => (string) $reference,
                    'updated_at' => Carbon::now()->toIso8601String(),
                    'tax_amount' => '0.00',
                    'shipping_amount' => '0.00',
                    'discount_amount' => '0.00',
                    'items' => [[
                        'title' => $description,
                        'description' => $description,
                        'quantity' => 1,
                        'unit_price' => number_format($amount, 2, '.', ''),
                        'discount_amount' => '0.00',
                        'reference_id' => (string) $reference,
                        'category' => 'Services',
                    ]],
                ],

                'order_history' => [],

                'meta' => [
                    'order_id' => $reference,
                    'customer' => (string) ($payment?->user_id ?? ''),
                ],
            ],

            'lang' => 'en',
            'merchant_code' => $this->merchantCode,
            'merchant_urls' => [
                'success' => route('payment.success', [
                    'reference_id' => $reference,
                    'payment_type' => $data['payment_method'] ?? 'tabby',
                ]),
                'cancel' => route('payment.cancelled', [
                    'reference_id' => $reference,
                    'payment_type' => $data['payment_method'] ?? 'tabby',
                ]),
                'failure' => route('payment.failed', [
                    'reference_id' => $reference,
                    'payment_type' => $data['payment_method'] ?? 'tabby',
                ]),
            ],
            'token' => null,
        ];
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('checkout', $payload);

            $body = $response->json();

            Log::info('Tabby checkout response', [
                'http_status' => $response->status(),
                'session_id' => $body['id'] ?? null,
                'payment_id' => $body['payment']['id'] ?? null,
                'reference' => $reference,
            ]);

            if (! $response->successful()) {
                Log::error('Tabby checkout failed', ['body' => $body, 'reference' => $reference]);

                return ['success' => false, 'message' => $body['error'] ?? $body['errorType'] ?? 'Tabby checkout failed.'];
            }

            // Response shape (confirmed from live API):
            // $body['payment']['id']                                                     → tabby payment ID
            // $body['configuration']['available_products']['installments'][0]['web_url'] → checkout URL
            // $body['id']                                                                → session ID
            $tabbyPayId = $body['payment']['id'] ?? null;
            $sessionId = $body['id'] ?? null;
            $checkoutUrl = $body['configuration']['available_products']['installments'][0]['web_url']
                        ?? $body['payment']['checkout_url']
                        ?? null;

            if (! $checkoutUrl) {
                Log::error('Tabby: no web_url in response', ['body' => $body]);

                return ['success' => false, 'message' => __('lang.tabby_no_checkout_url')];
            }

            // Append reservation_id / subscription_id to the redirect URLs so
            // success/cancel/failure handlers know what to activate or revert
            $reservationId = $data['reservation_id'] ?? $payment?->reservation_id ?? null;
            $subscriptionId = $data['subscription_id'] ?? $payment?->subscription_id ?? null;

            $appendParams = array_filter([
                'reservation_id' => $reservationId,
                'subscription_id' => $subscriptionId,
            ]);

            if ($appendParams) {
                // merchant_urls were already set in the payload — append IDs to each
                // (Tabby echoes them back in $body['merchant_urls'] but we built them
                //  before the payment model was linked, so IDs may have been null then)
                $payload['merchant_urls'] = [
                    'success' => route('payment.success', array_merge(array_filter([
                        'reference_id' => $reference,
                        'payment_type' => $data['payment_method'] ?? 'tabby',
                    ]), $appendParams)),
                    'cancel' => route('payment.cancelled', array_merge(array_filter([
                        'reference_id' => $reference,
                        'payment_type' => $data['payment_method'] ?? 'tabby',
                    ]), $appendParams)),
                    'failure' => route('payment.failed', array_merge(array_filter([
                        'reference_id' => $reference,
                        'payment_type' => $data['payment_method'] ?? 'tabby',
                    ]), $appendParams)),
                ];
            }

            if ($payment) {
                $payment->update(['payment_id' => $tabbyPayId]);
            }

            return [
                'success' => true,
                'payment_url' => $checkoutUrl,
                'item_id' => $tabbyPayId,
                'session_id' => $sessionId,
            ];

        } catch (\Throwable $e) {
            Log::error('Tabby sendPayment error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function callBack(Request $request): bool
    {
        $tapId = $request->tap_id ?? $request->input('tap_id');
        if (! $tapId) {
            return false;
        }

        try {
            $response = Http::withToken($this->secretKey)->get("{$this->baseUrl}/charges/{$tapId}");
            $charge = $response->json();

            if (($charge['status'] ?? '') !== 'CAPTURED') {
                return false;
            }

            $referenceId = $charge['reference']['transaction'] ?? null;
            $payment = Payment::where('reference_id', $referenceId)->first();
            if (! $payment) {
                return false;
            }

            $payment->update(['payment_id' => $tapId, 'status' => 'paid']);
            (new \App\Repositories\Payment\PaymentRepository)->handlePostPayment($payment);

            return true;
        } catch (\Throwable $e) {
            Log::error('Tappy callback error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function confirmByOrderId(string $orderId): array
    {
        try {
            $r = Http::withToken($this->secretKey)->get("{$this->baseUrl}/charges/{$orderId}");

            return ['success' => $r->successful(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPaymentDetails(string $paymentId): array
    {
        try {
            $r = Http::withToken($this->secretKey)->get("{$this->baseUrl}/charges/{$paymentId}");

            return $r->json();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function refund(string $orderId, ?float $amount = null, string $reason = 'Customer cancellation'): array
    {
        $payload = ['charge_id' => $orderId, 'reason' => $reason];
        if ($amount) {
            $payload['amount'] = $amount;
        }

        try {
            $r = Http::withToken($this->secretKey)->post("{$this->baseUrl}/refunds", $payload);

            return ['success' => $r->successful(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function retrieveTabbyPayment($id)
    {
        $http = Http::withToken($this->secretKey)->baseUrl($this->baseUrl);
        $response = $http->get("payments/$id");

        return json_decode($response->getBody()->getContents(), true);
    }

    public function capturePaymentRequest($payment_id, $reference_id, $amount)
    {
        try {
            $http = Http::withToken($this->secretKey)
                ->baseUrl($this->baseUrl)
                ->withHeaders(['Content-Type' => 'application/json']);

            $response = $http->post("payments/$payment_id/captures", [
                'amount' => $amount,
                'currency' => 'SAR',
                'tax_amount' => '0.00',
                'shipping_amount' => '0.00',
                'discount_amount' => '0.00',
                'reference_id' => $reference_id,
            ]);

            // BUG FIX: $response is an Illuminate\Http\Client\Response object,
            // not a JSON string — json_decode($response, true) always returned
            // null because PHP silently stringifies the object to its class
            // name rather than its body. ->json() decodes the actual response
            // body correctly.
            if (! $response->successful()) {
                Log::error('Tabby capturePaymentRequest failed', [
                    'payment_id' => $payment_id,
                    'reference_id' => $reference_id,
                    'http_status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Tabby capturePaymentRequest error', [
                'payment_id' => $payment_id,
                'reference_id' => $reference_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function handleSuccessRedirect(Request $request)
    {
        // BUG FIX: previously, if payment_id was missing from the redirect URL,
        // this method fell through with no return statement — Laravel renders
        // a blank/empty response, leaving the customer on a broken page with
        // no error message and nothing in the logs to diagnose it.
        if (! $request->has('payment_id')) {
            Log::warning('Tabby handleSuccessRedirect: payment_id missing from redirect', [
                'reference_id' => $request->input('reference_id'),
                'payment_type' => $request->input('payment_type'),
                'query' => $request->query(),
            ]);

            return $this->errorResultView();
        }

        $payment = Payment::where('payment_type', $request->input('payment_type'))
            ->where('reference_id', $request->input('reference_id'))
            ->orderByDesc('created_at')
            ->first();

        // BUG FIX: same silent-null failure mode when payment_id was present
        // but no matching Payment record existed (e.g. stale/tampered
        // reference_id, or the record was deleted) — this branch previously
        // had no else, so it also fell through silently.
        if (! $payment) {
            Log::warning('Tabby handleSuccessRedirect: no matching payment found', [
                'reference_id' => $request->input('reference_id'),
                'payment_type' => $request->input('payment_type'),
                'payment_id' => $request->input('payment_id'),
            ]);

            return $this->errorResultView();
        }

        // Retrieve payment
        $pay = $this->retrieveTabbyPayment($request->payment_id);
        // Capture if authorized
        if (isset($pay['status']) && $pay['status'] === 'AUTHORIZED') {
            $pay = $this->capturePaymentRequest(
                $request->payment_id,
                $payment->reference_id,
                $payment->amount
            );
        }

        $payment->update(['payment_id' => $request->input('payment_id'), 'status' => 'paid']);
        (new \App\Repositories\Payment\PaymentRepository)->handlePostPayment($payment);

        // Tax breakdown
        $totalAmount = (float) $payment->amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => $payment->status === 'paid' ? 'paid' : 'failed',
            'payment_type' => 'tabby',
            'payment' => $payment,
            'phone' => $payment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }

    /**
     * Renders the shared Payment.result view in its generic-error state
     * (the $status value here matches none of the view's known branches,
     * so it falls to the "something went wrong" fallback) with a
     * placeholder $payment object so the view's amount/phone fields
     * don't trigger property-access-on-null warnings.
     */
    private function errorResultView()
    {
        return view('Payment.result', [
            'status' => 'error',
            'payment_type' => 'tabby',
            'payment' => (object) ['amount' => 0, 'phone' => null],
            'phone' => null,
            'priceWithoutTax' => 0,
            'taxAmount' => 0,
        ]);
    }
}
