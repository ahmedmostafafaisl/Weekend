<?php

namespace Database\Seeders;

use App\Models\PropertyPackage;
use Illuminate\Database\Seeder;

class PropertyPackagesTableSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'باقة زمنية مبتدئة',
                'description' => 'باقة عقارية أساسية لعرض محدود المدة.',
                'type' => 'time',
                'duration' => 30,
                'percentage' => null,
                'price' => 99.99,
                'image' => 'packages/property_starter_time.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة زمنية قياسية',
                'description' => 'باقة عقارية صالحة لمدة 60 يومًا.',
                'type' => 'time',
                'duration' => 60,
                'percentage' => null,
                'price' => 179.99,
                'image' => 'packages/property_standard_time.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة زمنية مميزة',
                'description' => 'باقة عقارية صالحة لمدة 90 يومًا.',
                'type' => 'time',
                'duration' => 90,
                'percentage' => null,
                'price' => 299.99,
                'image' => 'packages/property_premium_time.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة نسبية مبتدئة',
                'description' => 'باقة عقارية بعمولة 5%.',
                'type' => 'percentage',
                'duration' => null,
                'percentage' => 5,
                'price' => 49.99,
                'image' => 'packages/property_starter_percentage.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة نسبية قياسية',
                'description' => 'باقة عقارية بعمولة 10%.',
                'type' => 'percentage',
                'duration' => null,
                'percentage' => 10,
                'price' => 79.99,
                'image' => 'packages/property_standard_percentage.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة نسبية مميزة',
                'description' => 'باقة عقارية بعمولة 15%.',
                'type' => 'percentage',
                'duration' => null,
                'percentage' => 15,
                'price' => 119.99,
                'image' => 'packages/property_premium_percentage.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عقارية معطلة',
                'description' => 'باقة معطلة لأغراض الاختبار.',
                'type' => 'time',
                'duration' => 15,
                'percentage' => null,
                'price' => 39.99,
                'image' => 'packages/property_inactive.jpg',
                'status' => 'inactive',
            ],
        ];

        foreach ($packages as $package) {
            PropertyPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
