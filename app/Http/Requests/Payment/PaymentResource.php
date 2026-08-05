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
            'reference_id' => $this->reference_id,
            'gateway_id' => $this->payment_id,

            // ── Classification ────────────────────────────────────────────
            'payment_type' => $this->payment_type,
            'for' => $this->isForReservation()
                ? 'reservation'
                : ($this->isForSubscription() ? 'subscription' : 'other'),

            // ── Who ───────────────────────────────────────────────────────
            'user' => new UserResource($this->whenLoaded('user')),

            // ── Linked Records ────────────────────────────────────────────
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
            'original_amount' => $this->original_amount ? (float) $this->original_amount : null,
            'discount_amount' => $this->discount_amount ? (float) $this->discount_amount : null,
            'has_discount' => $this->hasDiscount(),
            'phone' => $this->phone,

            // Promo code (if applied)
            'promo_code' => $this->whenLoaded('promoCode', fn () => $this->promoCode ? [
                'id' => $this->promoCode->id,
                'code' => $this->promoCode->code,
                'discount_type' => $this->promoCode->discount_type,
                'discount_value' => $this->promoCode->discount_value,
            ] : null),

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
