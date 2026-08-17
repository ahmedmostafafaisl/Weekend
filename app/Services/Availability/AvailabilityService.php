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

    public function monthCalendar(Unite $unite, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        // Load everything we need in 3 queries — no N+1 inside the loop
        $slots = $unite->slots()->with('periods')->get()->keyBy('day_of_week');

        $prices = $unite->prices()->get()->keyBy('day');

        $activeOffers = $unite->offers()
            ->where('status', 'active')
            ->where('start', '<=', $end->toDateString())
            ->where('end', '>=', $start->toDateString())
            ->get();

        $bookingPackages = $unite->package_booking_enabled
            ? $unite->bookingPackages()->where('status', 'active')->get()
            : collect();

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
                $reservations->get($date->format('Y-m-d'), collect()),
                $bookingPackages
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

    /**
     * Availability across an arbitrary date range (not necessarily aligned
     * to a calendar month) — e.g. start_date=2026-08-09&end_date=2026-08-12.
     * Reuses the exact same per-day data and buildDateEntry() logic as
     * monthCalendar(), so a date's status here is always identical to
     * what the month view would show for that same date.
     *
     * Also answers the specific question a multi-day full_day booking
     * needs: is EVERY day in this range available for full_day, with
     * nothing already booked, and (if applicable) is the venue even a
     * full_day-supporting type at all. full_day_range_available is only
     * ever true for venue types that support full_day at all — for a
     * stadium (hourly-only), it's false for every range, matching
     * Unite::allowedPeriodTypes() exactly rather than duplicating that
     * matrix here.
     *
     * @return array{
     *   unite_id: int,
     *   unite_name: string,
     *   unite_type: string,
     *   start_date: string,
     *   end_date: string,
     *   supports_full_day: bool,
     *   full_day_range_available: bool,
     *   unavailable_dates: array,
     *   dates: array
     * }
     */
    public function rangeAvailability(Unite $unite, Carbon $start, Carbon $end): array
    {
        $slots = $unite->slots()->with('periods')->get()->keyBy('day_of_week');
        $prices = $unite->prices()->get()->keyBy('day');

        $activeOffers = $unite->offers()
            ->where('status', 'active')
            ->where('start', '<=', $end->toDateString())
            ->where('end', '>=', $start->toDateString())
            ->get();

        $bookingPackages = $unite->package_booking_enabled
            ? $unite->bookingPackages()->where('status', 'active')->get()
            : collect();

        $reservations = UniteReservation::where('unite_id', $unite->id)
            ->whereBetween('reservation_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy(fn ($r) => $r->reservation_date->format('Y-m-d'));

        $supportsFullDay = in_array('full_day', $unite->allowedPeriodTypes(), true);

        $dates = [];
        $unavailableDates = [];
        $totalPrice = $supportsFullDay ? 0.0 : null;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $entry = $this->buildDateEntry(
                $unite,
                $date,
                $slots,
                $prices,
                $activeOffers,
                $reservations->get($date->format('Y-m-d'), collect()),
                $bookingPackages
            );
            $dates[] = $entry;

            if ($supportsFullDay) {
                $fullDayPeriod = collect($entry['periods'])->firstWhere('period_type', 'full_day');
                $dayIsAvailable = $fullDayPeriod && $fullDayPeriod['availability'] === 'available';
                if (! $dayIsAvailable) {
                    $unavailableDates[] = $entry['date'];
                }

                // Summed straight from the same per-day price already
                // shown in periods[] above — total_price can never
                // disagree with what the client sees broken out per day
                // in this same response, since it's not a separate
                // calculation, just an accumulation of the one already
                // done. Included regardless of that specific day's
                // availability, so a partially-blocked range still shows
                // what the full stay would have cost.
                if ($fullDayPeriod) {
                    $totalPrice += (float) $fullDayPeriod['price'];
                }
            } else {
                $unavailableDates[] = $entry['date'];
            }
        }

        return [
            'unite_id' => $unite->id,
            'unite_name' => $unite->name,
            'unite_type' => $unite->type,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'supports_full_day' => $supportsFullDay,
            'full_day_range_available' => $supportsFullDay && count($unavailableDates) === 0,
            'unavailable_dates' => $unavailableDates,
            'total_price' => $totalPrice,
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
        Collection $dayReservations,
        Collection $bookingPackages
    ): array {
        $dayKey = strtolower($date->englishDayOfWeek);   // 'monday'
        $priceKey = $this->toPriceDay($dayKey);            // 'thursday' | 'friday' | 'saturday' | 'week_day'
        $isPast = $date->isPast() && ! $date->isToday();

        $slot = $slots->get($dayKey);
        $price = $prices->get($priceKey);

        // Packages are a genuinely separate booking mechanism from the
        // regular hourly/period system — they have their own day/day_from
        // fields, entirely independent of unite_slots. A package can
        // still be bookable on a day the venue's regular slot is closed,
        // so this is computed unconditionally, before the "no slot
        // config" early return below, rather than only when the slot
        // itself is open.
        $availablePackages = $this->buildAvailablePackages($unite, $bookingPackages, $date, $dayKey);

        // No slot config means this day is not bookable via the regular
        // hourly/period system — packages (just computed above) are
        // unaffected by this.
        if (! $slot || $slot->status !== 'available') {
            return [
                'date' => $date->toDateString(),
                'day_of_week' => $dayKey,
                'availability' => 'unavailable',
                'display_status' => $this->mapDisplayStatus('unavailable'),
                'reason' => $slot ? 'day_closed' : 'no_slot_config',
                'is_past' => $isPast,
                'working_hours' => $this->buildWorkingHours($slot),
                'custom_periods' => $this->buildCustomPeriods($slot, $dayReservations, $price),
                'periods' => [],
                'available_packages' => $availablePackages,
                'min_booking_minutes' => $this->buildMinBookingMinutes($unite, $price),
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
            'working_hours' => $this->buildWorkingHours($slot),
            'custom_periods' => $this->buildCustomPeriods($slot, $dayReservations, $price),
            'periods' => $periods,
            'available_packages' => $availablePackages,
            'min_booking_minutes' => $this->buildMinBookingMinutes($unite, $price),
        ];
    }

    /**
     * Booking-block size for stadiums — day_hour_price/night_hour_price
     * are priced per block of this many minutes, and booking duration
     * must be an exact multiple of it (see UnitePrice::
     * calculateHourlyPrice() and UniteReservationRepository's duration
     * validation). Scoped to stadium only, per request — other venue
     * types return null here even if they happen to have hourly_enabled
     * set, since this field's meaning (the pricing/booking unit for an
     * hourly-only venue) doesn't carry the same weight for a type where
     * hourly is an optional add-on rather than the entire booking model.
     * Also null whenever there's no price row for this date, or hourly
     * pricing isn't actually enabled on it — the field would be
     * meaningless without a configured hourly rate to attach it to.
     */
    private function buildMinBookingMinutes(Unite $unite, $price): ?int
    {
        if ($unite->type !== 'stadium' || ! $price || ! $price->hourly_enabled) {
            return null;
        }

        return (int) ($price->min_booking_minutes ?? 60);
    }

    /**
     * The daily operating window (requirement 1) for this slot, or null
     * if not configured — a venue that hasn't opted into day_start/
     * day_end simply omits this rather than showing a misleading window.
     */
    private function buildWorkingHours($slot): ?array
    {
        if (! $slot || ! $slot->day_start || ! $slot->day_end) {
            return null;
        }

        return [
            'start' => $slot->day_start,
            'end' => $slot->day_end,
        ];
    }

    /**
     * Custom availability periods (requirement 3) for this slot, each
     * annotated with its own availability against THIS SPECIFIC DATE's
     * reservations — reuses isPeriodBooked() so a custom period's
     * 'available' flag here can never disagree with whether
     * isWithinAvailableWindow() would actually accept a booking request
     * for that same window, buffer included.
     */
    private function buildCustomPeriods($slot, ?Collection $dayReservations = null, $price = null): array
    {
        if (! $slot) {
            return [];
        }

        $periods = $slot->relationLoaded('periods') ? $slot->periods : $slot->periods()->get();
        $bufferMinutes = (int) ($slot->buffer_minutes ?? 0);
        $minBookingMinutes = (int) ($price?->min_booking_minutes ?? 60);

        $result = [];

        foreach ($periods as $period) {
            if ($period->status !== 'available') {
                $result[] = ['start' => $period->start_time, 'end' => $period->end_time, 'available' => false];

                continue;
            }

            if ($dayReservations === null) {
                $result[] = ['start' => $period->start_time, 'end' => $period->end_time, 'available' => true];

                continue;
            }

            foreach ($this->splitPeriodAroundReservations(
                $period->start_time,
                $period->end_time,
                $dayReservations,
                $bufferMinutes,
                $minBookingMinutes
            ) as $gap) {
                $result[] = $gap;
            }
        }

        return $result;
    }

    /**
     * Splits [$periodStart, $periodEnd] around any reservation overlapping
     * it, expanded by buffer on both sides — the exact same merge-booked-
     * ranges-then-emit-free-gaps approach already used in
     * buildStadiumPeriods() for the main hourly periods array, applied
     * here to a single custom period's own bounds instead of the whole
     * day's operating window. Any resulting gap shorter than
     * $minBookingMinutes is dropped, since it wouldn't actually be
     * bookable regardless of showing as "available". If nothing is left
     * at all (booked solid, or the sole gap was too short), and the
     * period had at least one genuine overlap, returns the original
     * range marked unavailable rather than silently disappearing —
     * matching how a fully-booked stadium window still reports as
     * 'booked' rather than vanishing from the response entirely.
     *
     * @return array<int, array{start: string, end: string, available: bool}>
     */
    private function splitPeriodAroundReservations(
        string $periodStart,
        string $periodEnd,
        Collection $dayReservations,
        int $bufferMinutes,
        int $minBookingMinutes
    ): array {
        $overlapping = $dayReservations->filter(function ($r) use ($periodStart, $periodEnd, $bufferMinutes) {
            $from = $this->subtractMinutes($r->from_time, $bufferMinutes);
            $to = $this->addMinutes($r->to_time, $bufferMinutes);

            return $from < $periodEnd && $to > $periodStart;
        });

        if ($overlapping->isEmpty()) {
            return [['start' => $periodStart, 'end' => $periodEnd, 'available' => true]];
        }

        $merged = [];
        foreach ($overlapping->sortBy('from_time') as $r) {
            $range = [
                'from' => $this->subtractMinutes($r->from_time, $bufferMinutes),
                'to' => $this->addMinutes($r->to_time, $bufferMinutes),
            ];

            if ($merged && $range['from'] <= end($merged)['to']) {
                $merged[array_key_last($merged)]['to'] = max(end($merged)['to'], $range['to']);
            } else {
                $merged[] = $range;
            }
        }

        $gaps = [];
        $cursor = $periodStart;

        foreach ($merged as $range) {
            if ($range['from'] > $cursor) {
                $gaps[] = ['from' => $cursor, 'to' => min($range['from'], $periodEnd)];
            }
            $cursor = max($cursor, $range['to']);
        }

        if ($cursor < $periodEnd) {
            $gaps[] = ['from' => $cursor, 'to' => $periodEnd];
        }

        $bookable = [];
        foreach ($gaps as $gap) {
            $durationMinutes = Carbon::parse($gap['to'])->diffInMinutes(Carbon::parse($gap['from']));
            if ($durationMinutes >= $minBookingMinutes) {
                $bookable[] = ['start' => $gap['from'], 'end' => $gap['to'], 'available' => true];
            }
        }

        if (empty($bookable)) {
            return [['start' => $periodStart, 'end' => $periodEnd, 'available' => false]];
        }

        return $bookable;
    }

    // -------------------------------------------------------------------------
    // Packages — a separate booking mechanism from periods/hourly, checked
    // against the shared conflicting() scope so this always reflects the
    // exact same rule the reservation-creation flow itself enforces
    // -------------------------------------------------------------------------

    /**
     * For a given date, returns every active package that actually applies
     * to that date's day-type, each annotated with whether IT specifically
     * (not the venue as a whole) is available for that date.
     *
     * Key behavior confirmed explicitly: a package tied to a day-type
     * (e.g. 'friday' for 'hours' mode, or day_from='friday' for 'days'
     * mode) stays available on every OTHER Friday even once one specific
     * Friday gets booked through it — only that exact date (or, for a
     * 'days' package, that exact date range) becomes unavailable, not the
     * day-type itself.
     */
    private function buildAvailablePackages(Unite $unite, Collection $bookingPackages, Carbon $date, string $dayKey): array
    {
        $isPastDate = $date->isPast() && ! $date->isToday();
        $result = [];

        foreach ($bookingPackages as $package) {
            if (! $package->appliesToDay($dayKey)) {
                continue;
            }

            if ($package->booking_type === 'days') {
                $endDate = $date->copy()->addDays(max(1, $package->duration_days ?? 1) - 1)->format('Y-m-d');
                $conflict = UniteReservation::conflicting($unite->id, $date->toDateString(), $endDate)->exists();

                $result[] = [
                    'id' => $package->id,
                    'name' => $package->name,
                    'booking_type' => 'days',
                    'period_type' => 'package',
                    'day_from' => $package->day_from,
                    'day_to' => $package->day_to,
                    'duration_days' => $package->duration_days,
                    'end_date' => $endDate,
                    'price' => (float) $package->price,
                    'services' => $package->services ?? [],
                    'availability' => $isPastDate ? 'past' : ($conflict ? 'booked' : 'available'),
                ];
            } else {
                $conflict = UniteReservation::conflicting(
                    $unite->id,
                    $date->toDateString(),
                    $date->toDateString(),
                    $package->start_time,
                    $package->end_time
                )->exists();

                $result[] = [
                    'id' => $package->id,
                    'name' => $package->name,
                    'booking_type' => 'hours',
                    'period_type' => 'package',
                    'day' => $package->day,
                    'start_time' => $package->start_time,
                    'end_time' => $package->end_time,
                    'price' => (float) $package->price,
                    'services' => $package->services ?? [],
                    'availability' => $isPastDate ? 'past' : ($conflict ? 'booked' : 'available'),
                ];
            }
        }

        return $result;
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
            return $this->buildStadiumPeriods($slot, $price, $offer, $dayReservations, $date);
        }

        $periods = [];

        foreach (['morning', 'evening', 'full_day'] as $period) {
            $start = $slot->{$period === 'full_day' ? 'full_start' : "{$period}_start"};
            $end = $slot->{$period === 'full_day' ? 'full_end' : "{$period}_end"};

            if (! $start || ! $end) {
                continue;
            }

            $priceVal = $this->getPriceForPeriod($period, $price, $offer, $unite->type);
            $booked = $this->isPeriodBooked($period, $start, $end, $dayReservations, (int) ($slot->buffer_minutes ?? 0));

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

    /**
     * BUG FIX: stadiums are hourly-only — the operating window
     * (full_start-full_end) is not itself one bookable "period" the way
     * morning/evening/full_day are for other venue types. This used to
     * mark the WHOLE day 'booked' the instant any reservation existed,
     * even a 1-hour one out of a 17-hour window. Now computes the actual
     * free gaps around existing reservations via interval subtraction, so
     * a stadium booked 14:00-16:00 out of a 06:00-23:00 window correctly
     * returns two separate available slots (06:00-14:00 and 16:00-23:00)
     * instead of the entire day reading as unavailable.
     *
     * Each slot also carries day_hour_price/night_hour_price directly (not
     * a single flat 'price' the way the old single-period version did),
     * since that's the actual pricing mechanism for this venue type —
     * UnitePrice::calculateHourlyPrice() splits any given booking within a
     * slot proportionally between the two based on the day/night boundary.
     *
     * @return array<int, array>
     */
    private function buildStadiumPeriods($slot, $price, $offer, Collection $dayReservations, Carbon $date): array
    {
        $windowStart = $slot->full_start;
        $windowEnd = $slot->full_end;

        if (! $windowStart || ! $windowEnd) {
            return [];
        }

        $dayHourPrice = $offer?->day_hour_price ?? $price?->day_hour_price ?? 0;
        $nightHourPrice = $offer?->night_hour_price ?? $price?->night_hour_price ?? 0;
        $isPastDate = $date->isPast() && ! $date->isToday();

        // Merge overlapping/adjacent booked ranges first (ensureNoConflict
        // should prevent genuine overlaps from ever being created, but
        // merging defensively means this still produces a clean gap list
        // even against unexpected data). Each range is expanded by the
        // slot's buffer first — symmetrically, matching
        // UniteReservation::scopeConflicting() — so a computed "free" gap
        // here can't be narrower than what reservation creation would
        // actually reject once buffer is accounted for.
        $bufferMinutes = (int) ($slot->buffer_minutes ?? 0);
        $booked = $dayReservations
            ->map(fn ($r) => [
                'from' => $this->subtractMinutes($r->from_time, $bufferMinutes),
                'to' => $this->addMinutes($r->to_time, $bufferMinutes),
            ])
            ->sortBy('from')
            ->values();

        $merged = [];
        foreach ($booked as $range) {
            if ($merged && $range['from'] <= end($merged)['to']) {
                $merged[array_key_last($merged)]['to'] = max(end($merged)['to'], $range['to']);
            } else {
                $merged[] = $range;
            }
        }

        // Walk the operating window, emitting a free slot for every gap
        // before/between/after the merged booked ranges.
        $slots = [];
        $cursor = $windowStart;

        foreach ($merged as $range) {
            if ($range['from'] > $cursor) {
                $slots[] = $this->makeStadiumSlot($cursor, $range['from'], $dayHourPrice, $nightHourPrice, $isPastDate);
            }
            $cursor = max($cursor, $range['to']);
        }

        if ($cursor < $windowEnd) {
            $slots[] = $this->makeStadiumSlot($cursor, $windowEnd, $dayHourPrice, $nightHourPrice, $isPastDate);
        }

        // The whole window was booked solid, no gaps at all — return it as
        // a single, explicitly booked entry so the day still reads as
        // fully_booked rather than silently showing zero periods (which
        // summariseAvailability would otherwise read as 'unavailable',
        // the wrong status for "booked solid" vs "closed/no slot config").
        if (empty($slots) && ! empty($merged)) {
            return [[
                'period_type' => 'hourly',
                'from_time' => $windowStart,
                'to_time' => $windowEnd,
                'day_hour_price' => (float) $dayHourPrice,
                'night_hour_price' => (float) $nightHourPrice,
                'availability' => 'booked',
            ]];
        }

        return $slots;
    }

    private function makeStadiumSlot(string $from, string $to, $dayHourPrice, $nightHourPrice, bool $isPastDate): array
    {
        return [
            'period_type' => 'hourly',
            'from_time' => $from,
            'to_time' => $to,
            'day_hour_price' => (float) $dayHourPrice,
            'night_hour_price' => (float) $nightHourPrice,
            'availability' => $isPastDate ? 'past' : 'available',
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
        Collection $dayReservations,
        int $bufferMinutes = 0
    ): bool {
        if ($dayReservations->isEmpty()) {
            return false;
        }

        // full_day is booked if any reservation exists
        if ($period === 'full_day') {
            return $dayReservations->isNotEmpty();
        }

        // morning / evening: check time overlap, symmetrically expanding
        // each existing reservation's window by the buffer on both sides
        // first — matching UniteReservation::scopeConflicting() exactly,
        // so this preview can't show a period as available that actual
        // reservation creation would then reject.
        return $dayReservations->contains(function ($res) use ($fromTime, $toTime, $bufferMinutes) {
            $effectiveFrom = $this->subtractMinutes($res->from_time, $bufferMinutes);
            $effectiveTo = $this->addMinutes($res->to_time, $bufferMinutes);

            return $effectiveFrom < $toTime && $effectiveTo > $fromTime;
        });
    }

    /**
     * Plain string time arithmetic ("H:i" or "H:i:s" in, same format out)
     * for the buffer expansion above — avoids a Carbon round-trip per
     * reservation per period just to add/subtract a handful of minutes.
     *
     * KNOWN LIMITATION (pre-existing, not introduced by buffer): from_time/
     * to_time are time-only columns with no date component, so a result
     * that crosses midnight wraps around (e.g. 23:50 + 15min -> "00:05",
     * not "next day 00:05") rather than genuinely rolling over. This
     * mirrors an existing constraint already present throughout the
     * reservation system — a reservation itself can't span midnight in
     * this schema today, buffer just inherits the same limitation rather
     * than introducing a new one.
     */
    private function addMinutes(string $time, int $minutes): string
    {
        return $minutes === 0 ? $time : Carbon::parse($time)->addMinutes($minutes)->format(strlen($time) > 5 ? 'H:i:s' : 'H:i');
    }

    private function subtractMinutes(string $time, int $minutes): string
    {
        return $minutes === 0 ? $time : Carbon::parse($time)->subMinutes($minutes)->format(strlen($time) > 5 ? 'H:i:s' : 'H:i');
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
