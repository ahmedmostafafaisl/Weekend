<?php

namespace App\Policies;

use App\Models\UniteReservation;
use App\Models\User;

class UniteReservationPolicy
{
    /**
     * Customers see their own reservations (scoped in repository).
     * Providers see reservations on their unites (future scope).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * The customer who made the reservation, or the provider who owns the unite,
     * can view it.
     */
    public function view(User $user, UniteReservation $reservation): bool
    {
        if ($reservation->user_id === $user->id) {
            return true;
        }

        return $this->isProviderOfUnite($user, $reservation);
    }

    /**
     * Only customers (not providers) can create reservations.
     * A provider booking their own venue is not allowed.
     */
    public function create(User $user): bool
    {
        return $user->type === 'customer';
    }

    /**
     * Only the customer who made the reservation can update it (e.g. change date).
     * A confirmed or cancelled reservation cannot be mutated.
     */
    public function update(User $user, UniteReservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            && ! in_array($reservation->status, ['confirmed', 'cancelled']);
    }

    /**
     * Only the customer who made the reservation can delete/cancel it.
     */
    public function delete(User $user, UniteReservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    /**
     * A reservation can be cancelled by the customer who made it,
     * OR by the provider who owns the venue.
     */
    public function cancel(User $user, UniteReservation $reservation): bool
    {
        if ($reservation->status === 'cancelled') {
            return false;
        }

        if ($reservation->user_id === $user->id) {
            return true;
        }

        return $this->isProviderOfUnite($user, $reservation);
    }

    // -------------------------------------------------------------------------
    // Helper — checks if the user is the provider of the reservation's unite
    // -------------------------------------------------------------------------

    private function isProviderOfUnite(User $user, UniteReservation $reservation): bool
    {
        if ($user->type !== 'provider') {
            return false;
        }

        // Walk: reservation → unite → department → user_id
        $unite = $reservation->relationLoaded('unite')
            ? $reservation->unite
            : $reservation->unite()->first();

        if (! $unite) {
            return false;
        }

        $department = $unite->relationLoaded('department')
            ? $unite->department
            : $unite->department()->first();

        return $department && $department->user_id === $user->id;
    }
}
