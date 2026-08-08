{{--
  Booking Packages Section — inline add/edit for create and edit unite forms.
  $unite = Unite|null

  Genuinely universal across all 4 venue types (stadium, hall, lounge, camp),
  unlike every other section in this form which still branches by type —
  package booking is an optional add-on available to every venue equally.

  Each package is one of two genuinely different modes, chosen per row via
  the "Type" dropdown:
    'hours' — a time window within a single day-type (week_day/thursday/
              friday/saturday, matching the exact same enum already used
              for unite_prices.day).
    'days'  — a span of one or more consecutive calendar days, starting on
              day_from's weekday (e.g. "Saturday" alone, or "Sunday
              through Friday").

  BUG FIX: the previous version toggled visibility via an inline
  style="display:none" attribute, which for reasons that weren't fully
  reproducible statically, ended up showing BOTH field groups at once
  in practice. Rebuilt using Bootstrap's d-none class (display:none with
  !important), which can't lose to any conflicting class-based display
  rule, and toggleBookingPackageMode() now runs explicitly right after a
  new row is inserted too, rather than relying solely on a server-baked
  or template-baked initial state.

  services is plain free text (comma-separated in the UI) rather than a
  selection from the services catalog — the provider types these in
  themselves.
--}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">🎁 {{ __('lang.booking_packages') }}</h6>
                <div class="text-muted small">{{ __('lang.booking_packages_subtitle') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="addBookingPackageRow()">+ {{ __('lang.add_package') }}</button>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="package_booking_enabled" value="1"
                   id="package_booking_enabled"
                   {{ old('package_booking_enabled', $unite->package_booking_enabled ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="package_booking_enabled">{{ __('lang.package_booking_enabled') }}</label>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.name') }}</th>
                        <th style="min-width:110px">{{ __('lang.booking_type') }}</th>
                        <th style="min-width:280px">{{ __('lang.booking_package_when') }}</th>
                        <th>{{ __('lang.th_price') }}</th>
                        <th style="min-width:200px">{{ __('lang.included_services') }}</th>
                        <th>{{ __('lang.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="booking-packages-body">
                    @if($unite)
                        @foreach($unite->bookingPackages as $i => $pkg)
                        <tr>
                            <td><input class="form-control form-control-sm" name="booking_packages[{{ $i }}][name]" value="{{ $pkg->name }}"></td>
                            <td>
                                <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][booking_type]" onchange="toggleBookingPackageMode(this)">
                                    <option value="hours" {{ $pkg->booking_type === 'hours' ? 'selected' : '' }}>{{ __('lang.booking_type_hours') }}</option>
                                    <option value="days" {{ $pkg->booking_type === 'days' ? 'selected' : '' }}>{{ __('lang.booking_type_days') }}</option>
                                </select>
                            </td>
                            <td>
                                <div class="bp-hours-fields d-flex gap-1 {{ $pkg->booking_type !== 'hours' ? 'd-none' : '' }}">
                                    <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][day]" style="max-width:110px">
                                        @foreach(['week_day'=>__('lang.weekday'),'thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday')] as $v=>$l)
                                            <option value="{{ $v }}" {{ $pkg->day === $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                    {{-- lang="ar" nudges Chrome/Edge's native time picker into
                                         24-hour format regardless of the page's overall
                                         language, since most Arabic locales conventionally
                                         use 24-hour time — a well-known, reliable per-input
                                         technique rather than something controllable via a
                                         plain HTML attribute like a format string. --}}
                                    <input class="form-control form-control-sm" type="time" lang="ar" name="booking_packages[{{ $i }}][start_time]" value="{{ $pkg->start_time }}">
                                    <input class="form-control form-control-sm" type="time" lang="ar" name="booking_packages[{{ $i }}][end_time]" value="{{ $pkg->end_time }}">
                                </div>
                                <div class="bp-days-fields d-flex gap-1 align-items-center {{ $pkg->booking_type !== 'days' ? 'd-none' : '' }}">
                                    <span class="small text-muted">{{ __('lang.booking_package_from') }}</span>
                                    <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][day_from]">
                                        @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $d)
                                            <option value="{{ $d }}" {{ $pkg->day_from === $d ? 'selected' : '' }}>{{ __('lang.'.$d) }}</option>
                                        @endforeach
                                    </select>
                                    <span class="small text-muted">{{ __('lang.booking_package_to') }}</span>
                                    <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][day_to]">
                                        @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $d)
                                            <option value="{{ $d }}" {{ $pkg->day_to === $d ? 'selected' : '' }}>{{ __('lang.'.$d) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="booking_packages[{{ $i }}][price]" value="{{ $pkg->price }}"></td>
                            <td>
                                <input class="form-control form-control-sm" name="booking_packages[{{ $i }}][services_text]"
                                       value="{{ implode(', ', $pkg->services ?? []) }}" placeholder="{{ __('lang.included_services_placeholder') }}">
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][status]">
                                    <option value="active" {{ $pkg->status === 'active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
                                    <option value="inactive" {{ $pkg->status === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="form-text small mt-2">{{ __('lang.included_services_hint') }}</div>
    </div>
</div>

<template id="booking-package-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="booking_packages[__I__][name]"></td>
        <td>
            <select class="form-select form-select-sm" name="booking_packages[__I__][booking_type]" onchange="toggleBookingPackageMode(this)">
                <option value="hours">{{ __('lang.booking_type_hours') }}</option>
                <option value="days">{{ __('lang.booking_type_days') }}</option>
            </select>
        </td>
        <td>
            <div class="bp-hours-fields d-flex gap-1">
                <select class="form-select form-select-sm" name="booking_packages[__I__][day]" style="max-width:110px">
                    @foreach(['week_day'=>__('lang.weekday'),'thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday')] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
                <input class="form-control form-control-sm" type="time" lang="ar" name="booking_packages[__I__][start_time]">
                <input class="form-control form-control-sm" type="time" lang="ar" name="booking_packages[__I__][end_time]">
            </div>
            <div class="bp-days-fields d-flex gap-1 align-items-center d-none">
                <span class="small text-muted">{{ __('lang.booking_package_from') }}</span>
                <select class="form-select form-select-sm" name="booking_packages[__I__][day_from]">
                    @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $d)
                        <option value="{{ $d }}">{{ __('lang.'.$d) }}</option>
                    @endforeach
                </select>
                <span class="small text-muted">{{ __('lang.booking_package_to') }}</span>
                <select class="form-select form-select-sm" name="booking_packages[__I__][day_to]">
                    @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $d)
                        <option value="{{ $d }}">{{ __('lang.'.$d) }}</option>
                    @endforeach
                </select>
            </div>
        </td>
        <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="booking_packages[__I__][price]"></td>
        <td>
            <input class="form-control form-control-sm" name="booking_packages[__I__][services_text]" placeholder="{{ __('lang.included_services_placeholder') }}">
        </td>
        <td>
            <select class="form-select form-select-sm" name="booking_packages[__I__][status]">
                <option value="active">{{ __('lang.active') }}</option>
                <option value="inactive">{{ __('lang.inactive') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

@push('js')
<script>
let bookingPackageRowCounter = {{ $unite ? $unite->bookingPackages->count() : 0 }};

function addBookingPackageRow() {
    bookingPackageRowCounter++;
    const tpl = document.getElementById('booking-package-tpl').innerHTML
        .replaceAll('__I__', bookingPackageRowCounter);
    document.getElementById('booking-packages-body').insertAdjacentHTML('beforeend', tpl);

    // Explicitly set the correct initial visibility on the just-inserted
    // row rather than relying solely on the template's own baked-in
    // classes — belt-and-suspenders against any future edit to the
    // template accidentally leaving both groups visible again.
    const rows = document.querySelectorAll('#booking-packages-body tr');
    const newRow = rows[rows.length - 1];
    const typeSelect = newRow.querySelector('select[name*="[booking_type]"]');
    if (typeSelect) {
        toggleBookingPackageMode(typeSelect);
    }
}

// Toggles which field group (hours vs days) is visible within a single
// package row, based on that row's own Type dropdown — both groups
// always exist in the DOM, this just shows/hides them. Uses Bootstrap's
// d-none (display:none !important) rather than an inline style, so it
// can't lose to any conflicting class-based display rule.
function toggleBookingPackageMode(select) {
    const row = select.closest('tr');
    const hoursFields = row.querySelector('.bp-hours-fields');
    const daysFields = row.querySelector('.bp-days-fields');
    if (select.value === 'days') {
        hoursFields.classList.add('d-none');
        daysFields.classList.remove('d-none');
    } else {
        hoursFields.classList.remove('d-none');
        daysFields.classList.add('d-none');
    }
}

// Also apply the correct initial state to any EXISTING rows on page
// load (edit.blade.php) — same belt-and-suspenders reasoning as above.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#booking-packages-body select[name*="[booking_type]"]').forEach(function (select) {
        toggleBookingPackageMode(select);
    });
});
</script>
@endpush
