<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Resources\Json\JsonResource;

class UniteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'reservation_deposit' => (bool) $this->reservation_deposit,
            'reservation_deposit_type' => $this->reservation_deposit_type,
            'reservation_deposit_amount' => $this->reservation_deposit_amount,
            'insurance' => (bool) $this->insurance,
            'insurance_amount' => $this->insurance_amount,
            'refund_policy' => $this->refund_policy,
            'additional_terms' => $this->additional_terms,
            'status' => $this->status,
            'rating' => round((float) ($this->ratings_avg_rating ?? 0), 1),
            'rating_count' => (int) ($this->ratings_count ?? 0),
            'distance_km' => $this->distance_km ?? null,
            'details' => $this->whenLoaded($this->detailRelation()),
            'images' => $this->images->map(fn ($img) => asset($img->image))->values(),

            'features' => $this->features,
            'offers' => $this->offers,

            'reservations' => $this->reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'user_id' => $reservation->user_id,
                    'reservation_date' => optional($reservation->reservation_date)->format('Y-m-d'),
                    'period_type' => $reservation->period_type,
                    'from_time' => $reservation->from_time,
                    'to_time' => $reservation->to_time,
                    'price' => $reservation->price,
                    'status' => $reservation->status,
                ];
            })->values(),

            'slots' => $this->formatSlotsByType(),
            'prices' => $this->formatPricesByType(),
            'packages' => $this->packages,
            'new_features' => $this->newFeatures,
            'services' => $this->services,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function formatSlotsByType()
    {
        if ($this->type === 'stadium') {
            return $this->slots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'full_start' => $slot->full_start,
                    'full_end' => $slot->full_end,
                    'status' => $slot->status,
                ];
            })->values();
        }

        return $this->slots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'morning_start' => $slot->morning_start,
                'morning_end' => $slot->morning_end,
                'evening_start' => $slot->evening_start,
                'evening_end' => $slot->evening_end,
                'full_start' => $slot->full_start,
                'full_end' => $slot->full_end,
                'status' => $slot->status,
            ];
        })->values();
    }

    protected function formatPricesByType()
    {
        if ($this->type === 'stadium') {
            return $this->prices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'day' => $price->day,
                    'price' => $price->price,
                ];
            })->values();
        }

        return $this->prices->map(function ($price) {
            return [
                'id' => $price->id,
                'day' => $price->day,
                'morning_price' => $price->morning_price,
                'evening_price' => $price->evening_price,
                'full_price' => $price->full_price,
            ];
        })->values();
    }

    protected function detailRelation()
    {
        return match ($this->type) {
            'stadium' => 'stadiumDetail',
            'hall' => 'hallDetail',
            'lounge' => 'loungeDetail',
            'camp' => 'campDetail',
        };
    }
}
