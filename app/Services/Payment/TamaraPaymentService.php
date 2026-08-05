<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tamara — Buy Now Pay Later (BNPL) for Saudi Arabia.
 * Docs: https://docs.tamara.co
 */
class TamaraPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    private string $token;

    private string $baseUrl;

    private string $notifyUrl;

    protected $client;

    public function __construct()
    {
        $this->token = env('TAMARA_API_TOKEN', '');
        $this->baseUrl = env('TAMARA_BASE_URL', 'https://api.tamara.co');
        $this->notifyUrl = url('/api/tamara/callback');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function sendPayment(Request|array $input): array
    {

        $data = is_array($input) ? $input : $input->all();

        $payment = isset($data['merchantReferenceId'])
            ? Payment::where('reference_id', $data['merchantReferenceId'])->first()
            : null;

        $amount = (float) ($data['amount'] ?? $payment?->amount ?? 0);
        $reference = $data['merchantReferenceId'] ?? $payment?->reference_id ?? uniqid('ref_');
        $email = $data['customer']['email'] ?? $data['email'] ?? $payment?->user?->email ?? 'noreply@weekend.com';
        $phone = $data['customer']['phoneNumber'] ?? $data['phone'] ?? $payment?->phone ?? '0000000000';
        $name = $data['customer']['name'] ?? $data['name'] ?? 'Customer';
        $description = $data['description'] ?? 'Weekend booking';

        $payload = [
            'total_amount' => [
                'amount' => round($amount, 2),
                'currency' => 'SAR',
            ],
            'shipping_amount' => [
                'amount' => 0,
                'currency' => 'SAR',
            ],
            'tax_amount' => [
                'amount' => 0,
                'currency' => 'SAR',
            ],
            'order_reference_id' => $reference,
            'order_number' => $reference,
            'discount' => [
                'amount' => [
                    'amount' => round($payment['discount'] ?? 0, 2),
                    'currency' => 'SAR',
                ],
                'name' => 'Appointment Discount',
            ],
            'items' => [
                [
                    'name' => 'Item',
                    'type' => 'Digital',
                    'reference_id' => (string) $reference,
                    'sku' => 'SKU-'.$reference,
                    'quantity' => 1,
                    'discount_amount' => [
                        'amount' => 0.00,
                        'currency' => 'SAR',
                    ],
                    'tax_amount' => [
                        'amount' => 0,
                        'currency' => 'SAR',
                    ],
                    'unit_price' => [
                        'amount' => round($price ?? 0, 2),
                        'currency' => 'SAR',
                    ],
                    'total_amount' => [
                        'amount' => round(($price ?? 0) * 1, 2),
                        'currency' => 'SAR',
                    ],
                ],
            ],

            'consumer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Customer',
                'last_name' => 'User',
                'phone_number' => $phone ?? preg_replace('/\D/', '', '500000000'),
            ],
            'country_code' => 'SA',
            'description' => 'Appointment #'.'500000000',
            'merchant_url' => [
                'success' => route('payment.success', array_filter([
                    'reference_id' => $reference,
                    'payment_type' => $data['payment_method'] ?? 'tamara',
                    'reservation_id' => $data['reservation_id'] ?? $payment?->reservation_id ?? null,
                    'subscription_id' => $data['subscription_id'] ?? $payment?->subscription_id ?? null,
                ])),
                'cancel' => route('payment.cancelled', array_filter([
                    'reference_id' => $reference,
                    'payment_type' => $data['payment_method'] ?? 'tamara',
                    'reservation_id' => $data['reservation_id'] ?? $payment?->reservation_id ?? null,
                    'subscription_id' => $data['subscription_id'] ?? $payment?->subscription_id ?? null,
                ])),
                'failure' => route('payment.failed', array_filter([
                    'reference_id' => $reference,
                    'payment_type' => $data['payment_method'] ?? 'tamara',
                    'reservation_id' => $data['reservation_id'] ?? $payment?->reservation_id ?? null,
                    'subscription_id' => $data['subscription_id'] ?? $payment?->subscription_id ?? null,
                ])),
                'notification' => $this->notifyUrl,
            ],
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'instalments' => 3,
            'billing_address' => [
                'city' => $data['city'] ?? 'Riyadh',
                'country_code' => 'SA',
                'first_name' => 'Customer',
                'last_name' => 'User',
                'line1' => 'Street Line 1',
                'line2' => 'Building Info',
                'phone_number' => preg_replace('/\D/', '', '500000000'),
                'region' => 'Region',
            ],
            'shipping_address' => [
                'city' => 'Riyadh',
                'country_code' => 'SA',
                'first_name' => 'Customer',
                'last_name' => 'User',
                'line1' => 'Street Line 1',
                'line2' => 'Building Info',
                'phone_number' => preg_replace('/\D/', '', '500000000'),
                'region' => 'Region',
            ],
            'platform' => 'Naqi',
            'is_mobile' => false,
            'locale' => 'en_US',
        ];

        try {
            $response = Http::withToken($this->token)->post("{$this->baseUrl}/checkout", $payload);
            $body = $response->json();
            Log::info('Tamara checkout created', ['order_id' => $body['order_id'] ?? null, 'reference' => $reference]);

            if ($response->successful() && isset($body['checkout_url'])) {
                if ($payment) {
                    $payment->update(['payment_id' => $body['order_id'] ?? null]);
                }

                return ['success' => true, 'payment_url' => $body['checkout_url'], 'item_id' => $body['order_id'] ?? null];
            }

            return ['success' => false, 'message' => $body['message'] ?? 'Tamara checkout failed.'];
        } catch (\Throwable $e) {
            Log::error('Tamara sendPayment error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function callBack(Request $request): bool
    {
        // Tamara webhook body: { "order_id": "...", "event_type": "order_approved", ... }
        // Also handles redirect-style with order_reference_id / merchant_order_id
        $orderId = $request->input('order_id')
                   ?? $request->input('id')
                   ?? null;

        $referenceId = $request->input('order_reference_id')
                    ?? $request->input('merchant_order_id')
                    ?? null;

        Log::info('Tamara webhook received', [
            'order_id' => $orderId,
            'reference_id' => $referenceId,
            'event_type' => $request->input('event_type'),
        ]);

        if (! $orderId && ! $referenceId) {
            Log::warning('Tamara callback: no order_id or reference_id');

            return false;
        }

        try {
            // Find local payment — try payment_id first, then reference_id
            $payment = $orderId
                ? Payment::with(['reservation.unite', 'subscription.adPackage', 'subscription.propertyPackage'])
                    ->where('payment_id', $orderId)->first()
                : null;

            if (! $payment && $referenceId) {
                $payment = Payment::with(['reservation.unite', 'subscription.adPackage', 'subscription.propertyPackage'])
                    ->where('reference_id', $referenceId)->first();
            }

            if (! $payment) {
                Log::error('Tamara callback: Payment not found', compact('orderId', 'referenceId'));

                return false;
            }

            // Already processed — idempotent
            if ($payment->status === 'paid') {
                Log::info('Tamara callback: already paid', ['payment_id' => $payment->id]);

                return true;
            }

            // Persist Tamara order ID if not yet stored
            if ($orderId && empty($payment->payment_id)) {
                $payment->update(['payment_id' => $orderId]);
            }

            // ── Authorize → Capture flow ──────────────────────────────────────
            if ($orderId) {
                $statusResponse = $this->getOrderStatus($orderId);
                $tamaraStatus = $statusResponse['status'] ?? null;

                if ($tamaraStatus === 'approved') {
                    $authResponse = $this->authorizeOrder($orderId);
                    $tamaraStatus = $authResponse['status'] ?? $tamaraStatus;
                }

                if (in_array($tamaraStatus, ['authorised', 'authorized'])) {
                    $this->captureOrder($payment->reference_id, $orderId);
                }
            }

            // ── Mark paid + activate reservation or subscription ──────────────
            $payment->update(['status' => 'paid']);
            (new \App\Repositories\Payment\PaymentRepository)->handlePostPayment($payment);

            Log::info('Tamara callback: payment completed', [
                'payment_id' => $payment->id,
                'reservation_id' => $payment->reservation_id,
                'subscription_id' => $payment->subscription_id,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('Tamara callback error', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'reference_id' => $referenceId,
            ]);

            return false;
        }
    }

    public function confirmByOrderId(string $orderId): array
    {
        try {
            $r = Http::withToken($this->token)->get("{$this->baseUrl}/orders/{$orderId}");

            return ['success' => $r->successful(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPaymentDetails(string $paymentId): array
    {
        try {
            $r = Http::withToken($this->token)->get("{$this->baseUrl}/orders/{$paymentId}");

            return $r->json();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function refund(string $orderId, ?float $amount = null, string $reason = 'Customer cancellation'): array
    {
        try {
            $r = Http::withToken($this->token)->post("{$this->baseUrl}/orders/{$orderId}/refunds", [
                'total_amount' => ['amount' => $amount, 'currency' => 'SAR'],
                'comment' => $reason,
            ]);

            return ['success' => $r->successful(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function handleSuccessRedirect(Request $request)
    {
        // dd('Tamara payment success callback hit', $request->all());

        $referenceId = $request->query('reference_id');
        $orderId = $request->query('orderId');          // Tamara order UUID from redirect
        $payment_type = $request->query('payment_type') ?? 'tamara';

        // BUG FIX: previously, if orderId was missing from the redirect URL,
        // this method fell through with no return statement — same silent
        // failure class found and fixed in TabbyPaymentService. Laravel
        // renders a blank response and nothing is logged to diagnose it.
        if (! $orderId) {
            Log::warning('Tamara handleSuccessRedirect: orderId missing from redirect', [
                'reference_id' => $referenceId,
                'payment_type' => $payment_type,
                'query' => $request->query(),
            ]);

            return $this->tamaraErrorView();
        }

        $payment = Payment::where('payment_type', $request->input('payment_type'))
            ->where('reference_id', $request->input('reference_id'))
            ->orderByDesc('id')
            ->first();

        // BUG FIX: same silent-null failure mode when orderId was present but
        // no matching Payment record existed yet — this branch previously had
        // no else, so it also fell through silently instead of showing an
        // error page.
        if (! $payment) {
            Log::warning('Tamara handleSuccessRedirect: no matching payment found', [
                'reference_id' => $referenceId,
                'payment_type' => $payment_type,
                'order_id' => $orderId,
            ]);

            return $this->tamaraErrorView();
        }

        $payment = DB::transaction(function () use ($orderId, $referenceId, $payment_type) {

            $payment = Payment::where('payment_type', $payment_type)->where('reference_id', $referenceId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return null;
            }

            // Already processed by a concurrent webhook — bail out early
            if ($payment->status === 'paid') {
                return $payment;
            }

            // Persist Tamara orderId so the webhook can also use it reliably
            if ($orderId && empty($payment->payment_id)) {
                $payment->update(['payment_id' => $orderId]);
            }

            return $payment;
        });

        if (! $payment) {
            return response()->json(['status' => false, 'message' => __('lang.payment_not_found_short')]);
        }

        // Already handled — show success view without reprocessing
        if ($payment->status === 'paid') {
            return $this->tamaraResultView($payment, 'paid');
        }

        // ── 3. Tamara authorize → capture (best-effort, non-fatal) ───────────
        if ($orderId) {
            try {
                $statusResponse = $this->getOrderStatus($orderId);
                $tamaraStatus = $statusResponse['status'] ?? null;

                if ($tamaraStatus === 'approved') {
                    $authResponse = $this->authorizeOrder($orderId);
                    if (($authResponse['status'] ?? null) === 'authorised') {
                        $this->captureOrder($referenceId, $orderId);
                    }
                } elseif ($tamaraStatus === 'authorised') {
                    $this->captureOrder($referenceId, $orderId);
                }

            } catch (\Throwable $e) {
                Log::error('Tamara authorize/capture failed (newSuccess): '.$e->getMessage(), [
                    'order_id' => $orderId,
                    'reference_id' => $referenceId,
                ]);
            }
        }

        // ── 4. Mark paid + activate reservation or subscription ──────────────
        $payment->update(['status' => 'paid']);
        (new \App\Repositories\Payment\PaymentRepository)->handlePostPayment($payment);

        $payment->refresh();

        return $this->tamaraResultView($payment, 'paid', $request);
    }

    /**
     * Renders the shared Payment.result view in its generic-error state for
     * cases where handleSuccessRedirect has no real Payment record to show
     * (missing orderId, or no matching payment found). tamaraResultView()
     * requires a real Payment instance (it calls ->loadMissing() on it), so
     * this uses a placeholder object instead, matching the pattern used by
     * TabbyPaymentService::errorResultView().
     */
    private function tamaraErrorView()
    {
        return view('Payment.result', [
            'status' => 'error',
            'payment_type' => 'tamara',
            'payment' => (object) ['amount' => 0, 'phone' => null],
            'phone' => null,
            'priceWithoutTax' => 0,
            'taxAmount' => 0,
        ]);
    }

    public function authorizeOrder($order_id)
    {
        try {
            $response = $this->client->post('orders/'.$order_id.'/authorise');

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

    }

    public function captureOrder(string $referenceId, string $orderId)
    {

        try {
            $payment = Payment::where('payment_type', 'tamara')->where('reference_id', $referenceId)->orderByDesc('id')->first();
            $payment->payment_id = $orderId;
            $payment->save();

            $items = $payment->items->map(function ($item) {
                return [
                    'name' => $item->item_number ?? 'Unnamed Item',
                    'type' => 'Digital',
                    'reference_id' => (string) $item->id ?? 'unnamed',
                    'sku' => 'SKU-'.$item->item_number,
                    'quantity' => max(1, (int) ($item->quantity ?? 0)),
                    'discount_amount' => [
                        'amount' => round(0, 2),
                        'currency' => 'SAR',
                    ],
                    'tax_amount' => [
                        'amount' => 0,
                        'currency' => 'SAR',
                    ],
                    'unit_price' => [
                        'amount' => round($item->price ?? 0, 2),
                        'currency' => 'SAR',
                    ],
                    'total_amount' => [
                        'amount' => round($item->total_amount ?? 0, 2),
                        'currency' => 'SAR',
                    ],
                ];
            })->toArray();

            $payload = [
                'order_id' => $orderId,
                'total_amount' => [
                    'amount' => round($payment->amount, 2),
                    'currency' => 'SAR',
                ],
                'items' => $items,
                'discount_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR',
                ],
                'shipping_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR',
                ],
                'tax_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR',
                ],
                'shipping_info' => [
                    'shipped_at' => Carbon::now()->toIso8601String(),
                    'shipping_company' => 'Naqi Delivery',
                    'tracking_number' => 'TRK'.$payment->reference_id,
                    'tracking_url' => 'https://tracking.example.com?id='.$payment->reference_id,
                ],
            ];
            Log::info("📤 Sending Capture for payment ID {$payment->reference_id}: ".json_encode($payload));

            $response = $this->client->post('/payments/capture', [
                'json' => $payload,
                'http_errors' => false,
            ]);

            Log::info("📤 response Capture for payment ID {$payment->reference_id}: ".json_encode($payload));

            $result = json_decode($response->getBody(), true);
            Log::info("✅ response Capture for payment ID {$payment->reference_id}: ".json_encode($result));
            // Save status to DB

            return [
                'success' => true,
                'message' => __('lang.payment_captured_successfully'),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Error capturing payment: '.$e->getMessage());
            Log::error('❌ Trace: '.$e->getTraceAsString());

            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getOrderStatus($orderId)
    {
        try {
            $response = $this->client->get("/orders/{$orderId}");

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function tamaraResultView(Payment $payment, string $status, ?Request $request = null)
    {
        $payment->loadMissing(['reservation.unite', 'subscription.adPackage', 'subscription.propertyPackage']);

        $totalAmount = (float) $payment->amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        $reservation = $payment->reservation;
        $subscription = $payment->subscription;

        // Fallback from redirect query params if FK not set on payment
        if (! $reservation && $request?->filled('reservation_id')) {
            $reservation = \App\Models\UniteReservation::with('unite')->find($request->reservation_id);
        }
        if (! $subscription && $request?->filled('subscription_id')) {
            $subscription = \App\Models\Subscription::with('adPackage', 'propertyPackage')->find($request->subscription_id);
        }

        return view('Payment.result', [
            'status' => $status,
            'payment_type' => 'tamara',
            'payment' => $payment,
            'phone' => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
            'reservation' => $reservation,
            'subscription' => $subscription,
            'context_type' => $reservation ? 'reservation' : ($subscription ? 'subscription' : 'unknown'),
            'context_id' => $reservation?->id ?? $subscription?->id,
        ]);
    }
}
