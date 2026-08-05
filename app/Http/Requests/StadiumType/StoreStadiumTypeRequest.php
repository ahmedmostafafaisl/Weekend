<?php

namespace App\Http\Requests\StadiumType;

use Illuminate\Foundation\Http\FormRequest;

class StoreStadiumTypeRequest extends FormRequest
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
            'image' => 'nullable|image',
        ];
    }
}
