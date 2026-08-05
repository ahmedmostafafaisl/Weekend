<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // إذا user_id مش موجود أو null، حط قيمة auth user id
        if (! $this->filled('user_id')) {
            $this->merge([
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
            ],
            'phone' => [
                'nullable',
                'string',
            ],
            'description' => 'nullable|string',
            'type' => 'nullable|in:stadium,hall,lounge,camp',
            'location' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'facebook' => 'nullable|string',
            'twitter' => 'nullable|string',
            'instagram' => 'nullable|string',
            'youtube' => 'nullable|string',
            'website' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'snapchat' => 'nullable|string',
            'tiktok' => 'nullable|string',
            'user_id' => $this->isApi() ? 'nullable|exists:users,id' : 'required|exists:users,id',
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
        ];
    }

    /**
     * Check if the request is coming from API.
     */
    public function isApi(): bool
    {
        return $this->expectsJson();
    }
}
