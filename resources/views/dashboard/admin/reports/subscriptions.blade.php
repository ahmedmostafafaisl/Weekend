@extends('dashboard.admin.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | Subscriptions Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">📦 {{ __('lang.subscriptions_report') }}</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2"><select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">@foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach</select></form>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success"> {{ number_format($revenue,2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.subscription_revenue') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success">{{ $byStatus['active']??0 }}</div><div class="text-muted small">{{ __('lang.active') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-secondary">{{ $byStatus['inactive']??0 }}</div><div class="text-muted small">{{ __('lang.expired_subscriptions') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-warning">{{ $byStatus['pending']??0 }}</div><div class="text-muted small">{{ __('lang.pending') }}</div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-6"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.monthly_sub_revenue') }}</h6>
        <canvas id="subMonthly" height="100"></canvas>
    </div></div></div>
    <div class="col-lg-3"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.by_type') }}</h6>
        <canvas id="subType" height="180"></canvas>
    </div></div></div>
    <div class="col-lg-3"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.by_status') }}</h6>
        <canvas id="subStatus" height="180"></canvas>
    </div></div></div>
</div>
@include('dashboard.admin.reports._shared_charts')
<script>
barChart('subMonthly',@json($months),
    @json(collect(range(1,12))->map(fn($m)=>round((float)($monthly[$m]??0),2))->values()));
doughnutChart('subType',@json($byType->pluck('type')->map(fn($t)=>ucfirst($t))),@json($byType->pluck('revenue')));
doughnutChart('subStatus',
    @json(collect($byStatus)->keys()->map(fn($k)=>ucfirst($k))->values()),
    @json(collect($byStatus)->values()));
</script>
@endsection
