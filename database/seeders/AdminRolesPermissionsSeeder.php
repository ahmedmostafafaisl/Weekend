<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminRolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'admin';

        $modules = [
            // ── Core platform ─────────────────────────────────────────────────
            'dashboard',
            'admins',
            'users',
            'user_profiles',
            'roles',
            'permissions',

            // ── Venue management ──────────────────────────────────────────────
            'departments',
            'unites',
            'units',
            'unite_offers',
            'unite_slots',
            'unite_prices',
            'unite_packages',
            'unite_features',
            'unite_details',

            // ── Packages & subscriptions ──────────────────────────────────────
            'property_packages',
            'ad_packages',
            'subscriptions',

            // ── Ads ───────────────────────────────────────────────────────────
            'ads',
            'ad_comments',

            // ── Reservations & payments ───────────────────────────────────────
            'reservations',
            'unite_viewings',
            'payments',
            'promo_codes',
            'service_fees',

            // ── Fund transfers (new) ──────────────────────────────────────────
            'transfer_policies',
            'provider_transfers',
            'transfer_requests',

            // ── Supporting content ────────────────────────────────────────────
            'insurance_policies',
            'stadium_types',
            'service_groups',
            'services',
            'suggestions',
            'notifications',
        ];

        $actions = ['view', 'create', 'update', 'delete'];

        $allPermissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => $guard,
                ]);
                $allPermissions[] = $permission;
            }
        }

        // 'review' isn't one of the standard CRUD actions above — added
        // explicitly. admin's syncPermissions() below already includes it
        // automatically (its pattern is "not like %.delete", which this
        // matches), so only super_admin needs the explicit addition here.
        $allPermissions[] = Permission::firstOrCreate([
            'name' => 'ads.review',
            'guard_name' => $guard,
        ]);

        // ── Roles ─────────────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $manager = Role::firstOrCreate(['name' => 'manager',     'guard_name' => $guard]);
        $viewer = Role::firstOrCreate(['name' => 'viewer',      'guard_name' => $guard]);
        $reviewer = Role::firstOrCreate(['name' => 'reviewer',    'guard_name' => $guard]);

        // super_admin → all permissions
        $superAdmin->syncPermissions($allPermissions);

        // admin → everything except delete
        $admin->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', 'not like', '%.delete')
                ->get()
        );

        // manager → view + create + update
        $manager->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where(fn ($q) => $q
                    ->where('name', 'like', '%.view')
                    ->orWhere('name', 'like', '%.create')
                    ->orWhere('name', 'like', '%.update')
                )->get()
        );

        // viewer → view only
        $viewer->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', 'like', '%.view')
                ->get()
        );

        // reviewer → reservations.view only (venue restriction via admin_reviewer_scopes table)
        $reviewer->syncPermissions([
            Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => $guard]),
        ]);

        // ── Admin accounts ────────────────────────────────────────────────────
        $accounts = [
            ['email' => 'superadmin@dashboard.com', 'name' => 'المدير العام',  'role' => 'super_admin'],
            ['email' => 'admin@dashboard.com',      'name' => 'المدير',        'role' => 'admin'],
            ['email' => 'manager@dashboard.com',    'name' => 'المشرف',      'role' => 'manager'],
            ['email' => 'viewer@dashboard.com',     'name' => 'المشاهد',       'role' => 'viewer'],
            ['email' => 'admin1@example.com',       'name' => 'المدير الأول',    'role' => 'admin'],
            ['email' => 'admin2@example.com',       'name' => 'المدير الثاني',    'role' => 'admin'],
        ];

        foreach ($accounts as $acc) {
            $user = Admin::firstOrCreate(
                ['email' => $acc['email']],
                ['name' => $acc['name'], 'password' => Hash::make('123456789'), 'email_verified_at' => now()]
            );
            $user->syncRoles([$acc['role']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
