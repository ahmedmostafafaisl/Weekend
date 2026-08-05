<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use App\Models\UnitePrice;
use Illuminate\Support\Collection;

interface UnitePriceInterface
{
    public function allByUnite(Unite $unite): Collection;

    public function findByUnite(Unite $unite, int $priceId): ?UnitePrice;

    public function createForUnite(Unite $unite, array $data): UnitePrice;

    public function updateForUnite(Unite $unite, int $priceId, array $data): UnitePrice;

    public function deleteForUnite(Unite $unite, int $priceId): bool;
}
