<?php

namespace App\Http\Resources\Auth\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'commercial_name' => $this->commercial_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'type' => $this->type,
            'provider_type' => $this->provider_type,
            'nation' => $this->nation,
            'gender' => $this->gender,
            'id_number' => $this->id_number,
            'birth_date' => $this->birth_date,
            'photo' => $this->photo ? asset('storage/'.$this->photo) : null,
            'front_identity' => $this->front_identity ? asset('storage/'.$this->front_identity) : null,
            'back_identity' => $this->back_identity ? asset('storage/'.$this->back_identity) : null,
            'sak_image' => $this->sak_image ? asset('storage/'.$this->sak_image) : null,
            'commercial_register_number' => $this->commercial_register_number,
            'organization_name' => $this->organization_name,
            'commercial_register_image' => $this->commercial_register_image ? asset('storage/'.$this->commercial_register_image) : null,
            'ownership' => $this->ownership,
            'delegation' => $this->delegation,
            'created_at' => $this->created_at,
        ];
    }
}
