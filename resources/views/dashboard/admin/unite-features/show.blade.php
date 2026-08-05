@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Feature')

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_feature') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.unite-features.index', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('unites.update'))
            <a href="{{ route('admin.unite-features.edit', [$unite->id, $feature->id]) }}" class="btn btn-outline-primary">
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
                <div class="fw-semibold">{{ $feature->name }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.status') }}</div>
                <div class="fw-semibold">{{ __('lang.'.$feature->status) }}</div>
            </div>

            <div class="col-md-12">
                <div class="text-muted small">{{ __('lang.description') }}</div>
                <div class="fw-semibold">{{ $feature->description ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
