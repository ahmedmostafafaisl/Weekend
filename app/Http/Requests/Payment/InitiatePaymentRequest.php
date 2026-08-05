<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'description' => ['sometimes', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'reservation_id' => ['nullable', 'exists:unite_reservations,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],

            // Optional line-item detail (passed through to Geidea eInvoiceItems)
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'price' => ['sometimes', 'numeric', 'min:0'],

            // Customer info forwarded to Geidea
            'customer' => ['sometimes', 'array'],
            'customer.email' => ['sometimes', 'email'],
            'customer.phoneNumber' => ['sometimes', 'string'],
        ];
    }
}
