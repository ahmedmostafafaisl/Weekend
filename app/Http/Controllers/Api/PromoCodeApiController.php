<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromoCode\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeApiController extends Controller
{
    public function __construct(private PromoCodeService $service) {}

    /**
     * POST /api/promo-codes/validate
     *
     * Body: { "code": "SUMMER20", "amount": 500.00 }
     *
     * Returns a discount preview — no side effects, safe to call on the
     * checkout screen before the customer confirms.
     *
     * Response:
     * {
     *   "valid": true,
     *   "original_amount": 500.00,
     *   "discount_amount": 100.00,
     *   "final_amount": 400.00,
     *   "discount_label": "20% off"
     * }
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $userId = auth('sanctum')->id(); // null for guests — per-user limit check skipped
        $result = $this->service->validate($request->code, (float) $request->amount, $userId);

        if (! $result['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'],
            ], 422);
        }

        $promo = $result['promo_code'];

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'discount_label' => $promo->discount_type === 'percentage'
                ? "{$promo->discount_value}% off"
                : number_format($promo->discount_value, 2).' off',
            'original_amount' => $result['original_amount'],
            'discount_amount' => $result['discount_amount'],
            'final_amount' => $result['final_amount'],
        ]);
    }
}
