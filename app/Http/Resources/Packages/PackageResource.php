<?php

namespace App\Http\Resources\Packages;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'property_packages' => PropertyPackageResource::collection($this->resource['property_packages']),
            'ad_packages' => AdPackageResource::collection($this->resource['ad_packages']),
        ];
    }
}
