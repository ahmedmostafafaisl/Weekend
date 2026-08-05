{{--
  Prices Section — inline edit with hourly pricing support.
  $unite       = Unite|null
  $uniteType   = string

  BUG FIX: stadiums are hourly-only (day_hour_price/night_hour_price always
  required, no flat full-day price) — but this table previously reused the
  same interactive "Hourly" toggle built for hall/lounge/camp, where hourly
  is a genuinely optional add-on. Toggling it off made the rate inputs
  readOnly (not disabled — disabled inputs aren't submitted with the form,
  so readOnly was the deliberate choice) while they were still empty,
  submitting empty strings that failed stadium's unconditional 'required'
  validation. Stadiums now get their own row layout: no Full Price column,
  no toggle, hourly_enabled sent via a hidden input fixed to 1, and the
  rate/window fields are always enabled and never grey — there's no state
  in which they can be left blank via a UI control.
--}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">💰 {{ __('lang.pricing') }}</h6>
                <div class="text-muted small">
                    @if(($uniteType ?? ($unite->type ?? '')) === 'stadium')
                        {{ __('lang.stadium_hourly_only_hint') }}
                    @else
                        {{ __('lang.set_prices_per_day_type') }}
                    @endif
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="addPriceRow()"
                    @if(($uniteType ?? ($unite->type ?? '')) === 'stadium' || (($uniteType ?? ($unite->type ?? '')) === 'hall' && $unite)) style="display:none" @endif>
                + {{ __('lang.add_price_row') }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="prices-table">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.day_type') }}</th>
                        @if(($uniteType ?? ($unite->type ?? '')) === 'stadium')
                            <th>{{ __('lang.day_rate') }}</th>
                            <th>{{ __('lang.night_rate') }}</th>
                            <th>{{ __('lang.day_start') }}</th>
                            <th>{{ __('lang.day_end') }}</th>
                            <th>{{ __('lang.min') }} ({{ __('lang.min') }})</th>
                        @elseif(($uniteType ?? ($unite->type ?? '')) === 'hall')
                            <th>{{ __('lang.full_day') }}</th>
                            <th style="min-width:90px">{{ __('lang.hourly') }}</th>
                            <th>{{ __('lang.day_rate') }}</th>
                            <th>{{ __('lang.night_rate') }}</th>
                            <th>{{ __('lang.day_start') }}</th>
                            <th>{{ __('lang.day_end') }}</th>
                            <th>{{ __('lang.min') }} ({{ __('lang.min') }})</th>
                        @else
                            <th>{{ __('lang.morning') }}</th><th>{{ __('lang.evening') }}</th><th>{{ __('lang.full_day') }}</th>
                            <th style="min-width:90px">{{ __('lang.hourly') }}</th>
                            <th>{{ __('lang.day_rate') }}</th>
                            <th>{{ __('lang.night_rate') }}</th>
                            <th>{{ __('lang.day_start') }}</th>
                            <th>{{ __('lang.day_end') }}</th>
                            <th>{{ __('lang.min') }} ({{ __('lang.min') }})</th>
                        @endif
                        @unless(($uniteType ?? ($unite->type ?? '')) === 'stadium' || (($uniteType ?? ($unite->type ?? '')) === 'hall' && $unite))
                            <th></th>
                        @endunless
                    </tr>
                </thead>
                <tbody id="prices-body">
                    @php($type = $uniteType ?? ($unite->type ?? ''))
                    @if($type === 'stadium' || ($type === 'hall' && $unite))
                        {{-- Stadium: every day-type fixed, forced hourly-only
                             (no Full Price at all). Hall on EDIT: every
                             day-type fixed too, but keeps its normal
                             Full Price field plus the OPTIONAL hourly
                             toggle — same fields as hall's dynamic rows,
                             just non-addable/removable. Hall on CREATE
                             still uses the dynamic rows below, unchanged. --}}
                        @foreach([['week_day',__('lang.weekday')],['thursday',__('lang.thursday')],['friday',__('lang.friday')],['saturday',__('lang.saturday')]] as $i => [$v, $l])
                        @php($pr = $unite ? $unite->prices->firstWhere('day', $v) : null)
                        <tr>
                            <td>
                                <input type="hidden" name="prices[{{ $i }}][day]" value="{{ $v }}">
                                @if($type === 'stadium')
                                    <input type="hidden" name="prices[{{ $i }}][hourly_enabled]" value="1">
                                @endif
                                <span class="fw-semibold small">{{ $l }}</span>
                            </td>
                            @if($type === 'stadium')
                                <td><input class="form-control form-control-sm" type="number" step="0.01" min="0"
                                           name="prices[{{ $i }}][day_hour_price]" value="{{ $pr->day_hour_price ?? '' }}" placeholder="SAR/hr"></td>
                                <td><input class="form-control form-control-sm" type="number" step="0.01" min="0"
                                           name="prices[{{ $i }}][night_hour_price]" value="{{ $pr->night_hour_price ?? '' }}" placeholder="SAR/hr"></td>
                                <td><input class="form-control form-control-sm" type="time"
                                           name="prices[{{ $i }}][day_start]" value="{{ $pr->day_start ?? '06:00' }}"></td>
                                <td><input class="form-control form-control-sm" type="time"
                                           name="prices[{{ $i }}][day_end]" value="{{ $pr->day_end ?? '18:00' }}"></td>
                                <td><input class="form-control form-control-sm" type="number" min="15" max="1440"
                                           name="prices[{{ $i }}][min_booking_minutes]" value="{{ $pr->min_booking_minutes ?? 60 }}"></td>
                            @else
                                <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[{{ $i }}][full_price]" value="{{ $pr->full_price ?? '' }}" placeholder="0.00"></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox"
                                               name="prices[{{ $i }}][hourly_enabled]" value="1"
                                               id="he_{{ $i }}" onchange="toggleHourlyRow(this, {{ $i }})"
                                               {{ ($pr->hourly_enabled ?? false) ? 'checked':'' }}>
                                    </div>
                                </td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="number" step="0.01" min="0"
                                           name="prices[{{ $i }}][day_hour_price]" value="{{ $pr->day_hour_price ?? '' }}"
                                           placeholder="SAR/hr" style="{{ ! ($pr->hourly_enabled ?? false) ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="number" step="0.01" min="0"
                                           name="prices[{{ $i }}][night_hour_price]" value="{{ $pr->night_hour_price ?? '' }}"
                                           placeholder="SAR/hr" style="{{ ! ($pr->hourly_enabled ?? false) ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="time"
                                           name="prices[{{ $i }}][day_start]" value="{{ $pr->day_start ?? '06:00' }}"
                                           style="{{ ! ($pr->hourly_enabled ?? false) ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="time"
                                           name="prices[{{ $i }}][day_end]" value="{{ $pr->day_end ?? '18:00' }}"
                                           style="{{ ! ($pr->hourly_enabled ?? false) ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="number" min="15" max="1440"
                                           name="prices[{{ $i }}][min_booking_minutes]" value="{{ $pr->min_booking_minutes ?? 60 }}"
                                           style="{{ ! ($pr->hourly_enabled ?? false) ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                            @endif
                        </tr>
                        @endforeach
                    @elseif($unite)
                        @foreach($unite->prices as $i => $pr)
                        <tr>
                            <td>
                                <select class="form-select form-select-sm" name="prices[{{ $i }}][day]">
                                    @foreach(['thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday'),'week_day'=>__('lang.weekday')] as $v=>$l)
                                        <option value="{{ $v }}" {{ $pr->day === $v ? 'selected':'' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Only lounge/camp reach this loop now — stadium
                                 and hall-on-edit both use the fixed-rows
                                 branch above instead. --}}
                                <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[{{ $i }}][morning_price]" value="{{ $pr->morning_price }}" placeholder="0.00"></td>
                                <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[{{ $i }}][evening_price]" value="{{ $pr->evening_price }}" placeholder="0.00"></td>
                                <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[{{ $i }}][full_price]"    value="{{ $pr->full_price }}"    placeholder="0.00"></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox"
                                               name="prices[{{ $i }}][hourly_enabled]" value="1"
                                               id="he_{{ $i }}" onchange="toggleHourlyRow(this, {{ $i }})"
                                               {{ $pr->hourly_enabled ? 'checked':'' }}>
                                    </div>
                                </td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="number" step="0.01" min="0"
                                           name="prices[{{ $i }}][day_hour_price]" value="{{ $pr->day_hour_price }}"
                                           placeholder="SAR/hr" style="{{ !$pr->hourly_enabled ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="number" step="0.01" min="0"
                                           name="prices[{{ $i }}][night_hour_price]" value="{{ $pr->night_hour_price }}"
                                           placeholder="SAR/hr" style="{{ !$pr->hourly_enabled ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="time"
                                           name="prices[{{ $i }}][day_start]" value="{{ $pr->day_start ?? '06:00' }}"
                                           style="{{ !$pr->hourly_enabled ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="time"
                                           name="prices[{{ $i }}][day_end]" value="{{ $pr->day_end ?? '18:00' }}"
                                           style="{{ !$pr->hourly_enabled ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><input class="form-control form-control-sm hourly-field-{{ $i }}" type="number" min="15" max="1440"
                                           name="prices[{{ $i }}][min_booking_minutes]" value="{{ $pr->min_booking_minutes ?? 60 }}"
                                           style="{{ !$pr->hourly_enabled ? 'opacity:.4;background:#f8f9fa' : '' }}"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let priceRowCounter = {{ $unite ? $unite->prices->count() : 0 }};
const uniteTypeForPricing = {{ json_encode($uniteType ?? ($unite->type ?? '')) }};

function addPriceRow() {
    const i = priceRowCounter++;
    const hallCols = `<td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[${i}][full_price]" placeholder="0.00"></td>`;
    const loungeCampCols = `
        <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[${i}][morning_price]" placeholder="0.00"></td>
        <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[${i}][evening_price]" placeholder="0.00"></td>
        <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="prices[${i}][full_price]"    placeholder="0.00"></td>`;
    const priceCols = uniteTypeForPricing === 'hall' ? hallCols : loungeCampCols;

    const row = `<tr>
        <td>
            <select class="form-select form-select-sm" name="prices[${i}][day]">
                <option value="thursday">{{ __('lang.thursday') }}</option>
                <option value="friday">{{ __('lang.friday') }}</option>
                <option value="saturday">{{ __('lang.saturday') }}</option>
                <option value="week_day" selected>{{ __('lang.weekday') }}</option>
            </select>
        </td>
        ${priceCols}
        <td class="text-center">
            <div class="form-check form-switch d-flex justify-content-center mb-0">
                <input class="form-check-input" type="checkbox" id="he_${i}" value="1"
                       name="prices[${i}][hourly_enabled]" onchange="toggleHourlyRow(this, ${i})">
            </div>
        </td>
        <td><input class="form-control form-control-sm hourly-field-${i}" type="number" step="0.01" min="0" name="prices[${i}][day_hour_price]"      placeholder="SAR/hr" style="opacity:.4;background:#f8f9fa"></td>
        <td><input class="form-control form-control-sm hourly-field-${i}" type="number" step="0.01" min="0" name="prices[${i}][night_hour_price]"    placeholder="SAR/hr" style="opacity:.4;background:#f8f9fa"></td>
        <td><input class="form-control form-control-sm hourly-field-${i}" type="time"                       name="prices[${i}][day_start]"           value="06:00"        style="opacity:.4;background:#f8f9fa"></td>
        <td><input class="form-control form-control-sm hourly-field-${i}" type="time"                       name="prices[${i}][day_end]"             value="18:00"        style="opacity:.4;background:#f8f9fa"></td>
        <td><input class="form-control form-control-sm hourly-field-${i}" type="number" min="15" max="1440" name="prices[${i}][min_booking_minutes]" value="60"           style="opacity:.4;background:#f8f9fa"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>`;

    document.getElementById('prices-body').insertAdjacentHTML('beforeend', row);
}

function toggleHourlyRow(checkbox, i) {
    const fields = document.querySelectorAll('.hourly-field-' + i);
    fields.forEach(f => {
        // Do NOT disable — disabled inputs are not submitted with the form
        f.style.opacity    = checkbox.checked ? '1' : '0.4';
        f.style.background = checkbox.checked ? '' : '#f8f9fa';
        f.readOnly         = !checkbox.checked;
    });
}
</script>
