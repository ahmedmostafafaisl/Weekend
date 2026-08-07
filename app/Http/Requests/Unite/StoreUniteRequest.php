<?php

namespace App\Http\Requests\Unite;

use Illuminate\Foundation\Http\FormRequest;

class StoreUniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'department_id' => 'required|exists:departments,id',
            'type' => 'required|in:stadium,hall,lounge,camp',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'location_name' => 'nullable|string',
            'city' => 'nullable|string|in:'.implode(',', array_column(config('saudi_cities', []), 'key')),
            'families_and_singles' => ['nullable', 'in:families,singles,both'],
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'reservation_deposit' => 'boolean',
            'reservation_deposit_type' => 'nullable|in:amount,percentage',
            'reservation_deposit_amount' => 'nullable|numeric',
            'insurance' => 'boolean',
            'insurance_amount' => 'nullable|numeric',
            'refund_policy' => 'nullable|in:free,flexible,moderate,strict',
            'insurance_policy_id' => 'nullable|exists:insurance_policies,id',
            'additional_terms' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'add_to_story' => 'nullable|boolean',

            'images' => 'array',
            'images.*.image' => 'file|mimes:jpeg,png,jpg,gif,webp|max:2048',

            // BUG FIX: UniteRepository::update() deletes any existing image
            // whose ID isn't in keep_image_ids — but that field was never
            // validated here, so $request->validated() silently dropped it
            // from every request regardless of what the client sent. That
            // made the deletion branch's precondition ($keepIds !== null)
            // permanently false, so old images were never removed — only
            // ever added to. This validates it through so deletion works.
            'keep_image_ids' => 'nullable|array',
            'keep_image_ids.*' => 'integer',

            'features' => 'nullable|array',
            'features.*.name' => 'required|string',
            'features.*.description' => 'nullable|string',
            'features.*.status' => 'nullable|in:active,inactive',

            'offers' => 'nullable|array',
            'offers.*.start' => 'required|date',
            'offers.*.end' => 'required|date|after_or_equal:offers.*.start',
            'offers.*.morning_price' => 'nullable|numeric',
            'offers.*.evening_price' => 'nullable|numeric',
            'offers.*.full_day_price' => 'nullable|numeric',
            'offers.*.status' => 'nullable|in:active,inactive',

            'reservations' => 'nullable|array',
            'reservations.*.user_id' => 'nullable|exists:users,id',
            'reservations.*.reservation_date' => 'required|date',
            'reservations.*.period_type' => 'required|in:morning,evening,full_day,custom',
            'reservations.*.from_time' => 'required|date_format:H:i,H:i:s',
            'reservations.*.to_time' => 'required|date_format:H:i,H:i:s',
            'reservations.*.price' => 'nullable|numeric|min:0',
            'reservations.*.status' => 'required|in:pending,confirmed,cancelled',

            'packages' => 'nullable|array',
            'packages.*.name' => 'required|string',
            'packages.*.men_capacity' => 'required|integer|min:0',
            'packages.*.women_capacity' => 'required|integer|min:0',
            'packages.*.price' => 'required|numeric|min:0',

            'new_features' => 'nullable|array',
            'new_features.*.title' => 'required|string',
            'new_features.*.description' => 'nullable|json',

            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];

        // slots by weekday
        $rules['slots'] = 'nullable|array|min:1';
        $rules['slots.*.day_of_week'] = 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday';
        $rules['slots.*.status'] = 'nullable|in:available,booked,unavailable';

        $type = $this->input('type');

        if ($type === 'stadium') {
            // Stadiums are hourly-only — full_start/full_end still define the
            // stadium's operating window (the hours within which an hourly
            // booking can be made), not a whole-day reservation type.
            $rules['slots.*.full_start'] = 'required|date_format:H:i';
            $rules['slots.*.full_end'] = 'required|date_format:H:i|after:slots.*.full_start';
        } elseif ($type === 'hall') {
            // BUG FIX: halls are full-day only — morning/evening are no
            // longer accepted at all, matching the reservation flow which
            // should only ever offer 'full_day' for this type.
            $rules['slots.*.full_start'] = 'required|date_format:H:i';
            $rules['slots.*.full_end'] = 'required|date_format:H:i|after:slots.*.full_start';
        } else {
            // lounge, camp — unchanged, still support all 3 periods.
            $rules['slots.*.morning_start'] = 'required|date_format:H:i';
            $rules['slots.*.morning_end'] = 'required|date_format:H:i|after:slots.*.morning_start';
            $rules['slots.*.evening_start'] = 'required|date_format:H:i';
            $rules['slots.*.evening_end'] = 'required|date_format:H:i|after:slots.*.evening_start';
            $rules['slots.*.full_start'] = 'required|date_format:H:i';
            $rules['slots.*.full_end'] = 'required|date_format:H:i|after:slots.*.full_start';
        }

        if ($type === 'stadium') {
            // BUG FIX: the flat 'price' field used to be required, alongside
            // OPTIONAL hourly fields — meaning a stadium could be saved with
            // no hourly pricing at all despite hourly being the only booking
            // mode this venue type should support. price is now optional
            // (kept for backward compatibility / display only), while
            // day_hour_price ("morning hour price") and night_hour_price
            // ("evening hour price") are the required, primary pricing
            // fields.
            $rules['prices'] = 'nullable|array|min:1';
            $rules['prices.*.day'] = 'required|in:thursday,friday,saturday,week_day';
            $rules['prices.*.price'] = 'nullable|numeric|min:0';
            $rules['prices.*.hourly_enabled'] = 'nullable|boolean';
            $rules['prices.*.day_hour_price'] = 'required|numeric|min:0';
            $rules['prices.*.night_hour_price'] = 'required|numeric|min:0';
            $rules['prices.*.day_start'] = 'nullable|date_format:H:i,H:i:s';
            $rules['prices.*.day_end'] = 'nullable|date_format:H:i,H:i:s';
            $rules['prices.*.min_booking_minutes'] = 'nullable|integer|min:15|max:1440';
        } elseif ($type === 'hall') {
            // BUG FIX: morning_price/evening_price removed entirely for
            // halls — full_price is the only required rate, matching the
            // full-day-only booking restriction.
            $rules['prices'] = 'nullable|array|min:1';
            $rules['prices.*.day'] = 'required|in:thursday,friday,saturday,week_day';
            $rules['prices.*.full_price'] = 'required|numeric|min:0';
            $rules['prices.*.hourly_enabled'] = 'nullable|boolean';
            $rules['prices.*.day_hour_price'] = 'nullable|numeric|min:0';
            $rules['prices.*.night_hour_price'] = 'nullable|numeric|min:0';
            $rules['prices.*.day_start'] = 'nullable|date_format:H:i,H:i:s';
            $rules['prices.*.day_end'] = 'nullable|date_format:H:i,H:i:s';
            $rules['prices.*.min_booking_minutes'] = 'nullable|integer|min:15|max:1440';
        } else {
            // lounge, camp — unchanged.
            $rules['prices'] = 'nullable|array|min:1';
            $rules['prices.*.day'] = 'required|in:thursday,friday,saturday,week_day';
            $rules['prices.*.morning_price'] = 'required|numeric|min:0';
            $rules['prices.*.evening_price'] = 'required|numeric|min:0';
            $rules['prices.*.full_price'] = 'required|numeric|min:0';
            $rules['prices.*.hourly_enabled'] = 'nullable|boolean';
            $rules['prices.*.day_hour_price'] = 'nullable|numeric|min:0';
            $rules['prices.*.night_hour_price'] = 'nullable|numeric|min:0';
            $rules['prices.*.day_start'] = 'nullable|date_format:H:i,H:i:s';
            $rules['prices.*.day_end'] = 'nullable|date_format:H:i,H:i:s';
            $rules['prices.*.min_booking_minutes'] = 'nullable|integer|min:15|max:1440';
        }

        switch ($this->input('type')) {
            case 'stadium':
                $rules = array_merge($rules, $this->stadiumRules());
                break;
            case 'hall':
                $rules = array_merge($rules, $this->hallRules());
                break;
            case 'lounge':
                $rules = array_merge($rules, $this->loungeRules());
                break;
            case 'camp':
                $rules = array_merge($rules, $this->campRules());
                break;
        }

        // Package booking — available to every venue type as an optional
        // add-on (see Unite::package_booking_enabled), unlike everything
        // above this which still branches by type. 'days' accepts either
        // ["any"] or specific days like ["thursday","friday","saturday"].
        $rules['package_booking_enabled'] = ['nullable', 'boolean'];
        $rules['booking_packages'] = ['nullable', 'array'];
        $rules['booking_packages.*.name'] = ['nullable', 'string', 'max:255'];
        $rules['booking_packages.*.day'] = ['required_with:booking_packages', 'in:week_day,thursday,friday,saturday'];
        $rules['booking_packages.*.start_time'] = ['required_with:booking_packages', 'date_format:H:i'];
        $rules['booking_packages.*.end_time'] = ['required_with:booking_packages', 'date_format:H:i', 'after:booking_packages.*.start_time'];
        $rules['booking_packages.*.price'] = ['required_with:booking_packages', 'numeric', 'min:0'];
        $rules['booking_packages.*.status'] = ['nullable', 'in:active,inactive'];
        $rules['booking_packages.*.service_ids'] = ['nullable', 'array'];
        $rules['booking_packages.*.service_ids.*'] = ['integer', 'exists:services,id'];

        return $rules;
    }

    private function stadiumRules(): array
    {
        return [
            'stadium.customize_Category' => ['required', 'string'],
            'stadium.customize_Place' => ['required', 'string'],
            'stadium.width' => ['required', 'string'],
            'stadium.length' => ['required', 'string'],
            'stadium.amenities' => ['boolean'],
            'stadium.cafeteria' => ['boolean'],
        ];
    }

    private function hallRules(): array
    {
        return [
            'hall.max_chairs' => ['nullable', 'integer'],
            'hall.max_tables' => ['nullable', 'integer'],

            'hall.all_women_count' => ['nullable', 'integer'],
            'hall.all_men_count' => ['nullable', 'integer'],

            'hall.max_capacity' => ['nullable', 'integer'],
            'hall.women_tables_count' => ['nullable', 'integer'],
            'hall.women_chairs_count' => ['nullable', 'integer'],
            'hall.women_seating_details' => ['nullable', 'string'],
            'hall.women_buffet' => ['boolean'],
            'hall.women_buffet_details' => ['nullable', 'string'],
            'hall.men_tables_count' => ['nullable', 'integer'],
            'hall.men_chairs_count' => ['nullable', 'integer'],
            'hall.men_seating_details' => ['nullable', 'string'],
            'hall.men_buffet' => ['boolean'],
            'hall.men_buffet_details' => ['nullable', 'string'],
            'hall.women_seating' => ['boolean'],
            'hall.kusha' => ['boolean'],
            'hall.men_seating_available' => ['boolean'],
            'hall.men_seating_capacity' => ['nullable', 'integer'],
            'hall.women_seating_capacity' => ['nullable', 'integer'],
            'hall.buffet' => ['boolean'],
            'hall.buffet_details' => ['nullable', 'string'],
            'hall.morning_start_time' => ['nullable', 'date_format:H:i'],
            'hall.morning_end_time' => ['nullable', 'date_format:H:i'],
            'hall.evening_start_time' => ['nullable', 'date_format:H:i'],
            'hall.evening_end_time' => ['nullable', 'date_format:H:i'],
            'hall.full_day_start_time' => ['nullable', 'date_format:H:i'],
            'hall.full_day_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    private function loungeRules(): array
    {
        return [
            'lounge.area' => ['required', 'numeric'],
            'lounge.customize_Place' => ['required', 'string'],
            'lounge.bedroom' => ['boolean'],
            'lounge.bedroom_number' => ['nullable', 'integer'],
            'lounge.single_bed' => ['nullable', 'integer'],
            'lounge.big_bed' => ['nullable', 'integer'],
            'lounge.bathroom' => ['boolean'],
            'lounge.bathroom_number' => ['nullable', 'integer'],
            'lounge.kitchen' => ['boolean'],
            'lounge.pool' => ['boolean'],
            'lounge.council' => ['boolean'],
            'lounge.council_number' => ['nullable', 'integer'],
            'lounge.council_type' => ['nullable', 'string'],
            'lounge.morning_start_time' => ['nullable', 'date_format:H:i'],
            'lounge.morning_end_time' => ['nullable', 'date_format:H:i'],
            'lounge.evening_start_time' => ['nullable', 'date_format:H:i'],
            'lounge.evening_end_time' => ['nullable', 'date_format:H:i'],
            'lounge.full_day_start_time' => ['nullable', 'date_format:H:i'],
            'lounge.full_day_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    private function campRules(): array
    {
        return [
            'camp.width' => ['nullable', 'string'],
            'camp.length' => ['nullable', 'string'],
            'camp.seating_capacity' => ['nullable', 'integer'],
            'camp.television' => ['boolean'],
            'camp.fireplace' => ['boolean'],
            'camp.bathroom' => ['boolean'],
            'camp.bathroom_number' => ['nullable', 'integer'],
            'camp.morning_start_time' => ['nullable', 'date_format:H:i'],
            'camp.morning_end_time' => ['nullable', 'date_format:H:i'],
            'camp.evening_start_time' => ['nullable', 'date_format:H:i'],
            'camp.evening_end_time' => ['nullable', 'date_format:H:i'],
            'camp.full_day_start_time' => ['nullable', 'date_format:H:i'],
            'camp.full_day_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        $hall = $data['hall'] ?? [];

        // Existing: map legacy field names
        if (! array_key_exists('max_tables', $hall) && array_key_exists('all_men_count', $hall)) {
            $hall['max_tables'] = $hall['all_men_count'];
        }
        if (! array_key_exists('max_chairs', $hall) && array_key_exists('all_women_count', $hall)) {
            $hall['max_chairs'] = $hall['all_women_count'];
        }

        // Strip :ss from hall detail time fields (browser sends H:i:s, validation expects H:i)
        foreach (['morning_start_time', 'morning_end_time', 'evening_start_time',
            'evening_end_time', 'full_day_start_time', 'full_day_end_time'] as $f) {
            if (isset($hall[$f]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $hall[$f])) {
                $hall[$f] = substr($hall[$f], 0, 5);
            }
        }

        // Strip :ss from slot times
        $slots = $data['slots'] ?? [];
        foreach ($slots as &$slot) {
            foreach (['morning_start', 'morning_end', 'evening_start',
                'evening_end', 'full_start', 'full_end'] as $f) {
                if (isset($slot[$f]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $slot[$f])) {
                    $slot[$f] = substr($slot[$f], 0, 5);
                }
            }
        }
        unset($slot);

        // Strip :ss from reservation times
        $reservations = $data['reservations'] ?? [];
        foreach ($reservations as &$res) {
            foreach (['from_time', 'to_time'] as $f) {
                if (isset($res[$f]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $res[$f])) {
                    $res[$f] = substr($res[$f], 0, 5);
                }
            }
        }
        unset($res);

        $this->merge([
            'hall' => $hall,
            'slots' => $slots,
            'reservations' => $reservations,
        ]);
    }
}
