<?php

namespace App\Policies;

use App\Models\Unite;
use App\Models\User;

class UnitePolicy
{
    /**
     * Anyone can browse unites.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a single unite.
     */
    public function view(?User $user, Unite $unite): bool
    {
        return true;
    }

    /**
     * Only providers can create unites.
     */
    public function create(User $user): bool
    {
        return $user->type === 'provider';
    }

    /**
     * Only the provider who owns the department this unite belongs to can update it.
     * Unite → Department → user_id must match the authenticated user.
     */
    public function update(User $user, Unite $unite): bool
    {
        return $user->type === 'provider'
            && $this->ownsUnite($user, $unite);
    }

    /**
     * Only the owning provider can delete their unite.
     */
    public function delete(User $user, Unite $unite): bool
    {
        return $user->type === 'provider'
            && $this->ownsUnite($user, $unite);
    }

    // -------------------------------------------------------------------------
    // Helper — resolves ownership through the Department relationship
    // -------------------------------------------------------------------------

    private function ownsUnite(User $user, Unite $unite): bool
    {
        // Load department if not already loaded (avoid N+1 in policy checks)
        $department = $unite->relationLoaded('department')
            ? $unite->department
            : $unite->department()->first();

        return $department && $department->user_id === $user->id;
    }
}
