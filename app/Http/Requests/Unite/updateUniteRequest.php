<?php

namespace App\Http\Requests\Unite;

/**
 * Update variant of StoreUniteRequest -- "update any single value on the
 * unite; send it to change it, omit it to leave the existing value alone."
 *
 * Reuses StoreUniteRequest's rules() almost entirely unchanged: nearly
 * every top-level field is already 'nullable' there (not 'required'), and
 * every nested section's *.field rules (offers.*.start, packages.*.price,
 * etc.) only ever apply to an array that was actually submitted in the
 * first place -- Laravel never evaluates offers.*.start at all if
 * 'offers' itself is absent from the request. So most of the "only
 * validate/update what's sent" behavior already exists in the parent
 * class; the only genuine required-at-the-top-level fields are
 * department_id, type, and status, made optional here specifically.
 *
 * Nested sections (slots, prices, offers, features, packages,
 * booking_packages, viewing_times, new_features) are all-or-nothing per
 * section, not partial within a section: send the section to fully
 * replace it, omit the section entirely to leave the existing rows
 * untouched. See UniteRepository::update() for where that omit-vs-empty
 * distinction is actually enforced.
 */
class UpdateUniteRequest extends StoreUniteRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['department_id', 'type', 'status'] as $field) {
            $rules[$field] = $this->makeSometimes($rules[$field]);
        }

        // Replace one specific existing image by its own id -- e.g.
        // replace_images[12] = <file> -- update-only, since create() has
        // no existing images to replace yet.
        $rules['replace_images'] = ['nullable', 'array'];
        $rules['replace_images.*'] = ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,webp', 'max:20048'];

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
