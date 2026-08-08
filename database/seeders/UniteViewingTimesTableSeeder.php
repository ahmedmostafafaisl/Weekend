<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteViewingTime;
use Illuminate\Database\Seeder;

/**
 * Predefined, recurring weekly viewing slots — every unite gets a
 * realistic 2-3 day-a-week viewing schedule. One venue per type is
 * seeded with two SEPARATE windows on the same day (Saturday morning AND
 * Saturday afternoon), matching the exact example given, to actually
 * exercise the "multiple rows per day" case rather than only ever
 * seeding one window per day everywhere.
 */
class UniteViewingTimesTableSeeder extends Seeder
{
    public function run(): void
    {
        $unites = Unite::all();

        foreach ($unites as $i => $unite) {
            $cycle = $i % 3;

            // Every venue gets weekday viewing, cycling through 3 slightly
            // different weekly patterns for variety rather than seeding
            // the exact same schedule everywhere.
            $slots = match ($cycle) {
                0 => [
                    // Two separate windows on Saturday — the exact example
                    // given: 9:00-11:30 AND 15:00-17:00.
                    ['day_of_week' => 'saturday', 'start_time' => '09:00', 'end_time' => '11:30'],
                    ['day_of_week' => 'saturday', 'start_time' => '15:00', 'end_time' => '17:00'],
                    ['day_of_week' => 'monday', 'start_time' => '17:00', 'end_time' => '19:00'],
                ],
                1 => [
                    ['day_of_week' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00'],
                    ['day_of_week' => 'wednesday', 'start_time' => '16:00', 'end_time' => '18:00'],
                ],
                default => [
                    ['day_of_week' => 'thursday', 'start_time' => '11:00', 'end_time' => '13:00'],
                    ['day_of_week' => 'friday', 'start_time' => '09:00', 'end_time' => '10:30'],
                ],
            };

            foreach ($slots as $slot) {
                UniteViewingTime::updateOrCreate(
                    [
                        'unite_id' => $unite->id,
                        'day_of_week' => $slot['day_of_week'],
                        'start_time' => $slot['start_time'],
                    ],
                    [
                        'end_time' => $slot['end_time'],
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
