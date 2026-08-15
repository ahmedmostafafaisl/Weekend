@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Slot')

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_slot') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }} • {{ $unite->type }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.unite-slots.index', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        @if($me && $me->can('unites.update'))
            <a href="{{ route('admin.unite-slots.edit', [$unite->id, $slot->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
            </a>
        @endif
    </div>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.day_of_week') }}</div>
                <div class="fw-semibold">{{ $slot->day_of_week ? __('lang.'.$slot->day_of_week) : '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.status') }}</div>
                <div class="fw-semibold">{{ __('lang.'.$slot->status) }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.morning_start') }}</div>
                <div class="fw-semibold">{{ $slot->morning_start ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.morning_end') }}</div>
                <div class="fw-semibold">{{ $slot->morning_end ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.evening_start') }}</div>
                <div class="fw-semibold">{{ $slot->evening_start ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.evening_end') }}</div>
                <div class="fw-semibold">{{ $slot->evening_end ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.full_start') }}</div>
                <div class="fw-semibold">{{ $slot->full_start ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.full_end') }}</div>
                <div class="fw-semibold">{{ $slot->full_end ?? '—' }}</div>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.operating_day_start') }}</div>
                <div class="fw-semibold">{{ $slot->day_start ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.operating_day_end') }}</div>
                <div class="fw-semibold">{{ $slot->day_end ?? '—' }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">{{ __('lang.handover_buffer_minutes') }}</div>
                <div class="fw-semibold">{{ $slot->buffer_minutes ?? 0 }}</div>
            </div>
        </div>

        @if($slot->periods->isNotEmpty())
            <hr class="my-3">
            <div class="fw-semibold small text-muted mb-2">{{ __('lang.custom_availability_periods') }}</div>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>{{ __('lang.start_time') }}</th>
                        <th>{{ __('lang.end_time') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($slot->periods as $period)
                        <tr>
                            <td>{{ $period->start_time }}</td>
                            <td>{{ $period->end_time }}</td>
                            <td>{{ __('lang.'.$period->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
