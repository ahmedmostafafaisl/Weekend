<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'payment_method' => ['required', 'in:geidea,tamara,maysar,tabby'],
            'type' => 'required|in:property,ad',
            'package_id' => 'required|integer',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
