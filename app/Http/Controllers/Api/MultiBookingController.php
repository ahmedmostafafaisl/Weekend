<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MultiBookingGroup;
use App\Models\Payment;
use App\Models\ServiceFee;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Repositories\Reservation\UniteReservationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MultiBookingController extends Controller
{
    public function __construct(
        protected UniteReservationRepository $repo
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/departments/{department}/available-unites
    //
    // Returns every active unite inside the department that has no
    // confirmed/pending conflict for the requested shift, together with its
    // price for that shift.  The client uses this to build the unit-picker UI.
    //
    // Query params:
    //   reservation_date  required  YYYY-MM-DD
    //   period_type       required  morning|evening|full_day|hourly
    //   from_time         required when period_type=hourly  HH:mm
    //   to_time           required when period_type=hourly  HH:mm
    //   end_date          optional  for multi-day full_day ranges
    // ─────────────────────────────────────────────────────────────────────────

    public function availableUnites(Request $request, Department $department): JsonResponse
    {
        $data = $request->validate([
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:reservation_date'],
            'period_type' => ['required', 'in:morning,evening,full_day,hourly'],
            'from_time' => ['nullable', 'required_if:period_type,hourly', 'date_format:H:i'],
            'to_time' => ['nullable', 'required_if:period_type,hourly', 'date_format:H:i'],
        ]);

        $date = $data['reservation_date'];
        $periodType = $data['period_type'];
        $dayOfWeek = strtolower(\Carbon\Carbon::parse($date)->englishDayOfWeek);

        // Price day-category (matches UnitePricesTableSeeder and resolvePrice())
        $dayCategory = match ($dayOfWeek) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        $unites = $department->unites()
            ->with(['images'])          // only what we actually output
            ->where('status', 'active')
            ->get();

        $available = [];

        foreach ($unites as $unite) {

            // 1. Skip venue types that don't support this period
            if (! in_array($periodType, $unite->allowedPeriodTypes())) {
                continue;
            }

            // 2. Look up the slot for this day directly (no abort(), no try-catch)
            $slot = \App\Models\UniteSlot::where('unite_id', $unite->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('status', 'available')
                ->first();

            if (! $slot) {
                continue;
            }

            // 3. Resolve the period start/end from the slot
            [$fromTime, $toTime] = match ($periodType) {
                'morning' => [$slot->morning_start, $slot->morning_end],
                'evening' => [$slot->evening_start, $slot->evening_end],
                'full_day' => [$slot->full_start,    $slot->full_end],
                'hourly' => [$data['from_time'] ?? null, $data['to_time'] ?? null],
                default => [null, null],
            };

            // Skip if this period is not configured on the slot
            if (! $fromTime || ! $toTime) {
                continue;
            }

            $bufferMinutes = (int) ($slot->buffer_minutes ?? 0);
            $endDate = $data['end_date'] ?? null;

            // 4. Conflict check — correct parameter order matches scopeConflicting signature:
            //    ($query, uniteId, startDate, endDate, fromTime, toTime, ignoreId, bufferMinutes)
            $hasConflict = \App\Models\UniteReservation::scopeConflicting(
                \App\Models\UniteReservation::query(),
                $unite->id,
                $date,
                $endDate,
                $fromTime,
                $toTime,
                null,
                $bufferMinutes
            )->whereIn('status', ['pending', 'confirmed', 'pending_approval'])->exists();

            if ($hasConflict) {
                continue;
            }

            // 5. Get price directly — no try-catch, no abort()
            $priceRow = \App\Models\UnitePrice::where('unite_id', $unite->id)
                ->where('day', $dayCategory)
                ->first();

            if (! $priceRow) {
                continue;
            }

            $price = (float) match ($periodType) {
                'morning' => $priceRow->morning_price ?? 0,
                'evening' => $priceRow->evening_price ?? 0,
                'full_day' => $priceRow->full_price ?? 0,
                'hourly' => $priceRow->price ?? 0,
                default => 0,
            };

            $available[] = [
                'id' => $unite->id,
                'name' => $unite->name,
                'type' => $unite->type,
                'description' => $unite->description,
                'capacity' => $unite->capacity ?? null,
                'price' => round($price, 2),
                'from_time' => substr($fromTime, 0, 5),
                'to_time' => substr($toTime, 0, 5),
                'images' => $unite->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => asset($img->image),
                ])->values(),
            ];
        }

        return response()->json([
            'data' => $available,
            'meta' => [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'reservation_date' => $date,
                'period_type' => $periodType,
                'available_count' => count($available),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/multi-booking
    //
    // Books multiple unites within the same department for the same shift.
    // Creates one UniteReservation per selected unite and one Payment for the
    // consolidated total, then returns a single payment URL.
    //
    // Body:
    //   department_id     required  int
    //   unite_ids         required  array of int (min 2, same department)
    //   reservation_date  required  YYYY-MM-DD
    //   end_date          optional  YYYY-MM-DD  (full_day multi-day only)
    //   period_type       required  morning|evening|full_day|hourly
    //   from_time         required when hourly
    //   to_time           required when hourly
    //   payment_method    optional  geidea (default)|tabby|tamara|maysar
    //   promo_code        optional  string
    //   notes             optional  string
    //   guest_count       optional  int
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        // Customers only
        if (! $request->user() || $request->user()->type !== 'customer') {
            return response()->json(['message' => __('lang.customers_only')], 403);
        }

        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'unite_ids' => ['required', 'array', 'min:2'],
            'unite_ids.*' => ['integer', 'exists:unites,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:reservation_date'],
            'period_type' => ['required', 'in:morning,evening,full_day,hourly'],
            'from_time' => ['nullable', 'required_if:period_type,hourly', 'date_format:H:i'],
            'to_time' => ['nullable', 'required_if:period_type,hourly', 'date_format:H:i'],
            'payment_method' => ['nullable', 'in:geidea,tabby,tamara,maysar'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
        ]);

        $userId = $request->user()->id;
        $department = Department::findOrFail($data['department_id']);

        // Load and validate all unites belong to this department
        $unites = Unite::with(['prices', 'offers', 'slots'])
            ->whereIn('id', $data['unite_ids'])
            ->where('department_id', $department->id)
            ->where('status', 'active')
            ->get();

        if ($unites->count() !== count(array_unique($data['unite_ids']))) {
            return response()->json([
                'message' => __('lang.multi_booking_invalid_unites'),
            ], 422);
        }

        // Check no unite requires manual approval — multi-booking skips that flow
        $requiresApproval = $unites->first(fn ($u) => $u->requires_approval);
        if ($requiresApproval) {
            return response()->json([
                'message' => __('lang.multi_booking_approval_not_supported'),
            ], 422);
        }

        // Resolve times, check conflicts, and price each unite BEFORE the transaction
        $resolvedUnites = [];
        $totalPrice = 0.0;

        foreach ($unites as $unite) {
            try {
                [$fromTime, $toTime, $endDate, $bufferMinutes] = $this->repo->resolveTimes($unite, $data);
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            // Conflict check per unite — correct parameter order:
            // ($query, uniteId, startDate, endDate, fromTime, toTime, ignoreId, bufferMinutes)
            $conflict = UniteReservation::scopeConflicting(
                UniteReservation::query(),
                $unite->id,
                $data['reservation_date'],
                $endDate ?? null,
                $fromTime,
                $toTime,
                null,
                $bufferMinutes
            )->whereIn('status', ['pending', 'confirmed', 'pending_approval'])->exists();

            if ($conflict) {
                return response()->json([
                    'message' => __('lang.multi_booking_conflict', ['name' => $unite->name]),
                ], 422);
            }

            // Resolve price
            try {
                if ($data['period_type'] === 'hourly') {
                    $price = $this->repo->resolveHourlyPrice($unite, $fromTime, $toTime, $data['reservation_date']);
                } elseif ($data['period_type'] === 'full_day' && ! empty($endDate)) {
                    $price = $this->repo->resolveFullDayRangePrice($unite, $data['reservation_date'], $endDate);
                } else {
                    $price = $this->repo->resolvePrice($unite, $data['period_type'], $data['reservation_date']);
                }
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $resolvedUnites[] = compact('unite', 'fromTime', 'toTime', 'endDate', 'bufferMinutes', 'price');
            $totalPrice += (float) $price;
        }

        // Promo code — applied once on the combined total
        $promoResult = null;
        $chargeAmount = $totalPrice;

        if (! empty($data['promo_code'])) {
            $promoResult = app(\App\Services\PromoCode\PromoCodeService::class)
                ->validate($data['promo_code'], $chargeAmount, $userId);
            if (! $promoResult['valid']) {
                return response()->json(['message' => $promoResult['message']], 422);
            }
            $chargeAmount = $promoResult['final_amount'];
        }

        // Service fee — one fee for the whole booking group
        $serviceFee = ServiceFee::feeFor('reservation');
        $chargeAmount += $serviceFee;

        $paymentMethod = $data['payment_method'] ?? 'geidea';
        $gateway = \App\Services\Payment\PaymentMethodFactory::make($paymentMethod);

        try {
            $result = DB::transaction(function () use (
                $data, $userId, $department, $resolvedUnites,
                $totalPrice, $chargeAmount, $serviceFee,
                $promoResult, $gateway, $paymentMethod, $request
            ) {
                // 1. Create the group
                $group = MultiBookingGroup::create([
                    'user_id' => $userId,
                    'department_id' => $department->id,
                    'reservation_date' => $data['reservation_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'period_type' => $data['period_type'],
                    'from_time' => $data['from_time'] ?? null,
                    'to_time' => $data['to_time'] ?? null,
                    'total_price' => $totalPrice,
                    'total_amount' => $chargeAmount,
                    'status' => 'pending',
                ]);

                // 2. Create one reservation per unite
                $reservations = [];
                foreach ($resolvedUnites as $item) {
                    $reservations[] = UniteReservation::create([
                        'multi_booking_group_id' => $group->id,
                        'unite_id' => $item['unite']->id,
                        'user_id' => $userId,
                        'reservation_date' => $data['reservation_date'],
                        'end_date' => $item['endDate'],
                        'period_type' => $data['period_type'],
                        'from_time' => $item['fromTime'],
                        'to_time' => $item['toTime'],
                        'price' => $item['price'],
                        'status' => 'pending',
                        'guest_count' => $data['guest_count'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);
                }

                // 3. One consolidated payment row
                $user = $request->user();
                $phone = $user->phone ?? null;

                $payment = Payment::create([
                    'user_id' => $userId,
                    'payment_type' => $paymentMethod,
                    'amount' => $chargeAmount,
                    'status' => 'pending',
                    'phone' => $phone,
                    'promo_code_id' => $promoResult ? $promoResult['promo_code']->id : null,
                    'discount_amount' => $promoResult ? $promoResult['discount_amount'] : null,
                    'original_amount' => $promoResult ? $promoResult['original_amount'] : null,
                    'service_fee_amount' => $serviceFee > 0 ? $serviceFee : null,
                ]);

                // Payment line items — one per unite + optional fee line
                $baseTotal = $chargeAmount - $serviceFee;
                foreach ($resolvedUnites as $idx => $item) {
                    // Distribute base amount proportionally per unite
                    $unitShare = count($resolvedUnites) > 0
                        ? round($item['price'] / ($totalPrice ?: 1) * $baseTotal, 2)
                        : round($baseTotal / count($resolvedUnites), 2);

                    $payment->items()->create([
                        'name' => $item['unite']->name.' — '.ucfirst(str_replace('_', ' ', $data['period_type'])),
                        'item_number' => (string) $reservations[$idx]->id,
                        'price' => $unitShare,
                        'quantity' => 1,
                        'total_amount' => $unitShare,
                    ]);
                }
                if ($serviceFee > 0) {
                    $payment->items()->create([
                        'name' => __('lang.service_fee'),
                        'item_number' => 'fee-'.$group->id,
                        'price' => $serviceFee,
                        'quantity' => 1,
                        'total_amount' => $serviceFee,
                    ]);
                }

                // 4. Call the payment gateway
                $departmentName = $department->name ?? 'Department';
                $unitNames = collect($resolvedUnites)->map(fn ($i) => $i['unite']->name)->implode(', ');

                $gatewayResult = $gateway->sendPayment([
                    'amount' => $chargeAmount,
                    'currency' => 'SAR',
                    'description' => "Multi-booking — {$departmentName}: {$unitNames}",
                    'reference' => $payment->reference_id,
                    'customer' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phoneNumber' => $phone,
                        'phoneCountryCode' => '+966',
                    ],
                ]);

                $paymentUrl = $gatewayResult['payment_url'] ?? null;

                // 5. Store payment ID + URL on group and payment
                $payment->update([
                    'payment_id' => $gatewayResult['payment_id'] ?? null,
                ]);

                $group->update([
                    'payment_id' => $payment->id,
                    'payment_url' => $paymentUrl,
                ]);

                return compact('group', 'reservations', 'payment', 'paymentUrl');
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $group = $result['group'];
        $reservations = $result['reservations'];
        $payment = $result['payment'];

        return response()->json([
            'success' => true,
            'message' => __('lang.multi_booking_created'),
            'data' => [
                'group_id' => $group->id,
                'department' => $department->name,
                'period_type' => $group->period_type,
                'date' => $group->reservation_date->toDateString(),
                'reservations' => collect($reservations)->map(fn ($r) => [
                    'id' => $r->id,
                    'unite_id' => $r->unite_id,
                    'status' => $r->status,
                ])->values(),
                'total_price' => round($totalPrice, 2),
                'service_fee' => $serviceFee,
                'total_amount' => round($chargeAmount, 2),
                'payment' => [
                    'id' => $payment->id,
                    'reference_id' => $payment->reference_id,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                ],
                'payment_url' => $result['paymentUrl'],
            ],
        ], 201);
    }
}
