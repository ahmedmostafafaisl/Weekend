<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

interface PermissionRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator;
    public function findOrFail(int $id): Permission;

    public function create(array $data): Permission;
    public function update(int $id, array $data): Permission;
    public function delete(int $id): void;
}
