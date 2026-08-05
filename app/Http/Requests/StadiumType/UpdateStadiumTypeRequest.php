<?php

namespace App\Http\Requests\StadiumType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStadiumTypeRequest extends FormRequest
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
            'image' => 'nullable|image',
        ];
    }
}
