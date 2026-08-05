<?php

namespace App\Services\Availability;

use App\Models\Unite;
use App\Models\UniteReservation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    // -------------------------------------------------------------------------
    // Entry point — build a full month calendar for a unite
    // -------------------------------------------------------------------------

    /**
     * @return array{
     *   unite_id: int,
     *   unite_name: string,
     *   unite_type: string,
     *   year: int,
     *   month: int,
     *   dates: array
     * }
     */
    public function monthCalendar(Unite $unite, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        // Load everything we need in 3 queries — no N+1 inside the loop
        $slots = $unite->slots()->get()->keyBy('day_of_week');

        $prices = $unite->prices()->get()->keyBy('day');

        $activeOffers = $unite->offers()
            ->where('status', 'active')
            ->where('start', '<=', $end->toDateString())
            ->where('end', '>=', $start->toDateString())
            ->get();

        // Existing reservations for the whole month (pending + confirmed only)
        $reservations = UniteReservation::where('unite_id', $unite->id)
            ->whereBetween('reservation_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy(fn ($r) => $r->reservation_date->format('Y-m-d'));

        $dates = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dates[] = $this->buildDateEntry(
                $unite,
                $date,
                $slots,
                $prices,
                $activeOffers,
                $reservations->get($date->format('Y-m-d'), collect())
            );
        }

        return [
            'unite_id' => $unite->id,
            'unite_name' => $unite->name,
            'unite_type' => $unite->type,
            'year' => $year,
            'month' => $month,
            'dates' => $dates,
        ];
    }

    // -------------------------------------------------------------------------
    // Build one date entry
    // -------------------------------------------------------------------------

    private function buildDateEntry(
        Unite $unite,
        Carbon $date,
        Collection $slots,
        Collection $prices,
        Collection $activeOffers,
        Collection $dayReservations
    ): array {
        $dayKey = strtolower($date->englishDayOfWeek);   // 'monday'
        $priceKey = $this->toPriceDay($dayKey);            // 'thursday' | 'friday' | 'saturday' | 'week_day'
        $isPast = $date->isPast() && ! $date->isToday();

        $slot = $slots->get($dayKey);
        $price = $prices->get($priceKey);

        // No slot config means this day is not bookable
        if (! $slot || $slot->status !== 'available') {
            return [
                'date' => $date->toDateString(),
                'day_of_week' => $dayKey,
                'availability' => 'unavailable',
                'display_status' => $this->mapDisplayStatus('unavailable'),
                'reason' => $slot ? 'day_closed' : 'no_slot_config',
                'is_past' => $isPast,
                'periods' => [],
            ];
        }

        // Find the best offer for this date
        $offer = $activeOffers
            ->filter(fn ($o) => $date->between(Carbon::parse($o->start), Carbon::parse($o->end))
            )
            ->sortByDesc('id')
            ->first();

        $periods = $this->buildPeriods($unite, $slot, $price, $offer, $dayReservations, $date);

        $availability = $this->summariseAvailability($periods, $isPast);

        return [
            'date' => $date->toDateString(),
            'day_of_week' => $dayKey,
            'availability' => $availability,
            'display_status' => $this->mapDisplayStatus($availability),
            'is_past' => $isPast,
            'periods' => $periods,
        ];
    }

    // -------------------------------------------------------------------------
    // Build available periods for a date (stadium = one slot, others = 3)
    // -------------------------------------------------------------------------

    private function buildPeriods(
        Unite $unite,
        $slot,
        $price,
        $offer,
        Collection $dayReservations,
        Carbon $date
    ): array {
        if ($unite->type === 'stadium') {
            return [$this->buildStadiumPeriod($slot, $price, $offer, $dayReservations, $date)];
        }

        $periods = [];

        foreach (['morning', 'evening', 'full_day'] as $period) {
            $start = $slot->{$period === 'full_day' ? 'full_start' : "{$period}_start"};
            $end = $slot->{$period === 'full_day' ? 'full_end' : "{$period}_end"};

            if (! $start || ! $end) {
                continue;
            }

            $priceVal = $this->getPriceForPeriod($period, $price, $offer, $unite->type);
            $booked = $this->isPeriodBooked($period, $start, $end, $dayReservations);

            $periods[] = [
                'period_type' => $period,
                'from_time' => $start,
                'to_time' => $end,
                'price' => $priceVal,
                'availability' => $booked ? 'booked' : ($date->isPast() && ! $date->isToday() ? 'past' : 'available'),
            ];
        }

        return $periods;
    }

    private function buildStadiumPeriod($slot, $price, $offer, Collection $dayReservations, Carbon $date): array
    {
        $start = $slot->full_start;
        $end = $slot->full_end;
        $priceVal = $offer
            ? (float) ($offer->full_day_price ?? 0)
            : (float) ($price?->price ?? 0);

        // For stadiums, check for any overlapping reservations across the full window
        $booked = $dayReservations->isNotEmpty();

        return [
            'period_type' => 'full_day',
            'from_time' => $start,
            'to_time' => $end,
            'price' => $priceVal,
            'availability' => $booked ? 'booked' : ($date->isPast() && ! $date->isToday() ? 'past' : 'available'),
        ];
    }

    // -------------------------------------------------------------------------
    // Price resolution — offer takes precedence over base price
    // -------------------------------------------------------------------------

    private function getPriceForPeriod(string $period, $price, $offer, string $type): float
    {
        if ($offer) {
            return (float) match ($period) {
                'morning' => $offer->morning_price ?? 0,
                'evening' => $offer->evening_price ?? 0,
                'full_day' => $offer->full_day_price ?? 0,
                default => 0,
            };
        }

        if (! $price) {
            return 0;
        }

        return (float) match ($period) {
            'morning' => $price->morning_price ?? 0,
            'evening' => $price->evening_price ?? 0,
            'full_day' => $price->full_price ?? 0,
            default => 0,
        };
    }

    // -------------------------------------------------------------------------
    // Conflict detection — checks if a period overlaps any existing reservation
    // -------------------------------------------------------------------------

    private function isPeriodBooked(
        string $period,
        string $fromTime,
        string $toTime,
        Collection $dayReservations
    ): bool {
        if ($dayReservations->isEmpty()) {
            return false;
        }

        // full_day is booked if any reservation exists
        if ($period === 'full_day') {
            return $dayReservations->isNotEmpty();
        }

        // morning / evening: check time overlap
        return $dayReservations->contains(function ($res) use ($fromTime, $toTime) {
            return $res->from_time < $toTime && $res->to_time > $fromTime;
        });
    }

    /**
     * Consolidates the 5 detailed availability states down to exactly the
     * 3 states shown in the reference calendar design's legend:
     *   available     → متاح للحجز (green)  — available or partially_available
     *   unavailable   → غير متاح للحجز (red) — fully booked, nothing left to book
     *   holiday       → عطلة (yellow)        — day is closed / no slots configured
     *   past          → (greyed out, not part of the 3-color legend — past
     *                     dates aren't selectable, so the mobile app can
     *                     render these as a muted dot regardless of color)
     */
    private function mapDisplayStatus(string $availability): string
    {
        return match ($availability) {
            'available', 'partially_available' => 'available',
            'fully_booked' => 'unavailable',
            'unavailable' => 'holiday',
            'past' => 'past',
            default => 'holiday',
        };
    }

    // -------------------------------------------------------------------------
    // Summary: available / partial / fully_booked / unavailable / past
    // -------------------------------------------------------------------------

    private function summariseAvailability(array $periods, bool $isPast): string
    {
        if (empty($periods)) {
            return 'unavailable';
        }

        if ($isPast) {
            return 'past';
        }

        $available = collect($periods)->where('availability', 'available')->count();
        $total = count($periods);

        if ($available === 0) {
            return 'fully_booked';
        }

        if ($available < $total) {
            return 'partially_available';
        }

        return 'available';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Map Carbon day name to the price table's day enum */
    private function toPriceDay(string $dayName): string
    {
        return match ($dayName) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };
    }
}
