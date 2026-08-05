{{--
  Slots Section — inline edit for create and edit unite forms.
  $unite       = Unite|null      (null on create)
  $uniteType   = string          ('stadium','hall','lounge','camp' or '')
--}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">📅 {{ __('lang.time_slots') }}</h6>
                <div class="text-muted small">{{ __('lang.operating_hours_per_weekday_pattern') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="addRow('slots-body','slot-tpl')"
                    @if(($uniteType ?? ($unite->type ?? '')) === 'stadium' || (($uniteType ?? ($unite->type ?? '')) === 'hall' && $unite)) style="display:none" @endif>
                + {{ __('lang.add_slot') }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.day') }}</th>
                        @if(($uniteType ?? ($unite->type ?? '')) === 'stadium')
                            {{-- BUG FIX: stadiums are available 24 hours a day —
                                 there's nothing for an admin to configure here,
                                 so no start/end columns are shown at all
                                 (full_start/full_end are still sent as hidden
                                 00:00/23:59 inputs below, satisfying validation
                                 without asking anyone to type them in). --}}
                        @elseif(($uniteType ?? ($unite->type ?? '')) === 'hall')
                            <th>{{ __('lang.full_start') }}</th><th>{{ __('lang.full_end') }}</th>
                        @else
                            <th>{{ __('lang.morning_start') }}</th><th>{{ __('lang.morning_end') }}</th>
                            <th>{{ __('lang.evening_start') }}</th><th>{{ __('lang.evening_end') }}</th>
                            <th>{{ __('lang.full_start') }}</th><th>{{ __('lang.full_end') }}</th>
                        @endif
                        <th>{{ __('lang.status') }}</th>
                        @unless(($uniteType ?? ($unite->type ?? '')) === 'stadium' || (($uniteType ?? ($unite->type ?? '')) === 'hall' && $unite))
                            <th></th>
                        @endunless
                    </tr>
                </thead>
                <tbody id="slots-body">
                    @php($type = $uniteType ?? ($unite->type ?? ''))
                    @if($type === 'stadium' || ($type === 'hall' && $unite))
                        {{-- Stadium: every day fixed, hidden 00:00/23:59 (24hr,
                             nothing to configure). Hall on EDIT: every day
                             fixed too, but full_start/full_end stay genuinely
                             editable — hall's operating hours are real and
                             provider-specific, unlike stadium's fixed 24hr
                             window. Hall on CREATE still uses the dynamic
                             dropdown-based rows below, unchanged. --}}
                        @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $i => $d)
                            @php($sl = $unite ? $unite->slots->firstWhere('day_of_week', $d) : null)
                            <tr>
                                <td>
                                    <input type="hidden" name="slots[{{ $i }}][day_of_week]" value="{{ $d }}">
                                    @if($type === 'stadium')
                                        <input type="hidden" name="slots[{{ $i }}][full_start]" value="00:00">
                                        <input type="hidden" name="slots[{{ $i }}][full_end]" value="23:59">
                                    @endif
                                    <span class="fw-semibold small">{{ __('lang.'.$d) }}</span>
                                </td>
                                @if($type === 'hall')
                                    <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][full_start]" value="{{ $sl->full_start ?? '' }}"></td>
                                    <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][full_end]"   value="{{ $sl->full_end ?? '' }}"></td>
                                @endif
                                <td>
                                    <select class="form-select form-select-sm" name="slots[{{ $i }}][status]">
                                        @foreach(['available','booked','unavailable'] as $s)
                                            <option value="{{ $s }}" {{ ($sl->status ?? 'available') === $s ? 'selected' : '' }}>{{ __('lang.'.$s) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    @elseif($unite)
                        @foreach($unite->slots as $i => $sl)
                        <tr>
                            <td>
                                <select class="form-select form-select-sm" name="slots[{{ $i }}][day_of_week]">
                                    @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday','weekday'] as $d)
                                        <option value="{{ $d }}" {{ ($sl->day_of_week ?? '') === $d ? 'selected' : '' }}>{{ __('lang.'.$d) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @if($unite->type === 'hall')
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][full_start]" value="{{ $sl->full_start }}"></td>
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][full_end]"   value="{{ $sl->full_end }}"></td>
                            @else
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][morning_start]" value="{{ $sl->morning_start }}"></td>
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][morning_end]"   value="{{ $sl->morning_end }}"></td>
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][evening_start]" value="{{ $sl->evening_start }}"></td>
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][evening_end]"   value="{{ $sl->evening_end }}"></td>
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][full_start]"    value="{{ $sl->full_start }}"></td>
                                <td><input class="form-control form-control-sm" type="time" name="slots[{{ $i }}][full_end]"      value="{{ $sl->full_end }}"></td>
                            @endif
                            <td>
                                <select class="form-select form-select-sm" name="slots[{{ $i }}][status]">
                                    @foreach(['available','booked','unavailable'] as $s)
                                        <option value="{{ $s }}" {{ ($sl->status ?? 'available') === $s ? 'selected' : '' }}>{{ __('lang.'.$s) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Slot row template --}}
<template id="slot-tpl">
    <tr>
        <td>
            <select class="form-select form-select-sm" name="slots[__I__][day_of_week]">
                @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday','weekday'] as $d)
                    <option value="{{ $d }}">{{ __('lang.'.$d) }}</option>
                @endforeach
            </select>
        </td>
        @if(($uniteType ?? ($unite->type ?? '')) === 'hall')
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][full_start]"></td>
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][full_end]"></td>
        @else
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][morning_start]"></td>
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][morning_end]"></td>
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][evening_start]"></td>
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][evening_end]"></td>
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][full_start]"></td>
            <td><input class="form-control form-control-sm" type="time" name="slots[__I__][full_end]"></td>
        @endif
        <td>
            <select class="form-select form-select-sm" name="slots[__I__][status]">
                <option value="available">{{ __('lang.available') }}</option>
                <option value="booked">{{ __('lang.booked') }}</option>
                <option value="unavailable">{{ __('lang.unavailable') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>
