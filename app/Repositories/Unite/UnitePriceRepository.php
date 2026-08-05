<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UnitePrice;
use App\Repositories\Interfaces\UnitePriceInterface;
use Illuminate\Support\Collection;

class UnitePriceRepository implements UnitePriceInterface
{
    public function allByUnite(Unite $unite): Collection
    {
        return $unite->prices()->latest()->get();
    }

    public function findByUnite(Unite $unite, int $priceId): ?UnitePrice
    {
        return $unite->prices()->where('id', $priceId)->first();
    }

    public function createForUnite(Unite $unite, array $data): UnitePrice
    {
        return $unite->prices()->create($data);
    }

    public function updateForUnite(Unite $unite, int $priceId, array $data): UnitePrice
    {
        $price = $unite->prices()->where('id', $priceId)->firstOrFail();
        $price->update($data);

        return $price->fresh();
    }

    public function deleteForUnite(Unite $unite, int $priceId): bool
    {
        $price = $unite->prices()->where('id', $priceId)->firstOrFail();

        return (bool) $price->delete();
    }
}
