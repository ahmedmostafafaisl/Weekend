<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consolidates the previous HallDetail, StadiumDetail, LoungeDetail, and
 * CampDetail models into one polymorphic detail record per Unite.
 *
 * Each Unite has exactly one UniteDetail row (unite_id is unique). Only the
 * columns relevant to that unite's `type` are ever populated — the same
 * sparse-row pattern the 4 separate tables already had, just centralized.
 *
 * Field groups by original source model, preserved here only as documentation:
 *   Hall:    max_chairs, max_tables, max_capacity, women_*, men_*, buffet*
 *   Stadium: customize_Category, customize_Place, width, length, amenities, cafeteria
 *   Lounge:  area, bedroom*, single_bed, big_bed, council*, pool, kitchen
 *   Camp:    seating_capacity, television, fireplace
 *   Shared:  bathroom, bathroom_number, *_start_time, *_end_time
 */
class UniteDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        // Hall
        'max_chairs', 'max_tables', 'max_capacity',
        'women_seating', 'kusha', 'women_seating_capacity', 'women_seating_details',
        'women_buffet', 'women_buffet_details', 'women_tables_count', 'women_chairs_count',
        'men_seating_available', 'men_seating_capacity', 'men_seating_details',
        'men_tables_count', 'men_chairs_count', 'men_buffet', 'men_buffet_details',
        'buffet', 'buffet_details',
        // Stadium
        'customize_Category', 'customize_Place', 'width', 'length', 'amenities', 'cafeteria',
        // Lounge
        'area', 'bedroom', 'bedroom_number', 'single_bed', 'big_bed',
        'kitchen', 'pool', 'council', 'council_number', 'council_type',
        // Camp
        'seating_capacity', 'television', 'fireplace',
        // Shared
        'bathroom', 'bathroom_number',
        'morning_start_time', 'morning_end_time',
        'evening_start_time', 'evening_end_time',
        'full_day_start_time', 'full_day_end_time',
    ];

    protected $casts = [
        'women_seating' => 'boolean',
        'kusha' => 'boolean',
        'women_buffet' => 'boolean',
        'men_seating_available' => 'boolean',
        'men_buffet' => 'boolean',
        'buffet' => 'boolean',
        'amenities' => 'boolean',
        'cafeteria' => 'boolean',
        'bedroom' => 'boolean',
        'kitchen' => 'boolean',
        'pool' => 'boolean',
        'council' => 'boolean',
        'television' => 'boolean',
        'fireplace' => 'boolean',
        'bathroom' => 'boolean',
        'area' => 'decimal:2',
    ];

    // Preserved from the original HallDetail model — hall forms historically
    // submitted max_tables/max_chairs as "all_men_count"/"all_women_count".
    protected $appends = ['all_men_count', 'all_women_count'];

    protected $hidden = ['max_tables', 'max_chairs'];

    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
    }

    public function getAllMenCountAttribute()
    {
        return $this->max_tables;
    }

    public function getAllWomenCountAttribute()
    {
        return $this->max_chairs;
    }
}
