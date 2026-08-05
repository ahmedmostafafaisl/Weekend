<?php

namespace App\Http\Resources\Packages;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdPackageResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'count' => $this->count,
            'duration' => $this->duration,
            'price' => $this->price,
            'image' => $this->image ? asset($this->image) : null,
            'status' => $this->status,
        ];
    }
}
