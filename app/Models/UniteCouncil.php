<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A council type belonging to a venue, with a count — a lounge can have
 * several distinct types (e.g. "men's sitting", "women's sitting",
 * "mixed"), each represented by ONE row carrying how many councils of
 * that type exist (number), not one row per individual council.
 * Replaces the old single flat 'council_type' string on unite_details,
 * which could only describe one shared type for however many councils
 * council_number said existed.
 *
 * unite_details.council / council_number remain as summary fields
 * (whether this venue has councils at all, and how many) — this table
 * holds the individual entries.
 */
class UniteCouncil extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'type',
        'number',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }
}
