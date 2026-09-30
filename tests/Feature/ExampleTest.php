<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** The root route renders the public bilingual landing page (HomeController). */
    public function test_homepage_returns_ok(): void
    {
        $this->get('/')->assertStatus(200);
    }
}
