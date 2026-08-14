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
            'day_of_week' => ['required', 'in:week_day,thursday,friday,saturday'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Only enforced on creation — the web form always resends every
            // time field on both create and edit, but this same route also
            // serves API/mobile consumers who may legitimately send a
            // partial update (e.g. just {"status": "unavailable"}) without
            // resending time fields at all.
            if (! $this->isMethod('post')) {
                return;
            }

            /** @var Unite|null $unite */
            $unite = $this->route('unite');

            if ($unite && $unite->type === 'stadium') {
                return;
            }

            $hasMorning = $this->filled('morning_start') && $this->filled('morning_end');
            $hasEvening = $this->filled('evening_start') && $this->filled('evening_end');
            $hasFullDay = $this->filled('full_start') && $this->filled('full_end');

            if (! $hasMorning && ! $hasEvening && ! $hasFullDay) {
                $validator->errors()->add('full_start', __('lang.slot_needs_at_least_one_window'));
            }
        });
    }
}
