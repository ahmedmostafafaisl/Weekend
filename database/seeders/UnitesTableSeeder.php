<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unite;
use Illuminate\Database\Seeder;

class UnitesTableSeeder extends Seeder
{
    public function run(): void
    {
        $dep = fn ($name) => Department::where('name', $name)->value('id');

        $unites = [
            // ── ملاعب النخبة (3 وحدات) ───────────────────────────────────────
            ['department_id' => $dep('ملاعب النخبة'), 'type' => 'stadium', 'name' => 'ملعب النخبة أ', 'location_name' => 'الرياض - الملقا', 'latitude' => 24.7882500, 'longitude' => 46.6265600, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount',     'reservation_deposit_amount' => 100.00, 'insurance' => true,  'insurance_amount' => 200.00, 'families_and_singles' => true],
            ['department_id' => $dep('ملاعب النخبة'), 'type' => 'stadium', 'name' => 'ملعب النخبة ب', 'location_name' => 'الرياض - الياسمين', 'latitude' => 24.8201000, 'longitude' => 46.6432100, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount',     'reservation_deposit_amount' => null,   'insurance' => false, 'insurance_amount' => null,   'families_and_singles' => false],
            ['department_id' => $dep('ملاعب النخبة'), 'type' => 'stadium', 'name' => 'ملعب النخبة ج', 'location_name' => 'الرياض - حطين',   'latitude' => 24.7634000, 'longitude' => 46.6543000, 'refund_policy' => 'strict',   'reservation_deposit' => true,  'reservation_deposit_type' => 'percentage',  'reservation_deposit_amount' => 30,     'insurance' => true,  'insurance_amount' => 150.00, 'families_and_singles' => true],

            // ── Champions Arena (2 units) ─────────────────────────────────────
            ['department_id' => $dep('ساحة الأبطال'), 'type' => 'stadium', 'name' => 'ملعب الأبطال 1', 'location_name' => 'الرياض - السليمانية', 'latitude' => 24.7100000, 'longitude' => 46.6890000, 'refund_policy' => 'flexible', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 75, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => true],
            ['department_id' => $dep('ساحة الأبطال'), 'type' => 'stadium', 'name' => 'ملعب الأبطال 2', 'location_name' => 'الرياض - السليمانية', 'latitude' => 24.7102000, 'longitude' => 46.6893000, 'refund_policy' => 'moderate', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => false],

            // ── Dammam Sports Hub (2 units) ────────────────────────────────────
            ['department_id' => $dep('مركز الدمام الرياضي'), 'type' => 'stadium', 'name' => 'ملعب الدمام أ', 'location_name' => 'الدمام - الفيصلية', 'latitude' => 26.4207000, 'longitude' => 50.0888000, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => true],
            ['department_id' => $dep('مركز الدمام الرياضي'), 'type' => 'stadium', 'name' => 'ملعب الدمام ب', 'location_name' => 'الدمام - البادية',    'latitude' => 26.4300000, 'longitude' => 50.1000000, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 50,  'insurance' => true,  'insurance_amount' => 100,  'families_and_singles' => true],

            // ── قاعات النور (3 وحدات) ───────────────────────────────────────
            ['department_id' => $dep('قاعات النور'), 'type' => 'hall', 'name' => 'قاعة النور للأعراس',      'location_name' => 'جدة - الروضة',   'latitude' => 21.5654000, 'longitude' => 39.1643000, 'refund_policy' => 'strict',   'reservation_deposit' => true,  'reservation_deposit_type' => 'amount',     'reservation_deposit_amount' => 500, 'insurance' => true,  'insurance_amount' => 1000, 'families_and_singles' => false],
            ['department_id' => $dep('قاعات النور'), 'type' => 'hall', 'name' => 'قاعة النور للمناسبات',   'location_name' => 'جدة - الحمراء',    'latitude' => 21.5700000, 'longitude' => 39.1720000, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'percentage', 'reservation_deposit_amount' => 25,  'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => true],
            ['department_id' => $dep('قاعات النور'), 'type' => 'hall', 'name' => 'قاعة النور للحفلات',    'location_name' => 'جدة - الزهراء',    'latitude' => 21.5610000, 'longitude' => 39.1590000, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount',     'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => false],

            // ── القاعات الملكية للمناسبات (2 وحدة) ────────────────────────────
            ['department_id' => $dep('القاعات الملكية للمناسبات'), 'type' => 'hall', 'name' => 'القاعة الملكية الكبرى',  'location_name' => 'جدة - الأندلس', 'latitude' => 21.5800000, 'longitude' => 39.1900000, 'refund_policy' => 'strict',   'reservation_deposit' => true, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 1000, 'insurance' => true, 'insurance_amount' => 2000, 'families_and_singles' => false],
            ['department_id' => $dep('القاعات الملكية للمناسبات'), 'type' => 'hall', 'name' => 'قاعة الولائم الملكية', 'location_name' => 'جدة - النسيم',  'latitude' => 21.5850000, 'longitude' => 39.1950000, 'refund_policy' => 'moderate', 'reservation_deposit' => true, 'reservation_deposit_type' => 'percentage', 'reservation_deposit_amount' => 20, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => true],

            // ── قاعات المدينة (2 وحدة) ─────────────────────────────────────
            ['department_id' => $dep('قاعات المدينة'), 'type' => 'hall', 'name' => 'قاعة المدينة للحفلات', 'location_name' => 'المدينة المنورة - الحرم',    'latitude' => 24.4686600, 'longitude' => 39.6142100, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => false],
            ['department_id' => $dep('قاعات المدينة'), 'type' => 'hall', 'name' => 'قاعة المدينة للمناسبات',   'location_name' => 'المدينة المنورة - العزيزية',     'latitude' => 24.4720000, 'longitude' => 39.6180000, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 300, 'insurance' => true, 'insurance_amount' => 500, 'families_and_singles' => true],

            // ── Relax Lounges (3 units) ────────────────────────────────────────
            ['department_id' => $dep('صالات الاسترخاء'), 'type' => 'lounge', 'name' => 'صالة اللؤلؤة',        'location_name' => 'جدة - الكورنيش',    'latitude' => 21.5788000, 'longitude' => 39.1745000, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 200, 'insurance' => true,  'insurance_amount' => 500, 'families_and_singles' => false],
            ['department_id' => $dep('صالات الاسترخاء'), 'type' => 'lounge', 'name' => 'صالة الواحة',        'location_name' => 'جدة - الشاطئ',    'latitude' => 21.5840000, 'longitude' => 39.1810000, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => true],
            ['department_id' => $dep('صالات الاسترخاء'), 'type' => 'lounge', 'name' => 'صالة فيلا الغروب', 'location_name' => 'جدة - المرجان',   'latitude' => 21.5900000, 'longitude' => 39.1880000, 'refund_policy' => 'strict',   'reservation_deposit' => true,  'reservation_deposit_type' => 'percentage', 'reservation_deposit_amount' => 40, 'insurance' => true, 'insurance_amount' => 800, 'families_and_singles' => false],

            // ── صالات سيرينيتي الخاصة (2 وحدة) ────────────────────────────
            ['department_id' => $dep('صالات سيرينيتي الخاصة'), 'type' => 'lounge', 'name' => 'حديقة سيرينيتي',  'location_name' => 'الرياض - النخيل', 'latitude' => 24.6893000, 'longitude' => 46.6880000, 'refund_policy' => 'moderate', 'reservation_deposit' => true, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 300, 'insurance' => true, 'insurance_amount' => 600, 'families_and_singles' => false],
            ['department_id' => $dep('صالات سيرينيتي الخاصة'), 'type' => 'lounge', 'name' => 'سطح سيرينيتي', 'location_name' => 'الرياض - العليا',   'latitude' => 24.6950000, 'longitude' => 46.6910000, 'refund_policy' => 'flexible', 'reservation_deposit' => true, 'reservation_deposit_type' => 'percentage', 'reservation_deposit_amount' => 25, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => true],

            // ── Desert Camps (3 units) ─────────────────────────────────────────
            ['department_id' => $dep('مخيمات الصحراء'), 'type' => 'camp', 'name' => 'مخيم الصفا الصحراوي',   'location_name' => 'ضواحي الرياض',     'latitude' => 24.4256000, 'longitude' => 46.3147000, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => false],
            ['department_id' => $dep('مخيمات الصحراء'), 'type' => 'camp', 'name' => 'مخيم الصحراء الليلي',     'location_name' => 'الرياض - جنوب شرق',  'latitude' => 24.3900000, 'longitude' => 46.3600000, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 100, 'insurance' => true, 'insurance_amount' => 200, 'families_and_singles' => true],
            ['department_id' => $dep('مخيمات الصحراء'), 'type' => 'camp', 'name' => 'مخيم النجوم البدوي', 'location_name' => 'الرياض - الثمامة',  'latitude' => 24.5100000, 'longitude' => 46.8300000, 'refund_policy' => 'strict',   'reservation_deposit' => true,  'reservation_deposit_type' => 'percentage', 'reservation_deposit_amount' => 50, 'insurance' => true, 'insurance_amount' => 300, 'families_and_singles' => false],

            // ── Mountain View Camps (2 units) ──────────────────────────────────
            ['department_id' => $dep('مخيمات جبال الطائف'), 'type' => 'camp', 'name' => 'مخيم جبال الطائف أ', 'location_name' => 'الطائف - الهدا',  'latitude' => 21.2854000, 'longitude' => 40.4150000, 'refund_policy' => 'flexible', 'reservation_deposit' => false, 'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => null, 'insurance' => false, 'insurance_amount' => null, 'families_and_singles' => false],
            ['department_id' => $dep('مخيمات جبال الطائف'), 'type' => 'camp', 'name' => 'مخيم جبال الطائف ب', 'location_name' => 'الطائف - الشفا', 'latitude' => 21.3100000, 'longitude' => 40.4350000, 'refund_policy' => 'moderate', 'reservation_deposit' => true,  'reservation_deposit_type' => 'amount', 'reservation_deposit_amount' => 150, 'insurance' => true, 'insurance_amount' => 250, 'families_and_singles' => true],
        ];

        $typeLabelsAr = [
            'stadium' => 'ملعب',
            'hall' => 'قاعة',
            'lounge' => 'صالة',
            'camp' => 'مخيم',
        ];

        // Maps each venue's location_name (already Arabic, e.g. "الرياض -
        // الملقا") to the matching canonical city key from
        // config/saudi_cities.php, without needing to edit all 24 array
        // literals above individually.
        $cityFromLocation = function (string $locationName): ?string {
            $map = [
                'الرياض' => 'riyadh',
                'جدة' => 'jeddah',
                'الدمام' => 'dammam',
                'المدينة المنورة' => 'madinah',
                'الطائف' => 'taif',
            ];
            foreach ($map as $needle => $cityKey) {
                if (str_contains($locationName, $needle)) {
                    return $cityKey;
                }
            }

            return null;
        };

        // Old boolean true/false values (still present in $data above from
        // the original array literals) are superseded here with genuinely
        // varied tri-state values — a straight true→'both'/false→null
        // conversion would give every venue one of only 2 states, with no
        // real variety to exercise the corrected families/singles/both
        // filter against. Cycles evenly through all 3 states plus
        // "unspecified" across the 24 venues.
        $familiesAndSinglesCycle = ['families', 'singles', 'both', null];

        foreach ($unites as $i => $data) {
            Unite::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'city' => $cityFromLocation($data['location_name'] ?? ''),
                    'families_and_singles' => $familiesAndSinglesCycle[$i % count($familiesAndSinglesCycle)],
                    'description' => 'وحدة '.($typeLabelsAr[$data['type']] ?? $data['type']).' مميزة — '.$data['location_name'].'. مجهزة بالكامل بأحدث المرافق.',
                    'additional_terms' => 'يتم تأكيد الحجز فقط بعد دفع العربون. لا يُسمح بتقديم ضيافة خارجية.',
                    'status' => 'active',
                ])
            );
        }
    }
}
