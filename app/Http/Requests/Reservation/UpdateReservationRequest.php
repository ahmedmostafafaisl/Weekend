<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'period_type' => ['sometimes', 'in:morning,evening,full_day,custom'],
            'from_time' => ['nullable', 'date_format:H:i'],
            'to_time' => ['nullable', 'date_format:H:i', 'after:from_time'],
            'status' => ['nullable', 'in:pending,confirmed,cancelled'],
        ];
    }
}
