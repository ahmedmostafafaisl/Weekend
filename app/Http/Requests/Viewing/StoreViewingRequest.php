<?php

namespace App\Http\Requests\Viewing;

use Illuminate\Foundation\Http\FormRequest;

class StoreViewingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unite_id' => ['required', 'integer', 'exists:unites,id'],
            'unite_viewing_time_id' => ['required', 'integer', 'exists:unite_viewing_times,id'],
            'viewing_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'phone' => ['nullable', 'string'],
            // Only actually required if the venue has a deposit enabled —
            // enforced in the repository, not here, since that depends on
            // the specific unite being booked, not a fixed rule.
            'payment_method' => ['nullable', 'string', 'in:geidea,tamara,tabby,maysar'],

            // Additional people attached to this same appointment, beyond
            // the booker themselves (who's implicit via the auth token,
            // not repeated in this array). Each must be a real, existing
            // registered user — confirmed explicitly, not just a name
            // typed in at booking time. The booker's own ID being
            // accidentally included here is tolerated, not rejected — the
            // repository dedupes rather than erroring over it.
            'attendee_user_ids' => ['nullable', 'array'],
            'attendee_user_ids.*' => ['distinct', 'integer', 'exists:users,id'],
        ];
    }
}
