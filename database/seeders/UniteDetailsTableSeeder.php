<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteDetail;
use Illuminate\Database\Seeder;

/**
 * Updated for the unite_details consolidation — same seed data as before,
 * now written through the single UniteDetail model instead of 4 separate
 * StadiumDetail/HallDetail/LoungeDetail/CampDetail classes.
 */
class UniteDetailsTableSeeder extends Seeder
{
    public function run(): void
    {
        // ── Stadiums ──────────────────────────────────────────────────────────
        $stadiumConfigs = [
            ['5x5', 'داخلي',  '20', '40', true,  true],
            ['7x7', 'خارجي', '35', '55', false, true],
            ['5x5', 'خارجي', '22', '44', false, false],
            ['7x7', 'داخلي',  '40', '60', true,  true],
            ['5x5', 'داخلي',  '20', '38', true,  false],
            ['7x7', 'خارجي', '38', '58', false, true],
            ['5x5', 'خارجي', '25', '45', true,  true],
        ];

        foreach (Unite::where('type', 'stadium')->get() as $i => $unite) {
            $c = $stadiumConfigs[$i % count($stadiumConfigs)];
            UniteDetail::updateOrCreate(['unite_id' => $unite->id], [
                'customize_Category' => $c[0],
                'customize_Place' => $c[1],
                'width' => $c[2],
                'length' => $c[3],
                'cafeteria' => $c[4],
                'amenities' => $c[5],
            ]);
        }

        // ── Halls ─────────────────────────────────────────────────────────────
        $hallConfigs = [
            [500, 250, 250],
            [800, 400, 400],
            [350, 180, 170],
            [1200, 600, 600],
            [600, 300, 300],
            [700, 350, 350],
        ];

        foreach (Unite::where('type', 'hall')->get() as $i => $unite) {
            $c = $hallConfigs[$i % count($hallConfigs)];
            $capacity = $c[0];
            UniteDetail::updateOrCreate(['unite_id' => $unite->id], [
                'max_capacity' => $capacity,
                'max_chairs' => $capacity,
                'max_tables' => intval($capacity / 10),
                'kusha' => $i % 2 === 0,
                'buffet' => true,
                'buffet_details' => 'Full open buffet service with international and local cuisine.',

                'women_seating' => true,
                'women_seating_capacity' => $c[1],
                'women_tables_count' => intval($c[1] / 10),
                'women_chairs_count' => $c[1],
                'women_seating_details' => 'Private women section with dedicated entrance and full privacy.',
                'women_buffet' => true,
                'women_buffet_details' => 'Women section buffet with dessert station.',

                'men_seating_available' => true,
                'men_seating_capacity' => $c[2],
                'men_tables_count' => intval($c[2] / 10),
                'men_chairs_count' => $c[2],
                'men_seating_details' => 'Separate men section with outdoor lounge access.',
                'men_buffet' => $i % 3 !== 0,
                'men_buffet_details' => 'Men section buffet on request.',
            ]);
        }

        // ── Lounges ───────────────────────────────────────────────────────────
        $loungeConfigs = [
            [600, 4, 2, 4, 3, true,  true,  true,  true],
            [400, 3, 2, 3, 2, false, true,  false, true],
            [800, 5, 2, 6, 4, true,  true,  true,  false],
            [500, 4, 1, 4, 3, true,  false, true,  true],
            [350, 2, 1, 2, 2, false, true,  false, false],
        ];

        foreach (Unite::where('type', 'lounge')->get() as $i => $unite) {
            $c = $loungeConfigs[$i % count($loungeConfigs)];
            UniteDetail::updateOrCreate(['unite_id' => $unite->id], [
                'area' => $c[0],
                'bedroom_number' => $c[1],
                'big_bed' => $c[2],
                'single_bed' => $c[1] * 2,
                'bathroom_number' => $c[3],
                'council' => $c[4] > 0,
                'council_number' => $c[4],
                'pool' => $c[5],
                'kitchen' => $c[6],
                'customize_Place' => $i % 2 === 0 ? 'إطلالة بحرية' : 'إطلالة حدائقية',
            ]);
        }

        // ── Camps ─────────────────────────────────────────────────────────────
        $campConfigs = [
            [30, 60, 40, true,  true,  2],
            [25, 50, 30, false, true,  1],
            [35, 70, 50, true,  false, 3],
            [20, 40, 25, true,  true,  2],
            [40, 80, 60, false, true,  1],
        ];

        foreach (Unite::where('type', 'camp')->get() as $i => $unite) {
            $c = $campConfigs[$i % count($campConfigs)];
            UniteDetail::updateOrCreate(['unite_id' => $unite->id], [
                'width' => $c[0],
                'length' => $c[1],
                'seating_capacity' => $c[2],
                'television' => $c[3],
                'fireplace' => $c[4],
                'bathroom_number' => $c[5],
            ]);
        }
    }
}
