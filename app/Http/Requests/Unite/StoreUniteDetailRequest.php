<?php

namespace App\Http\Requests\Unite;

use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniteDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Unite|null $unite */
        $unite = $this->route('unite');

        if (! $unite) {
            return [];
        }

        return match ($unite->type) {
            'stadium' => $this->stadiumRules(),
            'hall' => $this->hallRules(),
            'lounge' => $this->loungeRules(),
            'camp' => $this->campRules(),
            default => [],
        };
    }

    protected function stadiumRules(): array
    {
        return [
            'customize_Category' => ['required', 'string'],
            'customize_Place' => ['required', 'string'],
            'width' => ['required', 'string'],
            'length' => ['required', 'string'],
            'amenities' => ['nullable', 'boolean'],
            'cafeteria' => ['nullable', 'boolean'],
        ];
    }

    protected function hallRules(): array
    {
        return [
            'max_chairs' => ['nullable', 'integer'],
            'max_tables' => ['nullable', 'integer'],
            'max_capacity' => ['nullable', 'integer'],
            'women_seating' => ['nullable', 'boolean'],
            'kusha' => ['nullable', 'boolean'],
            'women_tables_count' => ['nullable', 'integer'],
            'women_chairs_count' => ['nullable', 'integer'],
            'women_seating_capacity' => ['nullable', 'integer'],
            'women_seating_details' => ['nullable', 'string'],
            'women_buffet' => ['nullable', 'boolean'],
            'women_buffet_details' => ['nullable', 'string'],
            'men_seating_available' => ['nullable', 'boolean'],
            'men_tables_count' => ['nullable', 'integer'],
            'men_chairs_count' => ['nullable', 'integer'],
            'men_seating_capacity' => ['nullable', 'integer'],
            'men_seating_details' => ['nullable', 'string'],
            'men_buffet' => ['nullable', 'boolean'],
            'men_buffet_details' => ['nullable', 'string'],
            'buffet' => ['nullable', 'boolean'],
            'buffet_details' => ['nullable', 'string'],
            'morning_start_time' => ['nullable', 'date_format:H:i'],
            'morning_end_time' => ['nullable', 'date_format:H:i'],
            'evening_start_time' => ['nullable', 'date_format:H:i'],
            'evening_end_time' => ['nullable', 'date_format:H:i'],
            'full_day_start_time' => ['nullable', 'date_format:H:i'],
            'full_day_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function loungeRules(): array
    {
        return [
            'area' => ['required', 'numeric'],
            'customize_Place' => ['required', 'string'],
            'bedroom' => ['nullable', 'boolean'],
            'bedroom_number' => ['nullable', 'integer'],
            'single_bed' => ['nullable', 'integer'],
            'big_bed' => ['nullable', 'integer'],
            'bathroom' => ['nullable', 'boolean'],
            'bathroom_number' => ['nullable', 'integer'],
            'kitchen' => ['nullable', 'boolean'],
            'pool' => ['nullable', 'boolean'],
            'council' => ['nullable', 'boolean'],
            'council_number' => ['nullable', 'integer'],
            'council_type' => ['nullable', 'string'],
            'morning_start_time' => ['nullable', 'date_format:H:i'],
            'morning_end_time' => ['nullable', 'date_format:H:i'],
            'evening_start_time' => ['nullable', 'date_format:H:i'],
            'evening_end_time' => ['nullable', 'date_format:H:i'],
            'full_day_start_time' => ['nullable', 'date_format:H:i'],
            'full_day_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function campRules(): array
    {
        return [
            'width' => ['nullable', 'string'],
            'length' => ['nullable', 'string'],
            'seating_capacity' => ['nullable', 'integer'],
            'television' => ['nullable', 'boolean'],
            'fireplace' => ['nullable', 'boolean'],
            'bathroom' => ['nullable', 'boolean'],
            'bathroom_number' => ['nullable', 'integer'],
            'morning_start_time' => ['nullable', 'date_format:H:i'],
            'morning_end_time' => ['nullable', 'date_format:H:i'],
            'evening_start_time' => ['nullable', 'date_format:H:i'],
            'evening_end_time' => ['nullable', 'date_format:H:i'],
            'full_day_start_time' => ['nullable', 'date_format:H:i'],
            'full_day_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
