@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.service_details'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $service->name }}</h4>
        <div class="text-muted">{{ $service->group?->label ?? '—' }}</div>
    </div>

    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.name') }}</div>
                <div class="fw-semibold">{{ $service->name }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.group') }}</div>
                <div class="fw-semibold">{{ $service->group?->label ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.status') }}</div>
                <div class="fw-semibold">{{ __('lang.'.$service->status) }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.sort_order') }}</div>
                <div class="fw-semibold">{{ $service->sort_order }}</div>
            </div>

            <div class="col-12">
                <div class="text-muted small">{{ __('lang.description') }}</div>
                <div class="fw-semibold">{{ $service->description ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.created_at') }}</div>
                <div class="fw-semibold">{{ optional($service->created_at)->format('Y-m-d H:i') }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.updated_at') }}</div>
                <div class="fw-semibold">{{ optional($service->updated_at)->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
