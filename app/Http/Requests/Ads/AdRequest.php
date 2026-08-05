<?php

namespace App\Http\Requests\Ads;

use Illuminate\Foundation\Http\FormRequest;

class AdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // type defaults to 'ad' for admin-created ads — not shown in form
            'type' => ['nullable', 'in:property,ad'],
            'thumbnail' => ['nullable', 'file', 'image', 'max:10240'],
            'media' => ['nullable', 'array'],
            'media.*' => ['file', 'max:51200'],  // each file up to 50MB (images/video)
            'is_active' => ['nullable', 'boolean'],
            'user_id' => $this->isApiRequest() ? 'nullable' : 'required|exists:users,id',
            'city' => ['nullable', 'string', 'max:100'],
            'target_audience' => ['required', 'in:men,women,both'],
            'target_user_type' => ['required', 'in:all,customers,providers'],
            'target_users' => ['nullable', 'array'],
            'target_users.*' => ['exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => __('lang.user_id_required_web'),
            'user_id.exists' => __('lang.user_id_invalid'),
        ];
    }

    protected function isApiRequest(): bool
    {
        return $this->expectsJson() || $this->is('api/*');
    }

    protected function prepareForValidation(): void
    {
        // Default type to 'ad' if not provided (admin form doesn't show this field)
        if (! $this->filled('type')) {
            $this->merge(['type' => 'ad']);
        }

        if ($this->isApiRequest() && auth()->check()) {
            $this->merge([
                'user_id' => auth()->id(),
            ]);
        }
    }
}
