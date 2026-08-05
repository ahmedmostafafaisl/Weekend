@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.property_package_details'))

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.property_package_details') }}</h4>
        <div class="text-muted">#{{ $package->id }} • {{ $package->name }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.property-packages.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('property_packages.update'))
            <a href="{{ route('admin.property-packages.index', ['edit_id' => $package->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
            </a>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                @if($package->image)
                    <img src="{{ asset('storage/'.$package->image) }}" class="img-fluid rounded-3 mb-3" alt="package image">
                @endif

                <div class="fw-bold fs-5">{{ $package->name }}</div>
                <div class="text-muted small">{{ $package->description ?: '—' }}</div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark border">{{ $package->type }}</span>
                    <span class="badge {{ $package->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ __('lang.'.$package->status) }}
                    </span>
                </div>

                <hr>

                <div class="small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('lang.price') }}</span>
                        <span class="fw-semibold">{{ $package->price ? number_format($package->price, 2) : '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.created_at') }}</span>
                        <span class="fw-semibold">{{ optional($package->created_at)->format('Y-m-d') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.updated_at') }}</span>
                        <span class="fw-semibold">{{ optional($package->updated_at)->format('Y-m-d') }}</span>
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
                        <div class="text-muted small">{{ __('lang.name') }}</div>
                        <div class="fw-semibold">{{ $package->name }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.type') }}</div>
                        <div class="fw-semibold">{{ $package->type }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.duration') }}</div>
                        <div class="fw-semibold">{{ $package->duration ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.percentage') }}</div>
                        <div class="fw-semibold">{{ $package->percentage ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.price') }}</div>
                        <div class="fw-semibold">{{ $package->price ? number_format($package->price, 2) : '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.status') }}</div>
                        <div class="fw-semibold">{{ __('lang.'.$package->status) }}</div>
                    </div>

                    <div class="col-md-12">
                        <div class="text-muted small">{{ __('lang.description') }}</div>
                        <div class="fw-semibold">{{ $package->description ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
