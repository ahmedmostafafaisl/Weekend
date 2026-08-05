<?php

namespace Database\Seeders;

use App\Models\AdPackage;
use Illuminate\Database\Seeder;

class AdPackagesTableSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'باقة الإعلانات المبتدئة',
                'description' => 'باقة إعلانات أساسية بعدد محدود من الإعلانات.',
                'type' => 'count',
                'count' => 5,
                'duration' => null,
                'price' => 49.99,
                'image' => 'packages/ad_starter_count.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة الإعلانات القياسية',
                'description' => 'باقة قياسية لعدة إعلانات.',
                'type' => 'count',
                'count' => 10,
                'duration' => null,
                'price' => 89.99,
                'image' => 'packages/ad_standard_count.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة الإعلانات المميزة',
                'description' => 'باقة مميزة لنشر إعلانات بكثافة عالية.',
                'type' => 'count',
                'count' => 25,
                'duration' => null,
                'price' => 179.99,
                'image' => 'packages/ad_premium_count.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة إعلانات 7 أيام',
                'description' => 'باقة إعلانات نشطة لمدة 7 أيام.',
                'type' => 'duration',
                'count' => null,
                'duration' => 7,
                'price' => 39.99,
                'image' => 'packages/ad_7days.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة إعلانات 15 يوم',
                'description' => 'باقة إعلانات نشطة لمدة 15 يومًا.',
                'type' => 'duration',
                'count' => null,
                'duration' => 15,
                'price' => 69.99,
                'image' => 'packages/ad_15days.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة إعلانات 30 يوم',
                'description' => 'باقة إعلانات نشطة لمدة 30 يومًا.',
                'type' => 'duration',
                'count' => null,
                'duration' => 30,
                'price' => 119.99,
                'image' => 'packages/ad_30days.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة إعلانات قديمة معطلة',
                'description' => 'باقة معطلة لأغراض الاختبار.',
                'type' => 'count',
                'count' => 3,
                'duration' => null,
                'price' => 19.99,
                'image' => 'packages/ad_inactive.jpg',
                'status' => 'inactive',
            ],
        ];

        foreach ($packages as $package) {
            AdPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
