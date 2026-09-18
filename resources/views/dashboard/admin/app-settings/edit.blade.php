@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | ' . __('lang.edit_setting'))

@section('content')

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.app-settings.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; {{ __('lang.back') }}
        </a>
        <h4 class="fw-bold mb-0">{{ __('lang.edit_setting') }}</h4>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card card-soft shadow-sm" style="max-width:640px">
        <div class="card-body">
            <form action="{{ route('admin.app-settings.update', $setting) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Key is shown but immutable after creation --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('lang.key') }}</label>
                    <input type="text" class="form-control font-monospace bg-light"
                           value="{{ $setting->key }}" disabled>
                    <div class="form-text">{{ __('lang.key_immutable_hint') }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        {{ __('lang.label_en') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="label_en"
                           class="form-control @error('label_en') is-invalid @enderror"
                           value="{{ old('label_en', $setting->label_en) }}" required>
                    @error('label_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        {{ __('lang.label_ar') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="label_ar" dir="rtl"
                           class="form-control @error('label_ar') is-invalid @enderror"
                           value="{{ old('label_ar', $setting->label_ar) }}" required>
                    @error('label_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="is_active" value="1" id="isActive"
                               {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">
                            {{ __('lang.enabled') }}
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ __('lang.save') }}
                    </button>
                    <a href="{{ route('admin.app-settings.index') }}" class="btn btn-outline-secondary">
                        {{ __('lang.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
