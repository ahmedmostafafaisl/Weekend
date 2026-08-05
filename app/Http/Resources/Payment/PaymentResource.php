<?php

namespace App\Http\Resources\Payment;

use App\Http\Resources\Auth\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Identity ─────────────────────────────────────────────────
            'id' => $this->id,
            'reference_id' => $this->reference_id,   // PAY-20260608-XXXXXXXX
            'gateway_id' => $this->payment_id,      // Geidea order/intent ID

            // ── Classification ────────────────────────────────────────────
            'payment_type' => $this->payment_type,    // geidea / cash / etc.
            'for' => $this->isForReservation()
                ? 'reservation'
                : ($this->isForSubscription() ? 'subscription' : 'other'),

            // ── Who ───────────────────────────────────────────────────────
            'user' => new UserResource($this->whenLoaded('user')),

            // ── Linked Records ────────────────────────────────────────────
            // Inline summary only — prevents circular nesting
            'reservation' => $this->whenLoaded('reservation', fn () => [
                'id' => $this->reservation->id,
                'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
                'period_type' => $this->reservation->period_type,
                'status' => $this->reservation->status,
            ]),
            'subscription' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription->id,
                'type' => $this->subscription->type,
                'status' => $this->subscription->status,
            ]),

            // ── Financials ────────────────────────────────────────────────
            'amount' => (float) $this->amount,
            'phone' => $this->phone,

            // Line items (if loaded)
            'items' => PaymentItemResource::collection($this->whenLoaded('items')),

            // ── Status ────────────────────────────────────────────────────
            'status' => $this->status,
            'is_paid' => $this->isPaid(),
            'is_refunded' => $this->isRefunded(),
            'is_refund_failed' => $this->isRefundFailed(),

            // ── Timestamps ───────────────────────────────────────────────
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
