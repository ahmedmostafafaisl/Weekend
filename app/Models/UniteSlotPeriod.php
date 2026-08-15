<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One custom availability period belonging to a UniteSlot — e.g.
 * 06:00-08:00. A slot with one or more of these uses them instead of its
 * own day_start/day_end as a single continuous window. See UniteSlot's
 * periods() relationship and availabilityWindows() for how the two
 * interact.
 */
class UniteSlotPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_slot_id',
        'start_time',
        'end_time',
        'status',
    ];

    public function slot()
    {
        return $this->belongsTo(UniteSlot::class, 'unite_slot_id');
    }
}
