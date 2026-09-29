<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\MultiBookingGroup;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\ServiceFee;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds demonstration multi-unit booking groups.
 *
 * Strategy:
 *   For each department that has ≥ 2 active unites, create 3 multi-booking
 *   groups across upcoming dates, using the unites that the reservation
 *   seeder left free (all except the first in each department).
 *
 *   Each group gets:
 *     – One MultiBookingGroup row
 *     – One UniteReservation per selected unite (multi_booking_group_id set)
 *     – One consolidated Payment row
 *     – One PaymentItem per unite + optional service-fee item
 *
 *   Three scenarios:
 *     1. Confirmed + paid   (addDays +2)
 *     2. Pending  + pending (addDays +5)
 *     3. Cancelled + failed (addDays -3, past)
 */
class MultiBookingGroupSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('type', 'customer')->pluck('id')->values();

        if ($customers->isEmpty()) {
            return;
        }

        $serviceFeeAmount = ServiceFee::feeFor('reservation') ?: 0;

        $departments = Department::where('status', 'active')
            ->with(['unites' => fn ($q) => $q->where('status', 'active')->with(['prices', 'slots'])])
            ->get();

        $cIdx = 0;

        foreach ($departments as $dept) {
            // Need at least 2 unites per department for multi-booking
            $availableUnites = $dept->unites;
            if ($availableUnites->count() < 2) {
                continue;
            }

            // Use unites 2..N (index 1 onwards) — the first is taken by reservation seeder
            $multiUnites = $availableUnites->slice(1)->values();
            $pickCount = min($multiUnites->count(), 3); // pick up to 3 per group
            $selected = $multiUnites->take($pickCount);

            $scenarios = [
                ['days' => +2, 'group_status' => 'confirmed', 'pay_status' => 'paid',    'res_status' => 'confirmed', 'period' => 'morning'],
                ['days' => +5, 'group_status' => 'pending',   'pay_status' => 'pending', 'res_status' => 'pending',   'period' => 'evening'],
                ['days' => -3, 'group_status' => 'cancelled', 'pay_status' => 'failed',  'res_status' => 'cancelled', 'period' => 'morning'],
            ];

            foreach ($scenarios as $sIdx => $scenario) {
                $customerId = $customers[$cIdx % $customers->count()];
                $cIdx++;

                $date = Carbon::today()->addDays($scenario['days']);
                $periodType = $scenario['period'];
                $dayName = strtolower($date->englishDayOfWeek);

                // Resolve times and prices for each selected unite
                $items = [];
                $totalPrice = 0.0;
                $allOk = true;

                foreach ($selected as $unite) {
                    $slot = $unite->slots->firstWhere('day_of_week', $dayName);
                    if (! $slot) {
                        $allOk = false;
                        break;
                    }

                    [$from, $to] = match ($periodType) {
                        'morning' => [$slot->morning_start, $slot->morning_end],
                        'evening' => [$slot->evening_start, $slot->evening_end],
                        'full_day' => [$slot->full_start,    $slot->full_end],
                        default => [null, null],
                    };

                    if (! $from || ! $to) {
                        $allOk = false;
                        break;
                    }

                    $price = $this->resolvePrice($unite, $periodType, $date->format('Y-m-d'));
                    if ($price <= 0) {
                        $allOk = false;
                        break;
                    }

                    $items[] = compact('unite', 'from', 'to', 'price');
                    $totalPrice += $price;
                }

                if (! $allOk || empty($items)) {
                    continue;
                }

                $chargeAmount = $totalPrice + $serviceFeeAmount;

                // Create the group
                $group = MultiBookingGroup::create([
                    'user_id' => $customerId,
                    'department_id' => $dept->id,
                    'reservation_date' => $date->format('Y-m-d'),
                    'period_type' => $periodType,
                    'from_time' => $items[0]['from'],
                    'to_time' => $items[0]['to'],
                    'total_price' => round($totalPrice, 2),
                    'total_amount' => round($chargeAmount, 2),
                    'status' => $scenario['group_status'],
                ]);

                // One reservation per unite
                $reservations = [];
                foreach ($items as $item) {
                    $reservations[] = UniteReservation::create([
                        'multi_booking_group_id' => $group->id,
                        'unite_id' => $item['unite']->id,
                        'user_id' => $customerId,
                        'reservation_date' => $date->format('Y-m-d'),
                        'period_type' => $periodType,
                        'from_time' => $item['from'],
                        'to_time' => $item['to'],
                        'price' => $item['price'],
                        'status' => $scenario['res_status'],
                    ]);
                }

                // One consolidated payment
                $ref = 'MPAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
                $payment = Payment::create([
                    'user_id' => $customerId,
                    'payment_type' => 'geidea',
                    'amount' => round($chargeAmount, 2),
                    'reference_id' => $ref,
                    'payment_id' => 'GD-'.strtoupper(Str::random(12)),
                    'status' => $scenario['pay_status'],
                    'phone' => '96650000000'.$sIdx,
                    'service_fee_amount' => $serviceFeeAmount > 0 ? $serviceFeeAmount : null,
                ]);

                // Payment items — one per unite + optional fee
                $baseTotal = $chargeAmount - $serviceFeeAmount;
                foreach ($reservations as $i => $res) {
                    $unitShare = $totalPrice > 0
                        ? round($items[$i]['price'] / $totalPrice * $baseTotal, 2)
                        : round($baseTotal / count($reservations), 2);

                    PaymentItem::create([
                        'payment_id' => $payment->id,
                        'name' => $items[$i]['unite']->name.' — '.ucfirst(str_replace('_', ' ', $periodType)),
                        'item_number' => (string) $res->id,
                        'price' => $unitShare,
                        'quantity' => 1,
                        'total_amount' => $unitShare,
                    ]);
                }

                if ($serviceFeeAmount > 0) {
                    PaymentItem::create([
                        'payment_id' => $payment->id,
                        'name' => 'Service Fee',
                        'item_number' => 'fee-'.$group->id,
                        'price' => $serviceFeeAmount,
                        'quantity' => 1,
                        'total_amount' => $serviceFeeAmount,
                    ]);
                }

                // Link payment back to group
                $group->update(['payment_id' => $payment->id]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Inline price resolver — mirrors UniteReservationRepository::resolvePrice()
    // for the period types we seed (morning / evening / full_day only).
    // Does not need the full repo since we have prices eager-loaded.
    // ─────────────────────────────────────────────────────────────────────────
    private function resolvePrice(Unite $unite, string $periodType, string $date): float
    {
        $carbon = Carbon::parse($date);
        $dow = strtolower($carbon->englishDayOfWeek);
        $dayKey = match ($dow) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        $priceRow = $unite->prices->firstWhere('day', $dayKey);

        if (! $priceRow) {
            return 0.0;
        }

        return (float) match ($periodType) {
            'morning' => $priceRow->morning_price ?? $priceRow->price ?? 0,
            'evening' => $priceRow->evening_price ?? $priceRow->price ?? 0,
            'full_day' => $priceRow->full_price ?? $priceRow->price ?? 0,
            default => $priceRow->price ?? 0,
        };
    }
}
