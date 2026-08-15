<?php

namespace Tests\Feature\Availability;

use App\Models\Admin;
use App\Models\Department;
use App\Models\Unite;
use App\Models\UniteOffer;
use App\Models\UnitePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Covers Part 6 (10 scenarios each for UnitePrice and UniteOffer
 * authorization) and Part 5 (permission cache verification) from the
 * request. Written against the real HTTP routes (not the FormRequest/
 * controller directly), since that's what actually proves route
 * middleware + FormRequest::authorize() + Blade gating all agree, and
 * exercises the real permission cache exactly as a live request would.
 *
 * SAME HONEST LIMITATION as AvailabilityFeatureTest: this container's PHP
 * (8.3.6) doesn't satisfy composer.lock's PHP >=8.4 requirement
 * (symfony/css-selector v8.0.0), so `composer install` cannot complete
 * and these tests could not actually be executed in this session.
 * Verified with `php -l` and by reading the exact permission names/guard
 * already seeded (AdminRolesPermissionsSeeder's modules array includes
 * 'unites', generating unites.view/create/update/delete via its standard
 * loop) rather than assuming they exist. Run these in an environment
 * with a matching PHP version before relying on them.
 */
class UnitePriceOfferAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $permissions = []): Admin
    {
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);

        if (! empty($permissions)) {
            $role = Role::firstOrCreate(['name' => 'test-role-'.uniqid(), 'guard_name' => 'admin']);
            foreach ($permissions as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
            }
            $role->syncPermissions($permissions);
            $admin->assignRole($role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $admin;
    }

    private function makeUniteWithPriceAndOffer(): array
    {
        $provider = User::create([
            'name' => 'Test Provider',
            'email' => 'provider'.uniqid().'@example.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
            'type' => 'provider',
        ]);

        $department = Department::create([
            'user_id' => $provider->id,
            'name' => 'Test Department',
            'type' => 'lounge',
            'location' => 'Riyadh',
            'status' => 'active',
        ]);

        $unite = Unite::create([
            'department_id' => $department->id,
            'type' => 'lounge',
            'name' => 'Test Unite',
            'description' => 'Test description',
            'location_name' => 'Riyadh',
            'city' => 'Riyadh',
            'status' => 'active',
        ]);

        $price = UnitePrice::create([
            'unite_id' => $unite->id,
            'day' => 'week_day',
            'morning_price' => 100,
            'evening_price' => 150,
            'full_price' => 300,
        ]);

        $offer = UniteOffer::create([
            'unite_id' => $unite->id,
            'name' => 'Test Offer',
            'start' => now()->toDateString(),
            'end' => now()->addDays(30)->toDateString(),
            'morning_price' => 80,
            'evening_price' => 120,
            'full_day_price' => 250,
            'status' => 'active',
        ]);

        return compact('unite', 'price', 'offer');
    }

    // -------------------------------------------------------------------
    // UnitePrice — 10 scenarios
    // -------------------------------------------------------------------

    public function test_price_view_granted_allows_view(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.view']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.unite-prices.show', [$unite->id, $price->id]));

        $response->assertStatus(200);
    }

    public function test_price_view_revoked_returns_403(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin([]); // no permissions at all

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.unite-prices.show', [$unite->id, $price->id]));

        $response->assertStatus(403);
    }

    public function test_price_create_granted_allows_create(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.create']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.unite-prices.store', $unite->id), [
                'day' => 'thursday',
                'morning_price' => 90,
                'evening_price' => 140,
                'full_price' => 280,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('unite_prices', ['unite_id' => $unite->id, 'day' => 'thursday']);
    }

    public function test_price_create_revoked_returns_403(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.view']); // has view, not create

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.unite-prices.store', $unite->id), [
                'day' => 'thursday',
                'morning_price' => 90,
                'evening_price' => 140,
                'full_price' => 280,
            ]);

        $response->assertStatus(403);
    }

    public function test_price_update_granted_allows_update(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.update']);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-prices.update', [$unite->id, $price->id]), [
                'day' => 'week_day',
                'morning_price' => 999,
                'evening_price' => 150,
                'full_price' => 300,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('unite_prices', ['id' => $price->id, 'morning_price' => 999]);
    }

    public function test_price_update_revoked_returns_403(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.view']);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-prices.update', [$unite->id, $price->id]), [
                'day' => 'week_day',
                'morning_price' => 999,
                'evening_price' => 150,
                'full_price' => 300,
            ]);

        $response->assertStatus(403);
    }

    public function test_price_delete_granted_allows_delete(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.delete']);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.unite-prices.destroy', [$unite->id, $price->id]));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('unite_prices', ['id' => $price->id]);
    }

    public function test_price_delete_revoked_returns_403(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.update']);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.unite-prices.destroy', [$unite->id, $price->id]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('unite_prices', ['id' => $price->id]);
    }

    public function test_price_unrelated_permission_does_not_grant_access(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['services.create']); // unrelated module entirely

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.unite-prices.store', $unite->id), [
                'day' => 'thursday',
                'morning_price' => 90,
                'evening_price' => 140,
                'full_price' => 280,
            ]);

        $response->assertStatus(403);
    }

    public function test_price_unauthorized_unite_ownership_returns_403(): void
    {
        // Admin-permission path is unaffected by unite ownership at all --
        // this scenario is genuinely meaningful for the provider/API path
        // (a provider who doesn't own the target unite), not the admin
        // dashboard, since an admin holding the permission is authorized
        // for any venue by design (matching the admin route middleware's
        // own behavior, which is not unite-specific).
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();

        $otherProvider = User::create([
            'name' => 'Other Provider',
            'email' => 'other'.uniqid().'@example.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
            'type' => 'provider',
        ]);

        $response = $this->actingAs($otherProvider, 'sanctum')
            ->postJson("/api/unites/{$unite->id}/prices", [
                'day' => 'thursday',
                'morning_price' => 90,
                'evening_price' => 140,
                'full_price' => 280,
            ]);

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------
    // UniteOffer — 10 scenarios (identical shape to UnitePrice above)
    // -------------------------------------------------------------------

    public function test_offer_view_granted_allows_view(): void
    {
        ['unite' => $unite, 'offer' => $offer] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.view']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.unite-offers.show', [$unite->id, $offer->id]));

        $response->assertStatus(200);
    }

    public function test_offer_view_revoked_returns_403(): void
    {
        ['unite' => $unite, 'offer' => $offer] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin([]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.unite-offers.show', [$unite->id, $offer->id]));

        $response->assertStatus(403);
    }

    public function test_offer_create_granted_allows_create(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.create']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.unite-offers.store', $unite->id), [
                'name' => 'Winter Offer',
                'status' => 'active',
                'morning_price' => 70,
                'evening_price' => 110,
                'full_day_price' => 230,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('unite_offers', ['unite_id' => $unite->id, 'name' => 'Winter Offer']);
    }

    public function test_offer_create_revoked_returns_403(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.view']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.unite-offers.store', $unite->id), [
                'name' => 'Winter Offer',
                'status' => 'active',
                'morning_price' => 70,
                'evening_price' => 110,
                'full_day_price' => 230,
            ]);

        $response->assertStatus(403);
    }

    public function test_offer_update_granted_allows_update(): void
    {
        ['unite' => $unite, 'offer' => $offer] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.update']);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-offers.update', [$unite->id, $offer->id]), [
                'status' => 'inactive',
                'morning_price' => 80,
                'evening_price' => 120,
                'full_day_price' => 250,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('unite_offers', ['id' => $offer->id, 'status' => 'inactive']);
    }

    public function test_offer_update_revoked_returns_403(): void
    {
        ['unite' => $unite, 'offer' => $offer] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.view']);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-offers.update', [$unite->id, $offer->id]), [
                'status' => 'inactive',
                'morning_price' => 80,
                'evening_price' => 120,
                'full_day_price' => 250,
            ]);

        $response->assertStatus(403);
    }

    public function test_offer_delete_granted_allows_delete(): void
    {
        ['unite' => $unite, 'offer' => $offer] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.delete']);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.unite-offers.destroy', [$unite->id, $offer->id]));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('unite_offers', ['id' => $offer->id]);
    }

    public function test_offer_delete_revoked_returns_403(): void
    {
        ['unite' => $unite, 'offer' => $offer] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.update']);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.unite-offers.destroy', [$unite->id, $offer->id]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('unite_offers', ['id' => $offer->id]);
    }

    public function test_offer_unrelated_permission_does_not_grant_access(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['services.create']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.unite-offers.store', $unite->id), [
                'name' => 'Winter Offer',
                'status' => 'active',
                'morning_price' => 70,
                'evening_price' => 110,
                'full_day_price' => 230,
            ]);

        $response->assertStatus(403);
    }

    public function test_offer_unauthorized_unite_ownership_returns_403(): void
    {
        ['unite' => $unite] = $this->makeUniteWithPriceAndOffer();

        $otherProvider = User::create([
            'name' => 'Other Provider',
            'email' => 'other'.uniqid().'@example.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
            'type' => 'provider',
        ]);

        $response = $this->actingAs($otherProvider, 'sanctum')
            ->postJson("/api/unites/{$unite->id}/offers", [
                'name' => 'Winter Offer',
                'status' => 'active',
                'morning_price' => 70,
                'evening_price' => 110,
                'full_day_price' => 230,
            ]);

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------
    // Part 5 — permission cache verification
    // -------------------------------------------------------------------

    public function test_permission_cache_reflects_revoke_and_restore_without_restart(): void
    {
        ['unite' => $unite, 'price' => $price] = $this->makeUniteWithPriceAndOffer();
        $admin = $this->makeAdmin(['unites.update']);
        $role = $admin->roles()->first();

        // 1-2: has unites.update -> edit/update succeeds
        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-prices.update', [$unite->id, $price->id]), [
                'day' => 'week_day', 'morning_price' => 111, 'evening_price' => 150, 'full_price' => 300,
            ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('unite_prices', ['id' => $price->id, 'morning_price' => 111]);

        // 3-4: revoke unites.update -> next request is 403, no restart
        $role->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-prices.update', [$unite->id, $price->id]), [
                'day' => 'week_day', 'morning_price' => 222, 'evening_price' => 150, 'full_price' => 300,
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('unite_prices', ['id' => $price->id, 'morning_price' => 222]);

        // 5-6: restore unites.update -> succeeds again, no restart
        $role->syncPermissions(['unites.update']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.unite-prices.update', [$unite->id, $price->id]), [
                'day' => 'week_day', 'morning_price' => 333, 'evening_price' => 150, 'full_price' => 300,
            ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('unite_prices', ['id' => $price->id, 'morning_price' => 333]);
    }
}
