@php $editing = $editing ?? false; @endphp

@if($errors->any())
    <div class="alert alert-danger small">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">{{ __('lang.code') }} <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control text-uppercase"
               value="{{ old('code') }}" placeholder="{{ __('lang.code_example_placeholder') }}" required
               style="font-family:monospace;letter-spacing:.05em">
        <div class="form-text">{{ __('lang.code_help') }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">{{ __('lang.description') }}</label>
        <input type="text" name="description" class="form-control"
               value="{{ old('description') }}" placeholder="{{ __('lang.internal_note_placeholder') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.discount_type') }} <span class="text-danger">*</span></label>
        <select name="discount_type" class="form-select" id="{{ $editing ? 'edit' : 'create' }}_discount_type" required>
            <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>{{ __('lang.percentage') }} ({{ __('lang.percentage_symbol') }})</option>
            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>{{ __('lang.fixed_amount') }}</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.discount_value') }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" name="discount_value" class="form-control"
                   value="{{ old('discount_value') }}" step="0.01" min="0.01" required>
            <span class="input-group-text" id="{{ $editing ? 'edit' : 'create' }}_discount_suffix">%</span>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.max_discount_cap') }}</label>
        <input type="number" name="max_discount" class="form-control"
               value="{{ old('max_discount') }}" step="0.01" min="0" placeholder="{{ __('lang.max_discount_help') }}">
        <div class="form-text">{{ __('lang.max_discount_help') }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.min_amount') }}</label>
        <input type="number" name="min_amount" class="form-control"
               value="{{ old('min_amount') }}" step="0.01" min="0" placeholder="{{ __('lang.optional_placeholder') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.max_uses_total') }}</label>
        <input type="number" name="max_uses" class="form-control"
               value="{{ old('max_uses') }}" step="1" min="1" placeholder="{{ __('lang.unlimited_placeholder') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.max_uses_per_user') }}</label>
        <input type="number" name="max_uses_per_user" class="form-control"
               value="{{ old('max_uses_per_user') }}" step="1" min="1" placeholder="{{ __('lang.unlimited_placeholder') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.starts_at') }}</label>
        <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('lang.expires_at') }}</label>
        <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="{{ $editing ? 'edit' : 'create' }}_is_active"
                   {{ old('is_active', '1') ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="{{ $editing ? 'edit' : 'create' }}_is_active">
                {{ __('lang.active') }}
            </label>
        </div>
    </div>
</div>

<script>
(function() {
    const prefix = '{{ $editing ? 'edit' : 'create' }}';
    const sel    = document.getElementById(prefix + '_discount_type');
    const suffix = document.getElementById(prefix + '_discount_suffix');
    if (sel && suffix) {
        sel.addEventListener('change', function() {
            suffix.textContent = this.value === 'percentage' ? '%' : 'SAR';
        });
        suffix.textContent = sel.value === 'percentage' ? '%' : 'SAR';
    }
})();
</script>
