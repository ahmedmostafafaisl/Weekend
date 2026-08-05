@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Create Unite Slot')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.create_slot') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }} • {{ $unite->type }}</div>
    </div>

    <a href="{{ route('admin.unite-slots.index', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.unite-slots.store', $unite->id) }}" method="POST">
            @csrf
            @include('dashboard.admin.unite-slots.partials.form', ['slot' => null, 'unite' => $unite])
            <div class="mt-3">
                <button class="btn btn-accent">{{ __('lang.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
