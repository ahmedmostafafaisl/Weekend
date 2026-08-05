@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.insurance_policy_details'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $insurancePolicy->name }}</h4>
        <div class="text-muted">{{ $insurancePolicy->description ?? '—' }}</div>
    </div>

    <a href="{{ route('admin.insurance_policies.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.name') }}</div>
                <div class="fw-semibold">{{ $insurancePolicy->name }}</div>
            </div>


            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.description') }}</div>
                <div class="fw-semibold">{{ $insurancePolicy->description ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.created_at') }}</div>
                <div class="fw-semibold">{{ optional($insurancePolicy->created_at)->format('Y-m-d H:i') }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.updated_at') }}</div>
                <div class="fw-semibold">{{ optional($insurancePolicy->updated_at)->format('Y-m-d H:i') }}</div>
            </div>
    </div>
</div>
@endsection
