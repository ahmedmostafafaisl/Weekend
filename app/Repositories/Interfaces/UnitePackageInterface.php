<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use App\Models\UnitePackage;
use Illuminate\Support\Collection;

interface UnitePackageInterface
{
    public function allByUnite(Unite $unite): Collection;

    public function findByUnite(Unite $unite, int $packageId): ?UnitePackage;

    public function createForUnite(Unite $unite, array $data): UnitePackage;

    public function updateForUnite(Unite $unite, int $packageId, array $data): UnitePackage;

    public function deleteForUnite(Unite $unite, int $packageId): bool;
}
