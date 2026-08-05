<?php

namespace App\Repositories\Interfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\User;

interface UserInterface
{
    public function register(array $data);
    public function login(array $data);
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator;
    public function find(int $id): User;
    public function create(array $data): User;
    public function update(int $id, array $data): User;
    public function delete(int $id): void;
}
