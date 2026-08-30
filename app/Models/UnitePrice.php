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
     * day_hour_price / night_hour_price are the price PER BLOCK, where a
     * block is min_booking_minutes long (defaulting to 60 — a plain,
     * unconfigured venue therefore behaves exactly like the old per-hour
     * model, since a 60-minute block priced per block is identical to
     * being priced per hour). Each block is charged in full at whichever
     * rate applies to the block's START time — a block isn't split or
     * prorated even if it happens to straddle the day/night boundary.
     *
     * Example: 15:00 – 18:00 with day_end=18:00, min_booking_minutes=60
     *   3 blocks, all starting before 18:00 -> 3 × day_rate
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
        $blockMinutes = max(1, (int) ($this->min_booking_minutes ?? 60));

        if ($to->lte($from)) {
            // An overnight range (e.g. from=22:00, to=02:00 the next day) --
            // not invalid, just wraps past midnight. Bump $to forward a day
            // rather than rejecting it outright and silently pricing the
            // booking at 0.0.
            $to = \App\Support\OvernightRange::normalizeEnd($from, $to);
        }

        $totalMinutes = $from->diffInMinutes($to);
        $blockCount = intdiv($totalMinutes, $blockMinutes);
        if ($totalMinutes % $blockMinutes !== 0) {
            // Should never happen — resolveTimes() rejects non-multiple
            // durations before this is ever called — but if it somehow is,
            // round up rather than silently dropping the partial block's
            // charge entirely.
            $blockCount++;
        }

        $total = 0.0;
        $cursor = $from->copy();
        for ($i = 0; $i < $blockCount; $i++) {
            // $dayStart/$dayEnd describe a boundary that recurs every
            // calendar day (e.g. "day rate applies 06:00-18:00"), not a
            // single fixed date -- re-anchor both to $cursor's own date
            // before comparing, so a block that has advanced past midnight
            // is still correctly checked against that same recurring
            // boundary, not one still anchored to the original day.
            $todayDayStart = $cursor->copy()->setTimeFrom($dayStart);
            $todayDayEnd = $cursor->copy()->setTimeFrom($dayEnd);

            $isDay = $cursor->gte($todayDayStart) && $cursor->lt($todayDayEnd);
            $total += $isDay ? $dayRate : $nightRate;
            $cursor->addMinutes($blockMinutes);
        }

        return round($total, 2);
    }

    /**
     * Human-readable breakdown of an hourly booking for receipts /
     * confirmations. Block-based, matching calculateHourlyPrice() exactly
     * — has no callers anywhere in the codebase currently (confirmed
     * before changing its return shape), so this rebuild is not a
     * breaking change for anything actually using it today.
     *
     * @return array{block_minutes: int, day_blocks: int, night_blocks: int, day_cost: float, night_cost: float, total: float}
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
        $blockMinutes = max(1, (int) ($this->min_booking_minutes ?? 60));

        $totalMinutes = $from->diffInMinutes($to);
        $blockCount = intdiv($totalMinutes, $blockMinutes);
        if ($totalMinutes % $blockMinutes !== 0) {
            $blockCount++;
        }

        $dayBlocks = $nightBlocks = 0;
        $cursor = $from->copy();
        for ($i = 0; $i < $blockCount; $i++) {
            $cursor->gte($dayStart) && $cursor->lt($dayEnd) ? $dayBlocks++ : $nightBlocks++;
            $cursor->addMinutes($blockMinutes);
        }

        $dayCost = round($dayBlocks * $dayRate, 2);
        $nightCost = round($nightBlocks * $nightRate, 2);

        return [
            'block_minutes' => $blockMinutes,
            'day_blocks' => $dayBlocks,
            'night_blocks' => $nightBlocks,
            'day_cost' => $dayCost,
            'night_cost' => $nightCost,
            'total' => round($dayCost + $nightCost, 2),
        ];
    }
}
