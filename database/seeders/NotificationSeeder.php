<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('type', 'customer')->take(5)->get();

        if ($users->isEmpty()) {
            $this->command->warn('NotificationSeeder: no customers found — skipping.');

            return;
        }

        $samples = [
            [
                'type' => 'reservation_confirmed',
                'title' => 'تم تأكيد الحجز',
                'body' => 'تم تأكيد حجزك في قاعة النور بتاريخ 20 أغسطس 2026.',
                'data' => ['type' => 'reservation_confirmed', 'reservation_id' => 1, 'unite_name' => 'قاعة النور', 'reservation_date' => '2026-08-20', 'period_type' => 'evening', 'amount' => 960.00],
            ],
            [
                'type' => 'promotion',
                'title' => 'عرض الصيف — خصم 20%',
                'body' => 'احجز أي وحدة خلال شهر أغسطس ووفّر 20%. استخدم الرمز SUMMER20.',
                'data' => ['type' => 'promotion', 'title' => 'عرض الصيف', 'body' => 'احجز أي وحدة خلال شهر أغسطس ووفّر 20%.', 'promo_code' => 'SUMMER20'],
            ],
            [
                'type' => 'reservation_cancelled',
                'title' => 'تم إلغاء الحجز',
                'body' => 'تم إلغاء حجزك. سيتم رد المبلغ خلال 7 أيام.',
                'data' => ['type' => 'reservation_cancelled', 'reservation_id' => 2, 'unite_name' => 'ملعب النور', 'refund_amount' => 480.00],
            ],
        ];

        foreach ($users as $user) {
            foreach ($samples as $n) {
                // Insert directly into notifications table (faster than dispatching)
                DB::table('notifications')->insertOrIgnore([
                    'id' => Str::uuid(),
                    'type' => 'App\\Notifications\\'.match ($n['type']) {
                        'reservation_confirmed' => 'ReservationConfirmed',
                        'promotion' => 'PromotionNotification',
                        'reservation_cancelled' => 'ReservationCancelled',
                        default => 'PromotionNotification',
                    },
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode($n['data']),
                    'read_at' => null,
                    'created_at' => now()->subDays(rand(1, 14)),
                    'updated_at' => now()->subDays(rand(1, 14)),
                ]);
            }
        }
    }
}
