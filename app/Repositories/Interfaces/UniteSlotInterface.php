<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use App\Models\UniteSlot;
use Illuminate\Support\Collection;

interface UniteSlotInterface
{
    public function allByUnite(Unite $unite): Collection;

    public function findByUnite(Unite $unite, int $slotId): ?UniteSlot;

    public function createForUnite(Unite $unite, array $data): UniteSlot;

    public function updateForUnite(Unite $unite, int $slotId, array $data): UniteSlot;

    public function deleteForUnite(Unite $unite, int $slotId): bool;

    public function getAvailabilityAndPrices(Unite $unite, string $startDate, ?string $endDate = null): array;
}
