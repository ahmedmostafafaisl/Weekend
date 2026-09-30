<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unite;
use App\Models\UnitePrice;
use App\Models\UniteReservation;
use App\Models\UniteSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * AvailableUnitesTestSeeder
 *
 * Documents and validates the exact IDs that the Postman collection
 * hard-codes so any developer running migrate:fresh --seed gets a
 * confirmed working date + department + units without variable editing.
 *
 * GUARANTEED AVAILABLE UNITS AFTER SEEDING:
 *
 *   GET /api/departments/7/available-unites
 *       ?reservation_date=<today+10>
 *       &period_type=morning
 *
 *   Returns:
 *     ID 16 — صالة الواحة       — 300 SAR (week_day morning)
 *     ID 17 — صالة فيلا الغروب — 300 SAR (week_day morning)
 *
 * POSTMAN COLLECTION VARIABLES:
 *   dept_id      = 7
 *   unite_id_2   = 16
 *   unite_id_3   = 17
 *   booking_date = today+10  (pre-request script sets this dynamically)
 *   period_type  = morning
 */
class AvailableUnitesTestSeeder extends Seeder
{
    /** Target department + unit IDs derived from seeding order */
    private const DEPT_ID = 7;

    private const UNITE_ID_2 = 16;

    private const UNITE_ID_3 = 17;

    private const PERIOD = 'morning';

    private const DATE_OFFSET = 10;   // days ahead — clear of all group-seeder bookings

    public function run(): void
    {
        $testDate = Carbon::today()->addDays(self::DATE_OFFSET)->toDateString();

        $this->command->info('');
        $this->command->info('AvailableUnitesTestSeeder — validating seeded state...');

        $issues = $this->validate($testDate);

        if (! empty($issues)) {
            $this->command->error('Validation FAILED — issues found:');
            foreach ($issues as $issue) {
                $this->command->error("  * {$issue}");
            }
            $this->command->warn('Run php artisan db:seed --class=AvailableUnitesTestSeeder after checking seeder order.');

            return;
        }

        $dept = Department::find(self::DEPT_ID);
        $this->command->info('');
        $this->command->info('  ✅  All checks passed. Use these values in Postman:');
        $this->command->info('');
        $this->command->info('  Collection variables:');
        $this->command->info('    dept_id      = '.self::DEPT_ID.'  ('.$dept->name.')');
        foreach (['unite_id_2' => self::UNITE_ID_2, 'unite_id_3' => self::UNITE_ID_3] as $var => $uid) {
            $unite = Unite::find($uid);
            $this->command->info(sprintf('    %-12s = %d  (%s — %s SAR %s)', $var, $uid, $unite->name, $this->priceFor($uid, $testDate), self::PERIOD));
        }
        $this->command->info('    booking_date = '.$testDate.'  (today+'.self::DATE_OFFSET.')');
        $this->command->info('    period_type  = '.self::PERIOD);
        $this->command->info('');
        $this->command->info('  Test URL (no auth needed):');
        $this->command->info('    GET /api/departments/'.self::DEPT_ID.'/available-unites');
        $this->command->info('        ?reservation_date='.$testDate.'&period_type='.self::PERIOD);
        $this->command->info('');
        $this->command->info('  Expected: 2 units returned (IDs '.self::UNITE_ID_2.' and '.self::UNITE_ID_3.')');
        $this->command->info('  Multi-booking POST /api/multi-booking with these IDs should succeed.');
        $this->command->info('');
    }

    /** Actual seeded price for the test date (day category depends on the weekday). */
    private function priceFor(int $uniteId, string $date): string
    {
        $dayCategory = match (strtolower(Carbon::parse($date)->englishDayOfWeek)) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };
        $price = UnitePrice::where('unite_id', $uniteId)->where('day', $dayCategory)->value('morning_price');

        return $price !== null ? number_format((float) $price, 0) : '?';
    }

    private function validate(string $testDate): array
    {
        $issues = [];

        // 1. Dept exists
        $dept = Department::find(self::DEPT_ID);
        if (! $dept) {
            $issues[] = 'Department ID '.self::DEPT_ID.' not found — run DepartmentsTableSeeder first';

            return $issues;
        }
        if ($dept->type !== 'lounge') {
            $issues[] = 'Dept '.self::DEPT_ID." type={$dept->type}, expected lounge";
        }

        // 2. Units exist and belong to the right dept
        foreach ([self::UNITE_ID_2, self::UNITE_ID_3] as $uid) {
            $u = Unite::find($uid);
            if (! $u) {
                $issues[] = "Unite ID {$uid} not found";

                continue;
            }
            if ($u->department_id !== self::DEPT_ID) {
                $issues[] = "Unite {$uid} belongs to dept {$u->department_id}, expected ".self::DEPT_ID;
            }
            if ($u->status !== 'active') {
                $issues[] = "Unite {$uid} status={$u->status}, expected active";
            }

            // 3. Slot exists
            if (UniteSlot::where('unite_id', $uid)->whereNotNull('morning_start')->count() === 0) {
                $issues[] = "Unite {$uid} has no morning slots";
            }

            // 4. Price exists
            if (UnitePrice::where('unite_id', $uid)->whereNotNull('morning_price')->count() === 0) {
                $issues[] = "Unite {$uid} has no morning price";
            }

            // 5. No conflicts on testDate morning
            $conflicts = UniteReservation::where('unite_id', $uid)
                ->where('reservation_date', $testDate)
                ->whereIn('status', ['pending', 'confirmed', 'pending_approval'])
                ->count();
            if ($conflicts > 0) {
                $issues[] = "Unite {$uid} has {$conflicts} conflict(s) on {$testDate}";
            }
        }

        return $issues;
    }
}
