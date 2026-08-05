<?php

namespace App\Http\Requests\Packages;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:count,duration',
            'count' => 'nullable|integer|required_if:type,count',
            'duration' => 'nullable|integer|required_if:type,duration',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive',
        ];
    }
}
