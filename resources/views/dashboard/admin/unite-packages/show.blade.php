@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Package')

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_package') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.unite-packages.index', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('unites.update'))
            <a href="{{ route('admin.unite-packages.edit', [$unite->id, $package->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
            </a>
        @endif
    </div>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.name') }}</div>
                <div class="fw-semibold">{{ $package->name ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.price') }}</div>
                <div class="fw-semibold">{{ $package->price ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.men_capacity') }}</div>
                <div class="fw-semibold">{{ $package->men_capacity ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.women_capacity') }}</div>
                <div class="fw-semibold">{{ $package->women_capacity ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
