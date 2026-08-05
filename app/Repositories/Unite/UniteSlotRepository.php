<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UniteSlot;
use App\Repositories\Interfaces\UniteSlotInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class UniteSlotRepository implements UniteSlotInterface
{
    public function allByUnite(Unite $unite): Collection
    {
        return $unite->slots()->latest()->get();
    }

    public function findByUnite(Unite $unite, int $slotId): ?UniteSlot
    {
        return $unite->slots()->where('id', $slotId)->first();
    }

    public function createForUnite(Unite $unite, array $data): UniteSlot
    {
        $exists = $unite->slots()
            ->where('day_of_week', $data['day_of_week'])
            ->exists();

        if ($exists) {
            abort(422, __('lang.slot_already_exists_weekday'));
        }

        return $unite->slots()->create($data);
    }

    public function updateForUnite(Unite $unite, int $slotId, array $data): UniteSlot
    {
        $slot = $unite->slots()->where('id', $slotId)->firstOrFail();

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

        return $slot->fresh();
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
                    );

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

                        $isAvailable = ! $this->hasConflict($reservations, $from, $to);

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

        // Try exact day match first, then fall back to week_day, then any price row
        $price = $pricesCollection->firstWhere('day', $mappedDay)
            ?? $pricesCollection->firstWhere('day', 'week_day')
            ?? $pricesCollection->first();

        if (! $price) {
            return [
                'source' => 'default',
                'morning' => null,
                'evening' => null,
                'full_day' => 0,
            ];
        }

        if ($unite->type === 'stadium') {
            return [
                'source' => 'default',
                'morning' => null,
                'evening' => null,
                'full_day' => (float) ($price->price ?? 0),
            ];
        }

        return [
            'source' => 'default',
            'morning' => (float) ($price->morning_price ?? 0),
            'evening' => (float) ($price->evening_price ?? 0),
            'full_day' => (float) ($price->full_price ?? 0),
        ];
    }
}
