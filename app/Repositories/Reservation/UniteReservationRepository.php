<?php

namespace App\Repositories\Reservation;

use App\Models\Payment;
use App\Models\Unite;
use App\Models\UniteBookingPackage;
use App\Models\UniteReservation;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationPendingApproval;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use App\Repositories\Interfaces\UniteReservationInterface;
use App\Services\PromoCode\PromoCodeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniteReservationRepository implements UniteReservationInterface
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
        protected PromoCodeService $promoCodeService,
    ) {}

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function all()
    {
        return UniteReservation::with(['user', 'unite', 'payment'])->latest()->get();
    }

    public function allForUser($userId)
    {
        return UniteReservation::with(['user', 'unite', 'payment'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function find($id)
    {
        return UniteReservation::with(['user', 'unite', 'payment'])->findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Create — single atomic flow: validate → reserve → pay
    // -------------------------------------------------------------------------

    /**
     * Creates a reservation and a pending Payment record in a single transaction,
     * then requests a Geidea hosted-payment URL.
     *
     * Returns an array with:
     *   - reservation  UniteReservation  the newly created booking
     *   - payment      Payment           the pending payment record
     *   - payment_url  string            the Geidea hosted-checkout link
     *
     * Throws on conflict, missing slot config, or gateway failure.
     */
    public function create(array $data, $userId = null): array
    {
        $unite = Unite::with(['prices', 'offers', 'slots'])->findOrFail($data['unite_id']);

        [$fromTime, $toTime, $endDate] = $this->resolveTimes($unite, $data);
        // Hourly bookings: calculate price from from_time/to_time + hourly rates
        if ($data['period_type'] === 'hourly') {
            $fullPrice = $this->resolveHourlyPrice($unite, $fromTime, $toTime, $data['reservation_date']);
        } elseif ($data['period_type'] === 'package') {
            // Package reservations use the package's own fixed price
            // rather than hourly or period pricing — service fee and any
            // eligible discount/promo still apply on top of it further
            // down, exactly like every other period_type.
            $fullPrice = $this->resolvePackagePrice($unite, $data);
        } else {
            $fullPrice = $this->resolvePrice($unite, $data['period_type'], $data['reservation_date']);
        }
        $chargeAmount = $this->resolveChargeAmount($unite, $fullPrice);

        // BUG FIX: this used to reject unconditionally whenever
        // $chargeAmount was 0 — but that can no longer distinguish
        // "pricing was never configured" (now caught earlier, inside
        // resolvePrice()/resolveHourlyPrice(), which abort with a
        // specific error the moment they find a missing/null price
        // field) from "the provider deliberately priced this at 0".
        // Only a genuinely negative amount is guarded here — which
        // should never happen given every price field is validated as
        // min:0 wherever it's set, but costs nothing to catch defensively
        // — while an explicit 0 passes through as the valid free booking
        // it's meant to be.
        if ($chargeAmount < 0) {
            abort(422, __('lang.venue_no_price_configured'));
        }

        // Resolve promo code if provided (validate outside transaction — read-only)
        $promoResult = null;
        if (! empty($data['promo_code'])) {
            $promoResult = $this->promoCodeService->validate(
                $data['promo_code'],
                $chargeAmount,
                $userId
            );
            if (! $promoResult['valid']) {
                abort(422, $promoResult['message']);
            }
            $chargeAmount = $promoResult['final_amount'];
        }

        // Service fee is a flat charge, applied AFTER any promo-code
        // discount — it's not part of the discountable price. feeFor()
        // returns 0.0 for categories with no fee configured or explicitly
        // disabled, so this is always safe to add unconditionally.
        $serviceFee = \App\Models\ServiceFee::feeFor('reservation');
        $chargeAmount += $serviceFee;

        $this->ensureNoConflict($unite->id, $data['reservation_date'], $fromTime, $toTime, null, $endDate);

        // ── Provider approval mode ────────────────────────────────────────────────
        // If the venue requires approval, create the reservation in pending_approval
        // status and notify the provider — no Geidea call yet.
        if ($unite->requires_approval) {
            return $this->createPendingApproval(
                $unite, $data, $userId, $fromTime, $toTime, $fullPrice, $chargeAmount, $promoResult, $serviceFee, $endDate
            );
        }

        // Select payment gateway based on requested method (default: geidea)
        $paymentMethod = $data['payment_method'] ?? 'geidea';
        $gateway = \App\Services\Payment\PaymentMethodFactory::make($paymentMethod);

        // Wrap reservation + payment creation in a single transaction.
        // If Geidea returns an error we roll back both rows so the slot stays free.
        $result = DB::transaction(function () use (
            $unite, $data, $userId, $fromTime, $toTime, $fullPrice, $chargeAmount, $promoResult, $gateway, $paymentMethod, $serviceFee, $endDate
        ) {
            // 1. Create the reservation (status: pending — confirmed only after payment)
            $reservation = UniteReservation::create([
                'unite_id' => $unite->id,
                'user_id' => $userId,
                'reservation_date' => $data['reservation_date'],
                'end_date' => $endDate,
                'period_type' => $data['period_type'],
                'from_time' => $fromTime,
                'to_time' => $toTime,
                'price' => $fullPrice,
                'status' => 'pending',
                'unite_booking_package_id' => $data['period_type'] === 'package' ? $data['booking_package_id'] : null,
            ]);

            // 2. Resolve the user's phone — required by Geidea
            $user = $reservation->user ?? auth()->user();
            $phone = $user?->phone ?? $data['phone'] ?? null;

            // 3. Create the pending Payment row (reference_id auto-generated in model boot)
            $payment = Payment::create([
                'user_id' => $userId,
                'reservation_id' => $reservation->id,
                'payment_type' => $paymentMethod,
                'amount' => $chargeAmount,
                'status' => 'pending',
                'phone' => $phone,
                'promo_code_id' => $promoResult ? $promoResult['promo_code']->id : null,
                'discount_amount' => $promoResult ? $promoResult['discount_amount'] : null,
                'original_amount' => $promoResult ? $promoResult['original_amount'] : null,
                'service_fee_amount' => $serviceFee > 0 ? $serviceFee : null,
            ]);

            // Build the PaymentItem(s) that Geidea's eInvoice needs — split
            // into a base line + a separate Service Fee line when a fee
            // applies, so the customer sees an itemized breakdown rather
            // than a mysteriously inflated lump sum. The base line is
            // computed as chargeAmount minus the fee (not independently
            // recalculated) so the two lines always sum exactly to
            // chargeAmount with no floating-point rounding drift.
            $payment->items()->create([
                'name' => $unite->name.' — '.ucfirst(str_replace('_', ' ', $data['period_type'])),
                'item_number' => (string) $reservation->id,
                'price' => $chargeAmount - $serviceFee,
                'quantity' => 1,
                'total_amount' => $chargeAmount - $serviceFee,
            ]);
            if ($serviceFee > 0) {
                $payment->items()->create([
                    'name' => __('lang.service_fee'),
                    'item_number' => (string) $reservation->id.'-fee',
                    'price' => $serviceFee,
                    'quantity' => 1,
                    'total_amount' => $serviceFee,
                ]);
            }

            // 4. Call Geidea — if this throws or returns failure we roll back
            $gatewayResult = $gateway->sendPayment([
                'amount' => $chargeAmount,
                'price' => $chargeAmount,
                'quantity' => 1,
                'description' => $unite->name.' — '.$data['reservation_date'],
                'currency' => env('GEIDEA_CURRENCY'),

                // merchantReferenceId ties the callback back to our Payment row
                'merchantReferenceId' => $payment->reference_id,

                'customer' => [
                    'name' => $user?->name ?? 'Customer',
                    'email' => $user?->email,
                    'phoneNumber' => $phone,
                ],

                // callbackUrl: Geidea POSTs to this after payment
                // Must be a publicly reachable URL — set GEIDEA_CALLBACK_URL in .env for local dev (use ngrok)
                'callbackUrl' => config('services.geidea.callback_url', route('payment.callback')),

                // returnUrl: browser redirect after user completes payment on Geidea's page
                'returnUrl' => config('services.geidea.return_url', url('/payment-complete')),
            ]);

            if (! ($gatewayResult['success'] ?? false)) {
                // Force a rollback — throw an exception so DB::transaction catches it
                throw new \RuntimeException(
                    $gatewayResult['message'] ?? 'Payment gateway returned an error.'
                );
            }

            // 5. Store Geidea's paymentIntentId on the Payment row for later lookups
            $payment->update(['payment_id' => $gatewayResult['item_id']]);

            // 6. Record promo code usage (inside transaction — rolls back if Geidea fails)
            if ($promoResult) {
                $this->promoCodeService->recordUsage(
                    $promoResult['promo_code'],
                    $payment->id,
                    $promoResult['original_amount'],
                    $promoResult['discount_amount'],
                    $promoResult['final_amount'],
                    $userId
                );
            }
            // dd($gatewayResult['payment_url']);

            return [
                'reservation' => $reservation->load(['user', 'unite', 'payment']),
                'payment' => $payment->fresh()->load('items'),
                'payment_url' => $gatewayResult['payment_url'],
            ];
        });

        return $result;
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update($id, array $data)
    {
        $reservation = UniteReservation::with(['unite.prices', 'unite.offers', 'unite.slots'])->findOrFail($id);

        if ($reservation->status === 'cancelled') {
            abort(422, __('lang.cancelled_reservation_cannot_update'));
        }

        $payload = array_merge([
            'reservation_date' => $reservation->reservation_date?->format('Y-m-d'),
            'period_type' => $reservation->period_type,
            'from_time' => $reservation->from_time,
            'to_time' => $reservation->to_time,
            'unite_id' => $reservation->unite_id,
        ], $data);

        [$fromTime, $toTime, $endDate] = $this->resolveTimes($reservation->unite, $payload);
        // BUG FIX: this never branched for period_type at all — hourly
        // reservations would have gone through resolvePrice(), which
        // explicitly returns 0 for hourly (see its own comment further
        // down), and package reservations would have tried to resolve a
        // price with no package context. Matches the same 3-way branch
        // already used in create().
        if ($payload['period_type'] === 'hourly') {
            $price = $this->resolveHourlyPrice($reservation->unite, $fromTime, $toTime, $payload['reservation_date']);
        } elseif ($payload['period_type'] === 'package') {
            $price = $this->resolvePackagePrice($reservation->unite, $payload);
        } else {
            $price = $this->resolvePrice($reservation->unite, $payload['period_type'], $payload['reservation_date']);
        }

        $this->ensureNoConflict(
            $reservation->unite_id,
            $payload['reservation_date'],
            $fromTime,
            $toTime,
            $reservation->id,
            $endDate
        );

        $reservation->update([
            'reservation_date' => $payload['reservation_date'],
            'end_date' => $endDate,
            'period_type' => $payload['period_type'],
            'from_time' => $fromTime,
            'to_time' => $toTime,
            'price' => $price,
            'status' => $data['status'] ?? $reservation->status,
        ]);

        return $reservation->fresh(['user', 'unite', 'payment']);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function delete($id)
    {
        return UniteReservation::findOrFail($id)->delete();
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function cancel($id, $userId = null): UniteReservation
    {
        $reservation = UniteReservation::with(['payment', 'unite'])->findOrFail($id);

        if ($userId && $reservation->user_id !== $userId) {
            abort(403, __('lang.unauthorized_action'));
        }

        $startDateTime = Carbon::parse(
            $reservation->reservation_date->format('Y-m-d').' '.$reservation->from_time
        );

        if (now()->greaterThanOrEqualTo($startDateTime)) {
            abort(422, __('lang.reservation_cannot_cancel_started'));
        }

        return DB::transaction(function () use ($reservation) {
            // Mark reservation cancelled
            $reservation->update(['status' => 'cancelled']);

            $payment = $reservation->payment;

            // Nothing to refund if there is no paid payment
            if (! $payment || $payment->status !== 'paid') {
                Log::info('Reservation cancelled with no paid payment to refund', [
                    'reservation_id' => $reservation->id,
                    'payment_status' => $payment?->status,
                ]);

                $fresh = $reservation->fresh(['payment', 'unite.department.user', 'user']);
                $fresh->user?->notify(new ReservationCancelled($fresh, 0));
                $fresh->unite?->department?->user?->notify(new ReservationCancelled($fresh, 0, true));

                return $fresh;
            }

            // Determine refund amount from the venue's refund_policy
            $refundAmount = $this->resolveRefundAmount($reservation);

            if ($refundAmount <= 0) {
                Log::info('Reservation cancelled — refund_policy: no refund due', [
                    'reservation_id' => $reservation->id,
                    'policy' => $reservation->unite?->refund_policy,
                ]);
                // Mark payment as refunded with zero — keeps audit trail clean
                $payment->update(['status' => 'refunded']);

                $fresh = $reservation->fresh(['payment', 'unite.department.user', 'user']);
                $fresh->user?->notify(new ReservationCancelled($fresh, 0));
                $fresh->unite?->department?->user?->notify(new ReservationCancelled($fresh, 0, true));

                return $fresh;
            }

            // No Geidea order ID → cannot call the API
            if (empty($payment->payment_id)) {
                Log::warning('Reservation cancelled — paid payment has no gateway order ID; cannot auto-refund', [
                    'reservation_id' => $reservation->id,
                    'payment_id' => $payment->id,
                ]);
                $payment->update(['status' => 'refund_failed']);

                return $reservation->fresh('payment');
            }

            Log::info('Initiating Geidea refund', [
                'reservation_id' => $reservation->id,
                'payment_id' => $payment->id,
                'geidea_order_id' => $payment->payment_id,
                'refund_amount' => $refundAmount,
                'policy' => $reservation->unite?->refund_policy,
            ]);

            $result = $this->paymentGateway->refund(
                $payment->payment_id,
                $refundAmount,
                'Customer cancellation – reservation #'.$reservation->id
            );

            if ($result['success'] ?? false) {
                $payment->update(['status' => 'refunded']);
                Log::info('Geidea refund succeeded', ['reservation_id' => $reservation->id]);

                $fresh = $reservation->fresh(['payment', 'unite.department.user', 'user']);
                $fresh->user?->notify(new ReservationCancelled($fresh, $refundAmount));
                $fresh->unite?->department?->user?->notify(new ReservationCancelled($fresh, $refundAmount, true));
            } else {
                $payment->update(['status' => 'refund_failed']);
                Log::error('Geidea refund failed', [
                    'reservation_id' => $reservation->id,
                    'message' => $result['message'] ?? 'unknown',
                ]);
                // Do NOT re-throw — the reservation is already cancelled.
                // The refund failure is logged; admin can retry via the recovery endpoint.

                $fresh = $reservation->fresh(['payment', 'unite.department.user', 'user']);
                $fresh->user?->notify(new ReservationCancelled($fresh, 0)); // refund pending admin action
            }

            return $reservation->fresh('payment');
        });
    }

    // -------------------------------------------------------------------------
    // Resolve how much to refund based on the venue's refund_policy
    //
    // free       → 100 % refund (full paid amount)
    // flexible   → 100 % if cancelled > 24 h before start, 0 % otherwise
    // moderate   → 50 % if cancelled > 48 h before start, 0 % otherwise
    // strict     → 0 % (no refund ever)
    // null/other → 100 % (default to generous policy)
    // -------------------------------------------------------------------------

    // ── Provider approval mode ───────────────────────────────────────────────

    protected function createPendingApproval(
        Unite $unite,
        array $data,
        mixed $userId,
        ?string $fromTime,
        ?string $toTime,
        float $fullPrice,
        float $chargeAmount,
        ?array $promoResult,
        float $serviceFee = 0.0,
        ?string $endDate = null
    ): array {
        $reservation = DB::transaction(function () use (
            $unite, $data, $userId, $fromTime, $toTime, $fullPrice, $chargeAmount, $promoResult, $serviceFee, $endDate
        ) {
            $reservation = UniteReservation::create([
                'unite_id' => $unite->id,
                'user_id' => $userId,
                'reservation_date' => $data['reservation_date'],
                'end_date' => $endDate,
                'period_type' => $data['period_type'],
                'from_time' => $fromTime,
                'to_time' => $toTime,
                'price' => $fullPrice,
                'status' => 'pending_approval',
                'guest_count' => $data['guest_count'] ?? null,
                'notes' => $data['notes'] ?? null,
                'unite_booking_package_id' => $data['period_type'] === 'package' ? $data['booking_package_id'] : null,
            ]);

            $user = $reservation->user ?? auth()->user();
            $phone = $user?->phone ?? $data['phone'] ?? null;

            Payment::create([
                'user_id' => $userId,
                'reservation_id' => $reservation->id,
                'payment_type' => 'geidea',
                'amount' => $chargeAmount,
                'status' => 'pending',
                'phone' => $phone,
                'promo_code_id' => $promoResult ? $promoResult['promo_code']->id : null,
                'discount_amount' => $promoResult ? $promoResult['discount_amount'] : null,
                'original_amount' => $promoResult ? $promoResult['original_amount'] : null,
                'service_fee_amount' => $serviceFee > 0 ? $serviceFee : null,
            ]);

            return $reservation;
        });

        $fresh = $reservation->fresh(['unite.department.user', 'user', 'payment']);
        $fresh->unite?->department?->user?->notify(new ReservationPendingApproval($fresh));

        return [
            'reservation' => $fresh,
            'payment' => $fresh->payment,
            'payment_url' => null,
            'status' => 'pending_approval',
            'message' => __('lang.booking_request_sent_awaiting_payment'),
        ];
    }

    public function approve(int $reservationId, int $providerId): array
    {
        $reservation = UniteReservation::with(['unite.department', 'payment', 'user'])->findOrFail($reservationId);

        if ($reservation->unite?->department?->user_id !== $providerId) {
            abort(403, __('lang.only_provider_can_approve'));
        }

        if ($reservation->status !== 'pending_approval') {
            abort(422, str_replace(':status', $reservation->status, __('lang.reservation_not_awaiting_approval')));
        }

        $payment = $reservation->payment;
        if (! $payment) {
            abort(422, __('lang.no_payment_record_found'));
        }

        if ($payment->items()->doesntExist()) {
            $serviceFee = (float) ($payment->service_fee_amount ?? 0);
            $payment->items()->create([
                'name' => $reservation->unite->name.' — '.ucfirst(str_replace('_', ' ', $reservation->period_type)),
                'item_number' => (string) $reservation->id,
                'price' => $payment->amount - $serviceFee,
                'quantity' => 1,
                'total_amount' => $payment->amount - $serviceFee,
            ]);
            if ($serviceFee > 0) {
                $payment->items()->create([
                    'name' => __('lang.service_fee'),
                    'item_number' => (string) $reservation->id.'-fee',
                    'price' => $serviceFee,
                    'quantity' => 1,
                    'total_amount' => $serviceFee,
                ]);
            }
        }

        $user = $reservation->user;
        $phone = $user?->phone ?? $payment->phone;

        $gatewayResult = $this->paymentGateway->sendPayment([
            'amount' => $payment->amount,
            'price' => $payment->amount,
            'quantity' => 1,
            'description' => $reservation->unite->name.' — '.$reservation->reservation_date->format('Y-m-d'),
            'currency' => env('GEIDEA_CURRENCY'),
            'payment_id' => $payment->id,
            'reference' => $payment->reference_id,
            'email' => $user?->email,
            'phone' => $phone,
            'name' => $user?->name ?? 'Customer',
        ]);

        if (! isset($gatewayResult['payment_url'])) {
            abort(502, str_replace(':reason', $gatewayResult['message'] ?? __('lang.no_payment_url_returned'), __('lang.payment_gateway_error')));
        }

        $payment->update(['payment_id' => $gatewayResult['item_id'] ?? null]);
        $reservation->update(['status' => 'pending']);

        return [
            'reservation' => $reservation->fresh(),
            'payment_url' => $gatewayResult['payment_url'],
            'message' => __('lang.reservation_approved_awaiting_payment'),
        ];
    }

    public function reject(int $reservationId, int $providerId, ?string $reason = null): UniteReservation
    {
        $reservation = UniteReservation::with(['unite.department', 'payment', 'user'])->findOrFail($reservationId);

        if ($reservation->unite?->department?->user_id !== $providerId) {
            abort(403, __('lang.only_provider_can_reject'));
        }

        if ($reservation->status !== 'pending_approval') {
            abort(422, str_replace(':status', $reservation->status, __('lang.reservation_not_awaiting_approval')));
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'cancelled']);
            $reservation->payment?->update(['status' => 'failed']);
        });

        $fresh = $reservation->fresh(['unite', 'payment', 'user']);
        $fresh->user?->notify(new ReservationCancelled($fresh, 0));

        return $fresh;
    }

    protected function resolveRefundAmount(UniteReservation $reservation): float
    {
        $paid = (float) ($reservation->payment?->amount ?? 0);
        $policy = $reservation->unite?->refund_policy;

        $startDateTime = Carbon::parse(
            $reservation->reservation_date->format('Y-m-d').' '.$reservation->from_time
        );
        $hoursUntilStart = now()->diffInHours($startDateTime, false); // negative if past

        return match ($policy) {
            'free' => $paid,
            'flexible' => $hoursUntilStart >= 24 ? $paid : 0.0,
            'moderate' => $hoursUntilStart >= 48 ? round($paid * 0.5, 2) : 0.0,
            'strict' => 0.0,
            default => $paid, // no policy set → full refund
        };
    }

    // -------------------------------------------------------------------------
    // Private: charge amount (deposit vs full price)
    // -------------------------------------------------------------------------

    /**
     * If the venue requires a deposit, the customer is charged only the deposit
     * now; the remainder is settled at the venue.
     *
     * deposit_type = 'amount'     → charge exactly reservation_deposit_amount
     * deposit_type = 'percentage' → charge (reservation_deposit_amount / 100) * fullPrice
     * no deposit                  → charge fullPrice
     */
    private function resolveChargeAmount(Unite $unite, float $fullPrice): float
    {
        if (! $unite->reservation_deposit) {
            return $fullPrice;
        }

        $depositAmount = (float) ($unite->reservation_deposit_amount ?? 0);

        if ($unite->reservation_deposit_type === 'percentage') {
            return round(($depositAmount / 100) * $fullPrice, 2);
        }

        // 'amount' type — fixed deposit, capped at the full price
        return $fullPrice;
    }

    // -------------------------------------------------------------------------
    // Private: slot resolution
    // -------------------------------------------------------------------------

    protected function resolveTimes(Unite $unite, array $data): array
    {
        // Centralized reservation-level enforcement — replaces the old
        // stadium-only check with the general matrix from
        // Unite::allowedPeriodTypes(), which also covers hall (full_day +
        // package only) and gates 'package' behind package_booking_enabled
        // for every type.
        if (! in_array($data['period_type'], $unite->allowedPeriodTypes())) {
            abort(422, str_replace(':type', __('lang.'.$unite->type), __('lang.period_type_not_allowed_for_venue')));
        }

        // Packages have their own start_time/end_time, entirely independent
        // of the venue's unite_slots table — they skip the slot-based
        // resolution below altogether rather than trying to fit into it.
        if ($data['period_type'] === 'package') {
            return $this->resolvePackageTimes($unite, $data);
        }

        $dayOfWeek = strtolower(Carbon::parse($data['reservation_date'])->englishDayOfWeek);

        // Use already-loaded collection first to avoid an extra query
        $slot = $unite->relationLoaded('slots')
            ? $unite->slots->firstWhere('day_of_week', $dayOfWeek)
            : $unite->slots()->where('day_of_week', $dayOfWeek)->first();

        // Try 'weekday' slot as fallback if exact day is missing
        if (! $slot && $unite->relationLoaded('slots')) {
            $slot = $unite->slots->firstWhere('day_of_week', 'weekday')
                ?? $unite->slots->first();
        }

        if (! $slot) {
            abort(422, str_replace(':day', $dayOfWeek, __('lang.no_slot_config_for_day')));
        }

        [$from, $to] = match ($data['period_type']) {
            'morning' => [$slot->morning_start, $slot->morning_end],
            'evening' => [$slot->evening_start, $slot->evening_end],
            'full_day' => [$slot->full_start,    $slot->full_end],
            'custom',
            'hourly' => [$data['from_time'] ?? null, $data['to_time'] ?? null],
            default => abort(422, str_replace(':type', $data['period_type'], __('lang.invalid_period_type'))),
        };

        if (is_null($from) || is_null($to)) {
            // Distinguish the two causes of null so the error is actionable:
            // - custom/hourly: the caller didn't send from_time / to_time
            // - morning/evening/full_day: the venue slot has no times configured
            //   for that period (e.g. stadiums only support full_day)
            if (in_array($data['period_type'], ['custom', 'hourly'])) {
                abort(422, str_replace(':period', $data['period_type'], __('lang.from_to_time_required_for_period')));
            }

            abort(422, str_replace(':period', $data['period_type'], __('lang.venue_does_not_offer_period')));
        }

        // Hourly: enforce minimum booking duration
        if ($data['period_type'] === 'hourly') {
            $priceRow = $unite->relationLoaded('prices')
                ? $unite->prices->first()
                : $unite->prices()->first();

            if ($priceRow && $priceRow->hourly_enabled) {
                $minMinutes = $priceRow->min_booking_minutes ?? 60;
                $duration = Carbon::parse($from)->diffInMinutes(Carbon::parse($to));
                if ($duration < $minMinutes) {
                    abort(422, str_replace([':min', ':requested'], [$minMinutes, $duration], __('lang.min_booking_duration')));
                }
            }
        }

        return [$from, $to, null];
    }

    /**
     * Packages have their own start_time/end_time, entirely independent of
     * the venue's unite_slots table — validates the requested package
     * exists, belongs to this unite, is active, and actually applies to
     * the requested day, then returns its own time window (or, for
     * 'days'-type packages, an end_date instead — see below). The caller
     * threads whatever this returns straight into ensureNoConflict(),
     * which already knows how to handle both a plain time-window request
     * and a multi-day date-range request.
     */
    protected function resolvePackageTimes(Unite $unite, array $data): array
    {
        $package = $this->findValidPackage($unite, $data);

        if ($package->booking_type === 'days') {
            // A 'days' package occupies whole calendar days, not a
            // specific time window — from_time/to_time stay null (both
            // columns are nullable), and end_date is computed from the
            // package's own duration_days rather than taken from the
            // request, since the customer only picks a start date and the
            // package itself dictates how many nights it spans.
            $endDate = Carbon::parse($data['reservation_date'])
                ->addDays(max(1, $package->duration_days ?? 1) - 1)
                ->format('Y-m-d');

            return [null, null, $endDate];
        }

        // 'hours' mode — unchanged from before: a single day, specific
        // time window, no end_date.
        return [(string) $package->start_time, (string) $package->end_time, null];
    }

    /**
     * Shared lookup+validation used by both resolvePackageTimes() and
     * resolvePackagePrice() — kept in one place so the two can't drift
     * out of sync on what counts as a "valid" package for this booking.
     */
    protected function findValidPackage(Unite $unite, array $data): UniteBookingPackage
    {
        if (empty($data['booking_package_id'])) {
            abort(422, __('lang.booking_package_id_required'));
        }

        $package = UniteBookingPackage::where('id', $data['booking_package_id'])
            ->where('unite_id', $unite->id)
            ->first();

        if (! $package) {
            abort(422, __('lang.booking_package_not_found'));
        }

        if ($package->status !== 'active') {
            abort(422, __('lang.booking_package_not_available'));
        }

        $dayOfWeek = strtolower(Carbon::parse($data['reservation_date'])->englishDayOfWeek);

        if (! $package->appliesToDay($dayOfWeek)) {
            abort(422, str_replace(':day', $dayOfWeek, __('lang.booking_package_not_available_this_day')));
        }

        return $package;
    }

    // -------------------------------------------------------------------------
    // Private: price resolution (offers → base price)
    // -------------------------------------------------------------------------

    /**
     * Package reservations use the package's own fixed price, not hourly
     * or period pricing — deliberately does NOT check for an active offer
     * the way resolvePrice()/resolveHourlyPrice() do, since a package's
     * price is already a specific, deliberately-set bundle price, not a
     * base rate offers are meant to discount.
     */
    protected function resolvePackagePrice(Unite $unite, array $data): float
    {
        $package = $this->findValidPackage($unite, $data);

        return (float) $package->price;
    }

    protected function resolvePrice(Unite $unite, string $periodType, string $date): float
    {
        $reservationDate = Carbon::parse($date);

        $activeOffer = $unite->offers()
            ->where('status', 'active')
            ->whereDate('start', '<=', $reservationDate)
            ->whereDate('end', '>=', $reservationDate)
            ->latest('id')
            ->first();

        if ($activeOffer) {
            if ($unite->type === 'stadium') {
                return (float) ($activeOffer->full_day_price ?? 0);
            }

            // Hourly price is calculated separately; resolvePrice returns 0 for it
            if ($periodType === 'hourly') {
                return 0.0;
            }

            return (float) match ($periodType) {
                'morning' => $activeOffer->morning_price ?? 0,
                'evening' => $activeOffer->evening_price ?? 0,
                'full_day', 'custom', 'hourly' => $activeOffer->full_day_price ?? 0,
                default => 0,
            };
        }

        $mappedDay = match (strtolower($reservationDate->englishDayOfWeek)) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        $price = $unite->prices()->where('day', $mappedDay)->first();

        // BUG FIX: this used to silently `return 0` when no matching
        // UnitePrice row existed at all, letting a reservation through at
        // zero cost with nothing anywhere flagging that pricing was never
        // actually configured for this venue/day. A missing row (or a row
        // that exists but has the relevant field left null — never set,
        // as distinct from explicitly set to 0) now rejects immediately
        // with a specific error, so this can never again slip through as
        // a silent free booking. hourly never reaches this function (see
        // the create()/reschedule() branching above), so it doesn't need
        // handling here — resolveHourlyPrice() already has its own,
        // equivalent check.
        if (! $price) {
            abort(422, __('lang.no_pricing_configured_for_day'));
        }

        if ($unite->type === 'stadium') {
            if (is_null($price->price)) {
                abort(422, __('lang.no_pricing_configured_for_day'));
            }

            return (float) $price->price;
        }

        $field = match ($periodType) {
            'morning' => 'morning_price',
            'evening' => 'evening_price',
            'full_day', 'custom' => 'full_price',
            default => null,
        };

        // $field is only null for a period_type this function was never
        // meant to price (e.g. 'package', which has its own resolver) —
        // genuinely unreachable via the real create()/reschedule() flows,
        // but abort rather than silently return 0 if it ever is.
        if ($field === null || is_null($price->{$field})) {
            abort(422, __('lang.no_pricing_configured_for_day'));
        }

        return (float) $price->{$field};
    }

    // -------------------------------------------------------------------------
    // Private: conflict guard
    // -------------------------------------------------------------------------

    /**
     * Checks for a booking conflict on this unite across a date RANGE, not
     * just a single date — needed because 'days'-type packages can span
     * multiple calendar days and must be checked against everything else
     * that unite has booked during that whole span, not just exact-date
     * matches.
     *
     * $startDate/$endDate: the range being checked. For a normal
     * single-day booking (hourly/morning/evening/full_day/'hours'-type
     * package), pass the same date for both and provide $fromTime/$toTime
     * to also check time-overlap against other single-day bookings on
     * that exact date. For a 'days'-type package, pass the real
     * check-in/check-out range and omit the times — a multi-day booking
     * occupies whole days regardless of what specific hours a conflicting
     * reservation happens to use.
     *
     * Conflict rules, matching the confirmed design exactly:
     *   - Always scoped to this one unite_id — a conflict on one unit has
     *     zero effect on any other unit, even with identical dates/times.
     *   - A new single-day booking conflicts with any EXISTING multi-day
     *     reservation whose range covers that date, full stop — a 5-day
     *     package occupies the whole day, not just specific hours.
     *   - A new multi-day booking conflicts with ANY existing reservation
     *     (single or multi-day) whose date falls anywhere in the range,
     *     for the same reason — an hourly booking on day 3 of a 5-day
     *     range blocks that package from being sold.
     *   - Time-overlap (from_time/to_time) is only actually checked when
     *     BOTH the new booking and the existing one are genuinely
     *     single-day on the exact same date — that's the one case where
     *     two bookings can coexist on the same day without conflicting.
     */
    protected function ensureNoConflict(
        int $uniteId,
        string $startDate,
        ?string $fromTime = null,
        ?string $toTime = null,
        ?int $ignoreId = null,
        ?string $endDate = null
    ): void {
        if (UniteReservation::conflicting($uniteId, $startDate, $endDate, $fromTime, $toTime, $ignoreId)->exists()) {
            abort(422, __('lang.time_slot_conflict'));
        }
    }

    /**
     * Calculate the total price for an hourly booking.
     *
     * Looks up the correct price row for the day, then uses
     * UnitePrice::calculateHourlyPrice() to split the time range
     * at the day/night boundary.
     */
    protected function resolveHourlyPrice(
        Unite $unite,
        string $fromTime,
        string $toTime,
        string $date
    ): float {
        $reservationDate = Carbon::parse($date);

        $mappedDay = match (strtolower($reservationDate->englishDayOfWeek)) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        $prices = $unite->relationLoaded('prices')
            ? $unite->prices
            : $unite->prices()->get();

        $priceRow = $prices->firstWhere('day', $mappedDay)
            ?? $prices->firstWhere('day', 'week_day')
            ?? $prices->first();

        if (! $priceRow) {
            abort(422, __('lang.no_pricing_configured_for_day'));
        }

        // BUG FIX: "! $priceRow->day_hour_price" was falsy for BOTH null
        // (never configured) and 0 (deliberately configured as free) —
        // conflating "not set up" with "explicitly free". is_null() only
        // rejects the genuinely-unconfigured case, letting a real 0 rate
        // through as the valid free booking it's meant to be.
        if (! $priceRow->hourly_enabled || is_null($priceRow->day_hour_price)) {
            abort(422, __('lang.hourly_booking_unavailable_for_day'));
        }

        // BUG FIX: active offers were never checked here at all — this
        // always used the base price row's rates even when a cheaper
        // promotional offer covered the requested date, unlike
        // AvailabilityService (fixed a couple sessions ago), which
        // already correctly prioritizes offer rates over the base price.
        // A non-persisted clone lets calculateHourlyPrice()'s existing
        // day/night-split logic run unchanged against the offer's rates
        // instead of duplicating that calculation here.
        $offer = $unite->offers()
            ->where('status', 'active')
            ->where('start', '<=', $date)
            ->where('end', '>=', $date)
            ->latest('id')
            ->first();

        $effectiveRow = $priceRow;
        if ($offer && $offer->day_hour_price) {
            $effectiveRow = clone $priceRow;
            $effectiveRow->day_hour_price = $offer->day_hour_price;
            $effectiveRow->night_hour_price = $offer->night_hour_price ?? $offer->day_hour_price;
        }

        // BUG FIX: this used to reject any calculated total of 0 outright
        // — but with day_hour_price already confirmed genuinely
        // configured (not null) above, the only way the total can still
        // be 0 here is a deliberately-configured free rate, which must
        // succeed, not be rejected. There's no longer a distinct
        // "calculation failed" case this check was catching that isn't
        // already covered by the configuration checks above it.
        return $effectiveRow->calculateHourlyPrice($fromTime, $toTime);
    }
}
