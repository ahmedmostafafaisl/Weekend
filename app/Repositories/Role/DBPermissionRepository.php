<?php

namespace App\Repositories\Role;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\PermissionRepositoryInterface;

class DBPermissionRepository implements PermissionRepositoryInterface
{
    private string $guard = 'admin';

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Permission::query()
            ->where('guard_name', $this->guard)
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Permission
    {
        return Permission::where('guard_name', $this->guard)->findOrFail($id);
    }

    public function create(array $data): Permission
    {
        return DB::transaction(function () use ($data) {
            $permission = Permission::create([
                'name' => $data['name'],
                'guard_name' => $this->guard,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return $permission;
        });
    }

    public function update(int $id, array $data): Permission
    {
        return DB::transaction(function () use ($id, $data) {
            $permission = $this->findOrFail($id);
            $permission->update(['name' => $data['name']]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return $permission;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $permission = $this->findOrFail($id);
            $permission->delete();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
