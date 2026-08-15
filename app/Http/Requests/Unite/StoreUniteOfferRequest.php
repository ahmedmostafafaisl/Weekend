<?php

namespace App\Http\Requests\Unite;

use App\Http\Requests\Unite\Concerns\AuthorizesUniteSubResource;
use App\Models\Unite;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniteOfferRequest extends FormRequest
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
