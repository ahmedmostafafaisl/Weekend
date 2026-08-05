<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\AdminReviewerScope;
use App\Models\Unite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminReviewerScopeSeeder extends Seeder
{
    public function run(): void
    {
        // No admin account currently carries the 'reviewer' role in the base
        // seeders — create 3 so all 3 scope modes have a real account to
        // attach to (mirrors the reviewers admin UI: "All venues" / "By type"
        // / "Specific venues" as described in admin/reviewers).
        $reviewers = [
            ['name' => 'مراجع — جميع الوحدات',    'email' => 'reviewer.all@dashboard.com'],
            ['name' => 'مراجع — القاعات فقط',    'email' => 'reviewer.halls@dashboard.com'],
            ['name' => 'مراجع — وحدة محددة', 'email' => 'reviewer.specific@dashboard.com'],
        ];

        $accounts = [];
        foreach ($reviewers as $r) {
            $admin = Admin::updateOrCreate(
                ['email' => $r['email']],
                ['name' => $r['name'], 'password' => Hash::make('123456789'), 'email_verified_at' => now()]
            );
            $admin->syncRoles(['reviewer']);
            $accounts[] = $admin;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Case 1 — "All venues" — no restriction at all (unite_type & unite_id both null)
        AdminReviewerScope::updateOrCreate([
            'admin_id' => $accounts[0]->id,
            'unite_type' => null,
            'unite_id' => null,
        ]);

        // Case 2 — "By type" — restricted to a single venue type, any venue of that type
        foreach (['hall', 'stadium'] as $type) {
            AdminReviewerScope::updateOrCreate([
                'admin_id' => $accounts[1]->id,
                'unite_type' => $type,
                'unite_id' => null,
            ]);
        }

        // Case 3 — "Specific venues" — restricted to exact venue IDs, type null
        $specificUnites = Unite::inRandomOrder()->limit(3)->pluck('id');
        foreach ($specificUnites as $uniteId) {
            AdminReviewerScope::updateOrCreate([
                'admin_id' => $accounts[2]->id,
                'unite_type' => null,
                'unite_id' => $uniteId,
            ]);
        }
    }
}
