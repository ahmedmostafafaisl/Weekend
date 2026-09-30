<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Support\SafeUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(\Database\Seeders\AdminRolesPermissionsSeeder::class);
    }

    /** Always remove test uploads — even when an assertion fails mid-test. */
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\File::deleteDirectory(public_path('test-uploads'));
        parent::tearDown();
    }

    // ── SafeUpload ──────────────────────────────────────────────────────────

    /** @test */
    public function php_file_disguised_with_any_name_is_rejected(): void
    {
        $shell = $this->realUpload('shell.php', '<?php system($_GET["c"]); ?>');

        $this->expectException(ValidationException::class);
        SafeUpload::store($shell, 'test-uploads', [...SafeUpload::IMAGES, ...SafeUpload::VIDEOS]);
    }

    /** @test */
    public function php_content_renamed_to_jpg_is_still_rejected(): void
    {
        // Extension comes from content sniffing, not the client filename.
        // (UploadedFile::fake() infers MIME from the NAME, so it can't test this —
        // a real UploadedFile is sniffed by finfo from its bytes, as in production.)
        $shell = $this->realUpload('photo.jpg', '<?php system($_GET["c"]); ?>');

        $this->expectException(ValidationException::class);
        SafeUpload::store($shell, 'test-uploads');
    }

    /** @test */
    public function svg_is_rejected_as_an_image(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->expectException(ValidationException::class);
        SafeUpload::store($svg, 'test-uploads');
    }

    /** @test */
    public function real_image_is_stored_under_a_random_name_with_content_extension(): void
    {
        $img = UploadedFile::fake()->image('../../evil name.png', 10, 10);

        $path = SafeUpload::store($img, 'test-uploads');

        $this->assertMatchesRegularExpression('#^test-uploads/[0-9a-f-]{36}\.png$#', $path);
        $this->assertFileExists(public_path($path));
    }

    /** @test */
    public function directory_traversal_is_neutralised(): void
    {
        $this->assertSame('department/hall/x/sak_image', SafeUpload::sanitizeDirectory('department/hall/../../x/sak_image'));
        $this->assertSame('users/5/photo', SafeUpload::sanitizeDirectory('/users/./5//photo/'));
        $this->assertSame('department/قاعات_النور', SafeUpload::sanitizeDirectory('department/قاعات النور'));
    }

    // ── Admin permission gating ─────────────────────────────────────────────

    /** @test */
    public function reviewer_cannot_touch_app_settings_homepage_or_venues(): void
    {
        $reviewer = $this->adminWithRole('reviewer');

        $this->actingAs($reviewer, 'admin')->get('/admin/app-settings')->assertForbidden();
        $this->actingAs($reviewer, 'admin')->get('/admin/homepage')->assertForbidden();
        $this->actingAs($reviewer, 'admin')->get('/admin/unites/create')->assertForbidden();
    }

    /** @test */
    public function viewer_can_view_but_not_change_app_settings(): void
    {
        $viewer = $this->adminWithRole('viewer');

        $this->actingAs($viewer, 'admin')->get('/admin/app-settings')->assertOk();
        $this->actingAs($viewer, 'admin')->get('/admin/app-settings/create')->assertForbidden();
    }

    /** @test */
    public function super_admin_keeps_full_access(): void
    {
        $super = $this->adminWithRole('super_admin');

        $this->actingAs($super, 'admin')->get('/admin/app-settings')->assertOk();
        $this->actingAs($super, 'admin')->get('/admin/app-settings/create')->assertOk();
        $this->actingAs($super, 'admin')->get('/admin/homepage')->assertOk();
    }

    /** A genuine uploaded file (finfo MIME sniffing), not a name-based fake. */
    private function realUpload(string $clientName, string $content): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, $content);

        return new UploadedFile($tmp, $clientName, null, null, true);
    }

    private function adminWithRole(string $role): Admin
    {
        $admin = Admin::create([
            'name' => ucfirst($role),
            'email' => $role.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole($role);

        return $admin;
    }
}
