@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        @php
            $currentDayCategory = old('day_of_week', in_array($slot->day_of_week ?? null, ['sunday', 'monday', 'tuesday', 'wednesday'], true)
                ? 'week_day'
                : ($slot->day_of_week ?? ''));
        @endphp
        <label class="form-label">{{ __('lang.day_of_week') }}</label>
        <select name="day_of_week" class="form-select">
            @foreach(['week_day'=>__('lang.week_day'),'thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday')] as $day=>$dayLabel)
                <option value="{{ $day }}" {{ $currentDayCategory === $day ? 'selected' : '' }}>
                    {{ $dayLabel }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.status') }}</label>
        <select name="status" class="form-select">
            @foreach(['available'=>__('lang.available'),'booked'=>__('lang.booked'),'unavailable'=>__('lang.unavailable')] as $status=>$statusLabel)
                <option value="{{ $status }}" {{ old('status', $slot->status ?? 'available') === $status ? 'selected' : '' }}>
                    {{ $statusLabel }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12"><hr class="my-2"></div>
    <div class="col-12 fw-semibold small text-muted">{{ __('lang.daily_operating_hours') }}</div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.operating_day_start') }}</label>
        <input type="time" name="day_start" class="form-control" value="{{ old('day_start', $slot->day_start ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.operating_day_end') }}</label>
        <input type="time" name="day_end" class="form-control" value="{{ old('day_end', $slot->day_end ?? '') }}">
    </div>

    @if($unite->type === 'stadium')
        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_start') }}</label>
            <input type="time" name="full_start" class="form-control" value="{{ old('full_start', $slot->full_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_end') }}</label>
            <input type="time" name="full_end" class="form-control" value="{{ old('full_end', $slot->full_end ?? '') }}">
        </div>
    @else
        <div class="col-md-6">
            <label class="form-label">{{ __('lang.morning_start') }}</label>
            <input type="time" name="morning_start" class="form-control" value="{{ old('morning_start', $slot->morning_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.morning_end') }}</label>
            <input type="time" name="morning_end" class="form-control" value="{{ old('morning_end', $slot->morning_end ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.evening_start') }}</label>
            <input type="time" name="evening_start" class="form-control" value="{{ old('evening_start', $slot->evening_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.evening_end') }}</label>
            <input type="time" name="evening_end" class="form-control" value="{{ old('evening_end', $slot->evening_end ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_start') }}</label>
            <input type="time" name="full_start" class="form-control" value="{{ old('full_start', $slot->full_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_end') }}</label>
            <input type="time" name="full_end" class="form-control" value="{{ old('full_end', $slot->full_end ?? '') }}">
        </div>
    @endif

    <div class="col-12"><hr class="my-2"></div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div class="fw-semibold small text-muted">{{ __('lang.custom_availability_periods') }}</div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPeriodRow()">+ {{ __('lang.add_period') }}</button>
    </div>

    <div class="col-12">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:35%">{{ __('lang.start_time') }}</th>
                    <th style="width:35%">{{ __('lang.end_time') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
            </thead>
            <tbody id="periods-body">
                @foreach(old('periods', $slot?->periods ?? []) as $i => $period)
                    @php($period = is_array($period) ? $period : ['start_time' => $period->start_time, 'end_time' => $period->end_time, 'status' => $period->status])
                    <tr>
                        <td><input type="time" name="periods[{{ $i }}][start_time]" class="form-control form-control-sm" value="{{ $period['start_time'] }}"></td>
                        <td><input type="time" name="periods[{{ $i }}][end_time]" class="form-control form-control-sm" value="{{ $period['end_time'] }}"></td>
                        <td>
                            <select name="periods[{{ $i }}][status]" class="form-select form-select-sm">
                                <option value="available" {{ ($period['status'] ?? 'available') === 'available' ? 'selected' : '' }}>{{ __('lang.available') }}</option>
                                <option value="unavailable" {{ ($period['status'] ?? '') === 'unavailable' ? 'selected' : '' }}>{{ __('lang.unavailable') }}</option>
                            </select>
                        </td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">{{ __('lang.delete') }}</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <template id="period-tpl">
        <tr>
            <td><input type="time" name="periods[__I__][start_time]" class="form-control form-control-sm"></td>
            <td><input type="time" name="periods[__I__][end_time]" class="form-control form-control-sm"></td>
            <td>
                <select name="periods[__I__][status]" class="form-select form-select-sm">
                    <option value="available" selected>{{ __('lang.available') }}</option>
                    <option value="unavailable">{{ __('lang.unavailable') }}</option>
                </select>
            </td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">{{ __('lang.delete') }}</button></td>
        </tr>
    </template>

    <div class="col-12"><hr class="my-2"></div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.handover_buffer_minutes') }}</label>
        <input type="number" min="0" step="1" name="buffer_minutes" class="form-control" value="{{ old('buffer_minutes', $slot->buffer_minutes ?? 0) }}">
    </div>
</div>

<script>
let periodRowCounter = {{ count(old('periods', $slot?->periods ?? [])) }};

function addPeriodRow() {
    const i = periodRowCounter++;
    const tpl = document.getElementById('period-tpl').innerHTML.replaceAll('__I__', i);
    document.getElementById('periods-body').insertAdjacentHTML('beforeend', tpl);
}
</script>
