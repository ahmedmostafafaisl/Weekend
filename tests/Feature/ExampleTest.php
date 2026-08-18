<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root route deliberately redirects to the admin login screen --
     * this is an API/admin-first application with no public landing page
     * of its own (confirmed against the actual route definition in
     * routes/web.php: Route::get('/', fn () => redirect()->route('admin.login'))).
     */
    public function test_the_application_redirects_root_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('admin.login'));
    }
}
