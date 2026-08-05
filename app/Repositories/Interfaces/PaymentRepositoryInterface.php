<?php

namespace App\Repositories\Interfaces;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): Payment;

    public function create(array $payload): Payment;

    public function update(int $id, array $payload): Payment;

    public function updateStatus(int $id, string $status, ?string $paymentId = null): Payment;

    public function findByReference(string $referenceId): ?Payment;

    public function delete(int $id): void;

    public function providerPayments(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
