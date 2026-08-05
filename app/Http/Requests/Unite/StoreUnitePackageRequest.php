<?php

namespace App\Http\Requests\Unite;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'men_capacity' => ['nullable', 'integer', 'min:0'],
            'women_capacity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
