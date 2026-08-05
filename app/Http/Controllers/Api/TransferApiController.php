<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderTransfer;
use App\Models\TransferPolicy;
use App\Models\TransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferApiController extends Controller
{
    /** GET /api/transfer-policy  — active policy visible to provider */
    public function policy(): JsonResponse
    {
        $policy = TransferPolicy::where('is_active', true)->latest()->first();
        if (! $policy) {
            return response()->json(['success' => false, 'message' => __('lang.no_active_transfer_policy')], 404);
        }

        return response()->json(['success' => true, 'data' => [
            'title' => $policy->title,
            'description' => $policy->description,
            'transfer_days' => $policy->transfer_days,
            'transfer_methods' => $policy->transfer_methods,
            'tax_rate' => (float) $policy->tax_rate,
            'platform_fee_rate' => (float) $policy->platform_fee_rate,
        ]]);
    }

    /** GET /api/my-transfers  — provider's received transfers */
    public function myTransfers(Request $request): JsonResponse
    {
        $transfers = ProviderTransfer::where('user_id', $request->user()->id)
            ->with('policy')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(20);

        return response()->json(['success' => true,
            'summary' => [
                'total_received' => ProviderTransfer::where('user_id', $request->user()->id)->where('status', 'completed')->sum('net_amount'),
                'pending' => ProviderTransfer::where('user_id', $request->user()->id)->where('status', 'pending')->sum('net_amount'),
            ],
            'data' => $transfers->items(),
            'meta' => ['current_page' => $transfers->currentPage(), 'last_page' => $transfers->lastPage(), 'total' => $transfers->total()],
        ]);
    }

    /** POST /api/transfer-requests  — provider requests a transfer */
    public function requestTransfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'preferred_method' => ['required', 'in:bank_transfer,cash,check,digital_wallet'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $req = TransferRequest::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending']);

        return response()->json(['success' => true, 'message' => __('lang.transfer_request_submitted'), 'data' => $req], 201);
    }

    /** GET /api/transfer-requests  — provider sees their own requests */
    public function myRequests(Request $request): JsonResponse
    {
        $requests = TransferRequest::where('user_id', $request->user()->id)
            ->with('transfer')->latest()->paginate(20);

        return response()->json(['success' => true, 'data' => $requests->items(),
            'meta' => ['current_page' => $requests->currentPage(), 'last_page' => $requests->lastPage(), 'total' => $requests->total()]]);
    }

    /**
     * GET /api/refund-policy
     * Returns the platform's refund policy for customers.
     * Reads from the active TransferPolicy's description + transfer_days.
     */
    public function refundPolicy(): JsonResponse
    {
        $policy = TransferPolicy::where('is_active', true)->latest()->first();

        if (! $policy) {
            return response()->json([
                'success' => false,
                'message' => __('lang.no_refund_policy_configured'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'title' => $policy->title,
                'description' => $policy->description,
                'transfer_days' => $policy->transfer_days,
                'transfer_methods' => $policy->transfer_methods,
                'tax_rate' => (float) $policy->tax_rate,
                'platform_fee_rate' => (float) $policy->platform_fee_rate,
                'summary' => "Refunds are processed within {$policy->transfer_days} working days. "
                    ."A platform fee of {$policy->platform_fee_rate}% and tax of {$policy->tax_rate}% apply.",
            ],
        ]);
    }

    /** GET /api/payment-methods  — list available payment gateways */
    public function paymentMethods(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => \App\Services\Payment\PaymentMethodFactory::available()]);
    }
}
