@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.stadium_type_details'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $stadiumType->name }}</h4>
        <div class="text-muted">{{ $stadiumType->description ?? '—' }}</div>
    </div>

    <a href="{{ route('admin.stadium_types.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.name') }}</div>
                <div class="fw-semibold">{{ $stadiumType->name }}</div>
            </div>


            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.description') }}</div>
                <div class="fw-semibold">{{ $stadiumType->description ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.created_at') }}</div>
                <div class="fw-semibold">{{ optional($stadiumType->created_at)->format('Y-m-d H:i') }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.updated_at') }}</div>
                <div class="fw-semibold">{{ optional($stadiumType->updated_at)->format('Y-m-d H:i') }}</div>
            </div>

            <div class="col-12">
                <div class="text-muted small">{{ __('lang.image') }}</div>
                @if($stadiumType->image)
                    <img src="{{ asset('storage/' . $stadiumType->image) }}" alt="{{ $stadiumType->name }}" class="img-thumbnail" style="max-width: 200px;">
                @else
                    <div class="fw-semibold">—</div>
                @endif
        </div>
    </div>
</div>
@endsection
