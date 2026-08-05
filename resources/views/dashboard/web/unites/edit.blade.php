@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Edit ' . $unite->name)

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('unites.show', $unite->id) }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <div>
        <h4 class="fw-bold mb-0">{{ __('lang.edit_unite') }}</h4>
        <div class="text-muted small">{{ $unite->name }}</div>
    </div>
</div>

<form action="{{ route('unites.update', $unite->id) }}" method="POST" enctype="multipart/form-data" id="uniteForm">
    @csrf @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ── ROW 1: core + images ── --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.unite_name') }} — {{ __('lang.description') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('lang.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name', $unite->name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('lang.status') }}</label>
                            <select class="form-select" name="status">
                                <option value="active"   {{ old('status',$unite->status) === 'active'   ? 'selected' : '' }}>{{ __('lang.active') }}</option>
                                <option value="inactive" {{ old('status',$unite->status) === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.unite_type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="unite-type" disabled>
                                @foreach(['stadium','hall','lounge','camp'] as $t)
                                    <option value="{{ $t }}" {{ $unite->type === $t ? 'selected' : '' }}>{{ __('lang.'.$t) }}</option>
                                @endforeach
                            </select>
                            {{-- BUG FIX: disabled <select> elements never submit
                                 their value at all — this hidden input carries
                                 the real type so it's still sent with the form. --}}
                            <input type="hidden" name="type" value="{{ $unite->type }}">
                            <div class="form-text small">{{ __('lang.type_locked_after_creation') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.department') }} <span class="text-danger">*</span></label>
                            <select class="form-select" disabled>
                                @foreach($departments as $dep)
                                    <option value="{{ $dep->id }}" {{ $unite->department_id == $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="department_id" value="{{ $unite->department_id }}">
                            <div class="form-text small">{{ __('lang.department_locked_after_creation') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.description') }}</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description',$unite->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.city') }}</label>
                            <select class="form-select" name="city">
                                <option value="">—</option>
                                @foreach(config('saudi_cities', []) as $c)
                                    <option value="{{ $c['key'] }}" {{ old('city', $unite->city) === $c['key'] ? 'selected' : '' }}>
                                        {{ app()->getLocale() === 'ar' ? $c['label_ar'] : $c['label_en'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.location_name') }}</label>
                            <input class="form-control" name="location_name" value="{{ old('location_name',$unite->location_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('lang.latitude') }}</label>
                            <input class="form-control" name="latitude" type="number" step="any" value="{{ old('latitude',$unite->latitude) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('lang.longitude') }}</label>
                            <input class="form-control" name="longitude" type="number" step="any" value="{{ old('longitude',$unite->longitude) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.families_singles') }}</label>
                            <select class="form-select" name="families_and_singles">
                                <option value="">—</option>
                                @foreach(['families'=>__('lang.families'),'singles'=>__('lang.singles'),'both'=>__('lang.both')] as $v=>$vLabel)
                                    <option value="{{ $v }}" {{ old('families_and_singles',$unite->families_and_singles) === $v ? 'selected' : '' }}>{{ ucfirst($v) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang.refund_policy') }}</label>
                            <select class="form-select" name="refund_policy">
                                <option value="">—</option>
                                @foreach(['free'=>__('lang.free'),'flexible'=>__('lang.flexible'),'moderate'=>__('lang.moderate'),'strict'=>__('lang.strict')] as $v=>$vLabel)
                                    <option value="{{ $v }}" {{ old('refund_policy',$unite->refund_policy) === $v ? 'selected' : '' }}>{{ $vLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.additional_terms') }}</label>
                            <textarea class="form-control" name="additional_terms" rows="2">{{ old('additional_terms',$unite->additional_terms) }}</textarea>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.images') }}</h6>
                    @if($unite->images->count())
                        <div class="row g-2 mb-3" id="existing-images">
                            @foreach($unite->images as $img)
                                <div class="col-4" id="img-wrap-{{ $img->id }}">
                                    <div style="position:relative">
                                        <img src="{{ asset($img->image) }}" class="rounded w-100"
                                             style="height:65px;object-fit:cover" onerror="this.style.opacity='.3'">
                                        <button type="button"
                                                style="position:absolute;top:2px;right:2px;width:20px;height:20px;border-radius:50%;background:rgba(220,53,69,.85);border:none;color:#fff;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center"
                                                onclick="removeImage({{ $img->id }}, this)">×</button>
                                        <input type="hidden" class="keep-img" name="keep_image_ids[]" value="{{ $img->id }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label class="form-label small fw-semibold mt-2">{{ __('lang.add_new_images') }}</label>
                    <input class="form-control" type="file" name="images[]" multiple accept="image/*">
                    <div class="text-muted small mt-1">{{ __('lang.existing_images_note') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Deposit & Insurance ── --}}
    @include('dashboard.web.unites._deposit_insurance', [
        'unite'             => $unite,
        'insurancePolicies' => $insurancePolicies ?? collect(),
        'readonly'          => false,
    ])

    {{-- ── Type-specific Details ── --}}
    @include('dashboard.web.unites._detail_fields', ['unite' => $unite, 'type' => old('type', $unite->type)])

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
                    <tbody id="features-body">
                        @foreach($unite->features as $i => $f)
                        <tr>
                            <td><input class="form-control form-control-sm" name="features[{{ $i }}][name]" value="{{ $f->name }}" required></td>
                            <td><input class="form-control form-control-sm" name="features[{{ $i }}][description]" value="{{ $f->description }}"></td>
                            <td>
                                <select class="form-select form-select-sm" name="features[{{ $i }}][status]">
                                    <option value="active"   {{ $f->status === 'active'   ? 'selected':'' }}>{{ __('lang.active') }}</option>
                                    <option value="inactive" {{ $f->status === 'inactive' ? 'selected':'' }}>{{ __('lang.inactive') }}</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    </tbody>
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
                            @if($unite->type === 'stadium')
                                <th>{{ __('lang.day_rate') }}</th><th>{{ __('lang.night_rate') }}</th>
                            @elseif($unite->type === 'hall')
                                <th>{{ __('lang.th_full_day') }}</th>
                            @else
                                <th>{{ __('lang.th_morning') }}</th><th>{{ __('lang.th_evening') }}</th><th>{{ __('lang.th_full_day') }}</th>
                            @endif
                            <th>{{ __('lang.status') }}</th><th></th>
                        </tr>
                    </thead>
                    <tbody id="offers-body">
                        @foreach($unite->offers as $i => $o)
                        <tr>
                            <td><input class="form-control form-control-sm" name="offers[{{ $i }}][name]" value="{{ $o->name }}"></td>
                            <td><input class="form-control form-control-sm" name="offers[{{ $i }}][start]" type="date" value="{{ $o->start }}"></td>
                            <td><input class="form-control form-control-sm" name="offers[{{ $i }}][end]"   type="date" value="{{ $o->end }}"></td>
                            @if($unite->type === 'stadium')
                                <td><input class="form-control form-control-sm" name="offers[{{ $i }}][day_hour_price]"   type="number" step="0.01" value="{{ $o->day_hour_price }}" placeholder="SAR/hr"></td>
                                <td><input class="form-control form-control-sm" name="offers[{{ $i }}][night_hour_price]" type="number" step="0.01" value="{{ $o->night_hour_price }}" placeholder="SAR/hr"></td>
                            @elseif($unite->type === 'hall')
                                <td><input class="form-control form-control-sm" name="offers[{{ $i }}][full_day_price]" type="number" step="0.01" value="{{ $o->full_day_price }}"></td>
                            @else
                                <td><input class="form-control form-control-sm" name="offers[{{ $i }}][morning_price]"  type="number" step="0.01" value="{{ $o->morning_price }}"></td>
                                <td><input class="form-control form-control-sm" name="offers[{{ $i }}][evening_price]"  type="number" step="0.01" value="{{ $o->evening_price }}"></td>
                                <td><input class="form-control form-control-sm" name="offers[{{ $i }}][full_day_price]" type="number" step="0.01" value="{{ $o->full_day_price }}"></td>
                            @endif
                            <td>
                                <select class="form-select form-select-sm" name="offers[{{ $i }}][status]">
                                    <option value="active"   {{ $o->status === 'active'   ? 'selected':'' }}>{{ __('lang.active') }}</option>
                                    <option value="inactive" {{ $o->status === 'inactive' ? 'selected':'' }}>{{ __('lang.inactive') }}</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    {{-- ── Time Slots ── --}}
    @include('dashboard.web.unites.partials._slots_section', [
        'unite'     => $unite,
        'uniteType' => old('type', $unite->type),
    ])

    {{-- ── Pricing (with Hourly) ── --}}
    @include('dashboard.web.unites.partials._prices_section', [
        'unite'     => $unite,
        'uniteType' => old('type', $unite->type),
    ])

    {{-- ── Packages ── (not applicable to stadiums) --}}
    @unless($unite->type === 'stadium')
        @include('dashboard.web.unites.partials._packages_section', [
            'unite'     => $unite,
        ])
    @endunless

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
                    <tbody id="res-body">
                        @foreach($unite->reservations as $i => $r)
                        <tr>
                            <td><input class="form-control form-control-sm" name="reservations[{{ $i }}][reservation_date]" type="date" value="{{ $r->reservation_date?->format('Y-m-d') ?? $r->reservation_date }}" required></td>
                            <td>
                                <select class="form-select form-select-sm" name="reservations[{{ $i }}][period_type]" required>
                                    @foreach(['morning'=>__('lang.morning'),'evening'=>__('lang.evening'),'full_day'=>__('lang.full_day'),'custom'=>__('lang.custom')] as $p=>$pLabel)
                                        <option value="{{ $p }}" {{ $r->period_type === $p ? 'selected':'' }}>{{ $pLabel }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="form-control form-control-sm" name="reservations[{{ $i }}][from_time]" type="time" value="{{ $r->from_time }}" required></td>
                            <td><input class="form-control form-control-sm" name="reservations[{{ $i }}][to_time]"   type="time" value="{{ $r->to_time }}" required></td>
                            <td><input class="form-control form-control-sm" name="reservations[{{ $i }}][price]" type="number" step="0.01" value="{{ $r->price }}"></td>
                            <td>
                                <select class="form-select form-select-sm" name="reservations[{{ $i }}][status]" required>
                                    @foreach(['pending'=>__('lang.pending'),'confirmed'=>__('lang.confirmed'),'cancelled'=>__('lang.cancelled')] as $s=>$sLabel)
                                        <option value="{{ $s }}" {{ $r->status === $s ? 'selected':'' }}>{{ $sLabel }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-accent" type="submit">{{ __('lang.save_changes') }}</button>
        <a href="{{ route('unites.show', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.cancel') }}</a>
    </div>

</form>

{{-- Row templates --}}
<template id="feature-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="features[__I__][name]" placeholder="Name" required></td>
        <td><input class="form-control form-control-sm" name="features[__I__][description]" placeholder="Description"></td>
        <td><select class="form-select form-select-sm" name="features[__I__][status]"><option value="active">{{ __('lang.active') }}</option><option value="inactive">{{ __('lang.inactive') }}</option></select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

<template id="res-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="reservations[__I__][reservation_date]" type="date" required></td>
        <td><select class="form-select form-select-sm" name="reservations[__I__][period_type]" required><option value="morning">{{ __('lang.morning') }}</option><option value="evening">{{ __('lang.evening') }}</option><option value="full_day">{{ __('lang.full_day') }}</option><option value="custom">{{ __('lang.custom') }}</option></select></td>
        <td><input class="form-control form-control-sm" name="reservations[__I__][from_time]" type="time" required></td>
        <td><input class="form-control form-control-sm" name="reservations[__I__][to_time]"   type="time" required></td>
        <td><input class="form-control form-control-sm" name="reservations[__I__][price]" type="number" step="0.01" placeholder="0.00"></td>
        <td><select class="form-select form-select-sm" name="reservations[__I__][status]" required><option value="pending">{{ __('lang.pending') }}</option><option value="confirmed">{{ __('lang.confirmed') }}</option><option value="cancelled">{{ __('lang.cancelled') }}</option></select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

@push('js')
<script>
let rowCounters = {};
function addRow(bodyId, tplId) {
    rowCounters[tplId] = (rowCounters[tplId] ?? {{ max($unite->features->count(), $unite->offers->count(), $unite->reservations->count()) }}) + 1;
    const tpl  = document.getElementById(tplId).innerHTML.replaceAll('__I__', rowCounters[tplId]);
    document.getElementById(bodyId).insertAdjacentHTML('beforeend', tpl);
}

let offerRowCounter = {{ $unite->offers->count() }};
const uniteTypeForOffers = {{ json_encode($unite->type) }};

function addOfferRow() {
    const i = offerRowCounter++;
    let priceCols;

    if (uniteTypeForOffers === 'stadium') {
        priceCols = `
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][day_hour_price]"   placeholder="SAR/hr"></td>
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][night_hour_price]" placeholder="SAR/hr"></td>`;
    } else if (uniteTypeForOffers === 'hall') {
        priceCols = `
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][full_day_price]" placeholder="0.00"></td>`;
    } else {
        priceCols = `
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][morning_price]"  placeholder="0.00"></td>
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][evening_price]"  placeholder="0.00"></td>
            <td><input class="form-control form-control-sm" type="number" step="0.01" name="offers[${i}][full_day_price]" placeholder="0.00"></td>`;
    }

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

</script>
@endpush

<script>
function removeImage(id, btn) {
    // Remove the hidden keep input so this ID won't be in keep_image_ids[]
    const wrap = document.getElementById('img-wrap-' + id);
    if (wrap) {
        wrap.style.opacity = '0.3';
        // Remove the keep input so it's excluded from the submitted keep list
        const keepInput = wrap.querySelector('.keep-img');
        if (keepInput) keepInput.remove();
        btn.disabled = true;
    }
}
</script>
@endsection
