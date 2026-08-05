<?php

namespace App\Repositories\Interfaces;

use App\Models\Unite;
use Illuminate\Database\Eloquent\Model;

interface UniteDetailInterface
{
    public function findByUnite(Unite $unite): ?Model;

    public function createForUnite(Unite $unite, array $data): Model;

    public function updateForUnite(Unite $unite, array $data): Model;

    public function deleteForUnite(Unite $unite): bool;
}
