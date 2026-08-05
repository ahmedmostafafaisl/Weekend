<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniteResource2 extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstImage = $this->images->first();

        $price = null;
        $firstPrice = $this->prices->first();

        if ($firstPrice) {
            $price = $firstPrice->price
                ?? $firstPrice->morning_price
                ?? $firstPrice->evening_price
                ?? $firstPrice->full_price;
        }

        return [
            'id' => $this->id,
            'image' => $firstImage ? asset($firstImage->image) : null,
            'rating' => round((float) ($this->ratings_avg_rating ?? 0), 1),
            'name' => $this->name,
            'location_address' => $this->location_name,
            'price' => $price,
            'is_favorite' => $this->favorites->isNotEmpty(),
        ];
    }
}
