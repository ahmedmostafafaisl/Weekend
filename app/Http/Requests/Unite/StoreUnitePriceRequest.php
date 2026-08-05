<?php

namespace App\Http\Requests\Unite;

use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitePriceRequest extends FormRequest
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
            'day' => ['required', 'in:thursday,friday,saturday,week_day'],
        ];

        if ($unite && $unite->type === 'stadium') {
            $rules['price'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['morning_price'] = ['required', 'numeric', 'min:0'];
            $rules['evening_price'] = ['required', 'numeric', 'min:0'];
            $rules['full_price'] = ['required', 'numeric', 'min:0'];
        }

        // ── Hourly pricing (optional for all venue types) ──────────────────────
        $rules['hourly_enabled'] = ['boolean'];
        $rules['day_hour_price'] = ['nullable', 'numeric', 'min:0',
            'required_if:hourly_enabled,1',
            'required_if:hourly_enabled,true'];
        $rules['night_hour_price'] = ['nullable', 'numeric', 'min:0'];
        $rules['day_start'] = ['nullable', 'date_format:H:i'];
        $rules['day_end'] = ['nullable', 'date_format:H:i', 'after:day_start'];
        $rules['min_booking_minutes'] = ['nullable', 'integer', 'min:15', 'max:1440'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'day_hour_price.required_if' => __('lang.day_hour_price_required_if_hourly'),
            'day_end.after' => __('lang.day_end_after_start'),
        ];
    }
}
