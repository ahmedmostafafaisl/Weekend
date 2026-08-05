<?php

namespace App\Http\Requests\InsurancePolicy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
