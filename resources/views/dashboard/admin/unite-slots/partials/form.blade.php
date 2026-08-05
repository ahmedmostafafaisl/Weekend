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
        <label class="form-label">{{ __('lang.slot_date') }}</label>
        <input type="date" name="slot_date" class="form-control" value="{{ old('slot_date', optional($slot->slot_date ?? null)->format('Y-m-d')) }}">
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
</div>
