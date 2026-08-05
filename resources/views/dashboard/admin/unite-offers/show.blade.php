@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Offer')

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_offer') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.unite-offers.index', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('unites.update'))
            <a href="{{ route('admin.unite-offers.edit', [$unite->id, $offer->id]) }}" class="btn btn-outline-primary">
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
                <div class="fw-semibold">{{ $offer->name ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.status') }}</div>
                <div class="fw-semibold">{{ __('lang.'.$offer->status) }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.start') }}</div>
                <div class="fw-semibold">{{ $offer->start ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.end') }}</div>
                <div class="fw-semibold">{{ $offer->end ?: '—' }}</div>
            </div>

            @if($unite->type === 'stadium')
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('lang.day_rate') }}</div>
                    <div class="fw-semibold">{{ $offer->day_hour_price ?? '—' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">{{ __('lang.night_rate') }}</div>
                    <div class="fw-semibold">{{ $offer->night_hour_price ?? '—' }}</div>
                </div>
            @else
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('lang.morning_price') }}</div>
                    <div class="fw-semibold">{{ $offer->morning_price ?? '—' }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">{{ __('lang.evening_price') }}</div>
                    <div class="fw-semibold">{{ $offer->evening_price ?? '—' }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">{{ __('lang.full_day_price') }}</div>
                    <div class="fw-semibold">{{ $offer->full_day_price ?? '—' }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
