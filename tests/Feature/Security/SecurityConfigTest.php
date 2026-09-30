<?php

namespace Tests\Feature\Security;

use App\Support\ClientError;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SecurityConfigTest extends TestCase
{
    use RefreshDatabase;

    // ── ClientError ─────────────────────────────────────────────────────────

    /** @test */
    public function sql_errors_are_hidden_even_though_they_are_runtime_exceptions(): void
    {
        $sql = new QueryException('mysql', 'select * from users where password_hash = ?', ['x'], new \Exception('SQLSTATE[42S22]: Column not found'));

        $this->assertInstanceOf(\RuntimeException::class, $sql, 'precondition: QueryException IS a RuntimeException');
        $this->assertStringNotContainsString('users', ClientError::message($sql));
        $this->assertSame(__('lang.unexpected_error_try_again'), ClientError::message($sql));
    }

    /** @test */
    public function deliberate_abort_messages_still_reach_the_user(): void
    {
        $this->assertSame('This slot is taken', ClientError::message(new HttpException(422, 'This slot is taken')));
        $this->assertSame('Unknown payment method: x', ClientError::message(new \InvalidArgumentException('Unknown payment method: x')));
    }

    /** @test */
    public function php_runtime_errors_are_hidden(): void
    {
        $e = new \TypeError('Cannot assign null to property App\\Services\\Payment\\BasePaymentService::$api_key');
        $this->assertStringNotContainsString('BasePaymentService', ClientError::message($e));
    }

    // ── forgot-password must not leak SMTP details ──────────────────────────

    /** @test */
    public function forgot_password_mail_failure_does_not_leak_smtp_details(): void
    {
        Password::shouldReceive('sendResetLink')->andThrow(
            new \RuntimeException('Connection to smtp.hostinger.com:465 failed: auth user noreply@weekend.sa')
        );

        $res = $this->postJson('/api/forgot-password', ['email' => 'a@b.com']);

        $res->assertStatus(503)->assertJsonMissingPath('mail_error');
        $this->assertStringNotContainsString('smtp', strtolower($res->getContent()));
    }

    // ── rate limits ─────────────────────────────────────────────────────────

    /** @test */
    public function promo_code_guessing_is_rate_limited(): void
    {
        $codes = collect(range(1, 12))->map(fn ($i) => $this->postJson('/api/promo-codes/validate', ['code' => "GUESS{$i}", 'amount' => 100])->status());

        $this->assertContains(429, $codes->all(), 'the 11th+ guess within a minute must be throttled');
    }

    /** @test */
    public function forgot_password_is_rate_limited_per_ip(): void
    {
        Password::shouldReceive('sendResetLink')->andReturn(Password::RESET_LINK_SENT);

        $statuses = collect(range(1, 7))->map(fn ($i) => $this->postJson('/api/forgot-password', ['email' => "user{$i}@example.com"])->status());

        $this->assertContains(429, $statuses->all(), 'many different addresses from one IP must be throttled');
    }

    // ── headers ─────────────────────────────────────────────────────────────

    /** @test */
    public function responses_carry_security_headers(): void
    {
        $res = $this->getJson('/api/saudi-cities');

        $res->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}