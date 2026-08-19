@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Add Unite')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('unites.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <div>
        <h4 class="fw-bold mb-0">{{ __('lang.add_unite') }}</h4>
        <div class="text-muted small">{{ __('lang.create_unite_subtitle') }}</div>
    </div>
</div>

<form action="{{ route('unites.store') }}" method="POST" enctype="multipart/form-data" id="uniteForm">
    @csrf

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ── ROW 1: core + images ── --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card card-soft shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.basic_information') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('lang.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name') }}">
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('lang.status') }} <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="active"   {{ old('status','active') === 'active'   ? 'selected' : '' }}>{{ __('lang.active') }}</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.unite_type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="unite-type" required>
                                <option value="">{{ __('lang.select_type') }}</option>
                                @foreach(['stadium','hall','lounge','camp'] as $t)
                                    <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ __('lang.'.$t) }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.department') }} <span class="text-danger">*</span></label>
                            <select class="form-select" name="department_id" id="unite-department" required>
                                <option value="">{{ __('lang.select_department') }}</option>
                                @foreach($departments as $dep)
                                    @if(! old('type') || $dep->type === old('type'))
                                        <option value="{{ $dep->id }}" {{ old('department_id') == $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if(old('type'))
                                <div class="form-text small">{{ __('lang.showing_departments_of_type') }}</div>
                            @endif
                            @error('department_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.description') }}</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.city') }}</label>
                            <select class="form-select" name="city">
                                <option value="">—</option>
                                @foreach(config('saudi_cities', []) as $c)
                                    <option value="{{ $c['key'] }}" {{ old('city') === $c['key'] ? 'selected' : '' }}>
                                        {{ app()->getLocale() === 'ar' ? $c['label_ar'] : $c['label_en'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.location_name') }}</label>
                            <input class="form-control" name="location_name" value="{{ old('location_name') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('lang.latitude') }}</label>
                            <input class="form-control" name="latitude" type="number" step="any" value="{{ old('latitude') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('lang.longitude') }}</label>
                            <input class="form-control" name="longitude" type="number" step="any" value="{{ old('longitude') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.families_singles') }}</label>
                            <select class="form-select" name="families_and_singles">
                                <option value="">—</option>
                                @foreach(['families'=>__('lang.families'),'singles'=>__('lang.singles'),'both'=>__('lang.both')] as $v=>$vLabel)
                                    <option value="{{ $v }}" {{ old('families_and_singles') === $v ? 'selected' : '' }}>{{ $vLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.refund_policy') }}</label>
                            <select class="form-select" name="refund_policy">
                                <option value="">—</option>
                                @foreach(['free'=>__('lang.free'),'flexible'=>__('lang.flexible'),'moderate'=>__('lang.moderate'),'strict'=>__('lang.strict')] as $v=>$vLabel)
                                    <option value="{{ $v }}" {{ old('refund_policy') === $v ? 'selected' : '' }}>{{ $vLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.additional_terms') }}</label>
                            <textarea class="form-control" name="additional_terms" rows="2">{{ old('additional_terms') }}</textarea>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-soft shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.images') }}</h6>
                    <input class="form-control" type="file" name="images[]" multiple accept="image/*">
                    <div class="text-muted small mt-1">{{ __('lang.multiple_images_allowed') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Deposit & Insurance ── --}}
    @include('dashboard.web.unites._deposit_insurance', [
        'unite'             => null,
        'insurancePolicies' => $insurancePolicies ?? collect(),
        'readonly'          => false,
    ])

    {{-- ── Type-specific Details ── --}}
    <div id="typeDetails">
        @include('dashboard.web.unites._detail_fields', ['unite' => null, 'type' => old('type','')])
    </div>

    {{-- ── Features ── --}}
    <div class="card card-soft shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">{{ __('lang.features') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('features-body','feature-tpl')">{{ __('lang.add_feature') }}</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('lang.name') }}</th><th>{{ __('lang.description') }}</th><th>{{ __('lang.status') }}</th><th></th></tr>
                    </thead>
                    <tbody id="features-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── New Features (UniteNewFeature) — a separate, simpler highlights
         list from the 'features' card above; see UniteNewFeature's own doc
         comment for why both remain in active use side by side. --}}
    <div class="card card-soft shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">{{ __('lang.new_features') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('new-features-body','new-feature-tpl')">{{ __('lang.add_feature') }}</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('lang.title') }}</th><th>{{ __('lang.description') }}</th><th></th></tr>
                    </thead>
                    <tbody id="new-features-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Offers ── --}}
    <div class="card card-soft shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">{{ __('lang.offers') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOfferRow()">{{ __('lang.add_offer') }}</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('lang.name') }}</th><th>{{ __('lang.th_start') }}</th><th>{{ __('lang.th_end') }}</th>
                            @if(old('type') === 'stadium')
                                <th>{{ __('lang.day_rate') }}</th><th>{{ __('lang.night_rate') }}</th>
                            @elseif(old('type') === 'hall')
                                <th>{{ __('lang.th_full_day') }}</th>
                            @else
                                <th>{{ __('lang.th_morning') }}</th><th>{{ __('lang.th_evening') }}</th><th>{{ __('lang.th_full_day') }}</th>
                            @endif
                            <th>{{ __('lang.status') }}</th><th></th>
                        </tr>
                    </thead>
                    <tbody id="offers-body"></tbody>
                </table>
            </div>
        </div>
    </div>


    {{-- ── Time Slots ── --}}
    @include('dashboard.web.unites.partials._slots_section', [
        'unite'     => null,
        'uniteType' => old('type',''),
    ])

    {{-- ── Pricing (with Hourly) ── --}}
    @include('dashboard.web.unites.partials._prices_section', [
        'unite'     => null,
        'uniteType' => old('type',''),
    ])

    {{-- ── Packages ── (not applicable to stadiums — hourly-only booking has no capacity-tier concept) --}}
    @unless(old('type', '') === 'stadium')
        @include('dashboard.web.unites.partials._packages_section', [
            'unite'     => null,
        ])
    @endunless

    {{-- ── Booking Packages ── (universal — available to every venue type) --}}
    @include('dashboard.web.unites.partials._booking_packages_section', [
        'unite'     => null,
        'services'  => $services,
    ])

    {{-- ── Viewing Times & Deposit ── (universal — available to every venue type) --}}
    @include('dashboard.web.unites.partials._viewing_times_section', [
        'unite'     => null,
    ])

    {{-- ── Reservations ── --}}
    <div class="card card-soft shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">{{ __('lang.reservations') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('res-body','res-tpl')">{{ __('lang.add_reservation') }}</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('lang.th_date') }}</th><th>{{ __('lang.th_period') }}</th><th>{{ __('lang.th_from') }}</th><th>{{ __('lang.th_to') }}</th><th>{{ __('lang.th_price') }}</th><th>{{ __('lang.status') }}</th><th></th></tr>
                    </thead>
                    <tbody id="res-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-accent" type="submit">{{ __('lang.create_unite') }}</button>
        <a href="{{ route('unites.index') }}" class="btn btn-outline-secondary">{{ __('lang.cancel') }}</a>
    </div>

</form>

{{-- Row templates (hidden) --}}
<template id="feature-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="features[__I__][name]" placeholder="Name" required></td>
        <td><input class="form-control form-control-sm" name="features[__I__][description]" placeholder="Description"></td>
        <td>
            <select class="form-select form-select-sm" name="features[__I__][status]">
                <option value="active">{{ __('lang.active') }}</option>
                <option value="inactive">{{ __('lang.inactive') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

<template id="new-feature-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="new_features[__I__][title]" placeholder="{{ __('lang.title') }}" required></td>
        <td><input class="form-control form-control-sm new-feature-description-input" name="new_features[__I__][description]" placeholder="{{ __('lang.description') }}"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

<template id="res-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="reservations[__I__][reservation_date]" type="date" required></td>
        <td>
            <select class="form-select form-select-sm" name="reservations[__I__][period_type]" required>
                <option value="morning">{{ __('lang.morning') }}</option>
                <option value="evening">{{ __('lang.evening') }}</option>
                <option value="full_day">{{ __('lang.full_day') }}</option>
                <option value="custom">{{ __('lang.custom') }}</option>
            </select>
        </td>
        <td><input class="form-control form-control-sm" name="reservations[__I__][from_time]" type="time" required></td>
        <td><input class="form-control form-control-sm" name="reservations[__I__][to_time]"   type="time" required></td>
        <td><input class="form-control form-control-sm" name="reservations[__I__][price]"     type="number" step="0.01" placeholder="0.00"></td>
        <td>
            <select class="form-select form-select-sm" name="reservations[__I__][status]" required>
                <option value="pending">{{ __('lang.pending') }}</option>
                <option value="confirmed">{{ __('lang.confirmed') }}</option>
                <option value="cancelled">{{ __('lang.cancelled') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

@push('js')
<script>
let rowCounters = {};

function addRow(bodyId, tplId) {
    rowCounters[tplId] = (rowCounters[tplId] ?? 0) + 1;
    const tpl  = document.getElementById(tplId).innerHTML
                    .replaceAll('__I__', rowCounters[tplId]);
    const tbody = document.getElementById(bodyId);
    tbody.insertAdjacentHTML('beforeend', tpl);
}

let offerRowCounter = 0;

function addOfferRow() {
    const i = offerRowCounter++;
    const type = document.getElementById('unite-type').value;
    let priceCols;

    if (type === 'stadium') {
        // Stadium offers use hourly rates, not period-based prices —
        // matches the pricing section and the standalone offers pages.
        priceCols = `
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][day_hour_price]"   placeholder="SAR/hr"></td>
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][night_hour_price]" placeholder="SAR/hr"></td>`;
    } else if (type === 'hall') {
        // Halls are full-day only — no morning/evening offer pricing.
        priceCols = `
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][full_day_price]" placeholder="0.00"></td>`;
    } else {
        priceCols = `
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][morning_price]"  placeholder="0.00"></td>
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][evening_price]"  placeholder="0.00"></td>
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][full_day_price]" placeholder="0.00"></td>`;
    }

    // BUG FIX: hidden inputs must always be wrapped in a <td> when
    // inserted into a table via innerHTML/insertAdjacentHTML — a bare
    // <input> as a direct child of <tr> is invalid per the HTML5 table
    // parsing rules, and browsers "foster-parent" it (yank it out of the
    // table entirely) when parsing the inserted string, which can silently
    // break the rest of the row or leave the field missing from the form.
    const row = `<tr>
        <td><input class="form-control form-control-sm" name="offers[${i}][name]" placeholder="{{ __('lang.name') }}"></td>
        <td><input class="form-control form-control-sm" name="offers[${i}][start]" type="date"></td>
        <td><input class="form-control form-control-sm" name="offers[${i}][end]"   type="date"></td>
        ${priceCols}
        <td>
            <select class="form-select form-select-sm" name="offers[${i}][status]">
                <option value="active">{{ __('lang.active') }}</option>
                <option value="inactive">{{ __('lang.inactive') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>`;

    document.getElementById('offers-body').insertAdjacentHTML('beforeend', row);
}


// Type → show correct detail fields via form submit
document.getElementById('unite-type').addEventListener('change', function () {
    document.getElementById('uniteForm').submit();
});

// new_features[].description is JSON-cast on the backend -- wrap each
// value in JSON.stringify() right before the real submit, so the admin
// only ever types plain text and never sees JSON syntax.
document.getElementById('uniteForm').addEventListener('submit', function () {
    document.querySelectorAll('.new-feature-description-input').forEach(function (input) {
        if (input.value !== '') {
            input.value = JSON.stringify(input.value);
        }
    });
});
</script>
@endpush

@endsection
