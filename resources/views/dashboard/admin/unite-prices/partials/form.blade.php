@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- ── Standard pricing ─────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label fw-semibold">{{ __('lang.day_type') }} <span class="text-danger">*</span></label>
        <select name="day" class="form-select">
            @foreach(['thursday' => __('lang.thursday'), 'friday' => __('lang.friday'), 'saturday' => __('lang.saturday'), 'week_day' => __('lang.weekday').' (Sun–Wed)'] as $val => $label)
                <option value="{{ $val }}" {{ old('day', $price->day ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if($unite->type === 'stadium')
        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ __('lang.full_day_price') }} (SAR)</label>
            <input type="number" step="0.01" min="0" name="price" class="form-control"
                   value="{{ old('price', $price->price ?? '') }}">
        </div>
    @else
        <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('lang.morning_price') }} (SAR)</label>
            <input type="number" step="0.01" min="0" name="morning_price" class="form-control"
                   value="{{ old('morning_price', $price->morning_price ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('lang.evening_price') }} (SAR)</label>
            <input type="number" step="0.01" min="0" name="evening_price" class="form-control"
                   value="{{ old('evening_price', $price->evening_price ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('lang.full_day_price') }} (SAR)</label>
            <input type="number" step="0.01" min="0" name="full_price" class="form-control"
                   value="{{ old('full_price', $price->full_price ?? '') }}">
        </div>
    @endif
</div>

{{-- ── Hourly booking toggle ────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm rounded-3 mb-0" style="background:#f8f7ff">
    <div class="card-body">
        <div class="form-check form-switch mb-3" style="font-size:15px">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="hourly_enabled" value="1" id="hourlySwitch"
                   onchange="toggleHourly(this.checked)"
                   {{ old('hourly_enabled', $price->hourly_enabled ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="hourlySwitch">
                ⏱ {{ __('lang.hourly_booking') }}
            </label>
            <div class="text-muted small mt-1">
                {{ __('lang.hourly_booking_help') }}
            </div>
        </div>

        <div id="hourlyFields" style="{{ old('hourly_enabled', $price->hourly_enabled ?? false) ? '' : 'display:none' }}">

            {{-- Day / Night rates --}}
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        ☀️ {{ __('lang.day_rate') }} (SAR/hr)
                        <span class="text-danger">*</span>
                        <span class="text-muted small">— {{ __('lang.day_rate_window_help') }}</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" name="day_hour_price"
                               id="dayHourPrice" class="form-control"
                               value="{{ old('day_hour_price', $price->day_hour_price ?? '') }}"
                               placeholder="e.g. 150.00"
                               oninput="updatePreview()">
                        <span class="input-group-text">SAR/hr</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        🌙 {{ __('lang.night_rate') }} (SAR/hr)
                        <span class="text-muted small">— {{ __('lang.night_rate_help') }}</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" name="night_hour_price"
                               id="nightHourPrice" class="form-control"
                               value="{{ old('night_hour_price', $price->night_hour_price ?? '') }}"
                               placeholder="e.g. 200.00"
                               oninput="updatePreview()">
                        <span class="input-group-text">SAR/hr</span>
                    </div>
                </div>
            </div>

            {{-- Day / Night boundary times --}}
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">☀️ {{ __('lang.day_start') }}</label>
                    <input type="time" name="day_start" class="form-control"
                           id="dayStart"
                           value="{{ old('day_start', $price->day_start ?? '06:00') }}"
                           oninput="updatePreview()">
                    <div class="text-muted small mt-1">{{ __('lang.day_start_help') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">🌙 {{ __('lang.day_end') }}</label>
                    <input type="time" name="day_end" class="form-control"
                           id="dayEnd"
                           value="{{ old('day_end', $price->day_end ?? '18:00') }}"
                           oninput="updatePreview()">
                    <div class="text-muted small mt-1">{{ __('lang.day_end_help') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">⏱ {{ __('lang.min_booking') }}</label>
                    <div class="input-group">
                        <input type="number" min="15" max="1440" step="15"
                               name="min_booking_minutes" class="form-control"
                               value="{{ old('min_booking_minutes', $price->min_booking_minutes ?? 60) }}">
                        <span class="input-group-text">min</span>
                    </div>
                    <div class="text-muted small mt-1">{{ __('lang.min_booking_help') }}</div>
                </div>
            </div>

            {{-- Live price preview --}}
            <div id="hourlyPreview" class="alert alert-light border py-2 px-3 small mb-0" style="display:none">
                <strong>{{ __('lang.preview') }}:</strong> <span id="previewText"></span>
            </div>

        </div>{{-- /hourlyFields --}}
    </div>
</div>

<script>
function toggleHourly(on) {
    document.getElementById('hourlyFields').style.display = on ? '' : 'none';
    if (on) updatePreview();
}

function updatePreview() {
    const dayRate   = parseFloat(document.getElementById('dayHourPrice').value)   || 0;
    const nightRate = parseFloat(document.getElementById('nightHourPrice').value) || dayRate;
    const dayStart  = document.getElementById('dayStart').value  || '06:00';
    const dayEnd    = document.getElementById('dayEnd').value    || '18:00';

    if (dayRate <= 0) {
        document.getElementById('hourlyPreview').style.display = 'none';
        return;
    }

    // Example: 3h during day + 1h during night
    const exampleDayCost   = (3 * dayRate).toFixed(2);
    const exampleNightCost = (1 * nightRate).toFixed(2);
    const exampleTotal     = (parseFloat(exampleDayCost) + parseFloat(exampleNightCost)).toFixed(2);

    document.getElementById('previewText').innerHTML =
        `Daytime (${dayStart}–${dayEnd}): <strong>${dayRate.toFixed(2)} SAR/hr</strong> &nbsp;|&nbsp; `
      + `Nighttime: <strong>${nightRate.toFixed(2)} SAR/hr</strong> &nbsp;|&nbsp; `
      + `Example: 3h day + 1h night = <strong>${exampleTotal} SAR</strong>`;
    document.getElementById('hourlyPreview').style.display = '';
}

// Init on page load
document.addEventListener('DOMContentLoaded', function() {
    const enabled = {{ old('hourly_enabled', ($price->hourly_enabled ?? false) ? 'true' : 'false') }};
    if (enabled) { toggleHourly(true); updatePreview(); }
});
</script>
