<?php

namespace App\Http\Resources\Department;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request)
    {
        // The provider's current, non-exhausted property subscription's
        // remaining count -- via the same shared method
        // UniteController::store()'s unite-creation gate itself uses, so
        // this always agrees with what that gate would actually enforce.
        // null means either no qualifying subscription at all, or an
        // unlimited one (a time/percentage-type package, which doesn't
        // limit by unite count). Computed once here and reused both at
        // the top level and on every individual unite entry below (same
        // value for all of them, since they all belong to the same
        // provider/department), rather than querying once per place it's
        // needed.
        $maxCount = $this->user?->activePropertySubscription()?->count;

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
            'max_count' => $maxCount,
            'images' => $this->images->map(fn ($img) => asset('storage/'.$img->image)),
            'unites_count' => $this->unites->count(),

            'unites' => collect($this->unites)->map(function ($unite) use ($maxCount) {
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
                    'max_count' => $maxCount,
                ];
            }),

        ];
    }
}
