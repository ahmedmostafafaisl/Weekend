<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitePriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'unite_id' => $this->unite_id,
            'day' => $this->day,
            'price' => $this->price,
            'morning_price' => $this->morning_price,
            'evening_price' => $this->evening_price,
            'full_price' => $this->full_price,
            'pricing' => $this->pricing,

            // Hourly pricing
            'hourly_enabled' => (bool) $this->hourly_enabled,
            'day_hour_price' => $this->day_hour_price,
            'night_hour_price' => $this->night_hour_price,
            'day_start' => $this->day_start,
            'day_end' => $this->day_end,
            'min_booking_minutes' => $this->min_booking_minutes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return $data;
    }
}
