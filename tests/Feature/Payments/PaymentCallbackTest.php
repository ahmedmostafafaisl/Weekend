<?php

namespace Tests\Feature\Payments;

use App\Models\Department;
use App\Models\MultiBookingGroup;
use App\Models\Payment;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Models\User;
use App\Services\Payment\TamaraPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression tests for the §5 payment fixes. Every "forged" test posts what an
 * attacker could send to a public callback URL; the gateway's own API is faked
 * to say the payment was NOT made. None of these may mark anything paid.
 */
class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['services.geidea.webhook_secret' => null]);
        $this->customer = $this->user('customer');
    }

    // ── Maysar ──────────────────────────────────────────────────────────────

    /** @test */
    public function forged_maysar_callback_does_not_mark_payment_paid(): void
    {
        [$payment, $reservation] = $this->pendingReservationPayment('maysar', 'sess_123');
        Http::fake(['*/checkout/session/*' => Http::response(['status' => 'initiated'])]);

        $this->postJson('/api/maysar/callback', ['session_id' => 'sess_123', 'status' => 'paid'])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $reservation->fresh()->status);
    }

    /** @test */
    public function genuine_maysar_callback_confirms_the_reservation(): void
    {
        [$payment, $reservation] = $this->pendingReservationPayment('maysar', 'sess_ok');
        Http::fake(['*/checkout/session/*' => Http::response(['status' => 'paid'])]);

        $this->postJson('/api/maysar/callback', ['session_id' => 'sess_ok', 'status' => 'paid'])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('confirmed', $reservation->fresh()->status);
    }

    // ── Tamara ──────────────────────────────────────────────────────────────

    /** @test */
    public function tamara_callback_with_only_a_reference_id_cannot_mark_paid(): void
    {
        [$payment] = $this->pendingReservationPayment('tamara', null);
        $this->mockTamara(status: 'new');

        $this->postJson('/api/tamara/callback', ['order_reference_id' => $payment->reference_id])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /** @test */
    public function tamara_callback_for_an_uncaptured_order_does_not_mark_paid(): void
    {
        [$payment] = $this->pendingReservationPayment('tamara', 'tam_1');
        $this->mockTamara(status: 'new');

        $this->postJson('/api/tamara/callback', ['order_id' => 'tam_1'])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /** @test */
    public function tamara_callback_for_a_captured_order_confirms_the_reservation(): void
    {
        [$payment, $reservation] = $this->pendingReservationPayment('tamara', 'tam_2');
        $this->mockTamara(status: 'fully_captured');

        $this->postJson('/api/tamara/callback', ['order_id' => 'tam_2'])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('confirmed', $reservation->fresh()->status);
    }

    // ── Geidea ──────────────────────────────────────────────────────────────

    /** @test */
    public function unsigned_geidea_callback_is_verified_against_the_api(): void
    {
        [$payment, $reservation] = $this->pendingReservationPayment('geidea', null);
        Http::fake(['*/direct/session/*' => Http::response([
            'status' => 'Failed', 'detailedStatus' => 'Declined', 'merchantReferenceId' => $payment->reference_id,
        ])]);

        $this->postJson('/api/geidea/payment/callback', [
            'merchantReferenceId' => $payment->reference_id,
            'order' => ['orderId' => 'gd_1', 'status' => 'Success', 'detailedStatus' => 'Paid'],
        ])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status, 'payload said Paid, Geidea API said Declined');
        $this->assertSame('pending', $reservation->fresh()->status);
    }

    /** @test */
    public function geidea_callback_with_a_bad_signature_is_rejected(): void
    {
        config(['services.geidea.webhook_secret' => 'real-secret']);
        [$payment] = $this->pendingReservationPayment('geidea', null);

        $this->withHeaders(['X-Geidea-Signature' => 'forged'])->postJson('/api/geidea/payment/callback', [
            'merchantReferenceId' => $payment->reference_id,
            'order' => ['orderId' => 'gd_2', 'status' => 'Success', 'detailedStatus' => 'Paid'],
        ])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ── Fulfilment ──────────────────────────────────────────────────────────

    /** @test */
    public function paying_a_multi_booking_confirms_the_group_and_every_reservation(): void
    {
        $unites = [$this->unite(), $this->unite()];
        $group = MultiBookingGroup::create([
            'user_id' => $this->customer->id, 'department_id' => $unites[0]->department_id,
            'reservation_date' => now()->addDays(3)->toDateString(), 'period_type' => 'morning',
            'total_price' => 600, 'total_amount' => 600, 'status' => 'pending',
        ]);
        foreach ($unites as $u) {
            $this->reservation($u, ['multi_booking_group_id' => $group->id]);
        }
        $payment = Payment::create(['user_id' => $this->customer->id, 'payment_type' => 'geidea', 'amount' => 600, 'status' => 'pending']);
        $group->update(['payment_id' => $payment->id]);

        Http::fake(['*/direct/session/*' => Http::response([
            'status' => 'Success', 'detailedStatus' => 'Paid', 'merchantReferenceId' => $payment->reference_id,
        ])]);

        $this->postJson('/api/geidea/payment/callback', [
            'merchantReferenceId' => $payment->reference_id,
            'order' => ['orderId' => 'gd_multi', 'status' => 'Success', 'detailedStatus' => 'Paid'],
        ])->assertOk();

        $this->assertSame('confirmed', $group->fresh()->status);
        $this->assertSame(['confirmed', 'confirmed'], $group->reservations()->pluck('status')->all());
    }

    /** @test */
    public function a_late_payment_never_resurrects_a_cancelled_reservation(): void
    {
        [$payment, $reservation] = $this->pendingReservationPayment('maysar', 'sess_late');
        $reservation->update(['status' => 'cancelled']);
        Http::fake(['*/checkout/session/*' => Http::response(['status' => 'paid'])]);

        $this->postJson('/api/maysar/callback', ['session_id' => 'sess_late', 'status' => 'paid'])->assertOk();

        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    /** @test */
    public function count_type_subscription_gets_its_quota_on_non_geidea_gateways(): void
    {
        $pkg = PropertyPackage::create(['name' => 'P', 'type' => 'count', 'count' => 7, 'price' => 100, 'status' => 'active']);
        $sub = Subscription::create(['user_id' => $this->customer->id, 'type' => 'property', 'package_id' => $pkg->id, 'status' => 'pending', 'count' => null]);
        Payment::create(['user_id' => $this->customer->id, 'payment_type' => 'maysar', 'amount' => 100,
            'status' => 'pending', 'subscription_id' => $sub->id, 'payment_id' => 'sess_sub']);
        Http::fake(['*/checkout/session/*' => Http::response(['status' => 'paid'])]);

        $this->postJson('/api/maysar/callback', ['session_id' => 'sess_sub', 'status' => 'paid'])->assertOk();

        $this->assertSame('active', $sub->fresh()->status);
        $this->assertSame(7, (int) $sub->fresh()->count, 'count was NULL (= unlimited quota) before the fix');
    }

    // ── IDOR ────────────────────────────────────────────────────────────────

    /** @test */
    public function customers_only_see_their_own_payments(): void
    {
        $mine = Payment::create(['user_id' => $this->customer->id, 'payment_type' => 'geidea', 'amount' => 10, 'status' => 'paid']);
        $theirs = Payment::create(['user_id' => $this->user('customer')->id, 'payment_type' => 'geidea', 'amount' => 99, 'status' => 'paid']);

        $list = $this->actingAs($this->customer, 'sanctum')->getJson('/api/payments')->assertOk();
        $ids = collect($list->json('data'))->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
        $this->actingAs($this->customer, 'sanctum')->getJson("/api/payments/{$theirs->id}")->assertNotFound();
    }

    // ── Fixtures ────────────────────────────────────────────────────────────

    private function pendingReservationPayment(string $type, ?string $gatewayId): array
    {
        $reservation = $this->reservation($this->unite());
        $payment = Payment::create([
            'user_id' => $this->customer->id, 'payment_type' => $type, 'amount' => 300,
            'status' => 'pending', 'reservation_id' => $reservation->id, 'payment_id' => $gatewayId,
        ]);

        return [$payment, $reservation];
    }

    private function mockTamara(string $status): void
    {
        $this->partialMock(TamaraPaymentService::class, function ($m) use ($status) {
            $m->shouldReceive('getOrderStatus')->andReturn(['status' => $status]);
            $m->shouldReceive('authorizeOrder')->andReturn(['status' => $status]);
            $m->shouldReceive('captureOrder')->andReturn(['success' => true]);
        });
    }

    private function reservation(Unite $unite, array $extra = []): UniteReservation
    {
        return UniteReservation::create(array_merge([
            'unite_id' => $unite->id, 'user_id' => $this->customer->id,
            'reservation_date' => now()->addDays(3)->toDateString(), 'period_type' => 'morning',
            'from_time' => '09:00', 'to_time' => '14:00', 'price' => 300, 'status' => 'pending',
        ], $extra));
    }

    private function unite(): Unite
    {
        $dept = Department::create([
            'user_id' => $this->user('provider')->id, 'name' => 'Dept '.uniqid(), 'type' => 'lounge',
            'location' => 'Riyadh', 'latitude' => '24.7', 'longitude' => '46.7', 'status' => 'active',
        ]);

        return Unite::create(['department_id' => $dept->id, 'name' => 'Unite '.uniqid(), 'type' => 'lounge', 'status' => 'active']);
    }

    private function user(string $type): User
    {
        return User::create([
            'name' => ucfirst($type), 'email' => $type.uniqid().'@test.com', 'phone' => '05'.random_int(10000000, 99999999),
            'password' => bcrypt('password1'), 'status' => 'active', 'type' => $type,
        ]);
    }
}
