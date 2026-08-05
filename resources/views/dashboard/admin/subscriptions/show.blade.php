@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.subscription_details'))

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.subscription_details') }}</h4>
        <div class="text-muted">#{{ $subscription->id }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('subscriptions.update'))
            <a href="{{ route('admin.subscriptions.index', ['edit_id' => $subscription->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
            </a>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="fw-bold fs-5">{{ $subscription->user?->name ?? '—' }}</div>
                <div class="text-muted small">{{ $subscription->user?->email ?? '—' }}</div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark border">{{ $subscription->type }}</span>
                    <span class="badge {{ $subscription->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ __('lang.'.$subscription->status) }}
                    </span>
                </div>

                <hr>

                <div class="small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('lang.th_amount') }}</span>
                        <span class="fw-semibold">{{ number_format((float)$subscription->amount, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.created_at') }}</span>
                        <span class="fw-semibold">{{ optional($subscription->created_at)->format('Y-m-d') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.updated_at') }}</span>
                        <span class="fw-semibold">{{ optional($subscription->updated_at)->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.th_user') }}</div>
                        <div class="fw-semibold">{{ $subscription->user?->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.th_type') }}</div>
                        <div class="fw-semibold">{{ $subscription->type }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.th_package') }}</div>
                        <div class="fw-semibold">
                            @if($subscription->type === 'property')
                                {{ $subscription->propertyPackage?->name ?? '—' }}
                            @else
                                {{ $subscription->adPackage?->name ?? '—' }}
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.status') }}</div>
                        <div class="fw-semibold">{{ __('lang.'.$subscription->status) }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.th_amount') }}</div>
                        <div class="fw-semibold">{{ number_format((float)$subscription->amount, 2) }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.percentage') }}</div>
                        <div class="fw-semibold">{{ $subscription->percentage ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.count') }}</div>
                        <div class="fw-semibold">{{ $subscription->count ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.start_date') }}</div>
                        <div class="fw-semibold">{{ $subscription->start_date ? $subscription->start_date->format('Y-m-d') : '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.end_date') }}</div>
                        <div class="fw-semibold">{{ $subscription->end_date ? $subscription->end_date->format('Y-m-d') : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
