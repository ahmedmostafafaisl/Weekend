<?php

namespace App\Repositories\InsurancePolicy;

use App\Models\InsurancePolicy;
use App\Repositories\Interfaces\InsurancePolicyRepositoryInterface;

class InsurancePolicyRepository implements InsurancePolicyRepositoryInterface
{
    public function all($perPage = 10, $page = 1)
    {
        return InsurancePolicy::orderBy('created_at', 'desc')
            ->get();
    }

    public function find($id)
    {
        return InsurancePolicy::findOrFail($id);
    }

    public function create(array $data): InsurancePolicy
    {
        return InsurancePolicy::create($data);
    }

    public function update($id, array $data): bool
    {
        return InsurancePolicy::findOrFail($id)->update($data);
    }

    public function delete($id): bool
    {
        return InsurancePolicy::findOrFail($id)->delete();
    }
}
