<?php

namespace App\Http\Requests\Department;

/**
 * Update variant of DepartmentRequest -- update any single field; send it
 * to change it, omit it to leave the existing value alone. Mirrors
 * App\Http\Requests\Unite\UpdateUniteRequest's exact approach: extend the
 * base request (inheriting its rules almost entirely unchanged, since
 * nearly everything there is already 'nullable') rather than duplicating
 * them, and only adjust the handful of fields that are genuinely
 * required on the base request but shouldn't be on a partial update.
 *
 * 'name' is the only field DepartmentRequest requires unconditionally --
 * made optional here so a client can update, say, just 'phone' without
 * being forced to resend the department's existing name too.
 *
 * Image handling: 'images' (new uploads to add) is unchanged from the
 * base request. 'deleted_image_ids' is new here -- delete only the
 * specific images listed by id, matching the same convention already
 * established for unites (see UniteRepository::update()), rather than
 * the base request/repository's all-or-nothing "any new image submitted
 * deletes every existing one first" behavior, which doesn't fit a
 * genuine partial update at all.
 */
class UpdateDepartmentRequest extends DepartmentRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'] = ['sometimes', 'string'];

        $rules['deleted_image_ids'] = ['nullable', 'array'];
        $rules['deleted_image_ids.*'] = ['integer'];

        return $rules;
    }
}
