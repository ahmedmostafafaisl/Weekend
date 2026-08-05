<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('12345678');

        // ── Providers (own the departments) ───────────────────────────────────
        $providers = [
            ['name' => 'خالد العتيبي',  'email' => 'provider1@example.com', 'phone' => '966500000010', 'type' => 'provider', 'nation' => 'saudi', 'gender' => 'male'],
            ['name' => 'نورة القحطاني',    'email' => 'provider2@example.com', 'phone' => '966500000011', 'type' => 'provider', 'nation' => 'saudi', 'gender' => 'female'],
            ['name' => 'فيصل الدوسري',  'email' => 'provider3@example.com', 'phone' => '966500000012', 'type' => 'provider', 'nation' => 'saudi', 'gender' => 'male'],
            ['name' => 'لينا الشهري',    'email' => 'provider4@example.com', 'phone' => '966500000013', 'type' => 'provider', 'nation' => 'resident', 'gender' => 'female'],
            ['name' => 'عمر الحربي',    'email' => 'provider5@example.com', 'phone' => '966500000014', 'type' => 'provider', 'nation' => 'saudi', 'gender' => 'male'],
        ];

        // ── Customers ─────────────────────────────────────────────────────────
        $customerNames = [
            ['name' => 'أحمد العمري',    'email' => 'customer1@example.com',  'phone' => '966500000001', 'gender' => 'male'],
            ['name' => 'سارة الغامدي',     'email' => 'customer2@example.com',  'phone' => '966500000002', 'gender' => 'female'],
            ['name' => 'محمد علي',      'email' => 'customer3@example.com',  'phone' => '966500000003', 'gender' => 'male'],
            ['name' => 'فاطمة حسن',     'email' => 'customer4@example.com',  'phone' => '966500000004', 'gender' => 'female'],
            ['name' => 'عبدالله سالم',    'email' => 'customer5@example.com',  'phone' => '966500000005', 'gender' => 'male'],
            ['name' => 'مريم ناصر',     'email' => 'customer6@example.com',  'phone' => '966500000006', 'gender' => 'female'],
            ['name' => 'يوسف خليل',    'email' => 'customer7@example.com',  'phone' => '966500000007', 'gender' => 'male'],
            ['name' => 'حصة الراشدي',  'email' => 'customer8@example.com',  'phone' => '966500000008', 'gender' => 'female'],
            ['name' => 'طارق منصور',    'email' => 'customer9@example.com',  'phone' => '966500000009', 'gender' => 'male'],
            ['name' => 'رانيا فؤاد',       'email' => 'customer10@example.com', 'phone' => '966500000020', 'gender' => 'female'],
            ['name' => 'سعد الغامدي',   'email' => 'customer11@example.com', 'phone' => '966500000021', 'gender' => 'male'],
            ['name' => 'نوف السبيعي',    'email' => 'customer12@example.com', 'phone' => '966500000022', 'gender' => 'female'],
            ['name' => 'وليد إبراهيم',    'email' => 'customer13@example.com', 'phone' => '966500000023', 'gender' => 'male'],
            ['name' => 'دلال العتيبي',      'email' => 'customer14@example.com', 'phone' => '966500000024', 'gender' => 'female'],
            ['name' => 'حمد الدوسري',   'email' => 'customer15@example.com', 'phone' => '966500000025', 'gender' => 'male'],
            ['name' => 'شهد الحربي',    'email' => 'customer16@example.com', 'phone' => '966500000026', 'gender' => 'female'],
            ['name' => 'نواف القحطاني',   'email' => 'customer17@example.com', 'phone' => '966500000027', 'gender' => 'male'],
            ['name' => 'لجين المطيري', 'email' => 'customer18@example.com', 'phone' => '966500000028', 'gender' => 'female'],
            ['name' => 'بندر الشهري',   'email' => 'customer19@example.com', 'phone' => '966500000029', 'gender' => 'male'],
            ['name' => 'غادة الزهراني',  'email' => 'customer20@example.com', 'phone' => '966500000030', 'gender' => 'female'],
        ];

        foreach ($providers as $i => $p) {
            User::updateOrCreate(['email' => $p['email']], array_merge($p, [
                'password' => $password,
                'email_verified_at' => now(),
                'status' => 'active',
                'provider_type' => 'individual',
                'id_number' => '200000000'.$i,
                'birth_date' => '1985-0'.($i + 1).'-10',
                'ownership' => 0,
                'remember_token' => Str::random(10),
            ]));
        }

        foreach ($customerNames as $i => $c) {
            User::updateOrCreate(['email' => $c['email']], [
                'name' => $c['name'],
                'email' => $c['email'],
                'phone' => $c['phone'],
                'gender' => $c['gender'],
                'password' => $password,
                'email_verified_at' => now(),
                'status' => 'active',
                'type' => 'customer',
                'provider_type' => 'individual',
                'nation' => $i % 3 === 0 ? 'resident' : 'saudi',
                'id_number' => '100000000'.str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                'birth_date' => '199'.($i % 9 + 0).'-'.str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT).'-15',
                'ownership' => 0,
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
