<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Http\Requests\Unite\RateUniteRequest;
use App\Http\Resources\Reservation\ReservationResource;
use App\Models\UniteRating;
use App\Models\UniteReservation;
use App\Repositories\Interfaces\UniteReservationInterface;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UniteReservationController extends Controller
{
    use HasVersionedCache;

    public function __construct(protected UniteReservationInterface $repo) {}

    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $this->authorize('viewAny', UniteReservation::class);

        if ($request->expectsJson()) {
            $cacheKey = $this->versionedCacheKey("unite_reservations_index:{$request->user()->id}");

            $payload = Cache::remember($cacheKey, now()->addHours(24), function () {
                return ReservationResource::collection($this->repo->allForUser(auth()->id()))->resolve();
            });

            return response()->json(['data' => $payload]);
        }

        return view('dashboard.web.reservations.index', [
            'reservations' => $this->repo->all(),
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        // Authorization enforced in StoreReservationRequest (customers only)
        try {
            $result = $this->repo->create($request->validated(), $request->user()->id);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $isPendingApproval = ($result['status'] ?? null) === 'pending_approval';

        $this->bumpCacheVersion("unite_reservations_index:{$request->user()->id}");

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? ($isPendingApproval
                ? 'Booking request sent to provider. Payment will be taken after approval.'
                : 'Reservation created. Complete payment to confirm your booking.'),
            'status' => $result['status'] ?? 'pending',
            'data' => [
                'reservation' => new ReservationResource($result['reservation']),
                'payment' => [
                    'id' => $result['payment']->id,
                    'reference_id' => $result['payment']->reference_id,
                    'amount' => (float) $result['payment']->amount,
                    'status' => $result['payment']->status,
                ],
                'payment_url' => $result['payment_url'],  // null if pending_approval
            ],
        ], 201);
    }

    public function show(int $id, Request $request): JsonResponse|\Illuminate\View\View
    {
        $reservation = $this->repo->find($id);

        $this->authorize('view', $reservation);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => new ReservationResource($reservation),
            ]);
        }

        return view('dashboard.web.reservations.show', compact('reservation'));
    }

    public function update(UpdateReservationRequest $request, int $id): JsonResponse
    {
        $reservation = UniteReservation::findOrFail($id);

        $this->authorize('update', $reservation);

        $updated = $this->repo->update($id, $request->validated());

        $this->bumpCacheVersion("unite_reservations_index:{$reservation->user_id}");

        return response()->json([
            'success' => true,
            'message' => __('lang.reservation_updated_successfully_msg'),
            'data' => new ReservationResource($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $reservation = UniteReservation::findOrFail($id);

        $this->authorize('delete', $reservation);

        $this->repo->delete($id);

        return response()->json([
            'success' => true,
            'message' => __('lang.reservation_deleted_successfully_msg'),
        ]);
    }

    /**
     * GET /api/my-reservations
     * Paginated reservation history for the authenticated customer.
     *
     * Query params:
     *   status         — confirmed | pending | cancelled
     *   payment_status — paid | pending | failed | refunded | refund_failed
     *   period_type    — morning | evening | full_day | custom
     *   date_from      — Y-m-d
     *   date_to        — Y-m-d
     *   upcoming       — 1 = only future confirmed/pending reservations
     *   per_page       — 1-50 (default 15)
     */
    public function myReservations(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'status' => ['nullable', 'in:confirmed,pending,cancelled'],
            'payment_status' => ['nullable', 'in:paid,pending,failed,refunded,refund_failed'],
            'period_type' => ['nullable', 'in:morning,evening,full_day,custom'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'upcoming' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = UniteReservation::with([
            'unite.department',
            'unite.images',
            'rating',
            'payment.promoCode',
        ])
            ->where('user_id', $user->id)
            ->latest('reservation_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('payment', fn ($q) => $q->where('status', $request->payment_status)
            );
        }

        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('reservation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('reservation_date', '<=', $request->date_to);
        }

        if ($request->boolean('upcoming')) {
            $query->whereDate('reservation_date', '>=', now()->toDateString())
                ->whereIn('status', ['confirmed', 'pending']);
        }

        $perPage = min((int) ($request->per_page ?? 15), 50);
        $paginator = $query->paginate($perPage);

        // Viewing appointments — a genuinely separate booking mechanism
        // from reservations (different model, different status enum:
        // pending/confirmed/cancelled/completed vs confirmed/pending/
        // cancelled), so returned as its own key rather than merged into
        // the same paginated reservations list. The same date_from/
        // date_to/upcoming filters apply where they sensibly carry over —
        // 'status' and 'payment_status' don't, since a viewing's status
        // values differ and most viewings have no payment at all.
        $viewingsQuery = \App\Models\UniteViewing::with(['unite', 'viewingTime', 'payment'])
            ->where('user_id', $user->id)
            ->latest('viewing_date');

        if ($request->filled('date_from')) {
            $viewingsQuery->whereDate('viewing_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $viewingsQuery->whereDate('viewing_date', '<=', $request->date_to);
        }

        if ($request->boolean('upcoming')) {
            $viewingsQuery->whereDate('viewing_date', '>=', now()->toDateString())
                ->whereIn('status', ['confirmed', 'pending']);
        }

        $viewings = $viewingsQuery->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => ReservationResource::collection($paginator->items()),
            'viewings' => $viewings->map(fn ($v) => [
                'id' => $v->id,
                'unite_id' => $v->unite_id,
                'unite_name' => $v->unite->name ?? null,
                'viewing_date' => $v->viewing_date?->format('Y-m-d'),
                'day_of_week' => $v->viewingTime->day_of_week ?? null,
                'start_time' => $v->viewingTime->start_time ?? null,
                'end_time' => $v->viewingTime->end_time ?? null,
                'status' => $v->status,
                'deposit_required' => (bool) $v->deposit_required,
                'deposit_amount' => $v->deposit_amount ? (float) $v->deposit_amount : null,
                'deposit_refundable' => $v->deposit_refundable,
                'payment_status' => $v->payment->status ?? null,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $provider = $request->user();

        if (! $provider || $provider->type !== 'provider') {
            return response()->json(['success' => false, 'message' => __('lang.only_providers_can_approve')], 403);
        }

        try {
            $result = $this->repo->approve($id, $provider->id);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->bumpCacheVersion("unite_reservations_index:{$result['reservation']->user_id}");

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'payment_url' => $result['payment_url'],
            'reservation' => new ReservationResource($result['reservation']),
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $provider = $request->user();

        if (! $provider || $provider->type !== 'provider') {
            return response()->json(['success' => false, 'message' => __('lang.only_providers_can_reject')], 403);
        }

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $reservation = $this->repo->reject($id, $provider->id, $request->reason);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->bumpCacheVersion("unite_reservations_index:{$reservation->user_id}");

        return response()->json([
            'success' => true,
            'message' => __('lang.reservation_rejected_customer_notified'),
            'reservation' => new ReservationResource($reservation),
        ]);
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $reservation = UniteReservation::findOrFail($id);

        $this->authorize('cancel', $reservation);

        $result = $this->repo->cancel($id, $request->user()?->id);

        $this->bumpCacheVersion("unite_reservations_index:{$result->user_id}");

        return response()->json([
            'success' => true,
            'message' => __('lang.reservation_cancelled_successfully_msg'),
            'data' => new ReservationResource($result),
        ]);
    }

    /**
     * Rate a specific booking. Eligible once the reservation is confirmed
     * and its date is not in the past (today or a future date is fine) —
     * see UniteReservation::isRatable(). Each reservation gets exactly one
     * rating (enforced by unique('reservation_id') on unite_ratings) — a
     * customer who books the same venue multiple times can rate each stay
     * independently, unlike the legacy unites/{id}/rate endpoint which was
     * limited to one rating per venue ever, regardless of booking count.
     */
    public function rate(int $id, RateUniteRequest $request): JsonResponse
    {
        $reservation = UniteReservation::with('rating')->findOrFail($id);
        $user = $request->user();

        if ($reservation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('lang.unauthorized_action'),
            ], 403);
        }

        if (! $reservation->isRatable()) {
            return response()->json([
                'success' => false,
                'message' => __('lang.reservation_not_ratable'),
            ], 422);
        }

        if ($reservation->rating) {
            return response()->json([
                'success' => false,
                'message' => __('lang.reservation_already_rated'),
            ], 422);
        }

        $rating = UniteRating::create([
            'unite_id' => $reservation->unite_id,
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        $this->bumpCacheVersion("unite_reservations_index:{$user->id}");

        return response()->json([
            'success' => true,
            'message' => __('lang.unite_rated_successfully'),
            'data' => $rating,
        ]);
    }
}
