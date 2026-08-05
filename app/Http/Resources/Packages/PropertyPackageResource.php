<?php

namespace App\Http\Resources\Packages;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyPackageResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'duration' => $this->duration,
            'percentage' => $this->percentage,
            'price' => $this->price,
            'image' => $this->image ? asset($this->image) : null,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
