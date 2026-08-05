<?php

namespace App\Repositories\Interfaces;

interface UniteReservationInterface
{
    public function all();

    public function allForUser($userId);

    public function find($id);

    /**
     * Create a reservation and initiate payment atomically.
     *
     * @return array{
     *     reservation: \App\Models\UniteReservation,
     *     payment:     \App\Models\Payment,
     *     payment_url: string
     * }
     */
    public function create(array $data, $userId = null): array;

    public function update($id, array $data);

    public function delete($id);

    public function cancel($id, $userId = null): \App\Models\UniteReservation;

    public function approve(int $reservationId, int $providerId): array;

    public function reject(int $reservationId, int $providerId, ?string $reason = null): \App\Models\UniteReservation;
}
