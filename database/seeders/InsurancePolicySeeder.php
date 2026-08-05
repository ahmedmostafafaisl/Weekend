<?php

namespace Database\Seeders;

use App\Models\InsurancePolicy;
use Illuminate\Database\Seeder;

class InsurancePolicySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'تأمين أساسي',
                'description' => 'خطة تغطية أساسية',
            ],
            [
                'name' => 'تأمين مميز',
                'description' => 'خطة تغطية شاملة',
            ],
            [
                'name' => 'تأمين رياضي',
                'description' => 'تغطية إصابات الملاعب الرياضية',
            ],
        ];

        foreach ($data as $item) {
            InsurancePolicy::create($item);
        }
    }
}
