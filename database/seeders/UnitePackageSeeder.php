<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UnitePackage;
use Illuminate\Database\Seeder;

class UnitePackageSeeder extends Seeder
{
    public function run(): void
    {
        // Base capacity/price scale differs meaningfully by venue type — a
        // camp's "Gold" package is nowhere near a stadium's in scale.
        $scaleByType = [
            'stadium' => ['base_capacity' => 40, 'base_price' => 150],
            'hall' => ['base_capacity' => 100, 'base_price' => 800],
            'lounge' => ['base_capacity' => 20, 'base_price' => 400],
            'camp' => ['base_capacity' => 15, 'base_price' => 300],
        ];

        $tiers = [
            ['name' => 'الباقة البرونزية', 'multiplier' => 1.0],
            ['name' => 'الباقة الفضية', 'multiplier' => 1.6],
            ['name' => 'الباقة الذهبية', 'multiplier' => 2.4],
        ];

        // BUG FIX: skip stadiums — packages are no longer applicable to
        // this venue type at all (hidden from both the dashboard and the
        // API response), so seeding them here would just create orphaned
        // rows nothing ever displays.
        Unite::where('type', '!=', 'stadium')->get()->each(function (Unite $unite) use ($scaleByType, $tiers) {
            $scale = $scaleByType[$unite->type] ?? $scaleByType['hall'];

            // Not every venue offers all 3 tiers — most offer 2 or 3.
            $tierCount = rand(2, 3);

            foreach (array_slice($tiers, 0, $tierCount) as $tier) {
                $menCapacity = (int) round($scale['base_capacity'] * $tier['multiplier']);
                $womenCapacity = (int) round($menCapacity * (rand(70, 100) / 100));
                $price = round($scale['base_price'] * $tier['multiplier'] * (rand(90, 110) / 100), 2);

                UnitePackage::updateOrCreate(
                    ['unite_id' => $unite->id, 'name' => $tier['name']],
                    [
                        'men_capacity' => $menCapacity,
                        'women_capacity' => $womenCapacity,
                        'price' => $price,
                    ]
                );
            }
        });
    }
}
