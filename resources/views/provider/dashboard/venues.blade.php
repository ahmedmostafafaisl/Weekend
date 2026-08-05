@extends('provider.layouts.app')
@section('title','Weekend | '.__('lang.my_venues'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">🏢 {{ __('lang.my_venues') }}</h4>
    <a href="{{ route('unites.create') }}" class="btn btn-accent btn-sm">+ {{ __('lang.add_venue') }}</a>
</div>

<div class="row g-3">
@forelse($venues as $venue)
@php $img = $venue->images->first(); $price = $venue->prices->first(); @endphp
<div class="col-md-6 col-lg-4">
    <div class="card card-soft shadow-sm h-100">
        @if($img)
            <img src="{{ asset($img->image) }}" class="card-img-top" style="height:160px;object-fit:cover;border-radius:16px 16px 0 0" onerror="this.style.display='none'">
        @endif
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="fw-bold small">{{ $venue->name }}</div>
                <span class="badge {{ $venue->status === 'active' ? 'bg-success' : 'bg-secondary' }}" style="font-size:10px">{{ __('lang.'.$venue->status) }}</span>
            </div>
            <div class="text-muted" style="font-size:11px">{{ __('lang.'.$venue->type) }} · {{ $venue->location_name }}</div>
            @if($venue->requires_approval)
                <span class="badge bg-warning text-dark mt-1" style="font-size:10px">{{ __('lang.approval_mode_badge') }}</span>
            @endif
            <div class="d-flex gap-3 mt-2 small">
                <div class="text-center"><div class="fw-semibold text-success">{{ $venue->confirmed_count }}</div><div class="text-muted" style="font-size:10px">{{ __('lang.confirmed_word') }}</div></div>
                <div class="text-center"><div class="fw-semibold text-warning">{{ $venue->pending_approval_count }}</div><div class="text-muted" style="font-size:10px">{{ __('lang.pending_word') }}</div></div>
                <div class="text-center ms-auto"><div class="fw-semibold">SAR {{ $price ? number_format($price->price ?? $price->full_price ?? $price->morning_price ?? 0) : '—' }}</div><div class="text-muted" style="font-size:10px">{{ __('lang.from_price') }}</div></div>
            </div>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0 d-flex gap-1">
            <a href="{{ route('unites.show', $venue->id) }}" class="btn btn-sm btn-outline-secondary py-0 flex-fill" style="font-size:11px">{{ __('lang.view') }}</a>
            <a href="{{ route('unites.edit', $venue->id) }}" class="btn btn-sm btn-accent py-0 flex-fill" style="font-size:11px">{{ __('lang.edit') }}</a>
        </div>
    </div>
</div>
@empty
    <div class="col-12"><div class="card card-soft p-4 text-center text-muted">{{ __('lang.no_venues_yet') }} <a href="{{ route('unites.create') }}">{{ __('lang.add_first_venue') }}</a></div></div>
@endforelse
</div>
<div class="mt-3">{{ $venues->links() }}</div>
@endsection
