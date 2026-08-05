<?php

namespace App\Http\Resources\Department;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'type' => $this->type,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'facebook' => $this->facebook,
            'twitter' => $this->twitter,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
            'website' => $this->website,
            'whatsapp' => $this->whatsapp,
            'snapchat' => $this->snapchat,
            'tiktok' => $this->tiktok,
            'user' => $this->user ? $this->user->only(['id', 'name', 'email']) : null,
            'images' => $this->images->map(fn ($img) => asset('storage/'.$img->image)),
            'unites_count' => $this->unites->count(),

            'unites' => collect($this->unites)->map(function ($unite) {
                $firstImage = $unite->images->first();

                return [
                    'id' => $unite->id,
                    'name' => $unite->name,
                    'type' => $unite->type,
                    'description' => $unite->description,
                    'location_name' => $unite->location_name,
                    'latitude' => $unite->latitude,
                    'longitude' => $unite->longitude,
                    'image' => $firstImage ? asset($firstImage->image) : null,
                ];
            }),

        ];
    }
}
