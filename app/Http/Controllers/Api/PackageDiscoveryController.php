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
        $propertyPackages = PropertyPackage::where('status', 'active')
            ->orderBy('price')
            ->limit(5)
            ->get();

        $adPackages = AdPackage::where('status', 'active')
            ->orderBy('price')
            ->limit(5)
            ->get();

        $response = [
            'property_packages' => PropertyPackageResource::collection($propertyPackages),
            'ad_packages'       => AdPackageResource::collection($adPackages),
        ];

        // Append provider statistics when the authenticated user is a provider.
        // auth('sanctum')->user() interrogates the Sanctum guard directly from
        // the Bearer token — works on public routes without auth:sanctum middleware.
        // We call computeStatistics() directly rather than invoking the full
        // ProviderStatisticsController, which would call $request->user() and get
        // null (no middleware has set up the guard on this unprotected route).
        $user = auth('sanctum')->user();

        if ($user && $user->type === 'provider') {
            $year  = (int) ($request->input('year',  now()->year));
            $month = (int) ($request->input('month', now()->month));

            $statsController = app(ProviderStatisticsController::class);
            $cacheKey = "provider_statistics:{$user->id}:{$year}:{$month}";

            $statistics = \Illuminate\Support\Facades\Cache::remember(
                $cacheKey,
                now()->addHour(),
                fn () => $statsController->computeStatistics($user, $year, $month)
            );

            $response['statistics'] = $statistics;
        }

        return response()->json($response);
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
            'ad_package_activation'       => $hasAdSub,
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
