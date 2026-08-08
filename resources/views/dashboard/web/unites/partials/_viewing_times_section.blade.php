{{--
  Viewing Times & Deposit Section — inline add/edit for create and edit unite forms.
  $unite = Unite|null

  Genuinely universal across all 4 venue types, matching the booking
  packages section — a customer schedules a visit to inspect the venue
  before booking it, picking one of these predefined weekly slots.

  Deliberately separate from reservation_deposit_* (a different concept —
  a partial payment toward an actual reservation, not a viewing
  appointment) and from the booking-packages section.

  BUG FIX LESSON APPLIED: the deposit refundable/amount fields toggle
  visibility via Bootstrap's d-none class (display:none !important),
  matching the fix already applied to the booking-packages section's
  hours/days mode toggle — an earlier version of that toggle used an
  inline style and, for reasons that weren't fully reproducible
  statically, ended up showing both groups at once in practice. Using
  d-none from the start here avoids risking the same class of bug.
--}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">🚪 {{ __('lang.viewing_times') }}</h6>
                <div class="text-muted small">{{ __('lang.viewing_times_subtitle') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="addViewingTimeRow()">+ {{ __('lang.add_viewing_time') }}</button>
        </div>

        {{-- Deposit configuration --}}
        <div class="border rounded p-3 mb-3">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="viewing_deposit_enabled" value="1"
                       id="viewing_deposit_enabled"
                       onchange="toggleViewingDepositFields(this)"
                       {{ old('viewing_deposit_enabled', $unite->viewing_deposit_enabled ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="viewing_deposit_enabled">{{ __('lang.viewing_deposit_enabled') }}</label>
            </div>
            <div id="viewing-deposit-fields" class="row g-3 {{ old('viewing_deposit_enabled', $unite->viewing_deposit_enabled ?? false) ? '' : 'd-none' }}">
                <div class="col-md-6">
                    <label class="form-label small">{{ __('lang.viewing_deposit_amount') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" class="form-control" name="viewing_deposit_amount"
                               value="{{ old('viewing_deposit_amount', $unite->viewing_deposit_amount ?? '') }}">
                        <span class="input-group-text">SAR</span>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="viewing_deposit_refundable" value="1"
                               id="viewing_deposit_refundable"
                               {{ old('viewing_deposit_refundable', $unite->viewing_deposit_refundable ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="viewing_deposit_refundable">{{ __('lang.viewing_deposit_refundable') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.booking_package_days') }}</th>
                        <th>{{ __('lang.th_start') }}</th>
                        <th>{{ __('lang.th_end') }}</th>
                        <th>{{ __('lang.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="viewing-times-body">
                    @if($unite)
                        @foreach($unite->viewingTimes as $i => $vt)
                        <tr>
                            <td>
                                <select class="form-select form-select-sm" name="viewing_times[{{ $i }}][day_of_week]">
                                    @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $d)
                                        <option value="{{ $d }}" {{ $vt->day_of_week === $d ? 'selected' : '' }}>{{ __('lang.'.$d) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="form-control form-control-sm" type="time" lang="ar" name="viewing_times[{{ $i }}][start_time]" value="{{ $vt->start_time }}"></td>
                            <td><input class="form-control form-control-sm" type="time" lang="ar" name="viewing_times[{{ $i }}][end_time]" value="{{ $vt->end_time }}"></td>
                            <td>
                                <select class="form-select form-select-sm" name="viewing_times[{{ $i }}][status]">
                                    <option value="active" {{ $vt->status === 'active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
                                    <option value="inactive" {{ $vt->status === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="form-text small mt-2">{{ __('lang.viewing_times_hint') }}</div>
    </div>
</div>

<template id="viewing-time-tpl">
    <tr>
        <td>
            <select class="form-select form-select-sm" name="viewing_times[__I__][day_of_week]">
                @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $d)
                    <option value="{{ $d }}">{{ __('lang.'.$d) }}</option>
                @endforeach
            </select>
        </td>
        <td><input class="form-control form-control-sm" type="time" lang="ar" name="viewing_times[__I__][start_time]"></td>
        <td><input class="form-control form-control-sm" type="time" lang="ar" name="viewing_times[__I__][end_time]"></td>
        <td>
            <select class="form-select form-select-sm" name="viewing_times[__I__][status]">
                <option value="active">{{ __('lang.active') }}</option>
                <option value="inactive">{{ __('lang.inactive') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>

@push('js')
<script>
let viewingTimeRowCounter = {{ $unite ? $unite->viewingTimes->count() : 0 }};

function addViewingTimeRow() {
    viewingTimeRowCounter++;
    const tpl = document.getElementById('viewing-time-tpl').innerHTML
        .replaceAll('__I__', viewingTimeRowCounter);
    document.getElementById('viewing-times-body').insertAdjacentHTML('beforeend', tpl);
}

function toggleViewingDepositFields(checkbox) {
    const fields = document.getElementById('viewing-deposit-fields');
    if (checkbox.checked) {
        fields.classList.remove('d-none');
    } else {
        fields.classList.add('d-none');
    }
}
</script>
@endpush
