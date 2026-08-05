<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaginatedCollection extends JsonResource
{
    public function toArray($request)
    {
        $p = $this->resource;

        return [
            'data' => $p->items(),
            'pagination' => [
                'current_page' => $p->currentPage(),
                'total_pages'  => $p->lastPage(),
                'per_page'     => $p->perPage(),
                'total_items'  => $p->total(),
            ],
        ];
    }
}
