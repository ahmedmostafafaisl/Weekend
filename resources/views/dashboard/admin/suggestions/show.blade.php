@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.suggestion_details'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $suggestion->content }}</h4>
        <div class="text-muted">{{ $suggestion->user->name ?? '—' }}</div>
    </div>

    <a href="{{ route('admin.suggestions.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.content') }}</div>
                <div class="fw-semibold">{{ $suggestion->content ?? '—' }}</div>
            </div>


            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.user') }}</div>
                <div class="fw-semibold">{{ $suggestion->user->name ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.created_at') }}</div>
                <div class="fw-semibold">{{ optional($suggestion->created_at)->format('Y-m-d H:i') }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.updated_at') }}</div>
                <div class="fw-semibold">{{ optional($suggestion->updated_at)->format('Y-m-d H:i') }}</div>
            </div>
    </div>
</div>
@endsection
