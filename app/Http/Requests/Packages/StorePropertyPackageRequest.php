<?php

namespace App\Http\Requests\Packages;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyPackageRequest extends FormRequest
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
            'type' => 'required|in:time,percentage,count',
            'duration' => 'nullable|integer|required_if:type,time',
            'percentage' => 'nullable|integer|required_if:type,percentage',
            'count' => 'nullable|integer|required_if:type,count',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:4096',
            'status' => 'required|in:active,inactive',
        ];
    }
}
