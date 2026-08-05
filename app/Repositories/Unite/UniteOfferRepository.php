<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UniteOffer;
use App\Repositories\Interfaces\UniteOfferInterface;
use Illuminate\Support\Collection;

class UniteOfferRepository implements UniteOfferInterface
{
    public function allByUnite(Unite $unite): Collection
    {
        return $unite->offers()->latest()->get();
    }

    public function findByUnite(Unite $unite, int $offerId): ?UniteOffer
    {
        return $unite->offers()->where('id', $offerId)->first();
    }

    public function createForUnite(Unite $unite, array $data): UniteOffer
    {
        return $unite->offers()->create($data);
    }

    public function updateForUnite(Unite $unite, int $offerId, array $data): UniteOffer
    {
        $offer = $unite->offers()->where('id', $offerId)->firstOrFail();
        $offer->update($data);

        return $offer->fresh();
    }

    public function deleteForUnite(Unite $unite, int $offerId): bool
    {
        $offer = $unite->offers()->where('id', $offerId)->firstOrFail();

        return (bool) $offer->delete();
    }
}
