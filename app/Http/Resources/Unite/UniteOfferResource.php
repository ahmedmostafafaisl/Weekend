<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniteOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unite_id' => $this->unite_id,
            'name' => $this->name,
            'start' => $this->start,
            'end' => $this->end,
            'morning_price' => $this->morning_price,
            'evening_price' => $this->evening_price,
            'full_day_price' => $this->full_day_price,
            'day_hour_price' => $this->day_hour_price,
            'night_hour_price' => $this->night_hour_price,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
