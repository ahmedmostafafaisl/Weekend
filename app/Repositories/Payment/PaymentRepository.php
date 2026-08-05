<?php

namespace App\Repositories\Payment;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Payment::query()->with(['user', 'items', 'reservation', 'subscription'])->latest();

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['payment_type'])) {
            $q->where('payment_type', $filters['payment_type']);
        }

        if (! empty($filters['reference_id'])) {
            $q->where('reference_id', $filters['reference_id']);
        }

        if (! empty($filters['phone'])) {
            $q->where('phone', 'like', '%'.$filters['phone'].'%');
        }

        return $q->paginate($perPage);
    }

    public function findOrFail(int $id): Payment
    {
        return Payment::with(['user', 'items', 'reservation', 'subscription'])->findOrFail($id);
    }

    public function findByReference(string $referenceId): ?Payment
    {
        return Payment::with(['items', 'reservation'])->where('reference_id', $referenceId)->first();
    }

    public function create(array $payload): Payment
    {
        return DB::transaction(function () use ($payload) {
            $items = $payload['items'] ?? [];
            unset($payload['items']);

            $payment = Payment::create($payload);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['price'] ?? 0);

                $payment->items()->create([
                    'name' => $item['name'],
                    'item_number' => $item['item_number'] ?? null,
                    'price' => $price,
                    'quantity' => $qty,
                    'total_amount' => $price * $qty,
                ]);
            }

            // Keep amount in sync with the sum of items if items were provided
            if (! empty($items)) {
                $payment->update(['amount' => $payment->items()->sum('total_amount')]);
            }

            return $payment->load('items');
        });
    }

    public function update(int $id, array $payload): Payment
    {
        return DB::transaction(function () use ($id, $payload) {
            $payment = Payment::with('items')->findOrFail($id);

            $items = $payload['items'] ?? null;
            unset($payload['items']);

            $payment->update($payload);

            if (is_array($items)) {
                $payment->items()->delete();

                foreach ($items as $item) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);

                    $payment->items()->create([
                        'name' => $item['name'],
                        'item_number' => $item['item_number'] ?? null,
                        'price' => $price,
                        'quantity' => $qty,
                        'total_amount' => $price * $qty,
                    ]);
                }

                $payment->update(['amount' => $payment->items()->sum('total_amount')]);
            }

            return $payment->fresh()->load('items');
        });
    }

    public function updateStatus(int $id, string $status, ?string $paymentId = null): Payment
    {
        $payment = Payment::findOrFail($id);

        $data = ['status' => $status];
        if ($paymentId !== null) {
            $data['payment_id'] = $paymentId;
        }

        $payment->update($data);

        return $payment->fresh()->load('items');
    }

    public function delete(int $id): void
    {
        Payment::findOrFail($id)->delete();
    }

    /**
     * Paginated payment list scoped to the currently authenticated provider.
     * Filters are applied on the query builder — not after ->get().
     */
    public function providerPayments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Payment::query()
            ->with(['user', 'items', 'reservation', 'subscription'])
            ->where('user_id', auth()->id())
            ->latest();

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['reference_id'])) {
            $q->where('reference_id', $filters['reference_id']);
        }

        if (! empty($filters['phone'])) {
            $q->where('phone', 'like', '%'.$filters['phone'].'%');
        }

        return $q->paginate($perPage);
    }

    /**
     * Shared post-payment handler called by all gateway callbacks.
     * Fires notifications and activates reservation/subscription.
     * Extracted so all gateways (Geidea, Tappy, Tamara, Maysar) share the same flow.
     *
     * IMPORTANT: subscription activation is done in a dedicated try/catch so that
     * a notification error (FCM token invalid, mail misconfigured, etc.) never
     * silently prevents the subscription from being marked active.
     */
    public function handlePostPayment(\App\Models\Payment $payment): void
    {
        // Ensure relations are freshly loaded — the $payment object may come
        // from a gateway callback that didn't eager-load anything.
        $payment->loadMissing(['reservation.unite.department.user', 'subscription']);

        // ── Reservation ───────────────────────────────────────────────────────
        $reservation = $payment->reservation;

        if ($reservation) {
            $reservation->update(['status' => 'confirmed']);

            try {
                $reservation->user?->notify(
                    new \App\Notifications\ReservationConfirmed(
                        $reservation->load('unite', 'payment')
                    )
                );
                $reservation->unite?->department?->user?->notify(
                    new \App\Notifications\NewReservationReceived(
                        $reservation->load('user', 'unite', 'payment')
                    )
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('handlePostPayment: reservation notification failed', [
                    'payment_id' => $payment->id,
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ── Subscription ──────────────────────────────────────────────────────
        $subscription = $payment->subscription;

        if ($subscription) {
            // Use the correct relation based on subscription type — both
            // adPackage() and propertyPackage() share the same package_id FK,
            // so accessing the wrong one would load the wrong package.
            // Also: AdPackage uses column 'duration' (NOT 'duration_days').
            if ($subscription->type === 'ad') {
                $package = $subscription->adPackage;
                $durationDays = $package?->duration;      // 'duration' column, not 'duration_days'
            } else {
                $package = $subscription->propertyPackage;
                $durationDays = $package?->duration;      // same column name on PropertyPackage
            }

            $subscriptionData = ['status' => 'active'];

            // Only set date range for duration-type packages; count-type packages
            // have no expiry by date — they expire when the ad count hits zero.
            if ($durationDays !== null && $durationDays > 0) {
                $subscriptionData['start_date'] = now()->toDateString();
                $subscriptionData['end_date'] = now()->addDays($durationDays)->toDateString();
            } elseif ($subscription->start_date === null) {
                // For count-type packages set start_date for audit trail but no end_date
                $subscriptionData['start_date'] = now()->toDateString();
            }

            $subscription->update($subscriptionData);

            try {
                $subscription->user?->notify(
                    new \App\Notifications\SubscriptionActivated(
                        $subscription->load('adPackage', 'propertyPackage')
                    )
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('handlePostPayment: subscription notification failed', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
