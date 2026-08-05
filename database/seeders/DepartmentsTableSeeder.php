<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        $p = fn ($email) => User::where('email', $email)->value('id');

        $departments = [
            // ── Stadiums ──────────────────────────────────────────────────
            ['user_id' => $p('provider1@example.com'), 'name' => 'ملاعب النخبة',       'type' => 'stadium', 'location' => 'الرياض',  'latitude' => '24.7135517', 'longitude' => '46.6752957', 'whatsapp' => '966500000010'],
            ['user_id' => $p('provider1@example.com'), 'name' => 'ساحة الأبطال',      'type' => 'stadium', 'location' => 'الرياض',  'latitude' => '24.7882500', 'longitude' => '46.6265600', 'whatsapp' => '966500000010'],
            ['user_id' => $p('provider3@example.com'), 'name' => 'مركز الدمام الرياضي',    'type' => 'stadium', 'location' => 'الدمام',  'latitude' => '26.4207000', 'longitude' => '50.0888000', 'whatsapp' => '966500000012'],

            // ── Halls ─────────────────────────────────────────────────────
            ['user_id' => $p('provider2@example.com'), 'name' => 'قاعات النور',           'type' => 'hall',    'location' => 'جدة',  'latitude' => '21.543333',  'longitude' => '39.172779',  'whatsapp' => '966500000011'],
            ['user_id' => $p('provider2@example.com'), 'name' => 'القاعات الملكية للمناسبات',    'type' => 'hall',    'location' => 'جدة',  'latitude' => '21.5654000', 'longitude' => '39.1643000', 'whatsapp' => '966500000011'],
            ['user_id' => $p('provider4@example.com'), 'name' => 'قاعات المدينة',     'type' => 'hall',    'location' => 'المدينة المنورة', 'latitude' => '24.4686600', 'longitude' => '39.6142100', 'whatsapp' => '966500000013'],

            // ── Lounges ───────────────────────────────────────────────────
            ['user_id' => $p('provider2@example.com'), 'name' => 'صالات الاسترخاء',        'type' => 'lounge',  'location' => 'جدة',  'latitude' => '21.5788000', 'longitude' => '39.1745000', 'whatsapp' => '966500000011'],
            ['user_id' => $p('provider5@example.com'), 'name' => 'صالات سيرينيتي الخاصة', 'type' => 'lounge', 'location' => 'الرياض', 'latitude' => '24.6893000', 'longitude' => '46.6880000', 'whatsapp' => '966500000014'],

            // ── Camps ─────────────────────────────────────────────────────
            ['user_id' => $p('provider1@example.com'), 'name' => 'مخيمات الصحراء',         'type' => 'camp',    'location' => 'الرياض',  'latitude' => '24.4256000', 'longitude' => '46.3147000', 'whatsapp' => '966500000010'],
            ['user_id' => $p('provider5@example.com'), 'name' => 'مخيمات جبال الطائف',  'type' => 'camp',    'location' => 'الطائف',    'latitude' => '21.2854000', 'longitude' => '40.4150000', 'whatsapp' => '966500000014'],
        ];

        $typeLabelsAr = [
            'stadium' => 'ملاعب',
            'hall' => 'قاعات',
            'lounge' => 'صالات',
            'camp' => 'مخيمات',
        ];

        foreach ($departments as $data) {
            Department::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'description' => 'قسم '.($typeLabelsAr[$data['type']] ?? $data['type']).' مميز يقع في '.$data['location'].'.',
                    'status' => 'active',
                    'facebook' => 'https://facebook.com/'.Str_slug($data['name']),
                    'instagram' => 'https://instagram.com/'.Str_slug($data['name']),
                    'twitter' => 'https://twitter.com/'.Str_slug($data['name']),
                ])
            );
        }
    }
}

function Str_slug($str)
{
    return strtolower(preg_replace('/\s+/', '', $str));
}
