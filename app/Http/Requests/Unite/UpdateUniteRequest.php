<?php

namespace App\Http\Requests\Unite;

class UpdateUniteRequest extends StoreUniteRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('type')) {
            $unite = $this->route('unite');
            if ($unite) {
                $this->merge(['type' => $unite->type]);
            }
        }

        parent::prepareForValidation();
    }

    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['department_id', 'type', 'status'] as $field) {
            $rules[$field] = $this->makeSometimes($rules[$field]);
        }

        // The type-specific detail sub-rules (stadium.customize_Category,
        // hall.max_chairs, lounge.area, etc.) are still all required in
        // the parent's rule set -- make every one of them 'sometimes'
        // too, not just the 3 top-level fields above, so a client isn't
        // forced to resend an entire type's detail block just because
        // 'type' itself was included.
        $type = $this->input('type');
        if ($type) {
            foreach ($rules as $key => $rule) {
                if (str_starts_with($key, "{$type}.")) {
                    $rules[$key] = $this->makeSometimes($rule);
                }
            }
        }

        return $rules;
    }

    /**
     * Prepends 'sometimes' to a rule regardless of whether it's currently
     * a pipe-delimited string or an array -- both forms are used
     * throughout the parent's rule set.
     */
    private function makeSometimes(string|array $rule): array
    {
        $parts = is_string($rule) ? explode('|', $rule) : $rule;

        return array_merge(['sometimes'], $parts);
    }
}
