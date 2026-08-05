<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdsTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->values();
        $unites = Unite::pluck('id')->values();

        if ($users->isEmpty()) {
            return;
        }

        $ads = [];

        $adTitles = ['عرض خاص', 'خصم لفترة محدودة', 'إعلان مميز', 'عرض اليوم', 'فرصة لا تعوض'];

        // Real targeting variety across the 30 normal ads, cycling through
        // 4 distinct combinations, so the gender/city ad-filtering logic
        // (AdRepository::allActive() + Ad::scopeForUser()) has something
        // genuinely different to filter on instead of every ad silently
        // sharing the same untargeted 'both'/null-city defaults.
        $targetingCycle = [
            ['target_audience' => 'men', 'target_user_type' => 'all', 'city' => 'riyadh'],
            ['target_audience' => 'women', 'target_user_type' => 'all', 'city' => 'jeddah'],
            ['target_audience' => 'both', 'target_user_type' => 'customers', 'city' => 'dammam'],
            ['target_audience' => 'both', 'target_user_type' => 'all', 'city' => null],
        ];

        // 30 normal ads
        for ($i = 1; $i <= 30; $i++) {
            $targeting = $targetingCycle[$i % count($targetingCycle)];

            $ads[] = [
                'user_id' => $users[($i - 1) % $users->count()],
                'property_id' => null,
                'type' => 'ad',
                'title' => $adTitles[$i % count($adTitles)].' '.$i,
                'description' => 'هذا محتوى إعلاني رقم '.$i,
                'thumbnail' => 'Ads/sample_thumb_'.$i.'.jpg',
                'media' => 'Ads/sample_media_'.$i.'.jpg',
                'is_active' => $i % 5 !== 0,
                'activated_at' => $i % 5 !== 0 ? now()->subHours(rand(1, 12)) : null,
                'expires_at' => $i % 5 !== 0 ? now()->addHours(rand(1, 24)) : null,
                'target_audience' => $targeting['target_audience'],
                'target_user_type' => $targeting['target_user_type'],
                'city' => $targeting['city'],
            ];
        }

        // 20 property ads
        if ($unites->isNotEmpty()) {
            for ($i = 1; $i <= 20; $i++) {
                $ads[] = [
                    'user_id' => $users[($i - 1) % $users->count()],
                    'property_id' => $unites[($i - 1) % $unites->count()],
                    'type' => 'property',
                    'title' => 'وحدة مميزة '.$i,
                    'description' => 'إعلان لوحدة مميزة رقم '.$i,
                    'thumbnail' => 'Ads/property_thumb_'.$i.'.jpg',
                    'media' => 'Ads/property_media_'.$i.'.jpg',
                    'is_active' => true,
                    'activated_at' => now()->subHours(rand(1, 10)),
                    'expires_at' => now()->addHours(rand(2, 24)),
                ];
            }
        }

        foreach ($ads as $ad) {
            Ad::updateOrCreate(
                [
                    'user_id' => $ad['user_id'],
                    'title' => $ad['title'],
                ],
                $ad
            );
        }
    }
}
