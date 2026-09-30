<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Regression tests for the §2 API authentication fixes. */
class ApiAuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_locks_out_after_five_failures_even_with_the_right_password(): void
    {
        $user = $this->user();

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-pass'])->assertStatus(422);
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password1'])
            ->assertStatus(422)
            ->assertJsonMissingPath('token');
    }

    /** @test */
    public function successful_login_resets_the_failure_counter(): void
    {
        $user = $this->user();
        foreach (range(1, 4) as $i) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-pass']);
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password1'])->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-pass'])->assertStatus(422);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password1'])->assertOk();
    }

    /** @test */
    public function unknown_email_and_wrong_password_are_indistinguishable(): void
    {
        $user = $this->user();

        $unknown = $this->postJson('/api/login', ['email' => 'nobody@nowhere.test', 'password' => 'whatever1']);
        $wrong = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'whatever1']);

        $unknown->assertStatus(422);
        $this->assertSame($wrong->json('errors'), $unknown->json('errors'), 'response must not reveal whether an email is registered');
    }

    /** @test */
    public function registration_cannot_set_account_status(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Eve', 'email' => 'eve@test.com', 'password' => 'password1', 'password_confirmation' => 'password1',
            'type' => 'customer', 'nation' => 'saudi', 'status' => 'deactive',
        ])->assertSuccessful();

        $this->assertSame('active', User::where('email', 'eve@test.com')->value('status'));
    }

    /** @test */
    public function fcm_token_is_validated(): void
    {
        $this->actingAs($this->user(), 'sanctum')->postJson('/api/fcm/token', ['fcm_token' => str_repeat('x', 300)])->assertStatus(422);
        $this->actingAs($this->user(), 'sanctum')->postJson('/api/fcm/token', [])->assertStatus(422);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'U', 'email' => 'u'.uniqid().'@test.com', 'phone' => '05'.random_int(10000000, 99999999),
            'password' => bcrypt('password1'), 'status' => 'active', 'type' => 'customer',
        ]);
    }
}
