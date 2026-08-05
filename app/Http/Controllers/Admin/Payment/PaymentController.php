<?php

namespace App\Http\Controllers\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Resources\Payment\PaymentResource;
use App\Models\Payment;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Services\Payment\PaymentMethodFactory;
use App\Services\Payment\TabbyPaymentService;
use App\Services\Payment\TamaraPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
        protected PaymentRepositoryInterface $paymentRepository,
        protected TabbyPaymentService $tabbyPaymentService,
        protected TamaraPaymentService $tamaraPaymentService
    ) {}

    public function paymentProcess(InitiatePaymentRequest $request): JsonResponse
    {
        // Resolve gateway from the requested payment_method (default: geidea)
        $method = $request->input('payment_method', 'geidea');
        try {
            $gateway = PaymentMethodFactory::make($method);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $result = $gateway->sendPayment($request);

        if ($result['success'] ?? false) {
            return response()->json([
                'success' => true,
                'payment_method' => $method,
                'payment_url' => $result['payment_url'] ?? $result['url'] ?? null,
                'item_id' => $result['item_id'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Payment initiation failed.',
        ], 422);
    }

    /**
     * GET /api/payment-methods
     * Returns all available payment gateways for the checkout screen.
     */
    public function paymentMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PaymentMethodFactory::available(),
        ]);
    }

    /**
     * Geidea server-to-server webhook — NOT behind auth:sanctum.
     * Signature verified inside GeideaPaymentService::callBack().
     */
    public function callBack(Request $request): JsonResponse
    {
        $this->paymentGateway->callBack($request);

        // Always return 200 — Geidea will retry on non-2xx
        return response()->json(['received' => true], 200);
    }

    /**
     * Query a payment intent from Geidea by their paymentIntentId.
     */
    public function details(string $payment_id): JsonResponse
    {
        $data = $this->paymentGateway->getPaymentDetails($payment_id);

        return response()->json($data, $data['status'] ?? 200);
    }

    /**
     * Recovery endpoint — manually confirm a payment using the Geidea Order ID.
     *
     * Use this when:
     *   - The callback URL was unreachable (localhost / staging with no public URL)
     *   - The customer paid but the reservation/subscription is still 'pending'
     *
     * POST /api/payments/confirm-by-order
     * Body: { "order_id": "f6d33a48-8deb-468e-7b51-08dea38dcf6f" }
     */
    public function confirmByOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'string'],
        ]);

        $result = $this->paymentGateway->confirmByOrderId($request->input('order_id'));

        $status = ($result['success'] ?? false) ? 200 : 422;

        return response()->json($result, $status);
    }

    public function index(Request $request): JsonResponse|View
    {
        $payments = $this->paymentRepository->paginate($request->only([
            'status', 'payment_type', 'reference_id', 'phone',
        ]));

        return $request->expectsJson()
            ? response()->json(['data' => PaymentResource::collection($payments)])
            : view('dashboard.payments.index', compact('payments'));
    }

    public function show(int $id, Request $request): JsonResponse|View
    {
        $payment = $this->paymentRepository->findOrFail($id);

        return $request->expectsJson()
            ? response()->json(['data' => new PaymentResource($payment)])
            : view('dashboard.payments.show', compact('payment'));
    }

    public function myPayments(Request $request): JsonResponse
    {
        $payments = $this->paymentRepository->providerPayments(
            $request->only(['status', 'reference_id', 'phone'])
        );

        return response()->json(['data' => PaymentResource::collection($payments)]);
    }

    // ── Gateway-specific callbacks ───────────────────────────────────────────

    /**
     * POST /api/tappy/callback
     * Called by Tap Payments server when charge status changes.
     */
    public function tappyCallback(Request $request): JsonResponse
    {
        $gateway = PaymentMethodFactory::make('tappy');
        $gateway->callBack($request);

        return response()->json(['received' => true]);
    }

    /**
     * POST /api/tamara/callback
     * Called by Tamara server with order_id on approval/capture events.
     */
    public function tamaraCallback(Request $request): JsonResponse
    {
        $gateway = PaymentMethodFactory::make('tamara');
        $gateway->callBack($request);

        return response()->json(['received' => true]);
    }

    /**
     * POST /api/maysar/callback
     * Called by Maysar server with session_id + status on payment events.
     */
    public function maysarCallback(Request $request): JsonResponse
    {
        $gateway = PaymentMethodFactory::make('maysar');
        $gateway->callBack($request);

        return response()->json(['received' => true]);
    }

    // ── User-facing redirect pages ────────────────────────────────────────────

    public function success(Request $request)
    {
        // dd('Payment successful callback hit', $request->all());
        if ($request->input('payment_type') == 'tabby') {
            return $this->tabbyPaymentService->handleSuccessRedirect($request);
        } elseif ($request->input('payment_type') == 'tamara') {
            return $this->tamaraPaymentService->handleSuccessRedirect($request);
        }

    }

    public function failed(Request $request)
    {
        return $this->handleFailedOrCancelled($request, 'failed');
    }

    public function cancelled(Request $request)
    {
        return $this->handleFailedOrCancelled($request, 'cancelled');
    }

    /**
     * Shared handler for failed and cancelled payment redirects.
     *
     * Both Tabby and Tamara redirect here with:
     *   ?reference_id=ref_xxx
     *   &payment_type=tabby|tamara
     *   &reservation_id=N   (optional)
     *   &subscription_id=N  (optional)
     *   &payment_id=...     (tabby: tabby payment id; tamara: order_id)
     */
    private function handleFailedOrCancelled(Request $request, string $outcome)
    {
        $referenceId = $request->input('reference_id');
        $paymentType = $request->input('payment_type', 'unknown');
        $reservationId = $request->input('reservation_id');
        $subscriptionId = $request->input('subscription_id');

        Log::info("Payment {$outcome} redirect", $request->all());

        // ── Find local payment record ─────────────────────────────────────────
        $payment = null;
        if ($referenceId) {
            $payment = \App\Models\Payment::with([
                'reservation.unite',
                'subscription.adPackage',
                'subscription.propertyPackage',
            ])
                ->where('reference_id', $referenceId)
                ->when($paymentType !== 'unknown', fn ($q) => $q->where('payment_type', $paymentType))
                ->orderByDesc('created_at')
                ->first();
        }

        if ($payment && $payment->status === 'pending') {
            // ── Mark payment as failed/cancelled ─────────────────────────────
            $payment->update(['status' => $outcome === 'cancelled' ? 'failed' : 'failed']);

            // ── Revert reservation to pending so the customer can retry ───────
            $reservation = $payment->reservation
                ?? ($reservationId ? \App\Models\UniteReservation::find($reservationId) : null);

            if ($reservation && in_array($reservation->status, ['pending', 'pending_approval'])) {
                // Keep reservation row but mark payment as not completed
                // Provider's slot stays free until a successful payment
                $reservation->update(['status' => 'pending']);
            }

            // ── Keep subscription pending — customer can retry payment ────────
            $subscription = $payment->subscription
                ?? ($subscriptionId ? \App\Models\Subscription::find($subscriptionId) : null);

            // Notify customer about failed payment
            if ($outcome === 'failed') {
                try {
                    $payment->user?->notify(new \App\Notifications\PaymentFailed($payment));
                } catch (\Throwable $e) {
                    Log::error('PaymentFailed notification error', ['error' => $e->getMessage()]);
                }
            }

            Log::info("Payment marked {$outcome}", [
                'payment_id' => $payment->id,
                'reservation_id' => $reservation?->id,
                'subscription_id' => $subscription?->id,
            ]);
        }

        // ── Tax breakdown for view ────────────────────────────────────────────
        $totalAmount = (float) ($payment?->amount ?? 0);
        $priceWithoutTax = $totalAmount > 0 ? round($totalAmount / 1.15, 2) : 0;
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        // Fallback: load context from request params if payment not found
        $reservation = $payment?->reservation
            ?? ($reservationId ? \App\Models\UniteReservation::with('unite')->find($reservationId) : null);
        $subscription = $payment?->subscription
            ?? ($subscriptionId ? \App\Models\Subscription::with('adPackage', 'propertyPackage')->find($subscriptionId) : null);

        return view('Payment.result', [
            'status' => $outcome,          // 'failed' or 'cancelled'
            'payment_type' => $paymentType,
            'payment' => $payment,
            'phone' => $payment?->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
            'reservation' => $reservation,
            'subscription' => $subscription,
            'context_type' => $reservation ? 'reservation' : ($subscription ? 'subscription' : 'unknown'),
            'context_id' => $reservation?->id ?? $subscription?->id,
        ]);
    }
}
