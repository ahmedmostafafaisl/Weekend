<?php

namespace App\Http\Requests\Unite\Concerns;

use App\Models\Admin;
use App\Models\Unite;
use Illuminate\Contracts\Auth\Authenticatable;

trait AuthorizesUniteSubResource
{
    protected function userMayAccessUniteSubResource(?Authenticatable $user, ?Unite $unite, string $permission): bool
    {
        if ($user instanceof Admin) {
            return $user->can($permission);
        }

        if (! $user || ! $unite) {
            return false;
        }

        if ($user->type !== 'provider') {
            return false;
        }

        $department = $unite->relationLoaded('department')
            ? $unite->department
            : $unite->department()->first();

        return $department && $department->user_id === $user->id;
    }
}
