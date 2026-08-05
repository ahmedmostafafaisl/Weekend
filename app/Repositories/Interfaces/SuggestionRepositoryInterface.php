<?php

namespace App\Repositories\Interfaces;

use App\Models\Suggestion;

interface SuggestionRepositoryInterface
{
    public function all();

    public function find($id);

    public function create(array $data): Suggestion;

    public function update($id, array $data): bool;

    public function delete($id): bool;

    public function getByUser($userId);
}
