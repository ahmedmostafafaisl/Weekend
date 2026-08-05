@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Edit Unite Slot')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.edit_slot') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }} • {{ $unite->type }}</div>
    </div>

    <a href="{{ route('admin.unite-slots.show', [$unite->id, $slot->id]) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.unite-slots.update', [$unite->id, $slot->id]) }}" method="POST">
            @csrf
            @method('PUT')
            @include('dashboard.admin.unite-slots.partials.form', ['slot' => $slot, 'unite' => $unite])
            <div class="mt-3">
                <button class="btn btn-accent">{{ __('lang.update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
