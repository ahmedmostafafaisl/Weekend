<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\AdView;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8 — Ad / AdPackage Domain Audit.
 *
 * KNOWN GAPS DISCOVERED AND DOCUMENTED HERE:
 *
 * 1. NO subscription quota gate on Ad creation.
 *    Any authenticated user can create unlimited ads via POST /api/ads
 *    regardless of whether they hold an active AdPackage subscription.
 *    The PropertyPackage gate (UniteController::store()) exists; the
 *    equivalent for ads does not.
 *
 * 2. NO AdPolicy -- authorization for Ad CRUD is entirely absent.
 *    Any authenticated user can update or delete any other user's ad.
 *
 * 3. markSeen uses updateOrCreate(ad_id, user_id) -- safe against
 *    duplicate counting per user/ad pair. Confirmed correct.
 *
 * 4. activate() hardcodes 24-hour expiry regardless of AdPackage duration.
 *    A 'duration'-type AdPackage with a 7-day or 30-day window is ignored.
 *
 * The tests below verify the CURRENT behavior of what actually works,
 * document the gaps explicitly, and provide regression coverage for what
 * is correct, without inventing behavior not in the code.
 */
class AdDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminRolesPermissionsSeeder::class);
        $this->seed(\Database\Seeders\AppSettingsSeeder::class);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Ad CRUD — basic lifecycle
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function authenticated_user_can_create_an_ad(): void
    {
        $user = $this->makeProviderWithAdSub();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ads', [
                'title' => 'My Test Ad',
                'description' => 'Test description',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ads', ['title' => 'My Test Ad', 'user_id' => $user->id]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_an_ad(): void
    {
        $response = $this->postJson('/api/ads', [
            'title' => 'Unauthenticated Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function provider_without_ad_subscription_cannot_create_an_ad(): void
    {
        // The gate is now enforced. A provider without an active AdPackage
        // subscription gets a 403, not a 201.
        $user = $this->makeProvider(); // no subscription

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ads', [
                'title' => 'No Subscription',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function provider_with_active_ad_subscription_can_create_an_ad(): void
    {
        $user = $this->makeProvider();
        $pkg = AdPackage::create(['name' => 'Ad Pkg', 'type' => 'count', 'count' => 5, 'status' => 'active']);
        Subscription::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 5,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ads', [
                'title' => 'Subscribed Ad',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function free_trial_flag_allows_ad_creation_without_subscription(): void
    {
        // When allow_ads_without_subscription is enabled, the gate is
        // bypassed and any provider can create ads regardless of subscription.
        \App\Models\AppSetting::where('key', 'allow_ads_without_subscription')
            ->update(['is_active' => true]);

        $user = $this->makeProvider(); // still no subscription

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ads', [
                'title' => 'Free Trial Ad',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function disabling_free_trial_flag_restores_the_gate(): void
    {
        \App\Models\AppSetting::where('key', 'allow_ads_without_subscription')
            ->update(['is_active' => false]);

        $user = $this->makeProvider(); // no subscription

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ads', [
                'title' => 'Should Be Blocked',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function active_ads_are_returned_in_index(): void
    {
        $user = $this->makeProvider();
        Ad::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'title' => 'Active Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'is_active' => true,
            'activated_at' => now()->subHour(),
            'expires_at' => now()->addHour(),
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/ads');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    // ───────────────────────────────────────────────────────────────────────
    // markSeen — duplicate-counting guard
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function marking_ad_seen_twice_creates_only_one_view_record(): void
    {
        $user = $this->makeProvider();
        $ad = $this->makeActiveAd($user);

        // First mark
        $this->actingAs($user, 'sanctum')->postJson("/api/ads/{$ad->id}/seen");
        // Second mark — same user, same ad
        $this->actingAs($user, 'sanctum')->postJson("/api/ads/{$ad->id}/seen");

        $this->assertEquals(1, AdView::where('ad_id', $ad->id)->where('user_id', $user->id)->count(),
            'Marking the same ad seen twice must not create duplicate AdView records');
    }

    /** @test */
    public function different_users_marking_same_ad_seen_creates_separate_records(): void
    {
        $user1 = $this->makeProvider();
        $user2 = $this->makeProvider();
        $ad = $this->makeActiveAd($user1);

        $this->actingAs($user1, 'sanctum')->postJson("/api/ads/{$ad->id}/seen");
        $this->actingAs($user2, 'sanctum')->postJson("/api/ads/{$ad->id}/seen");

        $this->assertEquals(2, AdView::where('ad_id', $ad->id)->count(),
            'Different users marking the same ad creates one record each');
    }

    /** @test */
    public function inactive_ad_cannot_be_marked_seen(): void
    {
        $user = $this->makeProvider();
        $ad = Ad::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'title' => 'Inactive Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'is_active' => false, // inactive
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/ads/{$ad->id}/seen");

        $response->assertStatus(404); // activeNow() scope filters it out
    }

    // ───────────────────────────────────────────────────────────────────────
    // Activation
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function activating_an_ad_sets_is_active_and_expires_at(): void
    {
        $user = $this->makeProvider();
        $ad = Ad::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'title' => 'Pending Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'is_active' => false,
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/ads/{$ad->id}/activate");

        $response->assertStatus(200);
        $fresh = $ad->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertNotNull($fresh->expires_at);
    }

    /** @test */
    public function activation_hardcodes_24_hour_expiry_regardless_of_package(): void
    {
        // GAP DOCUMENTED: AdPackage duration is ignored during activation.
        // A provider with a 30-day duration package still gets 24h expiry.
        $user = $this->makeProvider();
        $pkg = AdPackage::create([
            'name' => '30-Day Package',
            'type' => 'duration',
            'duration' => 30, // 30 days
            'status' => 'active',
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'package_id' => $pkg->id,
            'status' => 'active',
        ]);

        $ad = Ad::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'title' => 'Package Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'is_active' => false,
            'approval_status' => 'approved',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/ads/{$ad->id}/activate");

        $fresh = $ad->fresh();
        // Documents current behavior: expires_at is always now()+24h,
        // not now()+30days as the package would suggest.
        $expiryHours = now()->diffInHours($fresh->expires_at);
        $this->assertLessThanOrEqual(24, $expiryHours,
            'Ad activation currently hardcodes 24h expiry -- package duration is not used');
    }

    // ───────────────────────────────────────────────────────────────────────
    // AdPackage — Subscription::isExpiredByRules() applies correctly
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function ad_subscription_with_exhausted_count_is_expired(): void
    {
        $user = $this->makeProvider();
        $pkg = AdPackage::create([
            'name' => 'Count Ad Pkg',
            'type' => 'count',
            'count' => 5,
            'status' => 'active',
        ]);
        $sub = Subscription::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 0, // exhausted
        ]);

        $this->assertTrue($sub->isExpiredByRules(),
            'An ad subscription with count=0 must be considered expired by isExpiredByRules()');
    }

    /** @test */
    public function ad_subscription_with_active_count_is_not_expired(): void
    {
        $user = $this->makeProvider();
        $pkg = AdPackage::create([
            'name' => 'Count Ad Pkg',
            'type' => 'count',
            'count' => 5,
            'status' => 'active',
        ]);
        $sub = Subscription::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 3,
        ]);

        $this->assertFalse($sub->isExpiredByRules(),
            'An ad subscription with count>0 must not be considered expired');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Authorization gap documentation
    // ───────────────────────────────────────────────────────────────────────

    // ───────────────────────────────────────────────────────────────────────
    // Authorization — AdPolicy
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function provider_cannot_update_another_providers_ad(): void
    {
        $owner = $this->makeProviderWithAdSub();
        $attacker = $this->makeProviderWithAdSub();

        $ad = Ad::create([
            'user_id' => $owner->id,
            'type' => 'ad',
            'title' => 'Owner Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/ads/{$ad->id}", [
                'title' => 'Hijacked Title',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('Owner Ad', $ad->fresh()->title,
            'Title must not change when update is rejected by policy');
    }

    /** @test */
    public function provider_can_update_their_own_ad(): void
    {
        $owner = $this->makeProviderWithAdSub();

        $ad = Ad::create([
            'user_id' => $owner->id,
            'type' => 'ad',
            'title' => 'My Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/ads/{$ad->id}", [
                'title' => 'My Updated Ad',
                'target_audience' => 'both',
                'target_user_type' => 'all',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('My Updated Ad', $ad->fresh()->title);
    }

    /** @test */
    public function provider_cannot_delete_another_providers_ad(): void
    {
        $owner = $this->makeProviderWithAdSub();
        $attacker = $this->makeProviderWithAdSub();

        $ad = Ad::create([
            'user_id' => $owner->id,
            'type' => 'ad',
            'title' => 'Protected Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/ads/{$ad->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('ads', ['id' => $ad->id]);
    }

    /** @test */
    public function provider_cannot_activate_another_providers_ad(): void
    {
        $owner = $this->makeProviderWithAdSub();
        $attacker = $this->makeProviderWithAdSub();

        $ad = Ad::create([
            'user_id' => $owner->id,
            'type' => 'ad',
            'title' => 'Inactive Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'is_active' => false,
            'approval_status' => 'approved',
        ]);

        $this->actingAs($attacker, 'sanctum')
            ->postJson("/api/ads/{$ad->id}/activate")
            ->assertStatus(403);

        $this->assertFalse($ad->fresh()->is_active);
    }

    /** @test */
    public function any_authenticated_user_can_mark_any_active_ad_seen(): void
    {
        // markSeen is intentionally open to all authenticated users —
        // any viewer can register having seen an ad, not just the owner.
        $owner = $this->makeProviderWithAdSub();
        $viewer = $this->makeProviderWithAdSub();
        $ad = $this->makeActiveAd($owner);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/ads/{$ad->id}/seen")
            ->assertStatus(200);
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

    private function makeProviderWithAdSub(): User
    {
        $user = $this->makeProvider();
        $pkg = AdPackage::create(['name' => 'Pkg'.uniqid(), 'type' => 'count', 'count' => 5, 'status' => 'active']);
        Subscription::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'package_id' => $pkg->id,
            'status' => 'active',
            'count' => 5,
        ]);

        return $user;
    }

    private function makeActiveAd(User $user): Ad
    {
        return Ad::create([
            'user_id' => $user->id,
            'type' => 'ad',
            'title' => 'Active Ad',
            'target_audience' => 'both',
            'target_user_type' => 'all',
            'is_active' => true,
            'activated_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'approval_status' => 'approved',
        ]);
    }
}
