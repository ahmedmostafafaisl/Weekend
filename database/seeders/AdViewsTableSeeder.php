<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdView;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdViewsTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->values();
        $ads = Ad::pluck('id')->values();

        if ($users->isEmpty() || $ads->isEmpty()) {
            return;
        }

        foreach ($ads as $aIndex => $adId) {
            foreach ($users as $uIndex => $userId) {
                // نخلي بعض الإعلانات فقط viewed لبعض المستخدمين
                if ((($aIndex + $uIndex) % 2) === 0) {
                    AdView::updateOrCreate(
                        [
                            'ad_id' => $adId,
                            'user_id' => $userId,
                        ],
                        [
                            'seen_at' => now()->subMinutes(($aIndex + 1) * ($uIndex + 1)),
                        ]
                    );
                }
            }
        }
    }
}
