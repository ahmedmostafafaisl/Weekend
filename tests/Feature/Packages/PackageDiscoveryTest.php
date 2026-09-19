<?php

namespace Tests\Feature\Packages;

use App\Models\AdPackage;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AppSettingsSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/home (public)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function top_packages_is_accessible_without_auth(): void
    {
        $this->getJson('/api/home')->assertStatus(200);
    }

    /** @test */
    public function top_packages_returns_property_and_ad_keys(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
                 ->assertJsonStructure(['property_packages', 'ad_packages']);
    }

    /** @test */
    public function top_packages_returns_at_most_5_of_each(): void
    {
        foreach (range(1, 8) as $i) {
            PropertyPackage::create(['name' => "PP{$i}", 'type' => 'count', 'count' => 5, 'price' => $i * 10, 'status' => 'active']);
            AdPackage::create(['name' => "AP{$i}", 'type' => 'count', 'count' => 5, 'price' => $i * 10, 'status' => 'active']);
        }

        $response = $this->getJson('/api/home');

        $this->assertCount(5, $response->json('property_packages'));
        $this->assertCount(5, $response->json('ad_packages'));
    }

    /** @test */
    public function top_packages_ordered_by_price_ascending(): void
    {
        PropertyPackage::create(['name' => 'Expensive', 'type' => 'count', 'count' => 5, 'price' => 500, 'status' => 'active']);
        PropertyPackage::create(['name' => 'Cheap',     'type' => 'count', 'count' => 5, 'price' => 50,  'status' => 'active']);
        PropertyPackage::create(['name' => 'Mid',       'type' => 'count', 'count' => 5, 'price' => 200, 'status' => 'active']);

        $response = $this->getJson('/api/home');

        $prices = collect($response->json('property_packages'))->pluck('price')->map(fn ($p) => (float) $p);
        $this->assertEquals($prices->sort()->values()->toArray(), $prices->values()->toArray(),
            'Property packages must be ordered by price ASC');
    }

    /** @test */
    public function top_packages_excludes_inactive_packages(): void
    {
        PropertyPackage::create(['name' => 'Active',   'type' => 'count', 'count' => 5, 'price' => 100, 'status' => 'active']);
        PropertyPackage::create(['name' => 'Inactive', 'type' => 'count', 'count' => 5, 'price' => 50,  'status' => 'inactive']);

        $response = $this->getJson('/api/home');

        $names = collect($response->json('property_packages'))->pluck('name');
        $this->assertContains('Active',   $names);
        $this->assertNotContains('Inactive', $names);
    }

    /** @test */
    public function top_packages_returns_empty_arrays_when_no_packages_exist(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('property_packages'));
        $this->assertSame([], $response->json('ad_packages'));
    }

    /** @test */
    public function home_includes_statistics_for_authenticated_provider(): void
    {
        $provider = $this->makeUser('provider');
        $token    = $provider->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/home');

        $response->assertStatus(200)
                 ->assertJsonStructure(['property_packages', 'ad_packages', 'statistics']);

        $this->assertNotNull($response->json('statistics'));
    }

    /** @test */
    public function home_does_not_include_statistics_for_customer(): void
    {
        $customer = $this->makeUser('customer');
        $token    = $customer->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/home');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('statistics', $response->json());
    }

    /** @test */
    public function home_does_not_include_statistics_for_guest(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('statistics', $response->json());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/package-activation-keys (auth:sanctum) — returns booleans
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function activation_keys_requires_auth(): void
    {
        $this->getJson('/api/package-activation-keys')->assertStatus(401);
    }

    /** @test */
    public function provider_gets_both_activation_keys_as_booleans(): void
    {
        $provider = $this->makeUser('provider');

        $response = $this->actingAs($provider, 'sanctum')
                         ->getJson('/api/package-activation-keys');

        $response->assertStatus(200)
                 ->assertJsonStructure(['property_package_activation', 'ad_package_activation']);

        $this->assertIsBool($response->json('property_package_activation'));
        $this->assertIsBool($response->json('ad_package_activation'));
    }

    /** @test */
    public function customer_gets_only_ad_activation_key_as_boolean(): void
    {
        $customer = $this->makeUser('customer');

        $response = $this->actingAs($customer, 'sanctum')
                         ->getJson('/api/package-activation-keys');

        $response->assertStatus(200)
                 ->assertJsonStructure(['ad_package_activation'])
                 ->assertJsonMissing(['property_package_activation']);

        $this->assertIsBool($response->json('ad_package_activation'));
    }

    /** @test */
    public function activation_key_is_false_when_user_has_no_active_subscription(): void
    {
        $provider = $this->makeUser('provider'); // no subscriptions

        $response = $this->actingAs($provider, 'sanctum')
                         ->getJson('/api/package-activation-keys');

        $this->assertFalse($response->json('property_package_activation'));
        $this->assertFalse($response->json('ad_package_activation'));
    }

    /** @test */
    public function activation_key_is_true_when_user_has_active_property_subscription(): void
    {
        $provider = $this->makeUser('provider');
        $pp = PropertyPackage::create(['name' => 'PP', 'type' => 'count', 'count' => 5, 'price' => 100, 'status' => 'active']);
        Subscription::create(['user_id' => $provider->id, 'type' => 'property', 'package_id' => $pp->id, 'status' => 'active', 'count' => 5]);

        $response = $this->actingAs($provider, 'sanctum')
                         ->getJson('/api/package-activation-keys');

        $this->assertTrue($response->json('property_package_activation'));
        $this->assertFalse($response->json('ad_package_activation'));
    }

    /** @test */
    public function activation_key_is_false_when_subscription_is_exhausted(): void
    {
        $provider = $this->makeUser('provider');
        $pp = PropertyPackage::create(['name' => 'PP', 'type' => 'count', 'count' => 5, 'price' => 100, 'status' => 'active']);
        // count = 0 → isExpiredByRules() = true → activePropertySubscription() = null
        Subscription::create(['user_id' => $provider->id, 'type' => 'property', 'package_id' => $pp->id, 'status' => 'active', 'count' => 0]);

        $response = $this->actingAs($provider, 'sanctum')
                         ->getJson('/api/package-activation-keys');

        $this->assertFalse($response->json('property_package_activation'),
            'Exhausted subscription must not be counted as active');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/user-subscriptions (auth:sanctum)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function user_subscriptions_requires_auth(): void
    {
        $this->getJson('/api/user-subscriptions')->assertStatus(401);
    }

    /** @test */
    public function provider_sees_both_property_and_ad_subscription_groups(): void
    {
        $provider = $this->makeUser('provider');
        $pp = PropertyPackage::create(['name' => 'PP', 'type' => 'count', 'count' => 5, 'price' => 100, 'status' => 'active']);
        $ap = AdPackage::create(['name' => 'AP', 'type' => 'count', 'count' => 5, 'price' => 80, 'status' => 'active']);

        Subscription::create(['user_id' => $provider->id, 'type' => 'property', 'package_id' => $pp->id, 'status' => 'active', 'count' => 5]);
        Subscription::create(['user_id' => $provider->id, 'type' => 'ad',       'package_id' => $ap->id, 'status' => 'active', 'count' => 5]);

        $response = $this->actingAs($provider, 'sanctum')
                         ->getJson('/api/user-subscriptions');

        $response->assertStatus(200)
                 ->assertJsonStructure(['property_subscriptions', 'ad_subscriptions']);

        $this->assertCount(1, $response->json('property_subscriptions'));
        $this->assertCount(1, $response->json('ad_subscriptions'));
    }

    /** @test */
    public function customer_sees_only_ad_subscription_group(): void
    {
        $customer = $this->makeUser('customer');
        $ap = AdPackage::create(['name' => 'AP', 'type' => 'count', 'count' => 5, 'price' => 80, 'status' => 'active']);
        Subscription::create(['user_id' => $customer->id, 'type' => 'ad', 'package_id' => $ap->id, 'status' => 'active', 'count' => 5]);

        $response = $this->actingAs($customer, 'sanctum')
                         ->getJson('/api/user-subscriptions');

        $response->assertStatus(200)
                 ->assertJsonStructure(['ad_subscriptions'])
                 ->assertJsonMissing(['property_subscriptions']);

        $this->assertCount(1, $response->json('ad_subscriptions'));
    }

    /** @test */
    public function user_subscriptions_only_returns_own_subscriptions(): void
    {
        $provider1 = $this->makeUser('provider');
        $provider2 = $this->makeUser('provider');
        $pp = PropertyPackage::create(['name' => 'PP', 'type' => 'count', 'count' => 5, 'price' => 100, 'status' => 'active']);

        Subscription::create(['user_id' => $provider1->id, 'type' => 'property', 'package_id' => $pp->id, 'status' => 'active', 'count' => 5]);
        Subscription::create(['user_id' => $provider2->id, 'type' => 'property', 'package_id' => $pp->id, 'status' => 'active', 'count' => 5]);

        $response = $this->actingAs($provider1, 'sanctum')
                         ->getJson('/api/user-subscriptions');

        $this->assertCount(1, $response->json('property_subscriptions'),
            'Must only return the authenticated user\'s own subscriptions');
    }

    /** @test */
    public function expired_count_subscriptions_are_marked_inactive_in_response(): void
    {
        $provider = $this->makeUser('provider');
        $pp = PropertyPackage::create(['name' => 'PP', 'type' => 'count', 'count' => 5, 'price' => 100, 'status' => 'active']);

        $sub = Subscription::create([
            'user_id'    => $provider->id,
            'type'       => 'property',
            'package_id' => $pp->id,
            'status'     => 'active',
            'count'      => 0, // exhausted — isExpiredByRules() will return true
        ]);

        $response = $this->actingAs($provider, 'sanctum')
                         ->getJson('/api/user-subscriptions');

        $this->assertEquals('inactive', $response->json('property_subscriptions.0.status'),
            'Exhausted subscription must be returned with status=inactive');

        $this->assertEquals('inactive', $sub->fresh()->status,
            'DB row must also be updated to inactive');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeUser(string $type): User
    {
        return User::create([
            'name'     => ucfirst($type),
            'email'    => $type.uniqid().'@e.com',
            'phone'    => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'),
            'status'   => 'active',
            'type'     => $type,
        ]);
    }
}
