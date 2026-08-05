<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'day',
        'price',
        'morning_price',
        'evening_price',
        'full_price',
        // Hourly pricing
        'hourly_enabled',
        'day_hour_price',
        'night_hour_price',
        'day_start',
        'day_end',
        'min_booking_minutes',
    ];

    protected $casts = [
        'hourly_enabled' => 'boolean',
        'day_hour_price' => 'decimal:2',
        'night_hour_price' => 'decimal:2',
        'min_booking_minutes' => 'integer',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }

    public function getPricingAttribute(): array
    {
        if ($this->unite && $this->unite->type === 'stadium') {
            return ['price' => $this->price];
        }

        $base = [
            'morning' => $this->morning_price,
            'evening' => $this->evening_price,
            'full' => $this->full_price,
        ];

        if ($this->hourly_enabled) {
            $base['hourly'] = [
                'enabled' => true,
                'day_hour_price' => $this->day_hour_price,
                'night_hour_price' => $this->night_hour_price ?? $this->day_hour_price,
                'day_start' => $this->day_start,
                'day_end' => $this->day_end,
                'min_booking_minutes' => $this->min_booking_minutes,
            ];
        }

        return $base;
    }

    /**
     * Calculate the total price for an hourly booking between two times.
     *
     * Splits the period at the day/night boundary and charges each minute
     * at the appropriate per-minute rate. Returns a rounded total (SAR).
     *
     * Example: 15:00 – 21:00 with day_end=18:00
     *   3 h × day_rate  +  3 h × night_rate
     *
     * @param  string  $fromTime  H:i  (e.g. "14:00")
     * @param  string  $toTime  H:i  (e.g. "20:30")
     */
    public function calculateHourlyPrice(string $fromTime, string $toTime): float
    {
        if (! $this->hourly_enabled || ! $this->day_hour_price) {
            return 0.0;
        }

        $from = Carbon::createFromFormat('H:i', substr($fromTime, 0, 5));
        $to = Carbon::createFromFormat('H:i', substr($toTime, 0, 5));
        // BUG FIX: day_start/day_end are genuine MySQL TIME columns, which
        // MySQL always returns in H:i:s format (with seconds) regardless of
        // what was originally written to them — even a value submitted as
        // "06:00" through the dashboard's <input type="time"> comes back
        // as "06:00:00" once read from the database. $fromTime/$toTime
        // were already defensively truncated to 5 characters below to
        // handle exactly this; day_start/day_end need the same treatment,
        // or Carbon::createFromFormat('H:i', ...) throws "Trailing data"
        // the moment either one is ever actually populated.
        $dayStart = Carbon::createFromFormat('H:i', substr($this->day_start ?? '06:00', 0, 5));
        $dayEnd = Carbon::createFromFormat('H:i', substr($this->day_end ?? '18:00', 0, 5));
        $dayRate = (float) $this->day_hour_price;
        $nightRate = (float) ($this->night_hour_price ?? $this->day_hour_price);

        if ($to->lte($from)) {
            return 0.0; // invalid range — controller has already validated this
        }

        $totalMinutes = $from->diffInMinutes($to);
        $dayCost = 0.0;
        $nightCost = 0.0;

        // Walk minute by minute through the range
        // (efficient for ranges up to 24h which is the practical max)
        $cursor = $from->copy();
        while ($cursor->lt($to)) {
            $isDay = $cursor->gte($dayStart) && $cursor->lt($dayEnd);
            if ($isDay) {
                $dayCost += $dayRate / 60;
            } else {
                $nightCost += $nightRate / 60;
            }
            $cursor->addMinute();
        }

        return round($dayCost + $nightCost, 2);
    }

    /**
     * Human-readable breakdown of an hourly booking for receipts / confirmations.
     *
     * @return array{day_minutes: int, night_minutes: int, day_cost: float, night_cost: float, total: float}
     */
    public function hourlyBreakdown(string $fromTime, string $toTime): array
    {
        $from = Carbon::createFromFormat('H:i', substr($fromTime, 0, 5));
        $to = Carbon::createFromFormat('H:i', substr($toTime, 0, 5));
        // BUG FIX: same TIME-column seconds issue as calculateHourlyPrice()
        // above — see that method for the full explanation.
        $dayStart = Carbon::createFromFormat('H:i', substr($this->day_start ?? '06:00', 0, 5));
        $dayEnd = Carbon::createFromFormat('H:i', substr($this->day_end ?? '18:00', 0, 5));
        $dayRate = (float) $this->day_hour_price;
        $nightRate = (float) ($this->night_hour_price ?? $this->day_hour_price);

        $dayMin = $nightMin = 0;
        $cursor = $from->copy();
        while ($cursor->lt($to)) {
            $cursor->gte($dayStart) && $cursor->lt($dayEnd) ? $dayMin++ : $nightMin++;
            $cursor->addMinute();
        }

        $dayCost = round($dayMin * $dayRate / 60, 2);
        $nightCost = round($nightMin * $nightRate / 60, 2);

        return [
            'day_minutes' => $dayMin,
            'night_minutes' => $nightMin,
            'day_cost' => $dayCost,
            'night_cost' => $nightCost,
            'total' => round($dayCost + $nightCost, 2),
        ];
    }
}
