<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\PromoCode;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = Admin::value('id');

        $codes = [
            // ── Active percentage codes ───────────────────────────────────────
            [
                'code' => 'WELCOME10',
                'description' => 'خصم ترحيبي للعملاء الجدد — خصم 10%',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_amount' => null,
                'max_discount' => 100,
                'max_uses' => 500,
                'max_uses_per_user' => 1,
                'starts_at' => Carbon::today()->subDays(30)->format('Y-m-d'),
                'expires_at' => Carbon::today()->addDays(90)->format('Y-m-d'),
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER20',
                'description' => 'حملة صيف 2026 — خصم 20% على جميع الحجوزات',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_amount' => 200,
                'max_discount' => 300,
                'max_uses' => 300,
                'max_uses_per_user' => 2,
                'starts_at' => Carbon::today()->format('Y-m-d'),
                'expires_at' => Carbon::today()->addDays(45)->format('Y-m-d'),
                'is_active' => true,
            ],
            [
                'code' => 'VIP25',
                'description' => 'مكافأة عملاء VIP — خصم 25%',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'min_amount' => 500,
                'max_discount' => 500,
                'max_uses' => 100,
                'max_uses_per_user' => 1,
                'starts_at' => Carbon::today()->format('Y-m-d'),
                'expires_at' => Carbon::today()->addDays(60)->format('Y-m-d'),
                'is_active' => true,
            ],
            [
                'code' => 'WEEKEND15',
                'description' => 'حجوزات نهاية الأسبوع — خصم 15%',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_amount' => 150,
                'max_discount' => 200,
                'max_uses' => null,
                'max_uses_per_user' => 3,
                'starts_at' => Carbon::today()->format('Y-m-d'),
                'expires_at' => Carbon::today()->addMonths(3)->format('Y-m-d'),
                'is_active' => true,
            ],
            [
                'code' => 'FIRSTBOOK',
                'description' => 'عرض أول حجز — خصم 15% على أي وحدة',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_amount' => null,
                'max_discount' => 150,
                'max_uses' => null,
                'max_uses_per_user' => 1,
                'starts_at' => Carbon::today()->subMonths(3)->format('Y-m-d'),
                'expires_at' => Carbon::today()->addYear()->format('Y-m-d'),
                'is_active' => true,
            ],

            // ── Active fixed-amount codes ─────────────────────────────────────
            [
                'code' => 'SAVE50',
                'description' => 'خصم ثابت 50 ريال على أي حجز',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_amount' => 200,
                'max_discount' => null,
                'max_uses' => 1000,
                'max_uses_per_user' => 2,
                'starts_at' => null,
                'expires_at' => Carbon::today()->addDays(30)->format('Y-m-d'),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE100',
                'description' => 'خصم ثابت 100 ريال على حجوزات تزيد عن 500 ريال',
                'discount_type' => 'fixed',
                'discount_value' => 100,
                'min_amount' => 500,
                'max_discount' => null,
                'max_uses' => 500,
                'max_uses_per_user' => 1,
                'starts_at' => null,
                'expires_at' => Carbon::today()->addDays(60)->format('Y-m-d'),
                'is_active' => true,
            ],
            [
                'code' => 'HALL200',
                'description' => 'عرض خاص لحجوزات القاعات — خصم 200 ريال',
                'discount_type' => 'fixed',
                'discount_value' => 200,
                'min_amount' => 1000,
                'max_discount' => null,
                'max_uses' => 200,
                'max_uses_per_user' => 1,
                'starts_at' => Carbon::today()->format('Y-m-d'),
                'expires_at' => Carbon::today()->addMonths(2)->format('Y-m-d'),
                'is_active' => true,
            ],

            // ── Future (not yet started) ──────────────────────────────────────
            [
                'code' => 'EID2026',
                'description' => 'احتفال عيد الأضحى 2026 — خصم 30%',
                'discount_type' => 'percentage',
                'discount_value' => 30,
                'min_amount' => 300,
                'max_discount' => 600,
                'max_uses' => 400,
                'max_uses_per_user' => 2,
                'starts_at' => Carbon::today()->addDays(20)->format('Y-m-d'),
                'expires_at' => Carbon::today()->addDays(35)->format('Y-m-d'),
                'is_active' => true,
            ],

            // ── Expired / inactive ────────────────────────────────────────────
            [
                'code' => 'RAMADAN26',
                'description' => 'عرض رمضان 2026 — منتهي',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_amount' => null,
                'max_discount' => 250,
                'max_uses' => 1000,
                'max_uses_per_user' => 1,
                'starts_at' => Carbon::today()->subDays(50)->format('Y-m-d'),
                'expires_at' => Carbon::today()->subDays(10)->format('Y-m-d'),
                'is_active' => false,
            ],
            [
                'code' => 'WINTER50',
                'description' => 'عرض شتاء 2025 — معطل',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_amount' => null,
                'max_discount' => null,
                'max_uses' => 500,
                'max_uses_per_user' => 2,
                'starts_at' => Carbon::today()->subMonths(6)->format('Y-m-d'),
                'expires_at' => Carbon::today()->subMonths(3)->format('Y-m-d'),
                'is_active' => false,
            ],
        ];

        foreach ($codes as $data) {
            PromoCode::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['created_by' => $adminId])
            );
        }
    }
}
