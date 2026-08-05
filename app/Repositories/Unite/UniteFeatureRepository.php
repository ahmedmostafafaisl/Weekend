<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UniteFeature;
use App\Repositories\Interfaces\UniteFeatureInterface;
use Illuminate\Support\Collection;

class UniteFeatureRepository implements UniteFeatureInterface
{
    public function allByUnite(Unite $unite): Collection
    {
        return $unite->features()->latest()->get();
    }

    public function findByUnite(Unite $unite, int $featureId): ?UniteFeature
    {
        return $unite->features()->where('id', $featureId)->first();
    }

    public function createForUnite(Unite $unite, array $data): UniteFeature
    {
        return $unite->features()->create($data);
    }

    public function updateForUnite(Unite $unite, int $featureId, array $data): UniteFeature
    {
        $feature = $unite->features()->where('id', $featureId)->firstOrFail();
        $feature->update($data);

        return $feature->fresh();
    }

    public function deleteForUnite(Unite $unite, int $featureId): bool
    {
        $feature = $unite->features()->where('id', $featureId)->firstOrFail();

        return (bool) $feature->delete();
    }
}
