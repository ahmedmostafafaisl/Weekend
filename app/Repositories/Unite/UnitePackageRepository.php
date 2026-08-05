<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UnitePackage;
use App\Repositories\Interfaces\UnitePackageInterface;
use Illuminate\Support\Collection;

class UnitePackageRepository implements UnitePackageInterface
{
    public function allByUnite(Unite $unite): Collection
    {
        return $unite->packages()->latest()->get();
    }

    public function findByUnite(Unite $unite, int $packageId): ?UnitePackage
    {
        return $unite->packages()->where('id', $packageId)->first();
    }

    public function createForUnite(Unite $unite, array $data): UnitePackage
    {
        return $unite->packages()->create($data);
    }

    public function updateForUnite(Unite $unite, int $packageId, array $data): UnitePackage
    {
        $package = $unite->packages()->where('id', $packageId)->firstOrFail();
        $package->update($data);

        return $package->fresh();
    }

    public function deleteForUnite(Unite $unite, int $packageId): bool
    {
        $package = $unite->packages()->where('id', $packageId)->firstOrFail();

        return (bool) $package->delete();
    }
}
