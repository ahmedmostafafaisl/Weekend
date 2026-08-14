@php
    // All 4 venue types now share one UniteDetail relation — no branching needed.
    $type   = $type ?? optional($unite)->type ?? '';
    $detail = $unite?->detail;
@endphp

@if($type === 'stadium')
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.stadium_details') }}</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.width') }} <span class="text-danger">*</span></label>
                <input class="form-control" name="stadium[width]" value="{{ old('stadium.width', $detail->width ?? '') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.length') }} <span class="text-danger">*</span></label>
                <input class="form-control" name="stadium[length]" value="{{ old('stadium.length', $detail->length ?? '') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.category') }} <span class="text-danger">*</span></label>
                <input class="form-control" name="stadium[customize_Category]" value="{{ old('stadium.customize_Category', $detail->customize_Category ?? '') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.place_type') }} <span class="text-danger">*</span></label>
                <input class="form-control" name="stadium[customize_Place]" value="{{ old('stadium.customize_Place', $detail->customize_Place ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('lang.amenities') }}</label>
                <input class="form-control" name="stadium[amenities]" value="{{ old('stadium.amenities', $detail->amenities ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.cafeteria') }}</label>
                <select class="form-select" name="stadium[cafeteria]">
                    <option value="0" {{ old('stadium.cafeteria', $detail->cafeteria ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('stadium.cafeteria', $detail->cafeteria ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
        </div>
    </div>
</div>

@elseif($type === 'hall')
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.hall_details') }}</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.max_capacity') }}</label>
                <input class="form-control" name="hall[max_capacity]" type="number" value="{{ old('hall.max_capacity', $detail->max_capacity ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.kusha') }}</label>
                <select class="form-select" name="hall[kusha]">
                    <option value="0" {{ old('hall.kusha', $detail->kusha ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('hall.kusha', $detail->kusha ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.buffet') }}</label>
                <select class="form-select" name="hall[buffet]">
                    <option value="0" {{ old('hall.buffet', $detail->buffet ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('hall.buffet', $detail->buffet ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.buffet_details') }}</label>
                <input class="form-control" name="hall[buffet_details]" value="{{ old('hall.buffet_details', $detail->buffet_details ?? '') }}">
            </div>

            {{-- Women section --}}
            <div class="col-12"><div class="small fw-semibold text-muted border-bottom pb-1">{{ __('lang.women_section') }}</div></div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.seating') }}</label>
                <select class="form-select" name="hall[women_seating]">
                    <option value="0" {{ old('hall.women_seating', $detail->women_seating ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('hall.women_seating', $detail->women_seating ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.capacity') }}</label>
                <input class="form-control" name="hall[women_seating_capacity]" type="number" value="{{ old('hall.women_seating_capacity', $detail->women_seating_capacity ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.tables') }}</label>
                <input class="form-control" name="hall[women_tables_count]" type="number" value="{{ old('hall.women_tables_count', $detail->women_tables_count ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.chairs') }}</label>
                <input class="form-control" name="hall[women_chairs_count]" type="number" value="{{ old('hall.women_chairs_count', $detail->women_chairs_count ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.women_buffet') }}</label>
                <select class="form-select" name="hall[women_buffet]">
                    <option value="0" {{ old('hall.women_buffet', $detail->women_buffet ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('hall.women_buffet', $detail->women_buffet ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('lang.buffet_details') }}</label>
                <input class="form-control" name="hall[women_buffet_details]" value="{{ old('hall.women_buffet_details', $detail->women_buffet_details ?? '') }}">
            </div>
            <div class="col-md-9">
                <label class="form-label">{{ __('lang.seating_details') }}</label>
                <input class="form-control" name="hall[women_seating_details]" value="{{ old('hall.women_seating_details', $detail->women_seating_details ?? '') }}">
            </div>

            {{-- Men section --}}
            <div class="col-12"><div class="small fw-semibold text-muted border-bottom pb-1">{{ __('lang.men_section') }}</div></div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.seating_available') }}</label>
                <select class="form-select" name="hall[men_seating_available]">
                    <option value="0" {{ old('hall.men_seating_available', $detail->men_seating_available ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('hall.men_seating_available', $detail->men_seating_available ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.capacity') }}</label>
                <input class="form-control" name="hall[men_seating_capacity]" type="number" value="{{ old('hall.men_seating_capacity', $detail->men_seating_capacity ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.tables') }}</label>
                <input class="form-control" name="hall[men_tables_count]" type="number" value="{{ old('hall.men_tables_count', $detail->men_tables_count ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.chairs') }}</label>
                <input class="form-control" name="hall[men_chairs_count]" type="number" value="{{ old('hall.men_chairs_count', $detail->men_chairs_count ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('lang.men_buffet') }}</label>
                <select class="form-select" name="hall[men_buffet]">
                    <option value="0" {{ old('hall.men_buffet', $detail->men_buffet ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('hall.men_buffet', $detail->men_buffet ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('lang.buffet_details') }}</label>
                <input class="form-control" name="hall[men_buffet_details]" value="{{ old('hall.men_buffet_details', $detail->men_buffet_details ?? '') }}">
            </div>
            <div class="col-md-9">
                <label class="form-label">{{ __('lang.seating_details') }}</label>
                <input class="form-control" name="hall[men_seating_details]" value="{{ old('hall.men_seating_details', $detail->men_seating_details ?? '') }}">
            </div>

            {{-- Timing — full-day only for halls (matches the same rule
                 already applied to the booking slots and pricing sections;
                 this "Timing" block is separate metadata on unite_details,
                 not the unite_slots booking table, but the same business
                 rule applies: halls don't have a morning/evening concept). --}}
            <div class="col-12"><div class="small fw-semibold text-muted border-bottom pb-1">{{ __('lang.timing') }}</div></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.full_day_start_time') }}</label><input class="form-control" name="hall[full_day_start_time]" type="time" value="{{ old('hall.full_day_start_time', $detail->full_day_start_time ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.full_day_end_time') }}</label><input class="form-control" name="hall[full_day_end_time]" type="time" value="{{ old('hall.full_day_end_time', $detail->full_day_end_time ?? '') }}"></div>
        </div>
    </div>
</div>

@elseif($type === 'camp')
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.camp_details') }}</h6>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Width</label><input class="form-control" name="camp[width]" value="{{ old('camp.width', $detail->width ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">Length</label><input class="form-control" name="camp[length]" value="{{ old('camp.length', $detail->length ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.seating') }} {{ __('lang.capacity') }}</label><input class="form-control" name="camp[seating_capacity]" type="number" value="{{ old('camp.seating_capacity', $detail->seating_capacity ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.bathroom_number') }}</label><input class="form-control" name="camp[bathroom_number]" type="number" value="{{ old('camp.bathroom_number', $detail->bathroom_number ?? '') }}"></div>
            <div class="col-md-2">
                <label class="form-label">{{ __('lang.television') }}</label>
                <select class="form-select" name="camp[television]">
                    <option value="0" {{ old('camp.television', $detail->television ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('camp.television', $detail->television ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('lang.fireplace') }}</label>
                <select class="form-select" name="camp[fireplace]">
                    <option value="0" {{ old('camp.fireplace', $detail->fireplace ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('camp.fireplace', $detail->fireplace ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('lang.bathroom') }}</label>
                <select class="form-select" name="camp[bathroom]">
                    <option value="0" {{ old('camp.bathroom', $detail->bathroom ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('camp.bathroom', $detail->bathroom ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            <div class="col-12"><div class="small fw-semibold text-muted border-bottom pb-1">{{ __('lang.timing') }}</div></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.morning_start_time') }}</label><input class="form-control" name="camp[morning_start_time]" type="time" value="{{ old('camp.morning_start_time', $detail->morning_start_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.morning_end_time') }}</label><input class="form-control" name="camp[morning_end_time]" type="time" value="{{ old('camp.morning_end_time', $detail->morning_end_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.evening_start_time') }}</label><input class="form-control" name="camp[evening_start_time]" type="time" value="{{ old('camp.evening_start_time', $detail->evening_start_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.evening_end_time') }}</label><input class="form-control" name="camp[evening_end_time]" type="time" value="{{ old('camp.evening_end_time', $detail->evening_end_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.full_day_start_time') }}</label><input class="form-control" name="camp[full_day_start_time]" type="time" value="{{ old('camp.full_day_start_time', $detail->full_day_start_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.full_day_end_time') }}</label><input class="form-control" name="camp[full_day_end_time]" type="time" value="{{ old('camp.full_day_end_time', $detail->full_day_end_time ?? '') }}"></div>
        </div>
    </div>
</div>

@elseif($type === 'lounge')
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.lounge_details') }}</h6>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">{{ __('lang.area') }} <span class="text-danger">*</span></label><input class="form-control" name="lounge[area]" type="number" step="0.01" value="{{ old('lounge.area', $detail->area ?? '') }}" required></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.place_type') }} <span class="text-danger">*</span></label><input class="form-control" name="lounge[customize_Place]" value="{{ old('lounge.customize_Place', $detail->customize_Place ?? '') }}" required></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.bedroom_number') }}</label><input class="form-control" name="lounge[bedroom_number]" type="number" value="{{ old('lounge.bedroom_number', $detail->bedroom_number ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.single_beds') }}</label><input class="form-control" name="lounge[single_bed]" type="number" value="{{ old('lounge.single_bed', $detail->single_bed ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.double_beds') }}</label><input class="form-control" name="lounge[big_bed]" type="number" value="{{ old('lounge.big_bed', $detail->big_bed ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.bathrooms') }}</label><input class="form-control" name="lounge[bathroom_number]" type="number" value="{{ old('lounge.bathroom_number', $detail->bathroom_number ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('lang.council_number') }}</label><input class="form-control" id="councilNumberInput" name="lounge[council_number]" type="number" min="0" value="{{ old('lounge.council_number', $detail->council_number ?? '') }}"></div>
            <div class="col-md-9">
                <label class="form-label">{{ __('lang.councils') }}</label>
                <div id="councilsFields" class="d-flex flex-wrap gap-2"></div>
                <div class="form-text">{{ __('lang.councils_auto_sync_hint') }}</div>
            </div>
            @foreach([['bedroom',__('lang.bedroom')],['bathroom',__('lang.bathroom')],['kitchen',__('lang.kitchen')],['pool',__('lang.pool')],['council',__('lang.council')]] as [$field,$label])
            <div class="col-md-2">
                <label class="form-label">{{ $label }}</label>
                <select class="form-select" name="lounge[{{ $field }}]">
                    <option value="0" {{ old('lounge.'.$field, $detail->$field ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                    <option value="1" {{ old('lounge.'.$field, $detail->$field ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                </select>
            </div>
            @endforeach
            <div class="col-12"><div class="small fw-semibold text-muted border-bottom pb-1">{{ __('lang.timing') }}</div></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.morning_start_time') }}</label><input class="form-control" name="lounge[morning_start_time]" type="time" value="{{ old('lounge.morning_start_time', $detail->morning_start_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.morning_end_time') }}</label><input class="form-control" name="lounge[morning_end_time]" type="time" value="{{ old('lounge.morning_end_time', $detail->morning_end_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.evening_start_time') }}</label><input class="form-control" name="lounge[evening_start_time]" type="time" value="{{ old('lounge.evening_start_time', $detail->evening_start_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.evening_end_time') }}</label><input class="form-control" name="lounge[evening_end_time]" type="time" value="{{ old('lounge.evening_end_time', $detail->evening_end_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.full_day_start_time') }}</label><input class="form-control" name="lounge[full_day_start_time]" type="time" value="{{ old('lounge.full_day_start_time', $detail->full_day_start_time ?? '') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('lang.full_day_end_time') }}</label><input class="form-control" name="lounge[full_day_end_time]" type="time" value="{{ old('lounge.full_day_end_time', $detail->full_day_end_time ?? '') }}"></div>
        </div>
    </div>
</div>
@endif

@push('js')
<script>
(function () {
    const councilNumberInput = document.getElementById('councilNumberInput');
    if (!councilNumberInput) {
        return;
    }

    const container = document.getElementById('councilsFields');
    const existing = @json(
        old('lounge.councils', $unite?->councils->pluck('type')->values() ?? [])
    );

    function renderCouncilInputs() {
        const count = Math.max(parseInt(councilNumberInput.value, 10) || 0, 0);
        const current = Array.from(container.querySelectorAll('input')).map(i => i.value);

        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.style.width = '160px';
            input.name = 'lounge[councils][]';
            input.placeholder = @json(__('lang.council_type'));
            input.value = current[i] ?? existing[i] ?? '';
            container.appendChild(input);
        }
    }

    councilNumberInput.addEventListener('input', renderCouncilInputs);
    renderCouncilInputs();
})();
</script>
@endpush
