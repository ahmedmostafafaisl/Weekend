<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UniteDetail;
use App\Repositories\Interfaces\UniteDetailInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Simplified after consolidating HallDetail/StadiumDetail/LoungeDetail/
 * CampDetail into a single UniteDetail model. The previous version of this
 * repository branched on $unite->type to pick one of 4 model classes —
 * that branching no longer exists because there's only one model now.
 */
class UniteDetailRepository implements UniteDetailInterface
{
    public function findByUnite(Unite $unite): ?Model
    {
        return $unite->detail;
    }

    public function createForUnite(Unite $unite, array $data): Model
    {
        return UniteDetail::create(array_merge($data, [
            'unite_id' => $unite->id,
        ]));
    }

    public function updateForUnite(Unite $unite, array $data): Model
    {
        return UniteDetail::updateOrCreate(
            ['unite_id' => $unite->id],
            $data
        );
    }

    public function deleteForUnite(Unite $unite): bool
    {
        $detail = $this->findByUnite($unite);

        if (! $detail) {
            return false;
        }

        return (bool) $detail->delete();
    }
}
