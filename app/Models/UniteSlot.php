<?php

namespace App\Models;

use Carbon\Carbon;
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
        'day_start',
        'day_end',
        'buffer_minutes',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }

    /**
     * Custom availability periods for this slot, ordered by start time.
     * See availabilityWindows() for how these interact with
     * day_start/day_end.
     */
    public function periods()
    {
        return $this->hasMany(UniteSlotPeriod::class)->orderBy('start_time');
    }

    /**
     * The actual bookable windows for this slot, as [start, end] pairs.
     *
     * If this slot has ANY custom periods configured (regardless of their
     * individual status), those entirely replace day_start/day_end as the
     * source of truth — only the 'available' ones among them count as
     * actual bookable windows. A slot whose only period is 'unavailable'
     * correctly produces an empty list here (nothing bookable that day),
     * NOT a fallback to day_start/day_end or to "unrestricted" — see
     * hasWindowConfiguration() for how callers distinguish that genuinely-
     * closed case from "nothing configured at all".
     *
     * If it has no periods at all, day_start/day_end (if set) form a
     * single continuous window. If neither exists, returns an empty
     * array.
     */
    public function availabilityWindows(): array
    {
        $periods = $this->relationLoaded('periods') ? $this->periods : $this->periods()->get();

        if ($periods->isNotEmpty()) {
            return $periods->where('status', 'available')
                ->map(fn ($p) => [$p->start_time, $p->end_time])
                ->values()
                ->all();
        }

        if ($this->day_start && $this->day_end) {
            return [[$this->day_start, $this->day_end]];
        }

        return [];
    }

    /**
     * Whether this slot has ANY window configuration at all — custom
     * periods (any status) or day_start/day_end. Distinguishes "nothing
     * configured, no restriction applies" from "configured, but the
     * result happens to be zero bookable windows" (e.g. every custom
     * period is 'unavailable' — the day is deliberately closed, not
     * unconfigured). availabilityWindows() alone can't tell these apart
     * since both produce an empty array; isWithinAvailableWindow() needs
     * this to decide which one it's looking at.
     */
    public function hasWindowConfiguration(): bool
    {
        $periods = $this->relationLoaded('periods') ? $this->periods : $this->periods()->get();

        return $periods->isNotEmpty() || ($this->day_start && $this->day_end);
    }

    /**
     * Whether [$from, $to] (both "H:i" or "H:i:s" strings) falls entirely
     * inside at least one of this slot's availability windows. Used by
     * reservation creation/update to enforce requirement #7 independently
     * of whatever the availability-preview endpoint says — a direct API
     * call must not be able to book a time the admin didn't actually make
     * available, even if the preview endpoint were somehow bypassed.
     *
     * If this slot has no window configuration at all (see
     * hasWindowConfiguration()), returns true — a venue with no
     * operating-window configuration falls back to whatever the older
     * morning/evening/full_start/end fields already allow, which
     * resolveTimes() continues to check on its own. But if configuration
     * DOES exist and simply produces zero available windows (every
     * custom period closed), that's a genuine closure and must reject —
     * conflating the two here was a real bug caught by execution testing
     * before this shipped: a slot with only an 'unavailable' period was
     * incorrectly allowing any request through at all, since an empty
     * windows array was being read as "unrestricted" either way.
     */
    public function isWithinAvailableWindow(string $from, string $to): bool
    {
        if (! $this->hasWindowConfiguration()) {
            return true;
        }

        $from = substr($from, 0, 5);
        $to = substr($to, 0, 5);

        $windows = $this->availabilityWindows();

        // Fixed, arbitrary reference day -- only used to give every time a
        // real datetime to compare against; the actual calendar date never
        // matters here, only the relative day-offsets between the window's
        // start/end and the request's start/end.
        $anchor = '2000-01-01';

        foreach ($windows as [$windowStart, $windowEnd]) {
            $windowStart = substr($windowStart, 0, 5);
            $windowEnd = substr($windowEnd, 0, 5);

            $windowStartDt = Carbon::parse("{$anchor} {$windowStart}");
            $windowEndDt = Carbon::parse("{$anchor} {$windowEnd}");
            // An end time that isn't strictly after its own start means
            // the window wraps past midnight (e.g. 17:00-05:00) -- push it
            // to the next calendar day so the comparison below is a
            // normal, correctly-ordered datetime range.
            if ($windowEndDt->lte($windowStartDt)) {
                $windowEndDt->addDay();
            }

            // Try the request anchored on the window's own start day first
            // (covers a request entirely within the "evening part", or one
            // that spans the midnight boundary itself), then retry one day
            // later (covers a request entirely within the "morning part"
            // of an overnight window -- genuinely the next calendar day
            // relative to the window's own start).
            foreach ([0, 1] as $dayOffset) {
                $fromDt = Carbon::parse("{$anchor} {$from}")->addDays($dayOffset);
                $toDt = Carbon::parse("{$anchor} {$to}")->addDays($dayOffset);
                if ($toDt->lte($fromDt)) {
                    $toDt->addDay();
                }

                if ($fromDt->gte($windowStartDt) && $toDt->lte($windowEndDt)) {
                    return true;
                }
            }
        }

        return false;
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
