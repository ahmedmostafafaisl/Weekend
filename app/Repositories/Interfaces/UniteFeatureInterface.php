<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use App\Models\UniteFeature;
use Illuminate\Support\Collection;

interface UniteFeatureInterface
{
    public function allByUnite(Unite $unite): Collection;

    public function findByUnite(Unite $unite, int $featureId): ?UniteFeature;

    public function createForUnite(Unite $unite, array $data): UniteFeature;

    public function updateForUnite(Unite $unite, int $featureId, array $data): UniteFeature;

    public function deleteForUnite(Unite $unite, int $featureId): bool;
}
