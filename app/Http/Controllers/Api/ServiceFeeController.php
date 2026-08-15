<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * GET /api/service-fees — public, no auth required. Returns only the
 * active fees (amount > 0 and is_active), so a client doesn't need to
 * filter out disabled/zero entries itself. Locale-aware, matching the
 * existing SetLocale middleware already applied to the api group.
 */
class ServiceFeeController extends Controller
{
    use HasVersionedCache;

    public function index(): JsonResponse
    {
        $locale = app()->getLocale();
        $cacheKey = $this->versionedCacheKey('service_fees_index');

        // Locale only changes which label is shown, not which rows match
        // the query — cached once (locale-agnostic), the label picked at
        // read time from the cached, plain array below.
        $fees = Cache::remember($cacheKey, now()->addHours(24), function () {
            return ServiceFee::where('is_active', true)
                ->where('amount', '>', 0)
                ->get()
                ->map(fn ($fee) => [
                    'key' => $fee->key,
                    'label_en' => $fee->label_en,
                    'label_ar' => $fee->label_ar,
                    'amount' => (float) $fee->amount,
                ])
                ->values()
                ->all();
        });

        $data = collect($fees)->map(fn ($fee) => [
            'key' => $fee['key'],
            'label' => $locale === 'ar' ? $fee['label_ar'] : $fee['label_en'],
            'amount' => $fee['amount'],
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
