<?php

namespace App\Http\Resources\Reservation;

use App\Http\Resources\Auth\User\UserResource;
use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Identity ──────────────────────────────────────────────────
            'id' => $this->id,

            // ── Who & What ────────────────────────────────────────────────
            'user' => new UserResource($this->whenLoaded('user')),
            'unite' => $this->whenLoaded('unite', fn () => [
                'id' => $this->unite->id,
                'name' => $this->unite->name,
                'type' => $this->unite->type,
                'location_name' => $this->unite->location_name,
                'department' => $this->unite->department
                    ? ['id' => $this->unite->department->id, 'name' => $this->unite->department->name]
                    : null,
                'thumbnail' => $this->unite->images->first()
                    ? asset($this->unite->images->first()->image)
                    : null,
                // Whether THIS specific reservation has been rated — not
                // "has the user ever rated this unite," since a customer
                // can now rate the same venue independently on each
                // separate booking (see UniteRating's reservation_id).
                // Relies on 'rating' being eager-loaded on the reservation
                // itself (see UniteReservationController::myReservations()),
                // so this is a single query for the whole page.
                'has_rated' => $this->relationLoaded('rating')
                    ? $this->rating !== null
                    : null,
            ]),

            // Present only once this booking has actually been rated.
            'rating' => $this->whenLoaded('rating', fn () => $this->rating ? [
                'rating' => $this->rating->rating,
                'review' => $this->rating->review,
                'rated_at' => $this->rating->created_at?->toIso8601String(),
            ] : null),

            // ── Booking Window ─────────────────────────────────────────────
            'reservation_date' => $this->reservation_date?->format('Y-m-d'),
            'period_type' => $this->period_type,
            'from_time' => $this->from_time,
            'to_time' => $this->to_time,

            // Computed: ISO datetime of when the booking starts, useful for calendars
            'starts_at' => $this->starts_at?->toIso8601String(),

            // ── Financials ────────────────────────────────────────────────
            'price' => (float) $this->price,
            'guest_count' => $this->guest_count,
            'notes' => $this->notes,

            // ── Status ────────────────────────────────────────────────────
            'status' => $this->status,
            'is_paid' => $this->isPaid(),

            // ── Payment ───────────────────────────────────────────────────
            'payment' => new PaymentResource($this->whenLoaded('payment')),

            // ── Timestamps ───────────────────────────────────────────────
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
