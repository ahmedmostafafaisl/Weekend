<?php

namespace Tests\Feature\Booking;

use App\Models\Department;
use App\Models\Unite;
use App\Models\UnitePrice;
use App\Models\UniteReservation;
use App\Models\UniteSlot;
use App\Models\User;
use App\Support\OvernightRange;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for overnight booking support.
 *
 * The overnight booking feature was a significant, multi-layer fix
 * delivered across this engagement. These tests permanently protect
 * against regression in every layer:
 *
 *   - OvernightRange::normalizeEnd() helper
 *   - UniteSlot::isWithinAvailableWindow()
 *   - UnitePrice::calculateHourlyPrice()
 *   - UniteReservation::scopeConflicting()
 *   - Validation: StoreReservationRequest (to_time no longer requires after:from_time)
 */
class OvernightBookingTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────────────────────────────────────────────────────────────
    // OvernightRange helper
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function normalize_end_bumps_end_forward_when_it_equals_start(): void
    {
        $start = Carbon::parse('2026-01-01 22:00');
        $end = Carbon::parse('2026-01-01 22:00');

        $normalized = OvernightRange::normalizeEnd($start, $end);

        $this->assertTrue($normalized->isAfter($start));
        $this->assertEquals('2026-01-02 22:00:00', $normalized->toDateTimeString());
    }

    /** @test */
    public function normalize_end_bumps_end_forward_when_it_is_before_start(): void
    {
        $start = Carbon::parse('2026-01-01 22:00');
        $end = Carbon::parse('2026-01-01 02:00');

        $normalized = OvernightRange::normalizeEnd($start, $end);

        $this->assertEquals('2026-01-02 02:00:00', $normalized->toDateTimeString());
    }

    /** @test */
    public function normalize_end_does_not_mutate_same_day_window(): void
    {
        $start = Carbon::parse('2026-01-01 10:00');
        $end = Carbon::parse('2026-01-01 14:00');

        $normalized = OvernightRange::normalizeEnd($start, $end);

        $this->assertEquals('2026-01-01 14:00:00', $normalized->toDateTimeString());
    }

    /** @test */
    public function normalize_end_does_not_mutate_input_instances(): void
    {
        $start = Carbon::parse('2026-01-01 22:00');
        $end = Carbon::parse('2026-01-01 02:00');

        OvernightRange::normalizeEnd($start, $end);

        // original instances must be unchanged
        $this->assertEquals('22:00', $start->format('H:i'));
        $this->assertEquals('02:00', $end->format('H:i'));
    }

    // ───────────────────────────────────────────────────────────────────────
    // UniteSlot::isWithinAvailableWindow
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function slot_accepts_booking_in_evening_part_of_overnight_window(): void
    {
        // Window 17:00-05:00. Request at 19:00-21:00 (evening part).
        $slot = $this->makeSlotWithWindow('17:00', '05:00');

        $this->assertTrue($slot->isWithinAvailableWindow('19:00', '21:00'));
    }

    /** @test */
    public function slot_accepts_booking_in_morning_part_of_overnight_window(): void
    {
        // Window 17:00-05:00. Request at 01:00-04:00 (morning part of next day).
        $slot = $this->makeSlotWithWindow('17:00', '05:00');

        $this->assertTrue($slot->isWithinAvailableWindow('01:00', '04:00'));
    }

    /** @test */
    public function slot_accepts_booking_that_spans_midnight_within_window(): void
    {
        // Window 17:00-05:00. Request 23:00-02:00 spans midnight.
        $slot = $this->makeSlotWithWindow('17:00', '05:00');

        $this->assertTrue($slot->isWithinAvailableWindow('23:00', '02:00'));
    }

    /** @test */
    public function slot_rejects_booking_outside_overnight_window(): void
    {
        // Window 17:00-05:00. Request 10:00-12:00 is firmly outside.
        $slot = $this->makeSlotWithWindow('17:00', '05:00');

        $this->assertFalse($slot->isWithinAvailableWindow('10:00', '12:00'));
    }

    /** @test */
    public function slot_rejects_booking_that_exceeds_overnight_window(): void
    {
        // Window 22:00-02:00. Request 21:00-01:00 starts before the window.
        $slot = $this->makeSlotWithWindow('22:00', '02:00');

        $this->assertFalse($slot->isWithinAvailableWindow('21:00', '01:00'));
    }

    /** @test */
    public function slot_edge_23_to_01_accepted_in_overnight_window(): void
    {
        $slot = $this->makeSlotWithWindow('22:00', '03:00');
        $this->assertTrue($slot->isWithinAvailableWindow('23:00', '01:00'));
    }

    /** @test */
    public function slot_edge_22_to_00_accepted_in_overnight_window(): void
    {
        $slot = $this->makeSlotWithWindow('20:00', '02:00');
        $this->assertTrue($slot->isWithinAvailableWindow('22:00', '00:00'));
    }

    /** @test */
    public function slot_edge_00_to_05_accepted_in_overnight_window(): void
    {
        $slot = $this->makeSlotWithWindow('22:00', '06:00');
        $this->assertTrue($slot->isWithinAvailableWindow('00:00', '05:00'));
    }

    /** @test */
    public function slot_23_59_to_00_01_accepted_in_overnight_window(): void
    {
        $slot = $this->makeSlotWithWindow('22:00', '02:00');
        $this->assertTrue($slot->isWithinAvailableWindow('23:59', '00:01'));
    }

    // ───────────────────────────────────────────────────────────────────────
    // UnitePrice::calculateHourlyPrice — overnight pricing
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function overnight_booking_priced_correctly_not_zero(): void
    {
        // 22:00-02:00 = 4 hours at night rate 150 = 600
        $price = UnitePrice::make([
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'day_start' => '06:00',
            'day_end' => '18:00',
            'min_booking_minutes' => 60,
        ]);

        $result = $price->calculateHourlyPrice('22:00', '02:00');

        $this->assertEquals(600.0, $result, 'Expected 4 night-rate blocks × 150 = 600');
    }

    /** @test */
    public function same_day_booking_priced_at_day_rate(): void
    {
        // 10:00-12:00 = 2 hours at day rate 100 = 200
        $price = UnitePrice::make([
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'day_start' => '06:00',
            'day_end' => '18:00',
            'min_booking_minutes' => 60,
        ]);

        $result = $price->calculateHourlyPrice('10:00', '12:00');

        $this->assertEquals(200.0, $result, 'Expected 2 day-rate blocks × 100 = 200');
    }

    /** @test */
    public function overnight_booking_spanning_day_night_boundary_uses_correct_rates(): void
    {
        // 17:00-20:00: 1h day (17-18) + 2h night (18-20) = 100 + 300 = 400
        $price = UnitePrice::make([
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'day_start' => '06:00',
            'day_end' => '18:00',
            'min_booking_minutes' => 60,
        ]);

        $result = $price->calculateHourlyPrice('17:00', '20:00');

        $this->assertEquals(400.0, $result, 'Expected 100 (day) + 300 (2×night) = 400');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Validation: to_time no longer requires after:from_time
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function reservation_request_accepts_overnight_to_time_before_from_time(): void
    {
        $request = new \App\Http\Requests\Reservation\StoreReservationRequest;
        $data = [
            'unite_id' => 1,
            'reservation_date' => now()->addDays(5)->toDateString(),
            'period_type' => 'hourly',
            'from_time' => '22:00',
            'to_time' => '02:00',
        ];

        $validator = validator($data, $request->rules());
        $errors = $validator->errors()->toArray();

        $this->assertArrayNotHasKey('to_time', $errors,
            'to_time must not require after:from_time — overnight ranges are valid');
    }

    /** @test */
    public function slot_request_accepts_overnight_full_end_before_full_start(): void
    {
        $request = new \App\Http\Requests\Unite\StoreUniteSlotRequest;
        $data = [
            'day_of_week' => 'sunday',
            'status' => 'available',
            'full_start' => '17:00',
            'full_end' => '05:00',
            'day_start' => '17:00',
            'day_end' => '05:00',
        ];

        // Simulate the type context needed for the request
        request()->merge(['type' => 'stadium']);

        $validator = validator($data, $request->rules());
        $errors = $validator->errors()->toArray();

        $this->assertArrayNotHasKey('full_end', $errors,
            'full_end must not require after:full_start — overnight windows are valid');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Conflict detection for overnight reservations
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function overnight_reservation_conflicts_with_overlapping_request(): void
    {
        [$unite] = $this->makeStadiumWithSlot('22:00', '06:00');

        // Existing: 23:00-02:00 overnight, dated 2026-09-20.
        // This reservation ENDS at 02:00 on 2026-09-21 (the next day).
        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => null,
            'reservation_date' => '2026-09-20',
            'period_type' => 'hourly',
            'from_time' => '23:00',
            'to_time' => '02:00',
            'price' => 0,
            'status' => 'confirmed',
        ]);

        // New request: 01:00-03:00 on 2026-09-21 -- overlaps the tail of the
        // overnight reservation (which runs to 02:00 on that date).
        $conflicts = UniteReservation::conflicting(
            $unite->id,
            '2026-09-21',
            null,
            '01:00',
            '03:00'
        )->count();

        $this->assertGreaterThan(0, $conflicts,
            'A request on the next calendar day overlapping the morning part of an overnight reservation must conflict');
    }

    /** @test */
    public function adjacent_bookings_with_no_overlap_do_not_conflict(): void
    {
        [$unite] = $this->makeStadiumWithSlot('08:00', '23:00');

        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => null,
            'reservation_date' => '2026-09-20',
            'period_type' => 'hourly',
            'from_time' => '10:00',
            'to_time' => '12:00',
            'price' => 0,
            'status' => 'confirmed',
        ]);

        // Starts exactly when the previous one ends — no buffer, should not conflict.
        $conflicts = UniteReservation::conflicting(
            $unite->id,
            '2026-09-20',
            null,
            '12:00',
            '14:00'
        )->count();

        $this->assertEquals(0, $conflicts, 'Adjacent (touching) bookings must not conflict');
    }

    /** @test */
    public function cancelled_reservation_does_not_block_new_booking(): void
    {
        [$unite] = $this->makeStadiumWithSlot('08:00', '23:00');

        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => null,
            'reservation_date' => '2026-09-20',
            'period_type' => 'hourly',
            'from_time' => '10:00',
            'to_time' => '12:00',
            'price' => 0,
            'status' => 'cancelled',
        ]);

        $conflicts = UniteReservation::conflicting(
            $unite->id,
            '2026-09-20',
            null,
            '10:00',
            '12:00'
        )->count();

        $this->assertEquals(0, $conflicts, 'Cancelled reservations must not block new bookings');
    }

    /** @test */
    public function pending_reservation_blocks_overlapping_booking(): void
    {
        [$unite] = $this->makeStadiumWithSlot('08:00', '23:00');

        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => null,
            'reservation_date' => '2026-09-20',
            'period_type' => 'hourly',
            'from_time' => '10:00',
            'to_time' => '12:00',
            'price' => 0,
            'status' => 'pending',
        ]);

        $conflicts = UniteReservation::conflicting(
            $unite->id,
            '2026-09-20',
            null,
            '11:00',
            '13:00'
        )->count();

        $this->assertGreaterThan(0, $conflicts, 'Pending reservations must block overlapping bookings');
    }

    /** @test */
    public function exact_same_slot_is_a_conflict(): void
    {
        [$unite] = $this->makeStadiumWithSlot('08:00', '23:00');

        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => null,
            'reservation_date' => '2026-09-20',
            'period_type' => 'hourly',
            'from_time' => '10:00',
            'to_time' => '12:00',
            'price' => 0,
            'status' => 'confirmed',
        ]);

        $conflicts = UniteReservation::conflicting(
            $unite->id,
            '2026-09-20',
            null,
            '10:00',
            '12:00'
        )->count();

        $this->assertGreaterThan(0, $conflicts, 'Same-slot booking must conflict');
    }

    // ───────────────────────────────────────────────────────────────────────
    // End-to-end: create an overnight reservation via the API
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function customer_can_create_overnight_reservation_via_api(): void
    {
        [$unite, $slot] = $this->makeStadiumWithSlot('17:00', '05:00');
        UnitePrice::create([
            'unite_id' => $unite->id,
            'day' => 'friday',
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'day_start' => '17:00',
            'day_end' => '05:00',
            'min_booking_minutes' => 60,
        ]);

        $customer = User::create([
            'name' => 'Customer',
            'email' => 'cust'.uniqid().'@e.com',
            'phone' => '0500000001',
            'password' => bcrypt('x'),
            'status' => 'active',
            'type' => 'customer',
        ]);

        // Pick a Friday in the future
        $friday = Carbon::now()->next('Friday')->toDateString();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/reservations', [
                'unite_id' => $unite->id,
                'reservation_date' => $friday,
                'period_type' => 'hourly',
                'from_time' => '22:00',
                'to_time' => '02:00',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('unite_reservations', [
            'unite_id' => $unite->id,
            'from_time' => '22:00:00',
            'to_time' => '02:00:00',
        ]);
    }

    /** @test */
    public function overnight_reservation_price_is_nonzero(): void
    {
        [$unite] = $this->makeStadiumWithSlot('17:00', '05:00');
        UnitePrice::create([
            'unite_id' => $unite->id,
            'day' => 'friday',
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'day_start' => '17:00',
            'day_end' => '23:00',
            'min_booking_minutes' => 60,
        ]);

        $customer = User::create([
            'name' => 'Customer2',
            'email' => 'cust2'.uniqid().'@e.com',
            'phone' => '0500000002',
            'password' => bcrypt('x'),
            'status' => 'active',
            'type' => 'customer',
        ]);

        $friday = Carbon::now()->next('Friday')->toDateString();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/reservations', [
                'unite_id' => $unite->id,
                'reservation_date' => $friday,
                'period_type' => 'hourly',
                'from_time' => '23:00',
                'to_time' => '01:00',
            ]);

        $response->assertStatus(201);

        $reservation = UniteReservation::where('unite_id', $unite->id)->latest()->first();
        $this->assertGreaterThan(0, $reservation->price,
            'Overnight reservation must have a non-zero price');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────────────────────────────────────

    private function makeSlotWithWindow(string $start, string $end): UniteSlot
    {
        return UniteSlot::make([
            'day_start' => $start,
            'day_end' => $end,
        ]);
    }

    private function makeStadiumWithSlot(string $windowStart, string $windowEnd): array
    {
        $provider = User::create([
            'name' => 'Provider',
            'email' => 'prov'.uniqid().'@e.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'),
            'status' => 'active',
            'type' => 'provider',
        ]);

        $dept = Department::create([
            'user_id' => $provider->id,
            'name' => 'Dept',
            'type' => 'stadium',
            'location' => 'Riyadh',
            'status' => 'active',
        ]);

        $unite = Unite::create([
            'department_id' => $dept->id,
            'type' => 'stadium',
            'name' => 'Stadium',
            'description' => 'd',
            'location_name' => 'Riyadh',
            'city' => 'Riyadh',
            'status' => 'active',
            'requires_approval' => true,
        ]);

        $slot = UniteSlot::create([
            'unite_id' => $unite->id,
            'day_of_week' => 'friday',
            'status' => 'available',
            'full_start' => $windowStart,
            'full_end' => $windowEnd,
            'day_start' => $windowStart,
            'day_end' => $windowEnd,
        ]);

        // Also create for every weekday
        foreach (['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'saturday'] as $d) {
            UniteSlot::create([
                'unite_id' => $unite->id,
                'day_of_week' => $d,
                'status' => 'available',
                'full_start' => $windowStart,
                'full_end' => $windowEnd,
                'day_start' => $windowStart,
                'day_end' => $windowEnd,
            ]);
        }

        return [$unite, $slot];
    }
}
