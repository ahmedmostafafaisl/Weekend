<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\DepartmentImage;
use App\Models\FavoriteUnite;
use App\Models\Unite;
use App\Models\UniteDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the two public department browse endpoints.
 *
 *   GET /departments/browse
 *   GET /departments/{department}/unites
 *
 * Both are unauthenticated by default; the unites endpoint detects an
 * authenticated user (via optional auth:sanctum) and adds is_favorite.
 */
class DepartmentBrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminRolesPermissionsSeeder::class);
        $this->seed(\Database\Seeders\AppSettingsSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /departments/browse — images now return {id, url} objects
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function browse_returns_paginated_active_departments(): void
    {
        $this->makeDept('Stadium A', 'stadium');
        $this->makeDept('Hall B', 'hall');

        $response = $this->getJson('/api/departments/browse');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type', 'location', 'unites_count', 'images']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function browse_images_include_id_and_url(): void
    {
        $dept = $this->makeDept('With Images', 'stadium');
        $img = DepartmentImage::create(['department_id' => $dept->id, 'image' => 'images/test.jpg']);

        $response = $this->getJson('/api/departments/browse');

        $response->assertStatus(200);

        $images = $response->json('data.0.images');
        $this->assertCount(1, $images);
        $this->assertArrayHasKey('id', $images[0], 'Each image must include its id');
        $this->assertArrayHasKey('url', $images[0], 'Each image must include its url');
        $this->assertEquals($img->id, $images[0]['id']);
        $this->assertStringContainsString('test.jpg', $images[0]['url']);
    }

    /** @test */
    public function browse_images_for_department_with_no_images_returns_empty_array(): void
    {
        $this->makeDept('No Images', 'hall');

        $response = $this->getJson('/api/departments/browse');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data.0.images'));
    }

    /** @test */
    public function browse_multiple_images_all_have_id_and_url(): void
    {
        $dept = $this->makeDept('Multi-image', 'lounge');
        DepartmentImage::create(['department_id' => $dept->id, 'image' => 'images/a.jpg']);
        DepartmentImage::create(['department_id' => $dept->id, 'image' => 'images/b.jpg']);
        DepartmentImage::create(['department_id' => $dept->id, 'image' => 'images/c.jpg']);

        $response = $this->getJson('/api/departments/browse');

        $images = $response->json('data.0.images');
        $this->assertCount(3, $images);
        foreach ($images as $image) {
            $this->assertArrayHasKey('id', $image);
            $this->assertArrayHasKey('url', $image);
            $this->assertIsInt($image['id']);
            $this->assertNotEmpty($image['url']);
        }
    }

    /** @test */
    public function browse_inactive_departments_are_excluded(): void
    {
        $this->makeDept('Active', 'stadium', 'active');
        $this->makeDept('Inactive', 'stadium', 'inactive');

        $response = $this->getJson('/api/departments/browse');

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Active', $response->json('data.0.name'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /departments/{department}/unites — is_favorite per unite
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function unites_endpoint_returns_is_favorite_false_for_guests(): void
    {
        $dept = $this->makeDept('Dept', 'lounge');
        $this->makeUnite($dept);

        // No auth header — guest request
        $response = $this->getJson("/api/departments/{$dept->id}/unites");

        $response->assertStatus(200);
        $this->assertArrayHasKey('is_favorite', $response->json('data.0'),
            'is_favorite must be present even for guest requests');
        $this->assertFalse($response->json('data.0.is_favorite'),
            'Guest must always get is_favorite = false');
    }

    /** @test */
    public function unites_endpoint_returns_is_favorite_true_for_favorited_unite(): void
    {
        $user = $this->makeUser();
        $dept = $this->makeDept('Dept', 'lounge');
        $unite = $this->makeUnite($dept);

        FavoriteUnite::create(['user_id' => $user->id, 'unite_id' => $unite->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/departments/{$dept->id}/unites");

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.0.is_favorite'),
            'Authenticated user who favorited the unite must get is_favorite = true');
    }

    /** @test */
    public function unites_endpoint_returns_is_favorite_false_for_non_favorited_unite(): void
    {
        $user = $this->makeUser();
        $dept = $this->makeDept('Dept', 'lounge');
        $this->makeUnite($dept);

        // Authenticated but has not favorited this unite
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/departments/{$dept->id}/unites");

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.0.is_favorite'));
    }

    /** @test */
    public function is_favorite_is_per_user_not_global(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $dept = $this->makeDept('Dept', 'lounge');
        $unite = $this->makeUnite($dept);

        FavoriteUnite::create(['user_id' => $user1->id, 'unite_id' => $unite->id]);
        // user2 has NOT favorited

        $resp1 = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/departments/{$dept->id}/unites");
        $resp2 = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/departments/{$dept->id}/unites");

        $this->assertTrue($resp1->json('data.0.is_favorite'),
            'user1 favorited — must be true');
        $this->assertFalse($resp2->json('data.0.is_favorite'),
            'user2 did not favorite — must be false');
    }

    /** @test */
    public function is_favorite_is_per_unite_within_same_response(): void
    {
        $user = $this->makeUser();
        $dept = $this->makeDept('Dept', 'lounge');
        $fav = $this->makeUnite($dept, 'Favorited Unite');
        $notFav = $this->makeUnite($dept, 'Not Favorited Unite');

        FavoriteUnite::create(['user_id' => $user->id, 'unite_id' => $fav->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/departments/{$dept->id}/unites");

        $data = collect($response->json('data'));

        $favData = $data->firstWhere('name', 'Favorited Unite');
        $notFavData = $data->firstWhere('name', 'Not Favorited Unite');

        $this->assertNotNull($favData);
        $this->assertNotNull($notFavData);
        $this->assertTrue($favData['is_favorite']);
        $this->assertFalse($notFavData['is_favorite']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::create([
            'name' => 'User',
            'email' => 'u'.uniqid().'@e.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'),
            'status' => 'active',
            'type' => 'customer',
        ]);
    }

    private function makeDept(string $name, string $type, string $status = 'active'): Department
    {
        $provider = User::create([
            'name' => 'Provider',
            'email' => 'prov'.uniqid().'@e.com',
            'phone' => '05'.rand(10000000, 99999999),
            'password' => bcrypt('x'),
            'status' => 'active',
            'type' => 'provider',
        ]);

        return Department::create([
            'user_id' => $provider->id,
            'name' => $name,
            'type' => $type,
            'location' => 'Riyadh',
            'latitude' => '24.7',
            'longitude' => '46.7',
            'status' => $status,
            'whatsapp' => '966500000000',
        ]);
    }

    private function makeUnite(Department $dept, string $name = 'Test Unite'): Unite
    {
        $unite = Unite::create([
            'department_id' => $dept->id,
            'name' => $name,
            'type' => $dept->type,
            'status' => 'active',
        ]);

        UniteDetail::create(['unite_id' => $unite->id]);

        return $unite;
    }
}
