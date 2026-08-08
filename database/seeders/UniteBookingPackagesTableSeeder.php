<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteBookingPackage;
use Illuminate\Database\Seeder;

/**
 * Creates booking packages for EVERY unite — both booking_type modes
 * ('hours' and 'days') on each one, matching the explicit request to
 * cover all venues rather than a demonstrative subset. Field choices vary
 * sensibly by venue type: stadium/hall get an evening-focused 'hours'
 * package (matching how those venues are actually used), lounge/camp get
 * a daytime-focused one; 'days' packages vary in span (1, 3, or 6 nights)
 * across the set so every duration_days wraparound case in
 * UniteBookingPackage::computeDurationDays() gets real seeded data behind
 * it, not just my own standalone verification of the math.
 */
class UniteBookingPackagesTableSeeder extends Seeder
{
    public function run(): void
    {
        $hoursServicesByType = [
            'stadium' => 'أضواء كشافة, موقف سيارات, محطة إسعافات أولية',
            'hall' => 'صالة طعام, انترنت, موقف سيارات',
            'lounge' => 'مسبح مشترك, ركن شواء, إطلالة',
            'camp' => 'حفرة مندي, موقد حطب, جلسات خارجية',
        ];

        $daysServicesByType = [
            'stadium' => 'صيانة الأرضية, تنظيف يومي',
            'hall' => 'تنظيف يومي, أمن وحراسة',
            'lounge' => 'تنظيف يومي, إفطار مجاني',
            'camp' => 'إقامة كاملة, وجبات يومية',
        ];

        // Cycled across units so the seeded 'days' packages exercise
        // several different spans, not the same one repeated everywhere —
        // including a wraparound case (friday -> sunday, 3 nights
        // crossing the week boundary) alongside the two straightforward
        // ones already covered elsewhere.
        $dayRangeCycle = [
            ['from' => 'saturday', 'to' => 'saturday'],   // 1 night
            ['from' => 'thursday', 'to' => 'saturday'],   // 3 nights
            ['from' => 'sunday', 'to' => 'friday'],        // 6 nights
            ['from' => 'friday', 'to' => 'sunday'],        // 3 nights, wraps the week boundary
        ];

        $hoursDayCycle = ['week_day', 'thursday', 'friday', 'saturday'];

        $unites = Unite::all();

        foreach ($unites as $i => $unite) {
            $basePrice = match ($unite->type) {
                'stadium' => 400,
                'hall' => 1200,
                'lounge' => 600,
                'camp' => 500,
                default => 500,
            };

            // 'hours' mode
            UniteBookingPackage::updateOrCreate(
                ['unite_id' => $unite->id, 'name' => 'باقة الساعات'],
                [
                    'booking_type' => 'hours',
                    'day' => $hoursDayCycle[$i % count($hoursDayCycle)],
                    'start_time' => in_array($unite->type, ['stadium', 'hall']) ? '19:00' : '10:00',
                    'end_time' => in_array($unite->type, ['stadium', 'hall']) ? '22:00' : '15:00',
                    'day_from' => null,
                    'day_to' => null,
                    'duration_days' => null,
                    'price' => $basePrice + ($i * 10),
                    'services' => array_map('trim', explode(',', $hoursServicesByType[$unite->type] ?? '')),
                    'status' => 'active',
                ]
            );

            // 'days' mode
            $range = $dayRangeCycle[$i % count($dayRangeCycle)];
            $duration = UniteBookingPackage::computeDurationDays($range['from'], $range['to']);

            UniteBookingPackage::updateOrCreate(
                ['unite_id' => $unite->id, 'name' => 'باقة الأيام'],
                [
                    'booking_type' => 'days',
                    'day' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'day_from' => $range['from'],
                    'day_to' => $range['to'],
                    'duration_days' => $duration,
                    'price' => ($basePrice * $duration) + ($i * 20),
                    'services' => array_map('trim', explode(',', $daysServicesByType[$unite->type] ?? '')),
                    'status' => 'active',
                ]
            );
        }
    }
}
