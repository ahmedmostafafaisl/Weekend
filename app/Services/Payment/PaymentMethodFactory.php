<?php

namespace App\Services\Payment;

use App\Repositories\Interfaces\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentMethodFactory
{
    public static function make(string $method): PaymentGatewayInterface
    {
        return match (strtolower($method)) {
            'geidea' => app(GeideaPaymentService::class),
            'tabby', 'tap', 'tappy' => app(TabbyPaymentService::class),
            'tamara' => app(TamaraPaymentService::class),
            'maysar' => app(MoyasarPaymentService::class),
            default => throw new InvalidArgumentException("Unknown payment method: {$method}"),
        };
    }

    public static function available(): array
    {
        return [
            ['key' => 'geidea', 'name' => 'Credit / Debit Card (Geidea)', 'type' => 'card', 'logo' => 'geidea'],
            ['key' => 'tappy',  'name' => 'Tap Payments',                  'type' => 'card', 'logo' => 'tap'],
            ['key' => 'tamara', 'name' => 'Tamara — Pay Later',            'type' => 'bnpl', 'logo' => 'tamara'],
            ['key' => 'maysar', 'name' => 'Maysar — Islamic BNPL',         'type' => 'bnpl', 'logo' => 'maysar'],
        ];
    }
}
