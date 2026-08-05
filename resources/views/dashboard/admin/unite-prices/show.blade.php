@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Price')

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_price') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }} • {{ $unite->type }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.unite-prices.index', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('unites.update'))
            <a href="{{ route('admin.unite-prices.edit', [$unite->id, $price->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
            </a>
        @endif
    </div>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.th_day') }}</div>
                <div class="fw-semibold">{{ $price->day }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.price') }}</div>
                <div class="fw-semibold">{{ $price->price ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">{{ __('lang.morning_price') }}</div>
                <div class="fw-semibold">{{ $price->morning_price ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">{{ __('lang.evening_price') }}</div>
                <div class="fw-semibold">{{ $price->evening_price ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">{{ __('lang.full_price') }}</div>
                <div class="fw-semibold">{{ $price->full_price ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
