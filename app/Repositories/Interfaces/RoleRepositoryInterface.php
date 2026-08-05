<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator;
    public function findOrFail(int $id): Role;

    public function create(array $data): Role;
    public function update(int $id, array $data): Role;
    public function delete(int $id): void;

    public function syncPermissions(int $roleId, array $permissionIds): Role;
}
