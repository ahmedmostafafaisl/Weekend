<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UniteReservationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('type', 'customer')->pluck('id')->values();
        $unites = Unite::with(['prices', 'slots'])->get();

        if ($customers->isEmpty() || $unites->isEmpty()) {
            return;
        }

        $statuses = ['confirmed', 'confirmed', 'confirmed', 'pending', 'cancelled'];
        $paymentStatuses = ['paid', 'paid', 'paid', 'pending', 'failed'];

        foreach ($unites as $uIdx => $unite) {
            // 15 future reservations + 10 past reservations per unite
            $scenarios = array_merge(
                array_map(fn ($i) => ['days' => $i + 1, 'past' => false], range(1, 15)),
                array_map(fn ($i) => ['days' => -($i + 1), 'past' => true], range(1, 10))
            );

            foreach ($scenarios as $sIdx => $s) {
                $date = Carbon::today()->addDays($s['days']);
                $customerId = $customers[($uIdx + $sIdx) % $customers->count()];
                $statusIdx = $sIdx % count($statuses);
                $status = $s['past'] ? 'confirmed' : $statuses[$statusIdx];

                [$period, $from, $to] = $this->pickPeriod($unite, $sIdx);
                $price = $this->resolvePrice($unite, $period, $date->format('Y-m-d'), $from, $to);

                if ($price <= 0) {
                    continue;
                }

                $res = UniteReservation::updateOrCreate(
                    [
                        'unite_id' => $unite->id,
                        'user_id' => $customerId,
                        'reservation_date' => $date->format('Y-m-d'),
                        'period_type' => $period,
                    ],
                    [
                        'from_time' => $from,
                        'to_time' => $to,
                        'price' => $price,
                        'status' => $status,
                    ]
                );

                // Create payment for confirmed/pending
                if (in_array($status, ['confirmed', 'pending'])) {
                    $payStatus = $s['past'] ? 'paid' : $paymentStatuses[$statusIdx];

                    $existing = Payment::where('reservation_id', $res->id)->first();
                    if (! $existing) {
                        $ref = 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
                        $payment = Payment::create([
                            'user_id' => $customerId,
                            'reservation_id' => $res->id,
                            'payment_type' => 'geidea',
                            'amount' => $price,
                            'reference_id' => $ref,
                            'payment_id' => 'GD-'.strtoupper(Str::random(12)),
                            'status' => $payStatus,
                            'phone' => '96650000000'.$sIdx,
                        ]);

                        PaymentItem::create([
                            'payment_id' => $payment->id,
                            'name' => $unite->name.' — '.ucfirst(str_replace('_', ' ', $period)),
                            'item_number' => (string) $res->id,
                            'price' => $price,
                            'quantity' => 1,
                            'total_amount' => $price,
                        ]);
                    }
                }
            }
        }
    }

    private function pickPeriod(Unite $unite, int $idx): array
    {
        if ($unite->type === 'stadium') {
            // BUG FIX: stadiums are hourly-only — this used to seed
            // 'full_day' bookings, which the live reservation flow now
            // explicitly rejects for this type (UniteReservationRepository
            // aborts with venue_hourly_only). Cycles through a morning-only
            // booking, an evening-only booking, and one that genuinely
            // spans both rate windows — the same case
            // UnitePrice::calculateHourlyPrice() exists to price correctly
            // as a blend rather than a single flat rate.
            return match ($idx % 3) {
                0 => ['hourly', '10:00:00', '12:00:00'], // pure morning-rate window
                1 => ['hourly', '19:00:00', '21:00:00'], // pure evening-rate window
                default => ['hourly', '17:00:00', '20:00:00'], // spans both — priced as a blend
            };
        }

        if ($unite->type === 'hall') {
            // BUG FIX: halls are full-day only — their slots have no
            // morning/evening data at all anymore, so seeding those period
            // types here would create reservations the live booking flow
            // could never actually produce.
            return ['full_day', '08:00:00', '23:00:00'];
        }

        return match ($idx % 3) {
            0 => ['morning',  '08:00:00', '13:00:00'],
            1 => ['evening',  '16:00:00', '23:00:00'],
            default => ['full_day', '08:00:00', '23:00:00'],
        };
    }

    private function resolvePrice(Unite $unite, string $period, string $date, ?string $from = null, ?string $to = null): float
    {
        $day = match (strtolower(Carbon::parse($date)->englishDayOfWeek)) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        $price = $unite->prices->where('day', $day)->first();
        if (! $price) {
            return 0;
        }

        if ($unite->type === 'stadium') {
            // BUG FIX: use the exact same minute-by-minute day/night split
            // the live reservation flow uses, instead of the flat 'price'
            // field — that field is now optional/vestigial for this type,
            // and using it here would silently ignore day_hour_price/
            // night_hour_price entirely.
            return $from && $to ? $price->calculateHourlyPrice($from, $to) : 0;
        }

        return (float) match ($period) {
            'morning' => $price->morning_price ?? 0,
            'evening' => $price->evening_price ?? 0,
            default => $price->full_price ?? 0,
        };
    }
}
