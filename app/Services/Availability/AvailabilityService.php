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
        // -- widened one day earlier than $start, since an overnight
        // reservation dated the day before could spill into $start itself
        // (see reservationsForDate()).
        $reservations = UniteReservation::where('unite_id', $unite->id)
            ->whereBetween('reservation_date', [$start->copy()->subDay()->toDateString(), $end->toDateString()])
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
                $this->reservationsForDate($reservations, $date),
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
            ->whereBetween('reservation_date', [$start->copy()->subDay()->toDateString(), $end->toDateString()])
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
                $this->reservationsForDate($reservations, $date),
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

    /**
     * Reservations relevant to $date's availability: its own reservations,
     * plus a synthetic spillover entry for any reservation dated the day
     * BEFORE $date whose own to_time isn't strictly after its from_time --
     * the same signal used throughout this feature to mean "this
     * reservation wraps past midnight". Such a reservation's from_time
     * (e.g. 22:00 yesterday) already happened and has no bearing on
     * today; only the portion from the start of today through its
     * to_time (e.g. 02:00) is relevant here, so the spillover entry is
     * represented as 00:00-to_time, not the original range.
     *
     * A genuine clone of the reservation model (replicate()) rather than
     * a plain array, so every existing consumer (isPeriodBooked(),
     * splitPeriodAroundReservations(), buildStadiumPeriods()) keeps
     * working unmodified -- they only ever read ->from_time/->to_time,
     * which the clone provides identically to an actual same-day
     * reservation.
     */
    private function reservationsForDate(Collection $reservationsByDate, Carbon $date): Collection
    {
        $today = $reservationsByDate->get($date->format('Y-m-d'), collect());

        $yesterday = $reservationsByDate->get($date->copy()->subDay()->format('Y-m-d'), collect());

        $spillover = $yesterday
            ->filter(fn ($r) => $r->to_time <= $r->from_time)
            ->map(function ($r) {
                $clone = $r->replicate();
                $clone->from_time = strlen($r->from_time) > 5 ? '00:00:00' : '00:00';

                return $clone;
            });

        return $today->concat($spillover);
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
        $anchor = '2000-01-01';
        $format = strlen($periodStart) > 5 || strlen($periodEnd) > 5 ? 'H:i:s' : 'H:i';

        [$periodStartDt, $periodEndDt] = $this->normalizeRangeToDatetimes($anchor, $periodStart, $periodEnd);

        $overlapping = $dayReservations->filter(function ($r) use ($anchor, $periodStartDt, $periodEndDt, $bufferMinutes) {
            [$resStart, $resEnd] = $this->normalizeRangeToDatetimes($anchor, $r->from_time, $r->to_time);
            $from = $resStart->copy()->subMinutes($bufferMinutes);
            $to = $resEnd->copy()->addMinutes($bufferMinutes);

            return $from->lt($periodEndDt) && $to->gt($periodStartDt);
        });

        if ($overlapping->isEmpty()) {
            return [['start' => $periodStart, 'end' => $periodEnd, 'available' => true]];
        }

        $merged = [];
        foreach ($overlapping->sortBy('from_time') as $r) {
            [$resStart, $resEnd] = $this->normalizeRangeToDatetimes($anchor, $r->from_time, $r->to_time);
            $range = [
                'from' => $resStart->copy()->subMinutes($bufferMinutes),
                'to' => $resEnd->copy()->addMinutes($bufferMinutes),
            ];

            if ($merged && $range['from']->lte(end($merged)['to'])) {
                $merged[array_key_last($merged)]['to'] = $range['to']->gt(end($merged)['to']) ? $range['to'] : end($merged)['to'];
            } else {
                $merged[] = $range;
            }
        }

        $gaps = [];
        $cursor = $periodStartDt;

        foreach ($merged as $range) {
            if ($range['from']->gt($cursor)) {
                $gaps[] = ['from' => $cursor, 'to' => $range['from']->lt($periodEndDt) ? $range['from'] : $periodEndDt];
            }
            $cursor = $range['to']->gt($cursor) ? $range['to'] : $cursor;
        }

        if ($cursor->lt($periodEndDt)) {
            $gaps[] = ['from' => $cursor, 'to' => $periodEndDt];
        }

        $bookable = [];
        foreach ($gaps as $gap) {
            $durationMinutes = $gap['from']->diffInMinutes($gap['to']);
            if ($durationMinutes >= $minBookingMinutes) {
                $bookable[] = ['start' => $gap['from']->format($format), 'end' => $gap['to']->format($format), 'available' => true];
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

        $anchor = '2000-01-01';
        $format = strlen($windowStart) > 5 || strlen($windowEnd) > 5 ? 'H:i:s' : 'H:i';
        [$windowStartDt, $windowEndDt] = $this->normalizeRangeToDatetimes($anchor, $windowStart, $windowEnd);

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
            ->map(function ($r) use ($anchor, $bufferMinutes) {
                [$resStart, $resEnd] = $this->normalizeRangeToDatetimes($anchor, $r->from_time, $r->to_time);

                return [
                    'from' => $resStart->copy()->subMinutes($bufferMinutes),
                    'to' => $resEnd->copy()->addMinutes($bufferMinutes),
                ];
            })
            ->sortBy(fn ($r) => $r['from']->timestamp)
            ->values();

        $merged = [];
        foreach ($booked as $range) {
            if ($merged && $range['from']->lte(end($merged)['to'])) {
                $merged[array_key_last($merged)]['to'] = $range['to']->gt(end($merged)['to']) ? $range['to'] : end($merged)['to'];
            } else {
                $merged[] = $range;
            }
        }

        // Walk the operating window, emitting a free slot for every gap
        // before/between/after the merged booked ranges.
        $slots = [];
        $cursor = $windowStartDt;

        foreach ($merged as $range) {
            if ($range['from']->gt($cursor)) {
                $slots[] = $this->makeStadiumSlot($cursor->format($format), $range['from']->format($format), $dayHourPrice, $nightHourPrice, $isPastDate);
            }
            $cursor = $range['to']->gt($cursor) ? $range['to'] : $cursor;
        }

        if ($cursor->lt($windowEndDt)) {
            $slots[] = $this->makeStadiumSlot($cursor->format($format), $windowEndDt->format($format), $dayHourPrice, $nightHourPrice, $isPastDate);
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

        // morning / evening / stadium's hourly window: check real datetime
        // overlap (not plain time-string comparison, which cannot
        // correctly represent an overnight period or reservation),
        // symmetrically expanding each existing reservation's window by
        // the buffer on both sides first — matching
        // UniteReservation::scopeConflicting() exactly, so this preview
        // can't show a period as available that actual reservation
        // creation would then reject, or vice versa.
        $anchor = '2000-01-01';
        [$periodStart, $periodEnd] = $this->normalizeRangeToDatetimes($anchor, $fromTime, $toTime);

        return $dayReservations->contains(function ($res) use ($anchor, $periodStart, $periodEnd, $bufferMinutes) {
            [$resStart, $resEnd] = $this->normalizeRangeToDatetimes($anchor, $res->from_time, $res->to_time);

            $effectiveFrom = $resStart->copy()->subMinutes($bufferMinutes);
            $effectiveTo = $resEnd->copy()->addMinutes($bufferMinutes);

            return $effectiveFrom->lt($periodEnd) && $effectiveTo->gt($periodStart);
        });
    }

    /**
     * Anchors a [from, to] time-only range to real Carbon datetimes on a
     * fixed reference day, bumping the end forward a day whenever it
     * isn't strictly after its own start -- the same "wraps past
     * midnight" signal used throughout the rest of the overnight-booking
     * support (see App\Support\OvernightRange, used identically for
     * UnitePrice/reservation duration; kept as a private method here
     * rather than routed through that same helper since this one needs
     * to build both ends from bare time strings in one step, and is used
     * only by isPeriodBooked() above).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function normalizeRangeToDatetimes(string $anchor, string $from, string $to): array
    {
        $from = substr($from, 0, 5);
        $to = substr($to, 0, 5);

        $fromDt = Carbon::parse("{$anchor} {$from}");
        $toDt = Carbon::parse("{$anchor} {$to}");

        if ($toDt->lte($fromDt)) {
            $toDt->addDay();
        }

        return [$fromDt, $toDt];
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
