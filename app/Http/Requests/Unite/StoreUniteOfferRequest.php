<?php

namespace App\Http\Requests\Unite;

use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniteOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'status' => ['required', 'in:active,inactive'],
        ];

        if ($this->resolveUniteType() === 'stadium') {
            // Stadiums have no morning/evening/full-day offer concept —
            // only an hourly rate, matching the same split used on the
            // venue's regular pricing (unite_prices.day_hour_price/
            // night_hour_price).
            $rules['day_hour_price'] = ['required', 'numeric', 'min:0'];
            $rules['night_hour_price'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['morning_price'] = ['nullable', 'numeric', 'min:0'];
            $rules['evening_price'] = ['nullable', 'numeric', 'min:0'];
            $rules['full_day_price'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    /**
     * The live route (/unites/{unite}/offers, Admin\Unite\UniteOfferController)
     * binds Unite via the URL itself — {unite} is resolved by Laravel's
     * route-model binding before this FormRequest ever runs, so
     * $this->route('unite') already returns the actual Unite instance, not
     * just a raw ID. This is the primary path. The type/unite_id form-field
     * checks below exist only because this same request class is also
     * referenced by a second, unreachable controller (no live route points
     * to it) that submits the unite that way instead — kept as a fallback
     * for correctness rather than assuming that code stays dead forever.
     */
    private function resolveUniteType(): ?string
    {
        $routeUnite = $this->route('unite');
        if ($routeUnite instanceof Unite) {
            return $routeUnite->type;
        }

        $type = $this->input('type');
        if ($type) {
            return $type;
        }

        $uniteId = $this->input('unite_id');
        if (is_array($uniteId)) {
            $uniteId = $uniteId[0] ?? null;
        }

        return $uniteId ? Unite::find($uniteId)?->type : null;
    }
}
