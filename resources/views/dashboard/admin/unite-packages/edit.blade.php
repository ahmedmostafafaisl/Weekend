@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Edit Unite Package')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.edit_package') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <a href="{{ route('admin.unite-packages.show', [$unite->id, $package->id]) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.unite-packages.update', [$unite->id, $package->id]) }}" method="POST">
            @csrf
            @method('PUT')
            @include('dashboard.admin.unite-packages.partials.form', ['package' => $package])
            <div class="mt-3">
                <button class="btn btn-accent">{{ __('lang.update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
