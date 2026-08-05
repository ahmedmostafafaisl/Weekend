@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Edit Unite Offer')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.edit_offer') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <a href="{{ route('admin.unite-offers.show', [$unite->id, $offer->id]) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.unite-offers.update', [$unite->id, $offer->id]) }}" method="POST">
            @csrf
            @method('PUT')
            @include('dashboard.admin.unite-offers.partials.form', ['offer' => $offer])
            <div class="mt-3">
                <button class="btn btn-accent">{{ __('lang.update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
