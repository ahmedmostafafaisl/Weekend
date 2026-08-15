<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniteSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unite_id' => $this->unite_id,
            'day_of_week' => $this->day_of_week,
            'morning_start' => $this->morning_start,
            'morning_end' => $this->morning_end,
            'evening_start' => $this->evening_start,
            'evening_end' => $this->evening_end,
            'full_start' => $this->full_start,
            'full_end' => $this->full_end,
            'status' => $this->status,
            'day_start' => $this->day_start,
            'day_end' => $this->day_end,
            'buffer_minutes' => $this->buffer_minutes,
            'periods' => $this->whenLoaded('periods', function () {
                return $this->periods->map(fn ($p) => [
                    'id' => $p->id,
                    'start_time' => $p->start_time,
                    'end_time' => $p->end_time,
                    'status' => $p->status,
                ])->values();
            }, []),
            'slot_times' => $this->slot_times,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
