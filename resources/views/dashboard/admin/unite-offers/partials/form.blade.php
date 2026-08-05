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
        <label class="form-label">{{ __('lang.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $offer->name ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.status') }}</label>
        <select name="status" class="form-select">
            <option value="active" {{ old('status', $offer->status ?? 'active') === 'active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
            <option value="inactive" {{ old('status', $offer->status ?? 'active') === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.start') }}</label>
        <input type="date" name="start" class="form-control" value="{{ old('start', $offer->start ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.end') }}</label>
        <input type="date" name="end" class="form-control" value="{{ old('end', $offer->end ?? '') }}">
    </div>

    @if($unite->type === 'stadium')
        <div class="col-md-6">
            <label class="form-label">{{ __('lang.day_rate') }}</label>
            <input type="number" step="0.01" name="day_hour_price" class="form-control" value="{{ old('day_hour_price', $offer->day_hour_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('lang.night_rate') }}</label>
            <input type="number" step="0.01" name="night_hour_price" class="form-control" value="{{ old('night_hour_price', $offer->night_hour_price ?? '') }}">
        </div>
    @else
        <div class="col-md-4">
            <label class="form-label">{{ __('lang.morning_price') }}</label>
            <input type="number" step="0.01" name="morning_price" class="form-control" value="{{ old('morning_price', $offer->morning_price ?? '') }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">{{ __('lang.evening_price') }}</label>
            <input type="number" step="0.01" name="evening_price" class="form-control" value="{{ old('evening_price', $offer->evening_price ?? '') }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">{{ __('lang.full_day_price') }}</label>
            <input type="number" step="0.01" name="full_day_price" class="form-control" value="{{ old('full_day_price', $offer->full_day_price ?? '') }}">
        </div>
    @endif
</div>
