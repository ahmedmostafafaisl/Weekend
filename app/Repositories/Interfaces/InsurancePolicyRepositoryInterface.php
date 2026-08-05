<?php

namespace App\Repositories\Interfaces;

use App\Models\InsurancePolicy;

interface InsurancePolicyRepositoryInterface
{
    public function all();

    public function find($id);

    public function create(array $data): InsurancePolicy;

    public function update($id, array $data): bool;

    public function delete($id): bool;
}
