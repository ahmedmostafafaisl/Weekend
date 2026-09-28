<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Packages\AdPackageResource;
use App\Http\Resources\Packages\PropertyPackageResource;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\AdPackage;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PackageDiscoveryController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // 1. GET /api/home  (public — optional auth via auth:sanctum)
    //
    // Returns:
    //   - top 5 active property packages (price ASC)
    //   - top 5 active ad packages (price ASC)
    //   - statistics (only when the authenticated user is a provider)
    //
    // The route uses auth:sanctum middleware but does NOT abort on guest —
    // auth('sanctum')->user() returns null for unauthenticated requests.
    // ─────────────────────────────────────────────────────────────────────────

    public function home(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        // ── Provider ──────────────────────────────────────────────────────
        // Authenticated provider: return top packages + their statistics.
        if ($user && $user->type === 'provider') {
            $propertyPackages = PropertyPackage::where('status', 'active')
                ->orderBy('price')->limit(5)->get();

            $adPackages = AdPackage::where('status', 'active')
                ->orderBy('price')->limit(5)->get();

            $year = (int) ($request->input('year', now()->year));
            $month = (int) ($request->input('month', now()->month));

            $statsController = app(ProviderStatisticsController::class);
            $cacheKey = "provider_statistics:{$user->id}:{$year}:{$month}";

            $statistics = Cache::remember(
                $cacheKey,
                now()->addHour(),
                fn () => $statsController->computeStatistics($user, $year, $month)
            );

            return response()->json([
                'property_packages' => PropertyPackageResource::collection($propertyPackages),
                'ad_packages' => AdPackageResource::collection($adPackages),
                'statistics' => $statistics,
            ]);
        }

        // ── Guest or Customer ─────────────────────────────────────────────
        // Return the 10 admin-selected featured departments and the price
        // filter range configured in the homepage settings.
        $settings = \App\Models\HomeSetting::current();
        $deptIds = $settings->featured_department_ids ?? [];

        $deptQuery = \App\Models\Department::with(['images', 'unites'])
            ->where('status', 'active');

        if (! empty($deptIds)) {
            $ids = array_map('intval', array_slice($deptIds, 0, 10));
            $deptQuery->whereIn('id', $ids)
                ->orderByRaw('FIELD(id, '.implode(',', $ids).')');
        } else {
            $deptQuery->latest()->limit(10);
        }

        $departments = $deptQuery->get()->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'type' => $d->type,
            'location' => $d->location,
            'latitude' => $d->latitude,
            'longitude' => $d->longitude,
            'unites_count' => $d->unites->count(),
            'images' => $d->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => asset($img->image),
            ])->values(),
        ])->values();

        // Price range — computed live from all active unite prices,
        // identical to the meta.min_price / meta.max_price returned by
        // GET /api/unites2. Reflects the actual price distribution of
        // all available venues at request time.
        $allPrices = \App\Models\UnitePrice::whereHas('unite', fn ($q) => $q->where('status', 'active'))
            ->get(['price', 'morning_price', 'evening_price', 'full_price'])
            ->flatMap(fn ($row) => collect([
                $row->price,
                $row->morning_price,
                $row->evening_price,
                $row->full_price,
            ]))
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (float) $v);

        return response()->json([
            'departments' => $departments,
            'price_filter' => [
                'min' => $allPrices->isNotEmpty() ? $allPrices->min() : 0,
                'max' => $allPrices->isNotEmpty() ? $allPrices->max() : 0,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. GET /api/package-activation-keys  (auth:sanctum)
    //
    // Returns boolean flags indicating whether the authenticated user already
    // holds an active, non-exhausted subscription in each eligible domain:
    //
    //   Provider → {
    //       property_package_activation: true|false,
    //       ad_package_activation:       true|false
    //   }
    //   Customer → {
    //       ad_package_activation: true|false
    //   }
    //
    // true  = user HAS an active subscription → no new purchase needed
    // false = user has NO active subscription → should purchase a package
    //
    // Uses the same activePropertySubscription() / activeAdSubscription()
    // methods as the subscription gate in UniteController and AdController,
    // so the boolean is consistent with what those gates enforce.
    // ─────────────────────────────────────────────────────────────────────────

    public function activationKeys(Request $request): JsonResponse
    {
        $user = $request->user();

        $hasAdSub = $user->activeAdSubscription() !== null;

        if ($user->type === 'customer') {
            return response()->json([
                'ad_package_activation' => $hasAdSub,
            ]);
        }

        // Provider
        $hasPropertySub = $user->activePropertySubscription() !== null;

        return response()->json([
            'property_package_activation' => $hasPropertySub,
            'ad_package_activation' => $hasAdSub,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. GET /api/user-subscriptions  (auth:sanctum) — unchanged
    // ─────────────────────────────────────────────────────────────────────────

    public function userSubscriptions(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Subscription::with(['adPackage', 'propertyPackage', 'payment'])
            ->where('user_id', $user->id)
            ->latest();

        if ($user->type === 'customer') {
            $query->where('type', 'ad');
        }

        $all = $query->get();

        // Bulk-expire any subscriptions whose rules are now met.
        $dueIds = $all
            ->filter(fn ($s) => $s->status === 'active' && $s->isExpiredByRules())
            ->pluck('id');

        if ($dueIds->isNotEmpty()) {
            Subscription::whereIn('id', $dueIds)->update(['status' => 'inactive']);
            $all->each(function ($s) use ($dueIds) {
                if ($dueIds->contains($s->id)) {
                    $s->status = 'inactive';
                }
            });
        }

        if ($user->type === 'customer') {
            return response()->json([
                'ad_subscriptions' => SubscriptionResource::collection(
                    $all->where('type', 'ad')->values()
                ),
            ]);
        }

        return response()->json([
            'property_subscriptions' => SubscriptionResource::collection(
                $all->where('type', 'property')->values()
            ),
            'ad_subscriptions' => SubscriptionResource::collection(
                $all->where('type', 'ad')->values()
            ),
        ]);
    }
}
