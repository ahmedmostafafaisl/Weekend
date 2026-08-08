<?php

namespace App\Repositories\Viewing;

use App\Models\Payment;
use App\Models\Unite;
use App\Models\UniteViewing;
use App\Models\UniteViewingTime;
use App\Services\Payment\PaymentMethodFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UniteViewingRepository
{
    /**
     * Creates a viewing-appointment booking. If the venue requires a
     * deposit, this creates a pending UniteViewing + Payment and returns a
     * gateway payment_url, exactly matching the reservation flow's
     * pattern. If no deposit is required, there's nothing to pay for, so
     * the viewing is confirmed immediately with no Payment row at all.
     */
    public function create(array $data, int $userId): array
    {
        $unite = Unite::findOrFail($data['unite_id']);

        $viewingTime = UniteViewingTime::where('id', $data['unite_viewing_time_id'])
            ->where('unite_id', $unite->id)
            ->first();

        if (! $viewingTime) {
            abort(422, __('lang.viewing_time_not_found'));
        }

        if ($viewingTime->status !== 'active') {
            abort(422, __('lang.viewing_time_not_available'));
        }

        $requestedDayOfWeek = strtolower(Carbon::parse($data['viewing_date'])->englishDayOfWeek);
        if ($requestedDayOfWeek !== $viewingTime->day_of_week) {
            abort(422, str_replace(
                [':requested', ':expected'],
                [$requestedDayOfWeek, $viewingTime->day_of_week],
                __('lang.viewing_date_day_mismatch')
            ));
        }

        // Exclusive per slot+date — a viewing appointment is a real,
        // in-person meeting at the venue, so two customers can't both be
        // shown around at the exact same time. Excludes cancelled
        // appointments, which free up the slot again.
        $conflict = UniteViewing::where('unite_viewing_time_id', $viewingTime->id)
            ->where('viewing_date', $data['viewing_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($conflict) {
            abort(422, __('lang.viewing_slot_already_booked'));
        }

        $depositRequired = (bool) $unite->viewing_deposit_enabled;

        if (! $depositRequired) {
            // No deposit — nothing to pay for, so the appointment is
            // confirmed immediately with no Payment row at all.
            $viewing = UniteViewing::create([
                'unite_id' => $unite->id,
                'user_id' => $userId,
                'unite_viewing_time_id' => $viewingTime->id,
                'viewing_date' => $data['viewing_date'],
                'status' => 'confirmed',
                'deposit_required' => false,
                'deposit_amount' => null,
                'deposit_refundable' => null,
            ]);

            return [
                'viewing' => $viewing->load(['user', 'unite', 'viewingTime']),
                'payment' => null,
                'payment_url' => null,
            ];
        }

        // Deposit required — snapshot the unite's current deposit terms
        // onto the viewing itself, so a provider changing their deposit
        // settings later never retroactively alters what this customer
        // already agreed to and (about to) pay.
        $depositAmount = (float) ($unite->viewing_deposit_amount ?? 0);
        $depositRefundable = (bool) $unite->viewing_deposit_refundable;

        if ($depositAmount <= 0) {
            abort(422, __('lang.viewing_deposit_amount_not_configured'));
        }

        $paymentMethod = $data['payment_method'] ?? 'geidea';

        try {
            $gateway = PaymentMethodFactory::make($paymentMethod);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $result = DB::transaction(function () use (
            $unite, $data, $userId, $viewingTime, $depositAmount, $depositRefundable, $gateway, $paymentMethod
        ) {
            $viewing = UniteViewing::create([
                'unite_id' => $unite->id,
                'user_id' => $userId,
                'unite_viewing_time_id' => $viewingTime->id,
                'viewing_date' => $data['viewing_date'],
                'status' => 'pending',
                'deposit_required' => true,
                'deposit_amount' => $depositAmount,
                'deposit_refundable' => $depositRefundable,
            ]);

            $user = $viewing->user ?? auth()->user();
            $phone = $user?->phone ?? $data['phone'] ?? null;

            $payment = Payment::create([
                'user_id' => $userId,
                'unite_viewing_id' => $viewing->id,
                'payment_type' => $paymentMethod,
                'amount' => $depositAmount,
                'status' => 'pending',
                'phone' => $phone,
            ]);

            $payment->items()->create([
                'name' => $unite->name.' — '.__('lang.viewing_deposit'),
                'item_number' => (string) $viewing->id,
                'price' => $depositAmount,
                'quantity' => 1,
                'total_amount' => $depositAmount,
            ]);

            $gatewayResult = $gateway->sendPayment([
                'amount' => $depositAmount,
                'price' => $depositAmount,
                'quantity' => 1,
                'description' => $unite->name.' — '.__('lang.viewing_deposit').' — '.$data['viewing_date'],
                'currency' => env('GEIDEA_CURRENCY'),
                'merchantReferenceId' => $payment->reference_id,
                'customer' => [
                    'name' => $user?->name ?? 'Customer',
                    'email' => $user?->email,
                    'phoneNumber' => $phone,
                ],
                'callbackUrl' => config('services.geidea.callback_url', route('payment.callback')),
                'returnUrl' => config('services.geidea.return_url', url('/payment-complete')),
            ]);

            if (! ($gatewayResult['success'] ?? false)) {
                throw new \RuntimeException(
                    $gatewayResult['message'] ?? 'Payment gateway returned an error.'
                );
            }

            $payment->update(['payment_id' => $gatewayResult['item_id']]);

            return [
                'viewing' => $viewing->load(['user', 'unite', 'viewingTime', 'payment']),
                'payment' => $payment->fresh()->load('items'),
                'payment_url' => $gatewayResult['payment_url'],
            ];
        });

        return $result;
    }
}
