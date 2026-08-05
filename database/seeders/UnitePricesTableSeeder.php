<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UnitePrice;
use Illuminate\Database\Seeder;

class UnitePricesTableSeeder extends Seeder
{
    // Realistic price matrices per unite type
    private array $stadiumPrices = [
        // [week_day, thursday, friday, saturday]
        [150, 200, 250, 250],
        [180, 230, 280, 280],
        [120, 160, 200, 200],
        [200, 260, 320, 320],
        [160, 210, 260, 260],
        [140, 185, 230, 230],
        [170, 220, 270, 270],
    ];

    // BUG FIX: stadiums are now hourly-only — day_hour_price/night_hour_price
    // are the required, primary pricing fields (the flat 'price' above is
    // now optional/vestigial). Derived proportionally from the existing
    // flat price matrix rather than picking arbitrary new numbers, so the
    // relative pricing between the 7 seeded stadiums stays consistent.
    private array $stadiumHourlyPrices = [
        // [day_hour_price, night_hour_price] per [week_day, thursday, friday, saturday]
        'day' => [[19, 25, 31, 31], [22, 29, 35, 35], [15, 20, 25, 25], [25, 32, 40, 40], [20, 26, 32, 32], [18, 23, 29, 29], [21, 28, 34, 34]],
        'night' => [[24, 32, 41, 41], [29, 37, 46, 46], [20, 26, 32, 32], [32, 42, 52, 52], [26, 34, 42, 42], [23, 30, 37, 37], [28, 36, 44, 44]],
    ];

    private array $hallPrices = [
        // [morning, evening, full_day] per [week_day, thursday, friday, saturday]
        'week_day' => [800,  1200, 1800],
        'thursday' => [1000, 1500, 2200],
        'friday' => [1200, 1800, 2800],
        'saturday' => [1100, 1700, 2600],
    ];

    private array $loungePrices = [
        'week_day' => [300, 500,  700],
        'thursday' => [400, 650,  900],
        'friday' => [500, 800, 1100],
        'saturday' => [450, 750, 1050],
    ];

    private array $campPrices = [
        'week_day' => [200, 350, 500],
        'thursday' => [280, 450, 650],
        'friday' => [350, 550, 800],
        'saturday' => [320, 500, 750],
    ];

    public function run(): void
    {
        $days = ['week_day', 'thursday', 'friday', 'saturday'];

        $stadiums = Unite::where('type', 'stadium')->get();
        $halls = Unite::where('type', 'hall')->get();
        $lounges = Unite::where('type', 'lounge')->get();
        $camps = Unite::where('type', 'camp')->get();

        foreach ($stadiums as $i => $unite) {
            $row = $this->stadiumPrices[$i % count($this->stadiumPrices)];
            $dayRow = $this->stadiumHourlyPrices['day'][$i % count($this->stadiumHourlyPrices['day'])];
            $nightRow = $this->stadiumHourlyPrices['night'][$i % count($this->stadiumHourlyPrices['night'])];
            foreach ($days as $di => $day) {
                UnitePrice::updateOrCreate(
                    ['unite_id' => $unite->id, 'day' => $day],
                    [
                        'price' => $row[$di], 'morning_price' => null, 'evening_price' => null, 'full_price' => null,
                        'hourly_enabled' => true,
                        'day_hour_price' => $dayRow[$di],
                        'night_hour_price' => $nightRow[$di],
                        'day_start' => '06:00:00', 'day_end' => '18:00:00',
                        'min_booking_minutes' => 60,
                    ]
                );
            }
        }

        foreach ($halls as $i => $unite) {
            $multiplier = 1 + ($i * 0.15); // each hall slightly pricier
            foreach ($days as $day) {
                $base = $this->hallPrices[$day];
                UnitePrice::updateOrCreate(
                    ['unite_id' => $unite->id, 'day' => $day],
                    [
                        // BUG FIX: halls are full-day only — morning_price/
                        // evening_price are no longer accepted at all via
                        // the create/update form, so they must genuinely
                        // be null here too instead of populated with rates
                        // nobody can set through the app anymore.
                        'price' => null, 'morning_price' => null, 'evening_price' => null,
                        'full_price' => round($base[2] * $multiplier),
                    ]
                );
            }
        }

        foreach ($lounges as $i => $unite) {
            $multiplier = 1 + ($i * 0.2);
            foreach ($days as $day) {
                $base = $this->loungePrices[$day];
                UnitePrice::updateOrCreate(
                    ['unite_id' => $unite->id, 'day' => $day],
                    ['price' => null, 'morning_price' => round($base[0] * $multiplier), 'evening_price' => round($base[1] * $multiplier), 'full_price' => round($base[2] * $multiplier)]
                );
            }
        }

        foreach ($camps as $i => $unite) {
            $multiplier = 1 + ($i * 0.1);
            foreach ($days as $day) {
                $base = $this->campPrices[$day];
                UnitePrice::updateOrCreate(
                    ['unite_id' => $unite->id, 'day' => $day],
                    ['price' => null, 'morning_price' => round($base[0] * $multiplier), 'evening_price' => round($base[1] * $multiplier), 'full_price' => round($base[2] * $multiplier)]
                );
            }
        }
    }
}
