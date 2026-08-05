<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniteSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'day_of_week',
        'morning_start',
        'morning_end',
        'evening_start',
        'evening_end',
        'full_start',
        'full_end',
        'status',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }

    public function getSlotTimesAttribute()
    {
        if ($this->unite && $this->unite->type === 'stadium') {
            return [
                'day_of_week' => $this->day_of_week,
                'start' => $this->full_start,
                'end' => $this->full_end,
                'status' => $this->status,
            ];
        }

        return [
            'day_of_week' => $this->day_of_week,
            'morning' => [$this->morning_start, $this->morning_end],
            'evening' => [$this->evening_start, $this->evening_end],
            'full' => [$this->full_start, $this->full_end],
            'status' => $this->status,
        ];
    }
}
