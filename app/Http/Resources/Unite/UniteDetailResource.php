<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniteDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge([
            'id' => $this->id,
            'unite_id' => $this->unite_id,
            'type' => $this->unite?->type,
        ], $this->resource->toArray());
    }
}
