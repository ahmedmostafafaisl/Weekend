<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteOffer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UniteOffersTableSeeder extends Seeder
{
    public function run(): void
    {
        $unites = Unite::with('prices')->get();

        foreach ($unites as $unite) {
            $price = $unite->prices->where('day', 'friday')->first();
            if (! $price) {
                continue;
            }

            // Offer 1: Summer discount — next 30 days
            UniteOffer::updateOrCreate(
                ['unite_id' => $unite->id, 'name' => 'Summer 2026 Offer'],
                [
                    'unite_id' => $unite->id,
                    'name' => 'Summer 2026 Offer',
                    'start' => Carbon::today()->format('Y-m-d'),
                    'end' => Carbon::today()->addDays(30)->format('Y-m-d'),
                    'morning_price' => $unite->type === 'stadium' ? null : round(($price->morning_price ?? 0) * 0.80),
                    'evening_price' => $unite->type === 'stadium' ? null : round(($price->evening_price ?? 0) * 0.80),
                    'full_day_price' => $unite->type === 'stadium' ? null : round(($price->full_price ?? 0) * 0.80),
                    // BUG FIX: stadiums are hourly-only — offers need
                    // day_hour_price/night_hour_price, not a full_day_price
                    // derived from the now-vestigial flat 'price' field.
                    'day_hour_price' => $unite->type === 'stadium' ? round(($price->day_hour_price ?? 0) * 0.80) : null,
                    'night_hour_price' => $unite->type === 'stadium' ? round(($price->night_hour_price ?? 0) * 0.80) : null,
                    'status' => 'active',
                ]
            );

            // Offer 2: Weekend special — next Fri/Sat only
            UniteOffer::updateOrCreate(
                ['unite_id' => $unite->id, 'name' => 'عرض نهاية الأسبوع'],
                [
                    'unite_id' => $unite->id,
                    'name' => 'عرض نهاية الأسبوع',
                    'start' => Carbon::today()->next('Friday')->format('Y-m-d'),
                    'end' => Carbon::today()->next('Saturday')->format('Y-m-d'),
                    'morning_price' => $unite->type === 'stadium' ? null : round(($price->morning_price ?? 0) * 0.90),
                    'evening_price' => $unite->type === 'stadium' ? null : round(($price->evening_price ?? 0) * 0.90),
                    'full_day_price' => $unite->type === 'stadium' ? null : round(($price->full_price ?? 0) * 0.90),
                    'day_hour_price' => $unite->type === 'stadium' ? round(($price->day_hour_price ?? 0) * 0.90) : null,
                    'night_hour_price' => $unite->type === 'stadium' ? round(($price->night_hour_price ?? 0) * 0.90) : null,
                    'status' => 'active',
                ]
            );

            // Offer 3: Past offer (for history data)
            UniteOffer::updateOrCreate(
                ['unite_id' => $unite->id, 'name' => 'Ramadan 2026 Special'],
                [
                    'unite_id' => $unite->id,
                    'name' => 'Ramadan 2026 Special',
                    'start' => Carbon::today()->subDays(40)->format('Y-m-d'),
                    'end' => Carbon::today()->subDays(10)->format('Y-m-d'),
                    'morning_price' => $unite->type === 'stadium' ? null : round(($price->morning_price ?? 0) * 0.70),
                    'evening_price' => $unite->type === 'stadium' ? null : round(($price->evening_price ?? 0) * 0.70),
                    'full_day_price' => $unite->type === 'stadium' ? null : round(($price->full_price ?? 0) * 0.70),
                    'day_hour_price' => $unite->type === 'stadium' ? round(($price->day_hour_price ?? 0) * 0.70) : null,
                    'night_hour_price' => $unite->type === 'stadium' ? round(($price->night_hour_price ?? 0) * 0.70) : null,
                    'status' => 'inactive',
                ]
            );
        }
    }
}
