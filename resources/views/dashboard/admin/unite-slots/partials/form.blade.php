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

    {{-- day_of_week: only two valid values (week_day = Sun–Thu+Sat, friday) --}}
    <div class="col-md-6">
        <label class="form-label fw-semibold">
            {{ __('lang.day_of_week') }} <span class="text-danger">*</span>
        </label>
        <select name="day_of_week" class="form-select" required>
            @foreach(['week_day' => __('lang.week_day'), 'friday' => __('lang.friday')] as $val => $lbl)
                <option value="{{ $val }}"
                    {{ old('day_of_week', $slot->day_of_week ?? '') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('lang.day_of_week_help') }}</div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">{{ __('lang.status') }} <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            @foreach(['available' => __('lang.available'), 'unavailable' => __('lang.unavailable')] as $status => $statusLabel)
                <option value="{{ $status }}"
                    {{ old('status', $slot->status ?? 'available') === $status ? 'selected' : '' }}>
                    {{ $statusLabel }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Availability window: the primary operating hours --}}
    <div class="col-12">
        <hr class="my-1">
        <div class="fw-semibold mb-1">{{ __('lang.availability_window') }}</div>
        <div class="form-text mb-2">{{ __('lang.availability_window_help') }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('lang.availability_window_start') }}</label>
        <input type="time" name="day_start" class="form-control"
               value="{{ old('day_start', $slot->day_start ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('lang.availability_window_end') }}</label>
        <input type="time" name="day_end" class="form-control"
               value="{{ old('day_end', $slot->day_end ?? '') }}">
        <div class="form-text">{{ __('lang.availability_window_overnight_hint') }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('lang.buffer_minutes') }}</label>
        <input type="number" name="buffer_minutes" class="form-control" min="0"
               value="{{ old('buffer_minutes', $slot->buffer_minutes ?? 0) }}">
        <div class="form-text">{{ __('lang.buffer_minutes_help') }}</div>
    </div>

    {{-- Period-specific sub-windows --}}
    @if($unite->type === 'stadium')

        <div class="col-12">
            <hr class="my-1">
            <div class="fw-semibold mb-1">{{ __('lang.hourly_window') }}</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_start') }} <span class="text-danger">*</span></label>
            <input type="time" name="full_start" class="form-control" required
                   value="{{ old('full_start', $slot->full_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_end') }} <span class="text-danger">*</span></label>
            <input type="time" name="full_end" class="form-control" required
                   value="{{ old('full_end', $slot->full_end ?? '') }}">
            <div class="form-text">{{ __('lang.overnight_hint') }}</div>
        </div>

    @else

        <div class="col-12">
            <hr class="my-1">
            <div class="fw-semibold mb-1">{{ __('lang.period_windows') }}</div>
            <div class="form-text mb-2">{{ __('lang.period_windows_help') }}</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.morning_start') }}</label>
            <input type="time" name="morning_start" class="form-control"
                   value="{{ old('morning_start', $slot->morning_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.morning_end') }}</label>
            <input type="time" name="morning_end" class="form-control"
                   value="{{ old('morning_end', $slot->morning_end ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.evening_start') }}</label>
            <input type="time" name="evening_start" class="form-control"
                   value="{{ old('evening_start', $slot->evening_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.evening_end') }}</label>
            <input type="time" name="evening_end" class="form-control"
                   value="{{ old('evening_end', $slot->evening_end ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_start') }}</label>
            <input type="time" name="full_start" class="form-control"
                   value="{{ old('full_start', $slot->full_start ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.full_end') }}</label>
            <input type="time" name="full_end" class="form-control"
                   value="{{ old('full_end', $slot->full_end ?? '') }}">
            <div class="form-text">{{ __('lang.overnight_hint') }}</div>
        </div>

    @endif

</div>
