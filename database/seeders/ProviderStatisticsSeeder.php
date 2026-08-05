<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ProviderStatisticsSeeder
 *
 * Generates realistic 12-month reservation + payment history for every provider
 * so the provider statistics API, admin widget, and provider dashboard widget
 * all show meaningful charts and numbers.
 *
 * Patterns per provider:
 *   - Peak season (May–Aug): high bookings + revenue
 *   - Shoulder (Mar–Apr, Sep–Oct): medium
 *   - Quiet (Nov–Feb): lower
 *   - Each provider has a unique revenue multiplier (×0.6 – ×1.4) for variety
 *   - 2–8 bookings per venue per month, randomly spread across days
 *   - Mix of morning / evening / full_day periods
 *   - ~85% confirmed+paid, ~10% pending, ~5% cancelled
 *
 * Run standalone:  php artisan db:seed --class=ProviderStatisticsSeeder
 * Re-run safe:     uses updateOrCreate — no duplicates
 */
class ProviderStatisticsSeeder extends Seeder
{
    // Monthly demand multipliers (1 = baseline)
    private const MONTHLY_DEMAND = [
        1 => 0.55,   // January  — quiet
        2 => 0.60,   // February
        3 => 0.80,   // March    — shoulder
        4 => 0.90,   // April
        5 => 1.30,   // May      — peak
        6 => 1.40,   // June     — peak
        7 => 1.35,   // July     — peak
        8 => 1.20,   // August   — peak
        9 => 0.95,   // September
        10 => 0.85,  // October
        11 => 0.65,  // November — quiet
        12 => 0.70,  // December
    ];

    // Revenue multiplier per provider (indexed 0-4)
    private const PROVIDER_MULTIPLIERS = [1.4, 1.0, 0.8, 1.2, 0.6];

    public function run(): void
    {
        $year = now()->year;
        $customers = User::where('type', 'customer')->pluck('id')->values();
        $providers = User::where('type', 'provider')->get();

        if ($customers->isEmpty() || $providers->isEmpty()) {
            $this->command->warn('ProviderStatisticsSeeder: no providers or customers found — run UsersTableSeeder first.');

            return;
        }

        $this->command->info("Seeding provider statistics for {$providers->count()} providers × 12 months...");

        foreach ($providers as $pIdx => $provider) {
            $providerMultiplier = self::PROVIDER_MULTIPLIERS[$pIdx % count(self::PROVIDER_MULTIPLIERS)];

            $unites = Unite::with(['prices', 'slots'])
                ->whereHas('department', fn ($q) => $q->where('user_id', $provider->id))
                ->get();

            if ($unites->isEmpty()) {
                $this->command->warn("  [{$provider->name}] No venues — skipping.");

                continue;
            }

            $this->command->line("  [{$provider->name}] {$unites->count()} venues...");
            $created = 0;

            foreach (range(1, 12) as $month) {
                // Skip months in the future beyond the current month
                if ($month > now()->month) {
                    continue;
                }

                $demandFactor = self::MONTHLY_DEMAND[$month] * $providerMultiplier;
                $bookingsPerVenue = (int) round(max(1, 6 * $demandFactor));
                $daysInMonth = Carbon::create($year, $month)->daysInMonth;

                foreach ($unites as $uIdx => $unite) {
                    for ($b = 0; $b < $bookingsPerVenue; $b++) {
                        $day = rand(1, $daysInMonth);
                        $date = Carbon::create($year, $month, $day);
                        $custId = $customers[($pIdx + $uIdx + $b + $month) % $customers->count()];
                        $seed = $pIdx * 1000 + $uIdx * 100 + $b * 10 + $month;

                        [$period, $from, $to] = $this->pickPeriod($unite, $seed);
                        $price = $this->resolvePrice($unite, $period, $date->format('Y-m-d'));

                        if ($price <= 0) {
                            continue;
                        }

                        // Apply demand-based price variation (±15%)
                        $price = round($price * (0.85 + ($demandFactor * 0.15)), 2);

                        // Status distribution: 85% confirmed, 10% pending, 5% cancelled
                        $roll = ($seed * 7) % 100;
                        $status = match (true) {
                            $roll < 85 => 'confirmed',
                            $roll < 95 => 'pending',
                            default => 'cancelled',
                        };

                        // Past reservations are always confirmed
                        if ($date->isPast()) {
                            $status = 'confirmed';
                        }

                        $res = UniteReservation::updateOrCreate(
                            [
                                'unite_id' => $unite->id,
                                'user_id' => $custId,
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
                            $payStatus = ($status === 'confirmed' && $date->isPast()) ? 'paid'
                                : (($roll % 3 === 0) ? 'pending' : 'paid');

                            Payment::firstOrCreate(
                                ['reservation_id' => $res->id],
                                [
                                    'user_id' => $custId,
                                    'payment_type' => collect(['geidea', 'tappy', 'tamara', 'tabby'])->random(),
                                    'amount' => $price,
                                    'reference_id' => 'STAT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                                    'payment_id' => 'P-'.strtoupper(Str::random(10)),
                                    'status' => $payStatus,
                                    'phone' => '9665'.(string) (10000000 + $custId),
                                    'created_at' => $date->copy()->setTime(rand(8, 22), rand(0, 59)),
                                    'updated_at' => $date->copy()->setTime(rand(8, 22), rand(0, 59)),
                                ]
                            );

                            $created++;
                        }
                    }
                }
            }

            $this->command->line("    → {$created} payments created");
        }

        $this->command->info('ProviderStatisticsSeeder done ✓');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function pickPeriod(Unite $unite, int $seed): array
    {
        if ($unite->type === 'stadium') {
            return ['full_day', '18:00:00', '22:00:00'];
        }

        return match ($seed % 3) {
            0 => ['morning',  '08:00:00', '13:00:00'],
            1 => ['evening',  '16:00:00', '23:00:00'],
            default => ['full_day', '08:00:00', '23:00:00'],
        };
    }

    private function resolvePrice(Unite $unite, string $period, string $date): float
    {
        $dayName = match (strtolower(Carbon::parse($date)->englishDayOfWeek)) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        $priceRow = $unite->prices->where('day', $dayName)->first()
            ?? $unite->prices->where('day', 'week_day')->first()
            ?? $unite->prices->first();

        if (! $priceRow) {
            return 0.0;
        }

        return (float) match ($unite->type) {
            'stadium' => $priceRow->price ?? 0,
            default => match ($period) {
                'morning' => $priceRow->morning_price ?? 0,
                'evening' => $priceRow->evening_price ?? 0,
                default => $priceRow->full_price ?? 0,
            },
        };
    }
}
