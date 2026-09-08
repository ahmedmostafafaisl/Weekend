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
                'name' => 'باقة عدد مبتدئة',
                'description' => 'باقة عقارية تتيح إضافة 3 وحدات.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 3,
                'price' => 99.99,
                'image' => 'packages/property_starter_time.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عدد قياسية',
                'description' => 'باقة عقارية تتيح إضافة 10 وحدات.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 10,
                'price' => 179.99,
                'image' => 'packages/property_standard_time.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عدد مميزة',
                'description' => 'باقة عقارية تتيح إضافة 25 وحدة.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 25,
                'price' => 299.99,
                'image' => 'packages/property_premium_time.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عدد صغيرة',
                'description' => 'باقة عقارية تتيح إضافة 5 وحدات.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 5,
                'price' => 49.99,
                'image' => 'packages/property_starter_percentage.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عدد متوسطة',
                'description' => 'باقة عقارية تتيح إضافة 15 وحدة.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 15,
                'price' => 79.99,
                'image' => 'packages/property_standard_percentage.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عدد كبيرة',
                'description' => 'باقة عقارية تتيح إضافة 50 وحدة.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 50,
                'price' => 119.99,
                'image' => 'packages/property_premium_percentage.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'باقة عقارية معطلة',
                'description' => 'باقة معطلة لأغراض الاختبار.',
                'type' => 'count',
                'duration' => null,
                'percentage' => null,
                'count' => 2,
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
