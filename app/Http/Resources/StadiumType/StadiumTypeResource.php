<?php

namespace App\Http\Resources\StadiumType;

use Illuminate\Http\Resources\Json\JsonResource;

class StadiumTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'created_at' => $this->created_at,
        ];
    }
}
