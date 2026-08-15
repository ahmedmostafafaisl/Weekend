<?php

namespace Tests\Feature\Availability;

use App\Models\Department;
use App\Models\Unite;
use App\Models\UnitePrice;
use App\Models\UniteSlot;
use App\Models\UniteSlotPeriod;
use App\Models\User;

/**
 * Shared fixture builders for the availability feature tests. No factories
 * exist yet for Unite/Department/UniteSlot (confirmed by checking
 * database/factories before writing these), so fixtures are built directly
 * here via ::create() with explicit required fields, rather than
 * duplicating this setup across every test class.
 */
trait BuildsAvailabilityFixtures
{
    protected function makeProvider(): User
    {
        return User::create([
            'name' => 'Test Provider',
            'email' => 'provider'.uniqid().'@example.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
            'type' => 'provider',
        ]);
    }

    protected function makeDepartment(User $owner): Department
    {
        return Department::create([
            'user_id' => $owner->id,
            'name' => 'Test Department',
            'type' => 'lounge',
            'location' => 'Riyadh',
            'status' => 'active',
        ]);
    }

    protected function makeUnite(Department $department, string $type = 'lounge', array $overrides = []): Unite
    {
        return Unite::create(array_merge([
            'department_id' => $department->id,
            'type' => $type,
            'name' => 'Test Unite',
            'description' => 'Test description',
            'location_name' => 'Riyadh',
            'city' => 'Riyadh',
            'status' => 'active',
            // Critical: avoids create()'s real-payment-gateway branch (Geidea)
            // entirely -- see AvailabilityFeatureTest's class doc for why.
            'requires_approval' => true,
        ], $overrides));
    }

    /**
     * @param  array<int, array{start_time: string, end_time: string, status?: string}>  $periods
     */
    protected function makeSlot(Unite $unite, string $dayOfWeek, array $attributes = [], array $periods = []): UniteSlot
    {
        $slot = UniteSlot::create(array_merge([
            'unite_id' => $unite->id,
            'day_of_week' => $dayOfWeek,
            'status' => 'available',
            'morning_start' => '06:00',
            'morning_end' => '12:00',
            'evening_start' => '16:00',
            'evening_end' => '23:00',
            'full_start' => '06:00',
            'full_end' => '23:00',
        ], $attributes));

        foreach ($periods as $period) {
            UniteSlotPeriod::create([
                'unite_slot_id' => $slot->id,
                'start_time' => $period['start_time'],
                'end_time' => $period['end_time'],
                'status' => $period['status'] ?? 'available',
            ]);
        }

        return $slot->fresh('periods');
    }

    protected function makePrice(Unite $unite, string $day, array $overrides = []): UnitePrice
    {
        return UnitePrice::create(array_merge([
            'unite_id' => $unite->id,
            'day' => $day,
            'morning_price' => 100,
            'evening_price' => 150,
            'full_price' => 300,
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'day_start' => '06:00',
            'day_end' => '18:00',
            'min_booking_minutes' => 1,
        ], $overrides));
    }
}
