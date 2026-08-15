<?php

namespace App\Http\Requests\Unite;

use App\Http\Requests\Unite\Concerns\AuthorizesUniteSubResource;
use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitePriceRequest extends FormRequest
{
    use AuthorizesUniteSubResource;

    public function authorize(): bool
    {
        $permission = match (true) {
            $this->isMethod('post') => 'unites.create',
            $this->isMethod('put'), $this->isMethod('patch') => 'unites.update',
            default => 'unites.view',
        };

        return $this->userMayAccessUniteSubResource($this->user(), $this->route('unite'), $permission);
    }

    public function rules(): array
    {
        /** @var Unite|null $unite */
        $unite = $this->route('unite');
        $currentPriceId = $this->route('price');

        $rules = [
            'day' => [
                'required',
                'in:thursday,friday,saturday,week_day',
                Rule::unique('unite_prices', 'day')
                    ->where('unite_id', $unite?->id)
                    ->ignore($currentPriceId, 'id'),
            ],
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
            'day.unique' => __('lang.price_already_exists_for_day'),
            'day_hour_price.required_if' => __('lang.day_hour_price_required_if_hourly'),
            'day_end.after' => __('lang.day_end_after_start'),
        ];
    }
}
