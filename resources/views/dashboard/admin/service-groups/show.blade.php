@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.service_group'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $service_group->label }}</h4>
        <div class="text-muted">{{ $service_group->name }}</div>
    </div>
    <a href="{{ route('admin.service-groups.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm mb-3">
    <div class="card-body">
        <div><strong>{{ __('lang.status') }}:</strong> {{ $service_group->status }}</div>
        <div><strong>{{ __('lang.sort_order') }}:</strong> {{ $service_group->sort_order }}</div>
    </div>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">{{ __('lang.services') }}</h5>
        <ul class="mb-0">
            @forelse($service_group->services as $service)
                <li>{{ $service->name }}</li>
            @empty
                <li class="text-muted">{{ __('lang.no_data') }}</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
