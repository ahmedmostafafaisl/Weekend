<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use App\Models\UniteOffer;
use Illuminate\Support\Collection;

interface UniteOfferInterface
{
    public function allByUnite(Unite $unite): Collection;

    public function findByUnite(Unite $unite, int $offerId): ?UniteOffer;

    public function createForUnite(Unite $unite, array $data): UniteOffer;

    public function updateForUnite(Unite $unite, int $offerId, array $data): UniteOffer;

    public function deleteForUnite(Unite $unite, int $offerId): bool;
}
