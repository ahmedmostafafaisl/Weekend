<?php

namespace App\Http\Requests\Unite;

use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniteSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Unite|null $unite */
        $unite = $this->route('unite');

        $rules = [
            'day_of_week' => ['required', 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'],
            'status' => ['required', 'in:available,booked,unavailable'],
        ];

        if ($unite && $unite->type === 'stadium') {
            $rules['full_start'] = ['required', 'date_format:H:i'];
            $rules['full_end'] = ['required', 'date_format:H:i', 'after:full_start'];
        } else {
            $rules['morning_start'] = ['nullable', 'date_format:H:i'];
            $rules['morning_end'] = ['nullable', 'date_format:H:i', 'after:morning_start'];
            $rules['evening_start'] = ['nullable', 'date_format:H:i'];
            $rules['evening_end'] = ['nullable', 'date_format:H:i', 'after:evening_start'];
            $rules['full_start'] = ['nullable', 'date_format:H:i'];
            $rules['full_end'] = ['nullable', 'date_format:H:i', 'after:full_start'];
        }

        return $rules;
    }
}
