@extends('dashboard.admin.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | Reservations Report')
@section('content')
@php $me = auth('admin')->user(); @endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">📅 {{ __('lang.reservations_report') }}</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">
                @foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach
            </select>
        </form>
        @if($me && $me->can('reports.export'))
        <a href="{{ route('admin.reports.export',['type'=>'reservations','year'=>$year]) }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.export_csv') }}</a>
        @endif
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $total }}</div><div class="text-muted small">{{ __('lang.total_bookings') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success">{{ $byStatus['confirmed']??0 }}</div><div class="text-muted small">{{ __('lang.confirmed') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-danger">{{ $cancelRate }}%</div><div class="text-muted small">{{ __('lang.cancellation_rate') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ number_format($guestStats->avg??0,1) }}</div><div class="text-muted small">{{ __('lang.avg_guest_count') }}</div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.monthly_bookings') }}</h6>
        <canvas id="monthlyChart" height="90"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.by_status') }}</h6>
        <canvas id="statusChart" height="180"></canvas>
    </div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.by_period_type') }}</h6>
        <canvas id="periodChart" height="180"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.by_day_of_week') }}</h6>
        <canvas id="dayChart" height="180"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.top_10_venues') }}</h6>
        @foreach($topVenues->take(10) as $v)
        <div class="d-flex justify-content-between small py-1 border-bottom">
            <span>{{ $v->unite?->name ?? '—' }}</span>
            <span class="fw-semibold">{{ $v->count }} <span class="text-muted">{{ __('lang.bookings') }}</span></span>
        </div>
        @endforeach
    </div></div></div>
</div>

@include('dashboard.admin.reports._shared_charts')
<script>
barChart('monthlyChart',@json($months),
    @json(collect(range(1,12))->map(fn($m)=>(int)($monthly[$m]??0))->values()));
doughnutChart('statusChart',
    @json(collect($byStatus)->keys()->map(fn($k)=>__('lang.'.str_replace('_',' ',$k)))->values()),
    @json(collect($byStatus)->values()));
doughnutChart('periodChart',
    @json(collect($byPeriod)->keys()->map(fn($k)=>__('lang.'.str_replace('_',' ',$k)))->values()),
    @json(collect($byPeriod)->values()));
barChart('dayChart',
    @json(collect($byDay)->keys()->values()),
    @json(collect($byDay)->values()));
</script>
@endsection
