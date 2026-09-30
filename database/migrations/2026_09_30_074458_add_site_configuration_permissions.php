<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $modules = ['app_settings', 'homepage'];

    private array $actions = ['view', 'create', 'update', 'delete'];

    public function up(): void
    {
        if (! class_exists(Permission::class) || ! \Illuminate\Support\Facades\Schema::hasTable('permissions')) {
            return;
        }

        $guard = 'admin';
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = [];
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                $created[$action][] = Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => $guard]);
            }
        }

        $grants = [
            'super_admin' => ['view', 'create', 'update', 'delete'],
            'admin' => ['view', 'create', 'update'],   // everything except delete
            'manager' => ['view', 'create', 'update'],
            'viewer' => ['view'],
            // reviewer: intentionally nothing — reservations.view only
        ];

        foreach ($grants as $roleName => $actions) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if (! $role) {
                continue;
            }
            foreach ($actions as $action) {
                $role->givePermissionTo($created[$action]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ($this->modules as $module) {
            Permission::where('name', 'like', "{$module}.%")->where('guard_name', 'admin')->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
