<?php

namespace App\Http\Resources\Ads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail ? asset($this->thumbnail) : null,
            'media' => $this->media ? asset($this->media) : null,
            'is_active' => $this->is_active,
            'activated_at' => $this->activated_at,
            'expires_at' => $this->expires_at,
            'city' => $this->city,
            'target_audience' => $this->target_audience,
            'target_user_type' => $this->target_user_type,
            'property_id' => $this->type === 'property' ? $this->property_id : null,
            'property' => $this->when(
                $this->type === 'property' && $this->property,
                [
                    'id' => $this->property?->id,
                    'name' => $this->property?->name ?? null,
                ]
            ),
            'user_profile_image' => $this->type === 'ad'
                ? $this->user?->photo_url
                : null,
            'is_seen' => $this->when(isset($this->seen), $this->seen),
            'comments_count' => $this->comments_count ?? $this->comments()->count(),
            'comments' => AdCommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
