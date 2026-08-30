<?php

namespace App\Http\Requests\Unite;

use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniteSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user instanceof \App\Models\Admin) {
            return true;
        }

        /** @var Unite|null $unite */
        $unite = $this->route('unite');

        if (! $user || ! $unite) {
            return false;
        }

        if ($user->type !== 'provider') {
            return false;
        }

        $department = $unite->relationLoaded('department')
            ? $unite->department
            : $unite->department()->first();

        return $department && $department->user_id === $user->id;
    }

    public function rules(): array
    {
        /** @var Unite|null $unite */
        $unite = $this->route('unite');

        $rules = [
            'day_of_week' => ['required', 'in:week_day,friday'],
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

        $rules['day_start'] = ['nullable', 'date_format:H:i'];
        $rules['day_end'] = ['nullable', 'date_format:H:i', 'after:day_start'];
        $rules['buffer_minutes'] = ['nullable', 'integer', 'min:0'];

        $rules['periods'] = ['nullable', 'array'];
        $rules['periods.*.start_time'] = ['required_with:periods', 'date_format:H:i'];
        $rules['periods.*.end_time'] = ['required_with:periods', 'date_format:H:i', 'after:periods.*.start_time'];
        $rules['periods.*.status'] = ['nullable', 'in:available,unavailable'];

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

        $validator->after(function ($validator) {
            $dayStart = $this->input('day_start');
            $dayEnd = $this->input('day_end');
            $hasOperatingWindow = $this->filled('day_start') && $this->filled('day_end');

            // A custom period outside the daily operating window, or a
            // morning/evening period outside it — only checked when the
            // operating window is actually part of THIS request, since a
            // partial update that doesn't resend day_start/day_end has no
            // window here to validate against.
            if ($hasOperatingWindow) {
                foreach (['morning_start' => 'morning_end', 'evening_start' => 'evening_end'] as $startField => $endField) {
                    if ($this->filled($startField) && $this->filled($endField)) {
                        $start = $this->input($startField);
                        $end = $this->input($endField);
                        if ($start < $dayStart || $end > $dayEnd) {
                            $validator->errors()->add($startField, __('lang.period_outside_operating_hours'));
                        }
                    }
                }
            }

            $periods = $this->input('periods', []);

            if (is_array($periods)) {
                foreach ($periods as $index => $period) {
                    if (empty($period['start_time']) || empty($period['end_time'])) {
                        continue;
                    }

                    if ($hasOperatingWindow && ($period['start_time'] < $dayStart || $period['end_time'] > $dayEnd)) {
                        $validator->errors()->add("periods.{$index}.start_time", __('lang.period_outside_operating_hours'));
                    }
                }

                // Overlapping custom periods — every pair, not just
                // adjacent ones in submission order, since an admin could
                // submit them in any order.
                foreach ($periods as $i => $periodA) {
                    if (empty($periodA['start_time']) || empty($periodA['end_time'])) {
                        continue;
                    }

                    foreach ($periods as $j => $periodB) {
                        if ($j <= $i || empty($periodB['start_time']) || empty($periodB['end_time'])) {
                            continue;
                        }

                        $overlaps = $periodA['start_time'] < $periodB['end_time']
                            && $periodB['start_time'] < $periodA['end_time'];

                        if ($overlaps) {
                            $validator->errors()->add("periods.{$j}.start_time", __('lang.periods_overlap'));
                        }
                    }
                }
            }
        });
    }
}
