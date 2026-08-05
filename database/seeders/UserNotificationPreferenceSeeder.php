<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Database\Seeder;

class UserNotificationPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $types = array_keys(UserNotificationPreference::TYPES);

        User::all()->each(function (User $user) use ($types) {
            // Not every user has customized every preference — real users
            // who never opened the settings screen rely on the model's
            // default-true behavior. Simulate that by only seeding rows for
            // roughly 70% of users, and even then only for a subset of types.
            if (rand(1, 10) > 7) {
                return;
            }

            $customizedTypes = collect($types)->random(rand(3, count($types)));

            foreach ($customizedTypes as $type) {
                // Most users leave push on; email is more commonly turned off
                // (mirrors typical real-world notification-settings behavior).
                UserNotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'type' => $type],
                    [
                        'push_enabled' => rand(1, 10) > 1,   // ~90% keep push on
                        'email_enabled' => rand(1, 10) > 6,  // ~40% keep email on
                    ]
                );
            }

            // Guarantee at least one fully-disabled type per customized user,
            // so the "muted" case is represented too.
            UserNotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'promotion'],
                ['push_enabled' => false, 'email_enabled' => false]
            );
        });
    }
}
