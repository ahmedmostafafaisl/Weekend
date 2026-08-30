<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UniteSlot;
use App\Repositories\Interfaces\UniteSlotInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UniteSlotRepository implements UniteSlotInterface
{
    public function allByUnite(Unite $unite): Collection
    {
        return $unite->slots()->with('periods')->latest()->get();
    }

    public function findByUnite(Unite $unite, int $slotId): ?UniteSlot
    {
        return $unite->slots()->with('periods')->where('id', $slotId)->first();
    }

    /**
     * Every day except Friday -- the 6 individual days that 'week_day'
     * expands into. Deliberately does NOT match UnitePrice's own day
     * grouping (thursday/friday/saturday/week_day=sun-wed) -- this is
     * scoped to unite_slots.day_of_week specifically, per request.
     */
    private const WEEK_DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'saturday'];

    public function createForUnite(Unite $unite, array $data): Collection
    {
        $periods = $data['periods'] ?? null;
        unset($data['periods']);

        if ($data['day_of_week'] === 'week_day') {
            return $this->createWeekDayGroup($unite, $data, $periods);
        }

        $exists = $unite->slots()
            ->where('day_of_week', $data['day_of_week'])
            ->exists();

        if ($exists) {
            abort(422, __('lang.slot_already_exists_weekday'));
        }

        $slot = $unite->slots()->create($data);

        if ($periods !== null) {
            $this->storePeriodsForSlot($slot, $periods);
        }

        return new Collection([$slot]);
    }

    /**
     * Creates all 4 sunday-wednesday rows with the same submitted time/
     * status values. Checked upfront against all 4 before creating any of
     * them — a partial conflict (e.g. 'wednesday' already configured
     * individually) aborts the whole request rather than silently
     * creating only the 3 free days, since that would leave the group
     * inconsistent with no indication anything was skipped.
     */
    private function createWeekDayGroup(Unite $unite, array $data, ?array $periods = null): Collection
    {
        $conflicting = $unite->slots()
            ->whereIn('day_of_week', self::WEEK_DAYS)
            ->pluck('day_of_week');

        if ($conflicting->isNotEmpty()) {
            abort(422, str_replace(
                ':days',
                $conflicting->implode(', '),
                __('lang.slot_already_exists_for_week_days')
            ));
        }

        return DB::transaction(function () use ($unite, $data, $periods) {
            $created = new Collection;

            foreach (self::WEEK_DAYS as $day) {
                $slot = $unite->slots()->create(array_merge($data, ['day_of_week' => $day]));

                if ($periods !== null) {
                    $this->storePeriodsForSlot($slot, $periods);
                }

                $created->push($slot);
            }

            return $created;
        });
    }

    /**
     * Replaces this slot's custom availability periods entirely with the
     * submitted list — delete-then-create, matching the same pattern
     * already established for UniteRepository::storeCouncils(). Called
     * unconditionally whenever 'periods' was actually present in the
     * request (including an explicitly empty array, which correctly
     * clears any existing periods); a request that doesn't mention
     * 'periods' at all leaves whatever's already configured untouched.
     */
    private function storePeriodsForSlot(UniteSlot $slot, array $periods): void
    {
        $slot->periods()->delete();

        foreach ($periods as $period) {
            if (empty($period['start_time']) || empty($period['end_time'])) {
                continue;
            }

            $slot->periods()->create([
                'start_time' => $period['start_time'],
                'end_time' => $period['end_time'],
                'status' => $period['status'] ?? 'available',
            ]);
        }

        $slot->load('periods');
    }

    public function updateForUnite(Unite $unite, int $slotId, array $data): Collection
    {
        $slot = $unite->slots()->where('id', $slotId)->firstOrFail();

        $hasPeriods = array_key_exists('periods', $data);
        $periods = $data['periods'] ?? null;
        unset($data['periods']);

        if (isset($data['day_of_week']) && $data['day_of_week'] === 'week_day') {
            return $this->updateWeekDayGroup($unite, $slot, $data, $hasPeriods ? $periods : null);
        }

        if (isset($data['day_of_week'])) {
            $exists = $unite->slots()
                ->where('day_of_week', $data['day_of_week'])
                ->where('id', '!=', $slotId)
                ->exists();

            if ($exists) {
                abort(422, __('lang.slot_already_exists_weekday'));
            }
        }

        $slot->update($data);

        if ($hasPeriods) {
            $this->storePeriodsForSlot($slot, $periods ?? []);
        }

        return new Collection([$slot->fresh('periods')]);
    }

    /**
     * Upserts all 4 sunday-wednesday rows with the submitted time/status
     * values — updating whichever of the 4 already has a row for this
     * unite, creating whichever doesn't. If the slot actually being
     * edited isn't one of those 4 (e.g. it was 'thursday' and is being
     * converted into the week_day group), it's deleted once the 4
     * week_day rows are in place, since the intent of submitting
     * 'week_day' here is to move this configuration into that group, not
     * leave the old single day behind as a separate, orphaned row.
     */
    private function updateWeekDayGroup(Unite $unite, UniteSlot $slot, array $data, ?array $periods = null): Collection
    {
        $timeAndStatusData = array_diff_key($data, ['day_of_week' => null]);

        return DB::transaction(function () use ($unite, $slot, $timeAndStatusData, $periods) {
            $result = new Collection;

            foreach (self::WEEK_DAYS as $day) {
                $daySlot = $unite->slots()->updateOrCreate(
                    ['day_of_week' => $day],
                    $timeAndStatusData
                );

                if ($periods !== null) {
                    $this->storePeriodsForSlot($daySlot, $periods);
                } else {
                    $daySlot->load('periods');
                }

                $result->push($daySlot);
            }

            if (! in_array($slot->day_of_week, self::WEEK_DAYS, true)) {
                $slot->delete();
            }

            return $result;
        });
    }

    public function deleteForUnite(Unite $unite, int $slotId): bool
    {
        $slot = $unite->slots()->where('id', $slotId)->firstOrFail();

        return (bool) $slot->delete();
    }

    public function getAvailabilityAndPrices(Unite $unite, string $startDate, ?string $endDate = null): array
    {
        // Eager-load relationships so resolveDailyPrices doesn't do N+1 queries
        $unite->loadMissing(['prices', 'offers', 'slots']);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->startOfDay() : Carbon::parse($startDate)->startOfDay();

        if ($end->lt($start)) {
            abort(422, __('lang.end_date_must_be_after_start'));
        }

        $days = [];
        $totals = [
            'morning' => 0,
            'evening' => 0,
            'full_day' => 0,
        ];

        $fullDayBookingSummary = [
            'requested' => true,
            'all_days_available' => true,
            'available_days_count' => 0,
            'unavailable_days_count' => 0,
            'available_dates' => [],
            'unavailable_dates' => [],
            'total_full_day_price_for_range' => 0,
        ];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $formattedDate = $date->format('Y-m-d');
            $dayOfWeek = strtolower($date->englishDayOfWeek);

            $slot = $unite->slots()
                ->where('day_of_week', $dayOfWeek)
                ->first();

            $dailyPrices = $this->resolveDailyPrices($unite, $formattedDate);
            $availablePeriods = [];
            $fullDayAvailable = false;

            if ($slot && $slot->status === 'available') {
                $reservations = $unite->reservations()
                    ->whereDate('reservation_date', $formattedDate)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->get();

                if ($unite->type === 'stadium') {
                    $fullDayAvailable = ! $this->hasConflict(
                        $reservations,
                        $slot->full_start,
                        $slot->full_end
                    ) && ! is_null($dailyPrices['full_day']);

                    $availablePeriods[] = [
                        'type' => 'full_day',
                        'from_time' => $slot->full_start,
                        'to_time' => $slot->full_end,
                        'is_available' => $fullDayAvailable,
                        'price' => $dailyPrices['full_day'],
                    ];
                } else {
                    $definitions = [
                        'morning' => [$slot->morning_start, $slot->morning_end, $dailyPrices['morning']],
                        'evening' => [$slot->evening_start, $slot->evening_end, $dailyPrices['evening']],
                        'full_day' => [$slot->full_start, $slot->full_end, $dailyPrices['full_day']],
                    ];

                    foreach ($definitions as $type => [$from, $to, $price]) {
                        if (! $from || ! $to) {
                            continue;
                        }

                        $isAvailable = ! $this->hasConflict($reservations, $from, $to) && ! is_null($price);

                        if ($type === 'full_day') {
                            $fullDayAvailable = $isAvailable;
                        }

                        $availablePeriods[] = [
                            'type' => $type,
                            'from_time' => $from,
                            'to_time' => $to,
                            'is_available' => $isAvailable,
                            'price' => $price,
                        ];
                    }
                }
            }

            if ($fullDayAvailable) {
                $fullDayBookingSummary['available_days_count']++;
                $fullDayBookingSummary['available_dates'][] = [
                    'date' => $formattedDate,
                    'price' => (float) ($dailyPrices['full_day'] ?? 0),
                ];
                $fullDayBookingSummary['total_full_day_price_for_range'] += (float) ($dailyPrices['full_day'] ?? 0);
            } else {
                $fullDayBookingSummary['all_days_available'] = false;
                $fullDayBookingSummary['unavailable_days_count']++;
                $fullDayBookingSummary['unavailable_dates'][] = $formattedDate;
            }

            $days[] = [
                'date' => $formattedDate,
                'day_of_week' => $dayOfWeek,
                'slot_found' => (bool) $slot,
                'slot_status' => $slot?->status,
                'price_source' => $dailyPrices['source'],
                'full_day_available' => $fullDayAvailable,
                'available_periods' => $availablePeriods,
                'prices' => [
                    'morning' => $dailyPrices['morning'],
                    'evening' => $dailyPrices['evening'],
                    'full_day' => $dailyPrices['full_day'],
                ],
            ];

            $totals['morning'] += (float) ($dailyPrices['morning'] ?? 0);
            $totals['evening'] += (float) ($dailyPrices['evening'] ?? 0);
            $totals['full_day'] += (float) ($dailyPrices['full_day'] ?? 0);
        }

        return [
            'unite_id' => $unite->id,
            'unite_type' => $unite->type,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days_count' => count($days),
            'days' => $days,
            'full_day_booking_summary' => $fullDayBookingSummary,
            'totals' => $totals,
        ];
    }

    protected function hasConflict($reservations, ?string $fromTime, ?string $toTime): bool
    {
        if (! $fromTime || ! $toTime) {
            return true;
        }

        foreach ($reservations as $reservation) {
            if (
                $reservation->from_time < $toTime &&
                $reservation->to_time > $fromTime
            ) {
                return true;
            }
        }

        return false;
    }

    protected function resolveDailyPrices(Unite $unite, string $date): array
    {
        $reservationDate = Carbon::parse($date);

        // Use already-loaded offers collection when available
        $offersCollection = $unite->relationLoaded('offers')
            ? $unite->offers
            : $unite->offers()->get();

        $activeOffer = $offersCollection
            ->where('status', 'active')
            ->filter(fn ($o) => $o->start <= $reservationDate->toDateString() && $o->end >= $reservationDate->toDateString())
            ->sortByDesc('id')
            ->first();

        if ($activeOffer) {
            if ($unite->type === 'stadium') {
                $offerPrice = $activeOffer->full_day_price;

                if (! is_null($offerPrice) && (float) $offerPrice > 0) {
                    return [
                        'source' => 'offer',
                        'morning' => null,
                        'evening' => null,
                        'full_day' => (float) $offerPrice,
                    ];
                }
            } else {
                $morning = $activeOffer->morning_price;
                $evening = $activeOffer->evening_price;
                $fullDay = $activeOffer->full_day_price;

                if (
                    (! is_null($morning) && (float) $morning > 0) ||
                    (! is_null($evening) && (float) $evening > 0) ||
                    (! is_null($fullDay) && (float) $fullDay > 0)
                ) {
                    return [
                        'source' => 'offer',
                        'morning' => ! is_null($morning) ? (float) $morning : null,
                        'evening' => ! is_null($evening) ? (float) $evening : null,
                        'full_day' => ! is_null($fullDay) ? (float) $fullDay : 0,
                    ];
                }
            }
        }

        $mappedDay = match (strtolower($reservationDate->englishDayOfWeek)) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        // Use already-loaded prices collection (avoids N+1)
        $pricesCollection = $unite->relationLoaded('prices')
            ? $unite->prices
            : $unite->prices()->get();

        // BUG FIX: this used to fall back to the 'week_day' row, or even
        // just the first price row of any day, when the exact day
        // category had no configured price at all — silently showing a
        // bookable price in this preview for a day that the actual
        // reservation-creation path (resolvePrice(), hardened earlier
        // this session) would correctly reject with a 422. A customer
        // could see "available, price: X" here and then be rejected when
        // actually trying to book. Now reports the same missing-config
        // condition this preview is supposed to reflect, rather than
        // masking it.
        $price = $pricesCollection->firstWhere('day', $mappedDay);

        if (! $price) {
            return [
                'source' => 'missing',
                'morning' => null,
                'evening' => null,
                'full_day' => null,
            ];
        }

        if ($unite->type === 'stadium') {
            if (is_null($price->price)) {
                return [
                    'source' => 'missing',
                    'morning' => null,
                    'evening' => null,
                    'full_day' => null,
                ];
            }

            return [
                'source' => 'default',
                'morning' => null,
                'evening' => null,
                'full_day' => (float) $price->price,
            ];
        }

        if (is_null($price->morning_price) || is_null($price->evening_price) || is_null($price->full_price)) {
            return [
                'source' => 'missing',
                'morning' => ! is_null($price->morning_price) ? (float) $price->morning_price : null,
                'evening' => ! is_null($price->evening_price) ? (float) $price->evening_price : null,
                'full_day' => ! is_null($price->full_price) ? (float) $price->full_price : null,
            ];
        }

        return [
            'source' => 'default',
            'morning' => (float) $price->morning_price,
            'evening' => (float) $price->evening_price,
            'full_day' => (float) $price->full_price,
        ];
    }
}
