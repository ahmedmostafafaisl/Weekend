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
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'type' => ['required', Rule::in(['customer', 'provider'])],
            'status' => ['sometimes', Rule::in(['active', 'deactive'])],
            'provider_type' => ['nullable', Rule::in(['individual', 'organization'])],
            'nation' => ['required', Rule::in(['saudi', 'resident'])],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'id_number' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image'],
            'front_identity' => ['nullable', 'image'],
            'back_identity' => ['nullable', 'image'],
            'sak_image' => ['nullable', 'image'],
            'commercial_name' => ['nullable', 'string'],
            'commercial_register_number' => ['nullable', 'string'],
            'organization_name' => ['nullable', 'string'],
            'commercial_register_image' => ['nullable', 'image'],
            'ownership' => ['nullable', 'in:0,1,2'],
            'delegation' => ['nullable', 'string'],
        ];
    }
}
