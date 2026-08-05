<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MySubscriptionController extends Controller
{
    /**
     * GET /api/my-subscriptions
     *
     * Returns the authenticated user's subscriptions split into:
     *   - current:  the single active subscription (or null)
     *   - expired:  all inactive/expired subscriptions, newest first
     *
     * For providers: both 'ad' and 'property' type subscriptions.
     * For customers: 'ad' type subscriptions only.
     *
     * Query params:
     *   type   — filter by type: ad | property (optional)
     *   status — filter by status: active | inactive | pending (optional)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

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
        // (end_date passed or count hit zero) — updates DB so status is always accurate
        $all->each(fn ($s) => $s->expireIfDue());
        $all = $all->fresh(); // re-fetch after potential status changes

        // Current = the single active subscription (most recent if multiple)
        $current = $all->firstWhere('status', 'active');

        // Expired = everything that is not active, newest first
        $expired = $all->filter(fn ($s) => $s->status !== 'active')->values();

        return response()->json([
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
                ? new SubscriptionResource($current)
                : null,
            'expired' => SubscriptionResource::collection($expired),
        ]);
    }

    /**
     * GET /api/my-subscriptions/{id}
     *
     * Show a single subscription belonging to the authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::with(['adPackage', 'propertyPackage', 'payment'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // Fix expiry status on-the-fly if needed
        $subscription->expireIfDue();
        $subscription->refresh();

        return response()->json([
            'success' => true,
            'is_expired' => $subscription->isExpiredByRules() || $subscription->status === 'inactive',
            'data' => new SubscriptionResource($subscription),
        ]);
    }
}
