@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | ' . ($policy ? __('lang.edit_policy') : __('lang.new_transfer_policy')))
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.transfers.policy.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <h4 class="fw-bold mb-0">{{ $policy ? __('lang.edit_policy').' — '.$policy->title : __('lang.new_transfer_policy') }}</h4>
</div>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card card-soft shadow-sm" style="max-width:680px">
    <div class="card-body">
        <form action="{{ $policy ? route('admin.transfers.policy.update', $policy) : route('admin.transfers.policy.store') }}" method="POST">
            @csrf @if($policy) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.title') }} <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $policy->title ?? '') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.description') }}</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $policy->description ?? '') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.transfer_days') }} <span class="text-danger">*</span></label>
                <input type="number" name="transfer_days" class="form-control" min="1" value="{{ old('transfer_days', $policy->transfer_days ?? 7) }}" required>
                <div class="text-muted small mt-1">{{ __('lang.transfer_days_help') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.transfer_methods') }} <span class="text-danger">*</span></label>
                @foreach(['bank_transfer' => __('lang.bank_transfer'), 'cash' => __('lang.cash'), 'check' => __('lang.check'), 'digital_wallet' => __('lang.digital_wallet')] as $val => $label)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="transfer_methods[]"
                           value="{{ $val }}" id="tm_{{ $val }}"
                           {{ in_array($val, old('transfer_methods', $policy->transfer_methods ?? [])) ? 'checked' : '' }}>
                    <label class="form-check-label" for="tm_{{ $val }}">{{ $label }}</label>
                </div>
                @endforeach
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">{{ __('lang.tax_rate') }} (%) <span class="text-danger">*</span></label>
                    <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" max="100" value="{{ old('tax_rate', $policy->tax_rate ?? 15) }}" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">{{ __('lang.platform_fee') }} (%) <span class="text-danger">*</span></label>
                    <input type="number" name="platform_fee_rate" class="form-control" step="0.01" min="0" max="100" value="{{ old('platform_fee_rate', $policy->platform_fee_rate ?? 5) }}" required>
                </div>
            </div>
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $policy->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('lang.policy_active_help') }}</label>
                </div>
            </div>
            <button type="submit" class="btn btn-accent">{{ $button }}</button>
            <a href="{{ route('admin.transfers.policy.index') }}" class="btn btn-secondary ms-2">{{ __('lang.cancel') }}</a>
        </form>
    </div>
</div>
@endsection
