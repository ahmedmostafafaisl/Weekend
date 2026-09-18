<?php

namespace App\Policies;

use App\Models\Ad;
use App\Models\User;

class AdPolicy
{
    /**
     * Anyone can browse active ads (the public listing).
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a single active ad.
     */
    public function view(?User $user, Ad $ad): bool
    {
        return true;
    }

    /**
     * Only providers can create ads via the API.
     * The subscription gate in AdController::store() enforces quota
     * separately; this policy only checks the user *type*.
     */
    public function create(User $user): bool
    {
        return $user->type === 'provider';
    }

    /**
     * Only the ad's own provider can update it via the API.
     * The admin dashboard uses a separate AdminAdController gated by
     * 'permission:ads.update' — it never hits this policy.
     */
    public function update(User $user, Ad $ad): bool
    {
        return $this->owns($user, $ad);
    }

    /**
     * Only the ad's own provider can delete it via the API.
     */
    public function delete(User $user, Ad $ad): bool
    {
        return $this->owns($user, $ad);
    }

    /**
     * Only the ad's own provider can activate it via the API.
     * The admin-dashboard activate route (/ads/{id}/activate in web.php)
     * does not pass through this policy — it is guarded by its own
     * 'auth:admin' middleware group.
     */
    public function activate(User $user, Ad $ad): bool
    {
        return $this->owns($user, $ad);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    private function owns(User $user, Ad $ad): bool
    {
        return $ad->user_id === $user->id;
    }
}
