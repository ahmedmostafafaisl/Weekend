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
            // Only meaningful for period_type=full_day — turns a
            // single-day booking into a multi-day range when provided and
            // later than reservation_date. Omitted (or equal to
            // reservation_date) means the existing single-day behavior,
            // unchanged, for every other period type.
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:reservation_date'],
            'period_type' => ['required', 'in:morning,evening,full_day,custom,hourly,package'],
            'from_time' => ['nullable', 'required_if:period_type,custom', 'required_if:period_type,hourly', 'date_format:H:i'],
            'to_time' => ['nullable', 'required_if:period_type,custom', 'required_if:period_type,hourly', 'date_format:H:i'],
            'booking_package_id' => ['nullable', 'required_if:period_type,package', 'integer', 'exists:unite_booking_packages,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'in:geidea,tabby,tamara,maysar'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('end_date')
                && $this->end_date !== $this->reservation_date
                && $this->period_type !== 'full_day') {
                $validator->errors()->add('end_date', __('lang.end_date_only_for_full_day'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'from_time.required_if' => __('lang.from_time_required_custom'),
            'to_time.required_if' => __('lang.to_time_required_custom'),
        ];
    }
}
