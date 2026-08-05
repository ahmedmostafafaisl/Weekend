<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/service-fees — public, no auth required. Returns only the
 * active fees (amount > 0 and is_active), so a client doesn't need to
 * filter out disabled/zero entries itself. Locale-aware, matching the
 * existing SetLocale middleware already applied to the api group.
 */
class ServiceFeeController extends Controller
{
    public function index(): JsonResponse
    {
        $locale = app()->getLocale();

        $fees = ServiceFee::where('is_active', true)
            ->where('amount', '>', 0)
            ->get()
            ->map(fn ($fee) => [
                'key' => $fee->key,
                'label' => $locale === 'ar' ? $fee->label_ar : $fee->label_en,
                'amount' => (float) $fee->amount,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $fees,
        ]);
    }
}
