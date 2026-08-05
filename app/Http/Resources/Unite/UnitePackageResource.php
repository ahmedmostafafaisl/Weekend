<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitePackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unite_id' => $this->unite_id,
            'name' => $this->name,
            'men_capacity' => $this->men_capacity,
            'women_capacity' => $this->women_capacity,
            'price' => $this->price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
