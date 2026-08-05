<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/saudi-cities — the canonical, bilingual reference list backing
 * the unite 'city' field's validation and the admin/provider dashboard's
 * city dropdown. Public, no auth required — this is static reference data,
 * the same category as stadium-types/service-groups/etc.
 */
class SaudiCityController extends Controller
{
    public function index(): JsonResponse
    {
        $locale = app()->getLocale();

        $cities = collect(config('saudi_cities', []))->map(fn ($city) => [
            'key' => $city['key'],
            'label' => $locale === 'ar' ? $city['label_ar'] : $city['label_en'],
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }
}
