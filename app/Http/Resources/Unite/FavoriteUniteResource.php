<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteUniteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->images->first();
        $price = $this->prices->first();

        return [
            'id' => $this->id,
            'image' => $image ? asset($image->image) : null,
            'rating' => round((float) ($this->ratings_avg_rating ?? 0), 1),
            'name' => $this->name,
            'location_address' => $this->location_name,
            'price' => $this->type === 'stadium'
                ? ($price?->price)
                : ($price?->full_price ?? $price?->evening_price ?? $price?->morning_price),
            // 'is_favorite' => $this->favorites->isNotEmpty(),
        ];
    }
}
