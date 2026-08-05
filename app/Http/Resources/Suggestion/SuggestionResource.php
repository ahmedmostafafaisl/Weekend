<?php

namespace App\Http\Resources\Suggestion;

use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'content' => $this->content,
            'created_at' => $this->created_at,
        ];
    }
}
