<?php

namespace Tests\Feature\Unite;

use App\Models\Department;
use App\Models\Unite;
use App\Models\UniteDetail;
use App\Models\UniteImage;
use App\Models\UnitePrice;
use App\Models\UniteSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 3 — Partial Unite Update Tests
 * Phase 4 — Image Management Tests
 */
class UniteUpdateAndImageTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────────────────────────────────────────────────────────────
    // Phase 3: Partial Update
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function update_with_only_name_leaves_all_other_fields_unchanged(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        $originalDesc = $unite->description;

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", ['name' => 'New Name']);

        $response->assertStatus(200);

        $fresh = $unite->fresh();
        $this->assertEquals('New Name', $fresh->name);
        $this->assertEquals($originalDesc, $fresh->description,
            'description must be unchanged when not included in the request');
    }

    /** @test */
    public function update_omitting_slots_leaves_existing_slots_unchanged(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        $slotCountBefore = $unite->slots()->count();

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", ['name' => 'Updated Name']);

        $response->assertStatus(200);
        $this->assertEquals($slotCountBefore, $unite->fresh()->slots()->count(),
            'Slots must not be deleted when slots[] is absent from the update request');
    }

    /** @test */
    public function update_omitting_prices_leaves_existing_prices_unchanged(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        $priceCountBefore = $unite->prices()->count();

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", ['description' => 'New description']);

        $response->assertStatus(200);
        $this->assertEquals($priceCountBefore, $unite->fresh()->prices()->count(),
            'Prices must not be deleted when prices[] is absent from the update request');
    }

    /** @test */
    public function update_with_type_but_no_detail_fields_does_not_fail(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", ['type' => 'stadium']);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_replacing_slots_section_changes_only_slots(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        $priceBefore = $unite->prices()->first();

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", [
                'slots' => [
                    [
                        'day_of_week' => 'friday',
                        'status' => 'available',
                        'full_start' => '08:00',
                        'full_end' => '20:00',
                    ],
                ],
            ]);

        $response->assertStatus(200);

        // Prices must be untouched
        $this->assertDatabaseHas('unite_prices', [
            'id' => $priceBefore->id,
            'unite_id' => $unite->id,
        ]);
    }

    /** @test */
    public function update_overnight_slot_is_accepted(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", [
                'slots' => [
                    [
                        'day_of_week' => 'friday',
                        'status' => 'available',
                        'full_start' => '17:00',
                        'full_end' => '05:00',  // overnight
                        'day_start' => '17:00',
                        'day_end' => '05:00',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthorized_user_cannot_update_another_providers_unite(): void
    {
        [$_unused, $unite] = $this->makeUniteWithSlotAndPrice();
        $otherProvider = $this->makeProvider();

        $response = $this->actingAs($otherProvider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", ['name' => 'Stolen Name']);

        $response->assertStatus(403);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Phase 4: Image Management
    // ───────────────────────────────────────────────────────────────────────

    /** @test */
    public function deleting_specific_image_removes_only_that_image(): void
    {
        Storage::fake('public');
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();

        $img1 = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/one.jpg']);
        $img2 = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/two.jpg']);
        $img3 = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/three.jpg']);

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", [
                'deleted_image_ids' => [$img2->id],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('unite_images', ['id' => $img1->id]);
        $this->assertDatabaseMissing('unite_images', ['id' => $img2->id]);
        $this->assertDatabaseHas('unite_images', ['id' => $img3->id]);
    }

    /** @test */
    public function omitting_deleted_image_ids_leaves_all_images_intact(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();

        $img1 = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/one.jpg']);
        $img2 = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/two.jpg']);

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", ['name' => 'Updated Name']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('unite_images', ['id' => $img1->id]);
        $this->assertDatabaseHas('unite_images', ['id' => $img2->id]);
    }

    /** @test */
    public function empty_deleted_image_ids_array_deletes_nothing(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        $img = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/one.jpg']);

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", [
                'deleted_image_ids' => [],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('unite_images', ['id' => $img->id]);
    }

    /** @test */
    public function invalid_image_id_belonging_to_another_unite_is_silently_ignored(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        [$_unused, $otherUnite] = $this->makeUniteWithSlotAndPrice($provider);

        $myImg = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/mine.jpg']);
        $otherImg = UniteImage::create(['unite_id' => $otherUnite->id, 'image' => 'storage/unites/other.jpg']);

        $response = $this->actingAs($provider, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", [
                'deleted_image_ids' => [$otherImg->id], // belongs to a different unite
            ]);

        $response->assertStatus(200);
        // Other image must still exist (silently ignored)
        $this->assertDatabaseHas('unite_images', ['id' => $otherImg->id]);
        // Our own image untouched
        $this->assertDatabaseHas('unite_images', ['id' => $myImg->id]);
    }

    /** @test */
    public function single_unite_resource_returns_image_id_and_url(): void
    {
        [$provider, $unite] = $this->makeUniteWithSlotAndPrice();
        UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/test.jpg']);

        $response = $this->actingAs($provider, 'sanctum')
            ->getJson("/api/unites/{$unite->id}");

        $response->assertStatus(200);
        $images = $response->json('data.images');
        $this->assertNotEmpty($images);
        $this->assertArrayHasKey('id', $images[0],
            'SingleUniteResource must return image id, not just URL');
        $this->assertArrayHasKey('image', $images[0],
            'SingleUniteResource must return image URL');
    }

    /** @test */
    public function unauthorized_user_cannot_delete_another_providers_images(): void
    {
        [$_unused, $unite] = $this->makeUniteWithSlotAndPrice();
        $img = UniteImage::create(['unite_id' => $unite->id, 'image' => 'storage/unites/protected.jpg']);

        $attacker = $this->makeProvider();

        $response = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/unites/{$unite->id}", [
                'deleted_image_ids' => [$img->id],
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('unite_images', ['id' => $img->id]);
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

    private function makeUniteWithSlotAndPrice(?User $existingProvider = null): array
    {
        $provider = $existingProvider ?? $this->makeProvider();

        $dept = Department::create([
            'user_id' => $provider->id,
            'name' => 'Dept'.uniqid(),
            'type' => 'stadium',
            'location' => 'Riyadh',
            'status' => 'active',
        ]);

        $unite = Unite::create([
            'department_id' => $dept->id,
            'type' => 'stadium',
            'name' => 'Stadium '.uniqid(),
            'description' => 'Test description',
            'location_name' => 'Riyadh',
            'city' => 'riyadh',
            'status' => 'active',
            'requires_approval' => true,
        ]);

        UniteDetail::create([
            'unite_id' => $unite->id,
            'customize_Category' => 'football',
            'customize_Place' => 'both',
            'width' => '80',
            'length' => '70',
        ]);

        foreach (['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $d) {
            UniteSlot::create([
                'unite_id' => $unite->id,
                'day_of_week' => $d,
                'status' => 'available',
                'full_start' => '08:00',
                'full_end' => '23:00',
            ]);
        }

        UnitePrice::create([
            'unite_id' => $unite->id,
            'day' => 'week_day',
            'hourly_enabled' => true,
            'day_hour_price' => 100,
            'night_hour_price' => 150,
            'min_booking_minutes' => 60,
        ]);

        return [$provider, $unite];
    }
}
