<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniteBookingPackage extends Model
{
    protected $fillable = [
        'unite_id',
        'name',
        'booking_type',
        'day',
        'start_time',
        'end_time',
        'day_from',
        'day_to',
        'duration_days',
        'price',
        'services',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'services' => 'array', // free-text list, not a relation
    ];

    private const WEEK_ORDER = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }

    /**
     * Computes the number of consecutive calendar days a 'days'-type
     * package spans, from day_from through day_to inclusive, wrapping
     * around the week when day_to comes "before" day_from — e.g.
     * friday -> sunday spans exactly 3 days (fri, sat, sun), not a
     * negative or nonsensical range. saturday -> saturday (single day)
     * correctly spans 1 day.
     */
    public static function computeDurationDays(string $dayFrom, string $dayTo): int
    {
        $fromIdx = array_search($dayFrom, self::WEEK_ORDER);
        $toIdx = array_search($dayTo, self::WEEK_ORDER);

        if ($fromIdx === false || $toIdx === false) {
            return 1;
        }

        return $toIdx >= $fromIdx
            ? ($toIdx - $fromIdx + 1)
            : (7 - $fromIdx + $toIdx + 1);
    }

    /**
     * Whether this package can be booked STARTING on the given specific
     * day-of-week name (lowercase, e.g. 'friday').
     *
     * 'hours' mode: maps the day to its category (week_day/thursday/
     * friday/saturday) using the exact same mapping already used for
     * price resolution elsewhere, so 'day' here means the same thing it
     * means everywhere else in this project.
     *
     * 'days' mode: the booking must START on day_from's exact weekday —
     * day_to only affects how many nights the stay spans (via
     * duration_days), not which start days are valid.
     */
    public function appliesToDay(string $dayOfWeek): bool
    {
        if ($this->booking_type === 'days') {
            return $this->day_from === $dayOfWeek;
        }

        $category = match ($dayOfWeek) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        return $this->day === $category;
    }
}
