<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Unite;
use App\Models\UniteBookingPackage;
use Illuminate\Database\Seeder;

/**
 * Creates actual booking packages for the specific venues enabled in
 * UnitesTableSeeder — matching the infographic's own examples where
 * possible (stadium: Friday 8-11pm; hall: Saturday full-day) so the
 * seeded data genuinely demonstrates the feature rather than just
 * flipping the enabled flag with nothing behind it.
 *
 * 'day' matches the exact same 4-value enum already used for
 * unite_prices.day (week_day/thursday/friday/saturday) — a single row can
 * only ever apply to one of these, not several specific days at once, so
 * the lounge "weekend package" example below is genuinely 3 separate rows
 * (thursday, friday, saturday), each otherwise identical — not one row
 * with 3 days attached, which the corrected schema no longer supports.
 */
class UniteBookingPackagesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Looked up by name rather than assumed IDs — matches how every
        // other seeder in this project resolves dependent data, since
        // service IDs aren't guaranteed stable across re-seeds.
        $serviceNames = fn (array $names) => Service::whereIn('name', $names)->pluck('id')->all();

        $packages = [
            [
                'unite' => 'ملعب الدمام ب',
                'name' => 'باقة الجمعة المسائية',
                'day' => 'friday',
                'start_time' => '20:00',
                'end_time' => '23:00',
                'price' => 500,
                'services' => $serviceNames(['أضواء كشافة', 'موقف سيارات', 'محطة إسعافات أولية']),
            ],
            [
                'unite' => 'قاعة النور للأعراس',
                'name' => 'باقة السبت الكاملة',
                'day' => 'saturday',
                'start_time' => '10:00',
                'end_time' => '22:00',
                'price' => 1500,
                'services' => $serviceNames(['صالة طعام', 'انترنت', 'موقف سيارات']),
            ],
            // "Weekend package" — genuinely 3 separate rows (one per day),
            // since a single row can no longer span multiple specific days.
            [
                'unite' => 'صالة اللؤلؤة',
                'name' => 'باقة نهاية الأسبوع - الخميس',
                'day' => 'thursday',
                'start_time' => '16:00',
                'end_time' => '23:00',
                'price' => 800,
                'services' => $serviceNames(['مسبح مشترك', 'ركن شواء', 'إطلالة']),
            ],
            [
                'unite' => 'صالة اللؤلؤة',
                'name' => 'باقة نهاية الأسبوع - الجمعة',
                'day' => 'friday',
                'start_time' => '16:00',
                'end_time' => '23:00',
                'price' => 800,
                'services' => $serviceNames(['مسبح مشترك', 'ركن شواء', 'إطلالة']),
            ],
            [
                'unite' => 'صالة اللؤلؤة',
                'name' => 'باقة نهاية الأسبوع - السبت',
                'day' => 'saturday',
                'start_time' => '16:00',
                'end_time' => '23:00',
                'price' => 800,
                'services' => $serviceNames(['مسبح مشترك', 'ركن شواء', 'إطلالة']),
            ],
            // Camp example — "any day" doesn't exist in the corrected
            // 4-value system, so this maps to 'week_day' (the regular,
            // non-thu/fri/sat catch-all), the closest sensible equivalent.
            [
                'unite' => 'مخيم الصفا الصحراوي',
                'name' => 'باقة أيام الأسبوع',
                'day' => 'week_day',
                'start_time' => '15:00',
                'end_time' => '23:59',
                'price' => 600,
                'services' => $serviceNames(['حفرة مندي', 'موقد حطب', 'جلسات خارجية']),
            ],
        ];

        foreach ($packages as $pkg) {
            $unite = Unite::where('name', $pkg['unite'])->first();

            if (! $unite) {
                continue;
            }

            $bookingPackage = UniteBookingPackage::updateOrCreate(
                ['unite_id' => $unite->id, 'name' => $pkg['name']],
                [
                    'day' => $pkg['day'],
                    'start_time' => $pkg['start_time'],
                    'end_time' => $pkg['end_time'],
                    'price' => $pkg['price'],
                    'status' => 'active',
                ]
            );

            if (! empty($pkg['services'])) {
                $bookingPackage->services()->sync($pkg['services']);
            }
        }
    }
}
