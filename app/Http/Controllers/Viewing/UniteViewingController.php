<?php

namespace App\Http\Controllers\Viewing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Viewing\StoreViewingRequest;
use App\Repositories\Viewing\UniteViewingRepository;
use Illuminate\Http\JsonResponse;

/**
 * REVIEW NOTE: this controller was designed and delivered alongside the
 * rest of the viewing-appointment feature, but never actually made it
 * into the pushed repository — confirmed missing during a project
 * review. The route (POST /api/unite-viewings) already existed and
 * pointed directly at this class, so every request to that endpoint was
 * failing with a fatal "class not found" error until now.
 */
class UniteViewingController extends Controller
{
    public function __construct(protected UniteViewingRepository $repo) {}

    public function store(StoreViewingRequest $request): JsonResponse
    {
        try {
            $result = $this->repo->create($request->validated(), $request->user()->id);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $viewing = $result['viewing'];
        $depositRequired = (bool) $viewing->deposit_required;

        return response()->json([
            'success' => true,
            'message' => $depositRequired
                ? __('lang.viewing_created_pay_deposit')
                : __('lang.viewing_confirmed_no_deposit'),
            'status' => $viewing->status,
            'data' => [
                'viewing' => [
                    'id' => $viewing->id,
                    'unite_id' => $viewing->unite_id,
                    'unite_name' => $viewing->unite->name ?? null,
                    'viewing_date' => $viewing->viewing_date?->format('Y-m-d'),
                    'day_of_week' => $viewing->viewingTime->day_of_week ?? null,
                    'start_time' => $viewing->viewingTime->start_time ?? null,
                    'end_time' => $viewing->viewingTime->end_time ?? null,
                    'status' => $viewing->status,
                    'deposit_required' => $depositRequired,
                    'deposit_amount' => $viewing->deposit_amount ? (float) $viewing->deposit_amount : null,
                    'deposit_refundable' => $viewing->deposit_refundable,
                    // Every registered user linked to this appointment,
                    // including the primary booker — "Number of People"
                    // is simply the count of this array.
                    'attendee_count' => $viewing->attendees->count(),
                    'attendees' => $viewing->attendees->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'phone' => $u->phone,
                    ])->values(),
                ],
                'payment' => $result['payment'] ? [
                    'id' => $result['payment']->id,
                    'reference_id' => $result['payment']->reference_id,
                    'amount' => (float) $result['payment']->amount,
                    'status' => $result['payment']->status,
                ] : null,
                'payment_url' => $result['payment_url'],
            ],
        ], 201);
    }

    /**
     * Cancels the authenticated customer's own free (no-deposit) viewing
     * appointment. Deposit-based appointments are deliberately rejected
     * here — see UniteViewingRepository::cancel() for why.
     */
    public function cancel(int $id, \Illuminate\Http\Request $request): JsonResponse
    {
        $viewing = $this->repo->cancel($id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => __('lang.viewing_cancelled_successfully'),
            'data' => [
                'viewing' => [
                    'id' => $viewing->id,
                    'unite_id' => $viewing->unite_id,
                    'unite_name' => $viewing->unite->name ?? null,
                    'viewing_date' => $viewing->viewing_date?->format('Y-m-d'),
                    'status' => $viewing->status,
                ],
            ],
        ]);
    }
}
