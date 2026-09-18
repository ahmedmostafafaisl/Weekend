<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'      => 'allow_unites_without_subscription',
                'label_en' => 'Free trial — allow adding properties without an active subscription',
                'label_ar' => 'تجربة مجانية — السماح بإضافة العقارات بدون اشتراك نشط',
            ],
            [
                'key'      => 'allow_ads_without_subscription',
                'label_en' => 'Free trial — allow adding ad listings without an active subscription',
                'label_ar' => 'تجربة مجانية — السماح بإضافة الإعلانات بدون اشتراك نشط',
            ],
        ];

        foreach ($settings as $setting) {
            // firstOrCreate so re-running the seeder never flips a flag
            // an admin has deliberately enabled back to the default.
            AppSetting::firstOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['is_active' => false])
            );
        }
    }
}
