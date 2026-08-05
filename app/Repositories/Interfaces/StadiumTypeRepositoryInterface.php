<?php

namespace App\Repositories\Interfaces;

use App\Models\StadiumType;

interface StadiumTypeRepositoryInterface
{
    public function all();

    public function find($id);

    public function create(array $data): StadiumType;

    public function update($id, array $data): bool;

    public function delete($id): bool;
}
