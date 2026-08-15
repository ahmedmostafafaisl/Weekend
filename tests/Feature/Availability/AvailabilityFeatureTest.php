<?php

namespace Tests\Feature\Availability;

use App\Models\UniteReservation;
use App\Repositories\Reservation\UniteReservationRepository;
use App\Services\Availability\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers requirement 14's 6 test categories (A-F) for the availability/
 * booking-time feature. Written against the real repository/service layer
 * (Controller -> Service -> Repository -> Model), not the model directly,
 * so these exercise the actual enforcement path a real request would hit.
 *
 * NOTE ON EXECUTION: this container's PHP is 8.3.6, but composer.lock pins
 * symfony/css-selector v8.0.0, which requires PHP >=8.4 -- `composer
 * install` cannot complete here, so `vendor/` was never installable and
 * these tests could not actually be run against a live Laravel application
 * in this session. Verified with `php -l` (syntax only) and written to
 * match this project's existing test conventions (TestCase, RefreshDatabase,
 * test_snake_case naming) exactly. Run these in an environment with a
 * matching PHP version before relying on them.
 */
class AvailabilityFeatureTest extends TestCase
{
    use BuildsAvailabilityFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Notification::fake();
    }

    private function repo(): UniteReservationRepository
    {
        return app(UniteReservationRepository::class);
    }

    private function service(): AvailabilityService
    {
        return app(AvailabilityService::class);
    }

    // -------------------------------------------------------------------
    // A. Daily operating hours
    // -------------------------------------------------------------------

    public function test_booking_inside_operating_hours_is_allowed(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $result = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '09:00',
            'to_time' => '11:00',
        ], $provider->id);

        $this->assertIsArray($result);
    }

    public function test_booking_before_opening_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '05:00',
            'to_time' => '07:00',
        ], $provider->id);
    }

    public function test_booking_after_closing_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '22:30',
            'to_time' => '23:30',
        ], $provider->id);
    }

    // -------------------------------------------------------------------
    // B. Custom periods
    // -------------------------------------------------------------------

    public function test_booking_inside_a_configured_custom_period_is_allowed(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', [], [
            ['start_time' => '06:00', 'end_time' => '08:00'],
            ['start_time' => '09:00', 'end_time' => '12:00'],
        ]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $result = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '09:30',
            'to_time' => '11:00',
        ], $provider->id);

        $this->assertIsArray($result);
    }

    public function test_booking_inside_a_gap_between_custom_periods_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', [], [
            ['start_time' => '06:00', 'end_time' => '08:00'],
            ['start_time' => '09:00', 'end_time' => '12:00'],
        ]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        // 08:15-08:45 falls in the 08:00-09:00 gap between the two periods
        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '08:15',
            'to_time' => '08:45',
        ], $provider->id);
    }

    public function test_booking_crossing_a_gap_between_custom_periods_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', [], [
            ['start_time' => '06:00', 'end_time' => '08:00'],
            ['start_time' => '09:00', 'end_time' => '12:00'],
        ]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        // 07:00-10:00 spans across the 08:00-09:00 gap
        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '07:00',
            'to_time' => '10:00',
        ], $provider->id);
    }

    public function test_no_custom_periods_falls_back_to_day_start_day_end(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        // No periods array passed -> falls back to day_start/day_end
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $result = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '15:00',
            'to_time' => '17:00',
        ], $provider->id);

        $this->assertIsArray($result);
    }

    // -------------------------------------------------------------------
    // C. Buffer
    // -------------------------------------------------------------------

    public function test_booking_immediately_after_existing_reservation_is_rejected_with_buffer(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00', 'buffer_minutes' => 15]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $existing = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '16:00',
            'to_time' => '17:00',
        ], $provider->id);

        // scopeConflicting() only treats 'pending'/'confirmed' reservations as
        // blocking -- this fixture's requires_approval=true means create()
        // returns a 'pending_approval' reservation by default, which
        // wouldn't participate in conflict detection at all. Confirming it
        // isolates the buffer logic itself, which is what this test verifies.
        $existing['reservation']->update(['status' => 'confirmed']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        // Starts exactly when the existing reservation ends -- inside the 15min buffer
        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '17:00',
            'to_time' => '18:00',
        ], $provider->id);
    }

    public function test_booking_exactly_after_buffer_elapses_is_allowed(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00', 'buffer_minutes' => 15]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '16:00',
            'to_time' => '17:00',
        ], $provider->id);

        // Starts exactly 15 minutes after the existing reservation ends
        $result = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '17:15',
            'to_time' => '18:15',
        ], $provider->id);

        $this->assertIsArray($result);
    }

    public function test_conflict_detection_includes_buffer_via_the_scope_directly(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00', 'buffer_minutes' => 15]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $existing = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '16:00',
            'to_time' => '17:00',
        ], $provider->id);

        $existing['reservation']->update(['status' => 'confirmed']);

        $conflictsWithBuffer = UniteReservation::conflicting($unite->id, $sunday, null, '17:00', '18:00', null, 15)->exists();
        $conflictsWithoutBuffer = UniteReservation::conflicting($unite->id, $sunday, null, '17:00', '18:00', null, 0)->exists();

        $this->assertTrue($conflictsWithBuffer);
        $this->assertFalse($conflictsWithoutBuffer);
    }

    // -------------------------------------------------------------------
    // D. Multi-day availability
    // -------------------------------------------------------------------

    public function test_range_availability_returns_every_requested_date(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makeSlot($unite, 'monday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makePrice($unite, 'week_day');

        $start = Carbon::now()->next(Carbon::SUNDAY);
        $end = $start->copy()->addDay();

        $result = $this->service()->rangeAvailability($unite, $start, $end);

        $this->assertCount(2, $result['dates']);
        $this->assertSame($start->toDateString(), $result['dates'][0]['date']);
        $this->assertSame($end->toDateString(), $result['dates'][1]['date']);
    }

    public function test_range_availability_respects_different_configuration_per_day(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        // Monday has no slot at all -- should show as unavailable/no_slot_config
        $this->makePrice($unite, 'week_day');

        $start = Carbon::now()->next(Carbon::SUNDAY);
        $end = $start->copy()->addDay();

        $result = $this->service()->rangeAvailability($unite, $start, $end);

        $sundayEntry = $result['dates'][0];
        $mondayEntry = $result['dates'][1];

        $this->assertNotSame('no_slot_config', $sundayEntry['reason'] ?? null);
        $this->assertSame('no_slot_config', $mondayEntry['reason'] ?? null);
    }

    public function test_range_availability_reflects_existing_reservations_per_day(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY);

        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => $provider->id,
            'reservation_date' => $sunday->toDateString(),
            'period_type' => 'morning',
            'from_time' => '06:00',
            'to_time' => '12:00',
            'price' => 100,
            'status' => 'confirmed',
        ]);

        $result = $this->service()->rangeAvailability($unite, $sunday, $sunday->copy());

        $morningPeriod = collect($result['dates'][0]['periods'])->firstWhere('period_type', 'morning');
        $this->assertSame('booked', $morningPeriod['availability']);
    }

    // -------------------------------------------------------------------
    // E. Reservation
    // -------------------------------------------------------------------

    public function test_direct_creation_cannot_bypass_the_available_window(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', [], [
            ['start_time' => '09:00', 'end_time' => '12:00'],
        ]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        // Outside the only configured period, even though morning_start/end
        // (the older mechanism) would otherwise allow 06:00-12:00
        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '06:00',
            'to_time' => '08:00',
        ], $provider->id);
    }

    public function test_update_reschedule_follows_the_same_window_and_buffer_rules(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00', 'buffer_minutes' => 15]);
        $this->makePrice($unite, 'week_day');

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $created = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'hourly',
            'reservation_date' => $sunday,
            'from_time' => '10:00',
            'to_time' => '11:00',
        ], $provider->id);

        $reservation = $created['reservation'];

        // Attempt to reschedule outside operating hours -- should still be rejected
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->repo()->update($reservation->id, [
            'reservation_date' => $sunday,
            'period_type' => 'hourly',
            'from_time' => '23:30',
            'to_time' => '23:59',
        ]);
    }

    public function test_full_day_reservation_remains_functional(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department, 'hall');
        $this->makeSlot($unite, 'sunday', ['full_start' => '06:00', 'full_end' => '23:00']);
        $this->makePrice($unite, 'week_day', ['full_price' => 500]);

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $result = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'full_day',
            'reservation_date' => $sunday,
        ], $provider->id);

        $this->assertIsArray($result);
    }

    // -------------------------------------------------------------------
    // F. Pricing
    // -------------------------------------------------------------------

    public function test_existing_pricing_calculation_remains_unchanged_by_the_new_availability_fields(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        // day_start/day_end/buffer_minutes set alongside the older morning/
        // evening times -- pricing must still come from UnitePrice, not be
        // affected by the new availability-only fields.
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00', 'buffer_minutes' => 10]);
        $this->makePrice($unite, 'week_day', ['morning_price' => 123.45]);

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $result = $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'morning',
            'reservation_date' => $sunday,
        ], $provider->id);

        $reservation = $result['reservation'];
        $this->assertEquals(123.45, (float) $reservation->price);
    }

    public function test_missing_price_validation_behavior_remains_unchanged(): void
    {
        $provider = $this->makeProvider();
        $department = $this->makeDepartment($provider);
        $unite = $this->makeUnite($department);
        $this->makeSlot($unite, 'sunday', ['day_start' => '06:00', 'day_end' => '23:00']);
        // Deliberately no price row created for this unite at all.

        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->repo()->create([
            'unite_id' => $unite->id,
            'period_type' => 'morning',
            'reservation_date' => $sunday,
        ], $provider->id);
    }
}
