<?php

namespace Database\Seeders;

use App\Models\ServiceFee;
use Illuminate\Database\Seeder;

class ServiceFeesTableSeeder extends Seeder
{
    public function run(): void
    {
        $fees = [
            [
                'key' => 'reservation',
                'label_en' => 'Unit Reservations',
                'label_ar' => 'حجوزات الوحدات',
            ],
            [
                'key' => 'ad_package',
                'label_en' => 'Advertising Packages',
                'label_ar' => 'باقات الإعلانات',
            ],
            [
                'key' => 'property_package',
                'label_en' => 'Real Estate Packages',
                'label_ar' => 'باقات العقارات',
            ],
        ];

        foreach ($fees as $fee) {
            // Starts disabled (amount 0) — seeding this must not silently
            // start charging existing customers an unexpected fee. An
            // admin has to deliberately turn it on via the settings page.
            ServiceFee::firstOrCreate(
                ['key' => $fee['key']],
                array_merge($fee, ['amount' => 0, 'is_active' => false])
            );
        }
    }
}
