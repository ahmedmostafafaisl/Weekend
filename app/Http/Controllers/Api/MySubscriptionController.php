<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\Subscription;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MySubscriptionController extends Controller
{
    use HasVersionedCache;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $cacheKey = $this->versionedCacheKey(
            "my_subscriptions:{$user->id}",
            $request->get('type', ''),
            $request->get('status', '')
        );

        $payload = Cache::remember($cacheKey, now()->addHours(24), function () use ($request, $user) {
            $query = Subscription::with(['adPackage', 'propertyPackage', 'payment'])
                ->where('user_id', $user->id);

            // Customers only see ad subscriptions
            if ($user->type === 'customer') {
                $query->where('type', 'ad');
            }

            // Optional type filter (providers only — customers are already locked to 'ad')
            if ($user->type === 'provider' && $request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Optional status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $all = $query->latest()->get();

            // Eagerly fix any subscriptions whose expiry rules are now met
            // (end_date passed or count hit zero) — single bulk UPDATE rather
            // than one query per subscription, then the in-memory collection
            // is updated to match without any further DB round-trip.
            //
            // NOTE: caching this response means this expiry check now
            // effectively runs at most once per cache lifetime (24h, or
            // sooner if bumpCacheVersion() fires on a new subscription) —
            // not on every single request as it did before caching was
            // added. This is the deliberate, requested trade-off ("cache
            // key changes every 24 hours or on new subscription"), not an
            // accidental side effect — worth knowing explicitly rather
            // than discovering it as a surprise later.
            $dueForExpiryIds = $all
                ->filter(fn ($s) => $s->status === 'active' && $s->isExpiredByRules())
                ->pluck('id');

            if ($dueForExpiryIds->isNotEmpty()) {
                Subscription::whereIn('id', $dueForExpiryIds)->update(['status' => 'inactive']);

                $all->each(function ($s) use ($dueForExpiryIds) {
                    if ($dueForExpiryIds->contains($s->id)) {
                        $s->status = 'inactive';
                    }
                });
            }

            // Current = the single active subscription (most recent if multiple)
            $current = $all->firstWhere('status', 'active');

            // Expired = everything that is not active, newest first
            $expired = $all->filter(fn ($s) => $s->status !== 'active')->values();

            // Resolve every Resource to a plain array before this closure
            // returns — a Resource instance carries request-bound state and
            // isn't safe to serialize directly into a cache store.
            return [
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => $user->type,
                ],
                'summary' => [
                    'total' => $all->count(),
                    'active' => $all->where('status', 'active')->count(),
                    'expired' => $expired->count(),
                    'pending' => $all->where('status', 'pending')->count(),
                ],
                'current' => $current
                    ? (new SubscriptionResource($current))->resolve()
                    : null,
                'expired' => SubscriptionResource::collection($expired)->resolve(),
            ];
        });

        return response()->json($payload);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::with(['adPackage', 'propertyPackage', 'payment'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // Fix expiry status on-the-fly if needed. expireIfDue() already
        // updates the in-memory model via ->update() when it changes
        // anything — a ->refresh() here would just be a redundant extra
        // SELECT for data already correct in memory.
        $subscription->expireIfDue();

        return response()->json([
            'success' => true,
            'is_expired' => $subscription->isExpiredByRules() || $subscription->status === 'inactive',
            'data' => new SubscriptionResource($subscription),
        ]);
    }
}
