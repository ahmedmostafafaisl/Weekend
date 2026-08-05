<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteRating;
use App\Models\User;
use Illuminate\Database\Seeder;

class UniteRatingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('type', 'customer')->get();
        $unites = Unite::all();

        if ($customers->isEmpty() || $unites->isEmpty()) {
            return;
        }

        $reviews = [
            'مكان ممتاز ونظيف جدًا.',
            'تجربة جيدة بشكل عام.',
            'موقع جيد وأسعار مناسبة.',
            'سأحجز هنا مرة أخرى.',
            'منظم جدًا ومريح.',
        ];

        foreach ($customers as $cIndex => $customer) {
            foreach ($unites as $uIndex => $unite) {
                // BUG FIX: the previous formula ((cIndex + uIndex) % 5) + 1
                // mathematically guaranteed every venue would average to
                // EXACTLY 3.0 — with a customer count that's a multiple of 5,
                // values 1..5 each occur exactly 4 times per venue regardless
                // of which venue it is. This made rating-based filtering,
                // sorting, and display completely non-differentiating: every
                // single venue looked identically "3 stars," no matter what
                // rating filter was applied. Each venue now gets its own
                // realistic target rating (2.0–5.0) derived from a hash of
                // its own ID — genuinely varied across venues — with small
                // per-customer variation layered on top, also hash-derived
                // so the whole thing stays fully deterministic across
                // re-seeds (same output every time db:seed runs) without
                // the old formula's collision problem.
                $tierHash = crc32('rating-tier-'.$unite->id) % 100;
                $baseRating = 2.0 + ($tierHash / 100) * 3.0; // 2.0 .. 5.0

                $noiseHash = crc32($customer->id.'-'.$unite->id) % 3;
                $variation = $noiseHash - 1; // -1, 0, or +1

                $ratingValue = max(1, min(5, (int) round($baseRating + $variation)));

                UniteRating::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'unite_id' => $unite->id,
                    ],
                    [
                        'rating' => $ratingValue,
                        'review' => $reviews[($cIndex + $uIndex) % count($reviews)],
                    ]
                );
            }
        }
    }
}
