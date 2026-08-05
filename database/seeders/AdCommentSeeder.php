<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdCommentSeeder extends Seeder
{
    public function run(): void
    {
        $ads = Ad::all();
        $users = User::where('type', 'customer')->get();

        if ($ads->isEmpty() || $users->isEmpty()) {
            $this->command->warn('AdCommentSeeder: no ads or users found — skipping.');

            return;
        }

        $comments = [
            'وحدة رائعة، أنصح بها بشدة!',
            'حجزت الأسبوع الماضي وكانت تجربة مثالية.',
            'خدمة محترفة جدًا.',
            'سأحجز هنا مرة أخرى بالتأكيد.',
            'مرافق ممتازة وأسعار مناسبة.',
            'تجربة رائعة لمناسبة عائلتنا.',
            'واسعة ومُجهزة بشكل جيد.',
            'الطاقم كان متعاونًا جدًا.',
            'موقع جيد وسهل الوصول إليه.',
            'أفضل قيمة مقابل السعر في المنطقة.',
        ];

        foreach ($ads->take(10) as $i => $ad) {
            // 2–4 visible comments per ad
            $count = rand(2, 4);
            for ($j = 0; $j < $count; $j++) {
                $user = $users[($i + $j) % $users->count()];
                AdComment::firstOrCreate(
                    ['ad_id' => $ad->id, 'user_id' => $user->id, 'body' => $comments[($i + $j) % count($comments)]],
                    ['is_visible' => $j < $count - 1] // last comment hidden (to demo toggle feature)
                );
            }
        }
    }
}
