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
        <input type="text" name="name" class="form-control"
               value="{{ old('name', $feature->name ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.status') }}</label>
        <select name="status" class="form-select">
            <option value="active" {{ old('status', $feature->status ?? 'active') === 'active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
            <option value="inactive" {{ old('status', $feature->status ?? 'active') === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
        </select>
    </div>

    <div class="col-md-12">
        <label class="form-label">{{ __('lang.description') }}</label>
        <textarea name="description" class="form-control" rows="4">{{ old('description', $feature->description ?? '') }}</textarea>
    </div>
</div>
