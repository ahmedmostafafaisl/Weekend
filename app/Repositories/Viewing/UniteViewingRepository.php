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

        // The booker is always one of the attendees — confirmed
        // explicitly: "Number of People: 3" counts the booker as one of
        // the three, not as a 4th person on top of three others. Dedupe
        // rather than reject if a client happens to send the booker's own
        // ID in attendee_user_ids too.
        $attendeeIds = collect($data['attendee_user_ids'] ?? [])
            ->push($userId)
            ->unique()
            ->values()
            ->all();

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

            $viewing->attendees()->attach($attendeeIds);

            return [
                'viewing' => $viewing->load(['user', 'unite', 'viewingTime', 'attendees']),
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
            $unite, $data, $userId, $viewingTime, $depositAmount, $depositRefundable, $gateway, $paymentMethod, $attendeeIds
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

            $viewing->attendees()->attach($attendeeIds);

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
                'viewing' => $viewing->load(['user', 'unite', 'viewingTime', 'payment', 'attendees']),
                'payment' => $payment->fresh()->load('items'),
                'payment_url' => $gatewayResult['payment_url'],
            ];
        });

        return $result;
    }

    /**
     * Cancels a customer's own free viewing appointment. Deliberately
     * restricted to deposit_required=false — a deposit-based appointment
     * involves real money already taken (or about to be), which needs
     * its own refund-aware cancellation path, not this simple one. The
     * viewing row is never deleted, only its status changes to
     * 'cancelled' — preserving the full history of what was booked.
     */
    public function cancel(int $viewingId, int $userId): UniteViewing
    {
        $viewing = UniteViewing::where('id', $viewingId)
            ->where('user_id', $userId)
            ->first();

        if (! $viewing) {
            abort(404, __('lang.viewing_not_found'));
        }

        if ($viewing->deposit_required) {
            abort(422, __('lang.viewing_deposit_cancel_not_allowed'));
        }

        // A viewing already cancelled or completed (the visit already
        // happened) can't be cancelled again — one check against both
        // disallowed states rather than a separate one per status.
        if (in_array($viewing->status, ['cancelled', 'completed'], true)) {
            abort(422, __('lang.viewing_cannot_be_cancelled'));
        }

        $viewing->update(['status' => 'cancelled']);

        return $viewing->load(['unite', 'viewingTime', 'attendees']);
    }
}
