<?php

namespace App\Http\Requests\Auth\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'numeric', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'type' => ['required', Rule::in(['customer', 'provider'])],
            'provider_type' => ['nullable', Rule::in(['individual', 'organization'])],
            'nation' => ['required', Rule::in(['saudi', 'resident'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'id_number' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'front_identity' => ['nullable', 'image', 'max:4096'],
            'back_identity' => ['nullable', 'image', 'max:4096'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'commercial_register_number' => ['nullable', 'string', 'max:50'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'commercial_register_image' => ['nullable', 'image', 'max:4096'],
            'delegation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
