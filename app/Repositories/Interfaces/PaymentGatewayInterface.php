<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function sendPayment(Request|array $input): array;

    public function callBack(Request $request): bool;

    public function getPaymentDetails(string $payment_id): array;

    /**
     * Manually confirm a payment by querying Geidea directly with their order ID.
     * Use when the webhook callback was unreachable (e.g. localhost in development).
     */
    public function confirmByOrderId(string $geideaOrderId): array;

    /**
     * Issue a full or partial refund for a paid Geidea order.
     *
     * @param  string  $geideaOrderId  The payment_id stored on the Payment model
     * @param  float|null  $amount  Null = full refund; float = partial refund
     * @param  string  $reason  Short reason string sent to Geidea
     * @return array{success: bool, message?: string, data?: array}
     */
    public function refund(string $geideaOrderId, ?float $amount = null, string $reason = 'Customer cancellation'): array;
}
