<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use Illuminate\Support\Collection;

interface UniteRepositoryInterface
{
    public function all(array $filters = []): Collection;

    /**
     * @return array{unites: Collection, min_price: ?float, max_price: ?float}
     */
    public function all2(array $filters = [], ?int $userId = null): array;

    public function find(int $id): ?Unite;

    public function create(array $data): Unite;

    public function update(Unite $unite, array $data): Unite;

    public function delete(int $id): bool;

    public function userFavorites(int $userId, array $filters = []);
}
