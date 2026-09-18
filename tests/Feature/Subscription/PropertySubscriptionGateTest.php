<?php

namespace Tests\Feature\Subscription;

use App\Models\Department;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the property package subscription gate.
 *
 * A provider must hold an active, non-exhausted property subscription
 * to create a new Unite via the API. The same isExpiredByRules()
 * semantics used in activePropertySubscription() are verified here.
 */
class PropertySubscriptionGateTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────────────────────────────────────────────────────────────
    // User::activePropertySubscription()
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function active_count_subscription_is_detected(): void
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage(5);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 5,
        ]);

        $this->assertNotNull($provider->activePropertySubscription());
    }

    /** @test */
    public function inactive_subscription_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage(5);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'inactive',
            'count' => 5,
        ]);

        $this->assertNull($provider->activePropertySubscription());
    }

    /** @test */
    public function expired_date_subscription_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $pkg = PropertyPackage::create([
            'name' => 'Time Pkg',
            'type' => 'time',
            'status' => 'active',
        ]);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDay(), // expired yesterday
        ]);

        $this->assertNull($provider->activePropertySubscription());
    }

    /** @test */
    public function exhausted_count_zero_subscription_is_rejected(): void
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage(5);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 0, // exhausted
        ]);

        $this->assertNull($provider->activePropertySubscription());
    }

    /** @test */
    public function count_one_subscription_is_active(): void
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage(5);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 1,
        ]);

        $this->assertNotNull($provider->activePropertySubscription());
    }

    /** @test */
    public function null_count_time_type_is_treated_as_unlimited(): void
    {
        $provider = $this->makeProvider();
        $pkg = PropertyPackage::create([
            'name' => 'Time Pkg',
            'type' => 'time',
            'duration' => 90,
            'status' => 'active',
        ]);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => null, // unlimited
            'start_date' => now(),
            'end_date' => now()->addDays(90),
        ]);

        $this->assertNotNull($provider->activePropertySubscription(),
            'Null count (time-type subscription) must be treated as unlimited');
    }

    /** @test */
    public function oldest_subscription_is_used_first(): void
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage(5);

        $older = Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 3,
        ]);
        $newer = Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 10,
        ]);

        $active = $provider->activePropertySubscription();
        $this->assertEquals($older->id, $active->id,
            'activePropertySubscription must return the oldest qualifying subscription first');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Unite creation gate (via API)
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function provider_without_subscription_cannot_create_unite(): void
    {
        $provider = $this->makeProvider();
        $dept = $this->makeDept($provider);

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept));

        $response->assertStatus(403);
    }

    /** @test */
    public function provider_with_active_count_subscription_can_create_unite(): void
    {
        $provider = $this->makeProviderWithSubscription(3);
        $dept = $this->makeDept($provider);

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept));

        $response->assertStatus(201);
    }

    /** @test */
    public function creating_a_unite_decrements_count_by_one(): void
    {
        [$provider, $sub] = $this->makeProviderWithSubscription(3, returnSub: true);
        $dept = $this->makeDept($provider);

        $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept));

        $this->assertEquals(2, $sub->fresh()->count,
            'Subscription count must be decremented by 1 after Unite creation');
    }

    /** @test */
    public function subscription_becomes_inactive_when_count_reaches_zero(): void
    {
        [$provider, $sub] = $this->makeProviderWithSubscription(1, returnSub: true);
        $dept = $this->makeDept($provider);

        $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept));

        $this->assertEquals(0, $sub->fresh()->count);
        $this->assertEquals('inactive', $sub->fresh()->status,
            'Subscription must auto-expire to inactive when count hits zero');
    }

    /** @test */
    public function provider_with_exhausted_subscription_cannot_create_unite(): void
    {
        $provider = $this->makeProvider();
        $dept = $this->makeDept($provider);
        $pkg = $this->makeCountPackage(3);
        Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 0, // already exhausted
        ]);

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept));

        $response->assertStatus(403);
    }

    /** @test */
    public function time_type_subscription_allows_create_without_decrement(): void
    {
        $provider = $this->makeProvider();
        $dept = $this->makeDept($provider);
        $pkg = PropertyPackage::create([
            'name' => 'TimePkg',
            'type' => 'time',
            'status' => 'active',
        ]);
        $sub = Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => null,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept));

        $response->assertStatus(201);
        $this->assertNull($sub->fresh()->count,
            'Time-type subscription count must remain null after Unite creation');
    }

    /** @test */
    public function multiple_creates_deplete_count_correctly(): void
    {
        [$provider, $sub] = $this->makeProviderWithSubscription(3, returnSub: true);
        $dept = $this->makeDept($provider);

        // First create
        $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept, 'Unite A'));
        $this->assertEquals(2, $sub->fresh()->count);

        // Second create
        $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept, 'Unite B'));
        $this->assertEquals(1, $sub->fresh()->count);

        // Third (last)
        $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept, 'Unite C'));
        $this->assertEquals(0, $sub->fresh()->count);
        $this->assertEquals('inactive', $sub->fresh()->status);

        // Fourth — must fail
        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/unites', $this->stadiumPayload($dept, 'Unite D'));
        $response->assertStatus(403);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Issuance via payment (GeideaPaymentService) — unit-level check
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function activating_count_type_subscription_sets_count_from_package(): void
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage(7);
        $sub = Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'pending',
            'count' => null, // not yet activated
        ]);

        $controller = app(\App\Http\Controllers\Admin\Subscription\SubscriptionController::class);
        $method = new \ReflectionMethod($controller, 'prepareSubscriptionData');
        $method->setAccessible(true);

        $data = ['type' => 'property', 'package_id' => $pkg->id];
        $result = $method->invoke($controller, $data, $pkg);

        $this->assertEquals(7, $result['count'],
            'prepareSubscriptionData must copy package->count onto the subscription');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────────────────────────────────────

    private function makeProvider(): User
    {
        return User::create([
            'name' => 'Provider',
            'email' => 'prov'.uniqid().'@e.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'),
            'status' => 'active',
            'type' => 'provider',
        ]);
    }

    private function makeCountPackage(int $count): PropertyPackage
    {
        return PropertyPackage::create([
            'name' => 'Count Pkg',
            'type' => 'count',
            'count' => $count,
            'status' => 'active',
        ]);
    }

    private function makeDept(User $provider): Department
    {
        return Department::create([
            'user_id' => $provider->id,
            'name' => 'Dept',
            'type' => 'stadium',
            'location' => 'Riyadh',
            'status' => 'active',
        ]);
    }

    private function makeProviderWithSubscription(int $count, bool $returnSub = false): array|User
    {
        $provider = $this->makeProvider();
        $pkg = $this->makeCountPackage($count);
        $sub = Subscription::create([
            'user_id' => $provider->id,
            'type' => 'property',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => $count,
        ]);

        return $returnSub ? [$provider, $sub] : $provider;
    }

    private function stadiumPayload(Department $dept, string $name = 'My Stadium'): array
    {
        return [
            'department_id' => $dept->id,
            'type' => 'stadium',
            'name' => $name,
            'description' => 'Test stadium',
            'city' => 'riyadh',  // must match saudi_cities config key (lowercase)
            'location_name' => 'Riyadh',
            'families_and_singles' => 'both',
            'status' => 'active',
            'stadium' => [
                'customize_Category' => 'football',
                'customize_Place' => 'both',
                'width' => '80',
                'length' => '70',
            ],
        ];
    }
}
