<?php

namespace Database\Seeders;

use App\Models\TransferPolicy;
use Illuminate\Database\Seeder;

class TransferPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'title' => 'سياسة التحويل القياسية',
                'description' => 'سياسة التحويل الافتراضية لجميع المزودين. يتم تحويل الأموال بعد اكتمال الحجز والتحقق من تقديم الخدمة.',
                'transfer_days' => 7,
                'transfer_methods' => ['bank_transfer', 'digital_wallet'],
                'tax_rate' => 15.00,
                'platform_fee_rate' => 5.00,
                'is_active' => true,
            ],
            [
                'title' => 'سياسة التحويل السريع',
                'description' => 'تحويل أسرع للمزودين المميزين. يتطلب حسابًا بنكيًا موثّقًا.',
                'transfer_days' => 3,
                'transfer_methods' => ['bank_transfer'],
                'tax_rate' => 15.00,
                'platform_fee_rate' => 7.00,
                'is_active' => false,
            ],
            [
                'title' => 'سياسة الدفع النقدي',
                'description' => 'للمزودين الذين يفضلون الدفع النقدي. يتم الاستلام عبر ممثل عن Weekend.',
                'transfer_days' => 14,
                'transfer_methods' => ['cash', 'check'],
                'tax_rate' => 15.00,
                'platform_fee_rate' => 5.00,
                'is_active' => false,
            ],
        ];

        foreach ($policies as $policy) {
            TransferPolicy::firstOrCreate(
                ['title' => $policy['title']],
                $policy
            );
        }
    }
}
