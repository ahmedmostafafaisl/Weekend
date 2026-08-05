<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->type === 'customer';
    }

    public function rules(): array
    {
        return [
            'unite_id' => ['required', 'exists:unites,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'period_type' => ['required', 'in:morning,evening,full_day,custom,hourly'],
            'from_time' => ['nullable', 'required_if:period_type,custom', 'required_if:period_type,hourly', 'date_format:H:i'],
            'to_time' => ['nullable', 'required_if:period_type,custom', 'required_if:period_type,hourly', 'date_format:H:i', 'after:from_time'],
            'phone' => ['nullable', 'string', 'max:20'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'in:geidea,tabby,tamara,maysar'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_time.required_if' => __('lang.from_time_required_custom'),
            'to_time.required_if' => __('lang.to_time_required_custom'),
        ];
    }
}
