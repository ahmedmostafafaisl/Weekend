<?php

namespace App\Repositories\Role;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DBRoleRepository implements RoleRepositoryInterface
{
    private string $guard = 'admin';

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Role::query()
            ->where('guard_name', $this->guard)
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Role
    {
        return Role::where('guard_name', $this->guard)->findOrFail($id);
    }
    private function filterPermissionIds(array $ids): array
    {
        return Permission::where('guard_name', $this->guard)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();
    }

   public function create(array $data): Role
{
    return DB::transaction(function () use ($data) {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $this->guard,
        ]);

        $ids = $data['permission_ids'] ?? [];
        $ids = $this->filterPermissionIds($ids);

        // ✅ Convert IDs to Permission Models (same guard)
        $permissions = Permission::query()
            ->where('guard_name', $this->guard)
            ->whereIn('id', $ids)
            ->get();

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->load('permissions');
    });
}

public function update(int $id, array $data): Role
{
    return DB::transaction(function () use ($id, $data) {
        $role = Role::where('guard_name', $this->guard)->findOrFail($id);

        $role->update([
            'name' => $data['name'],
        ]);

        if (array_key_exists('permission_ids', $data)) {
            $ids = $data['permission_ids'] ?? [];
            $ids = $this->filterPermissionIds($ids);

            // ✅ Convert IDs to Permission Models (same guard)
            $permissions = Permission::query()
                ->where('guard_name', $this->guard)
                ->whereIn('id', $ids)
                ->get();

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->load('permissions');
    });
}

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $role = $this->findOrFail($id);
            $role->delete();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function syncPermissions(int $roleId, array $permissionIds): Role
    {
        return DB::transaction(function () use ($roleId, $permissionIds) {
            $role = $this->findOrFail($roleId);
            $role->syncPermissions($permissionIds);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return $role->load('permissions');
        });
    }
}
