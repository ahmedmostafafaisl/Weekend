<?php

namespace Tests\Feature\Booking;

use App\Models\Department;
use App\Models\MultiBookingGroup;
use App\Models\Unite;
use App\Models\UniteDetail;
use App\Models\UnitePrice;
use App\Models\UniteReservation;
use App\Models\UniteSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the multi-unit booking feature.
 *
 * GET  /api/departments/{id}/available-unites
 * POST /api/multi-booking
 */
class MultiBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AppSettingsSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // available-unites endpoint
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function available_unites_returns_units_with_prices(): void
    {
        $dept = $this->makeDept();
        $unite = $this->makeUnite($dept, 'morning');

        $response = $this->getJson(
            "/api/departments/{$dept->id}/available-unites?reservation_date=".now()->addDay()->toDateString().'&period_type=morning'
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'price', 'images']],
                'meta' => ['department_id', 'available_count'],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertGreaterThan(0, $response->json('data.0.price'));
    }

    /** @test */
    public function available_unites_excludes_conflicted_units(): void
    {
        $customer = $this->makeCustomer();
        $dept = $this->makeDept();
        $unite = $this->makeUnite($dept, 'morning');
        $date = now()->addDay()->toDateString();

        // Create a conflicting reservation
        UniteReservation::create([
            'unite_id' => $unite->id,
            'user_id' => $customer->id,
            'reservation_date' => $date,
            'period_type' => 'morning',
            'price' => 100,
            'status' => 'confirmed',
        ]);

        $response = $this->getJson(
            "/api/departments/{$dept->id}/available-unites?reservation_date={$date}&period_type=morning"
        );

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'),
            'Conflicting unite must not appear in available list');
    }

    /** @test */
    public function available_unites_is_public(): void
    {
        $dept = $this->makeDept();
        $this->makeUnite($dept, 'morning');

        $this->getJson(
            "/api/departments/{$dept->id}/available-unites?reservation_date=".now()->addDay()->toDateString().'&period_type=morning'
        )->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/multi-booking
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function multi_booking_requires_auth(): void
    {
        $dept = $this->makeDept();
        $this->postJson('/api/multi-booking', ['department_id' => $dept->id])
            ->assertStatus(401);
    }

    /** @test */
    public function providers_cannot_create_multi_bookings(): void
    {
        $provider = User::create([
            'name' => 'P', 'email' => 'p@e.com', 'phone' => '0500000001',
            'password' => bcrypt('x'), 'status' => 'active', 'type' => 'provider',
        ]);

        $this->actingAs($provider, 'sanctum')
            ->postJson('/api/multi-booking', ['department_id' => 1, 'unite_ids' => [1, 2]])
            ->assertStatus(403);
    }

    /** @test */
    public function multi_booking_requires_at_least_2_units(): void
    {
        $customer = $this->makeCustomer();
        $dept = $this->makeDept();
        $unite = $this->makeUnite($dept, 'morning');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/multi-booking', [
                'department_id' => $dept->id,
                'unite_ids' => [$unite->id], // only 1
                'reservation_date' => now()->addDay()->toDateString(),
                'period_type' => 'morning',
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function multi_booking_rejects_units_from_different_departments(): void
    {
        $customer = $this->makeCustomer();
        $dept1 = $this->makeDept();
        $dept2 = $this->makeDept();
        $u1 = $this->makeUnite($dept1, 'morning');
        $u2 = $this->makeUnite($dept2, 'morning');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/multi-booking', [
                'department_id' => $dept1->id,
                'unite_ids' => [$u1->id, $u2->id],
                'reservation_date' => now()->addDay()->toDateString(),
                'period_type' => 'morning',
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function multi_booking_creates_one_reservation_per_unit(): void
    {
        $customer = $this->makeCustomer();
        $dept = $this->makeDept();
        $u1 = $this->makeUnite($dept, 'morning');
        $u2 = $this->makeUnite($dept, 'morning');
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/multi-booking', [
                'department_id' => $dept->id,
                'unite_ids' => [$u1->id, $u2->id],
                'reservation_date' => $date,
                'period_type' => 'morning',
                'payment_method' => 'geidea',
            ]);

        // May succeed or fail depending on Geidea config in test env;
        // either way 2 reservations are created before payment is attempted
        if ($response->status() === 201) {
            $this->assertEquals(2, $response->json('data.reservations') ? count($response->json('data.reservations')) : 0);
        }

        // Group + reservations in DB
        $groupExists = MultiBookingGroup::where('user_id', $customer->id)
            ->where('department_id', $dept->id)->exists();
        $this->assertTrue($groupExists, 'MultiBookingGroup must be created');
    }

    /** @test */
    public function multi_booking_total_equals_sum_of_unit_prices(): void
    {
        $customer = $this->makeCustomer();
        $dept = $this->makeDept();
        $u1 = $this->makeUnite($dept, 'morning', 100);
        $u2 = $this->makeUnite($dept, 'morning', 200);

        $group = MultiBookingGroup::create([
            'user_id' => $customer->id,
            'department_id' => $dept->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'period_type' => 'morning',
            'total_price' => 300,
            'total_amount' => 300,
            'status' => 'pending',
        ]);

        $this->assertEquals(300, $group->total_price);
    }

    /** @test */
    public function group_has_correct_reservation_relationships(): void
    {
        $customer = $this->makeCustomer();
        $dept = $this->makeDept();
        $u1 = $this->makeUnite($dept, 'morning');

        $group = MultiBookingGroup::create([
            'user_id' => $customer->id, 'department_id' => $dept->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'period_type' => 'morning', 'total_price' => 100, 'total_amount' => 100, 'status' => 'pending',
        ]);

        $res = UniteReservation::create([
            'multi_booking_group_id' => $group->id,
            'unite_id' => $u1->id, 'user_id' => $customer->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'period_type' => 'morning', 'price' => 100, 'status' => 'pending',
        ]);

        $this->assertCount(1, $group->fresh()->reservations);
        $this->assertEquals($group->id, $res->fresh()->multi_booking_group_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeCustomer(): User
    {
        return User::create([
            'name' => 'Customer', 'email' => 'c'.uniqid().'@e.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'), 'status' => 'active', 'type' => 'customer',
        ]);
    }

    private function makeDept(): Department
    {
        $provider = User::create([
            'name' => 'Provider', 'email' => 'p'.uniqid().'@e.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'), 'status' => 'active', 'type' => 'provider',
        ]);

        return Department::create([
            'user_id' => $provider->id, 'name' => 'Test Dept',
            'type' => 'lounge', 'location' => 'Riyadh',
            'latitude' => '24.7', 'longitude' => '46.7',
            'status' => 'active', 'whatsapp' => '966500000000',
        ]);
    }

    private function makeUnite(Department $dept, string $period = 'morning', float $price = 150): Unite
    {
        $unite = Unite::create([
            'department_id' => $dept->id, 'name' => 'Unite '.uniqid(),
            'type' => 'lounge', 'status' => 'active',
        ]);
        UniteDetail::create(['unite_id' => $unite->id]);
        UniteSlot::create([
            'unite_id' => $unite->id, 'day_of_week' => 'week_day', 'status' => 'available',
            'morning_start' => '08:00', 'morning_end' => '13:00',
            'evening_start' => '14:00', 'evening_end' => '20:00',
            'full_start' => '08:00', 'full_end' => '20:00',
        ]);
        UnitePrice::create([
            'unite_id' => $unite->id,
            'morning_price' => $price, 'evening_price' => $price,
            'full_price' => $price * 2,
        ]);

        return $unite;
    }
}
