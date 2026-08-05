<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use App\Http\Resources\Auth\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Packages\AdPackageResource;
use App\Http\Resources\Packages\PropertyPackageResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'user'       => new UserResource($this->whenLoaded('user')),
            'type'       => $this->type,
            'package'    => $this->type  === 'property'
                ? new PropertyPackageResource($this->propertyPackage)
                : new AdPackageResource($this->adPackage),
            'amount'     => $this->amount,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
            'percentage' => $this->percentage,
            'count'      => $this->count,
            'status'     => $this->status,
        ];
    }
}
