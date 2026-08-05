@extends('provider.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | Reservations Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">📅 Reservations Report</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2"><select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">@foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach</select></form>
        <a href="{{ route('provider.reports.export',['type'=>'reservations','year'=>$year]) }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.export_csv') }}</a>
        <a href="{{ route('provider.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $total }}</div><div class="text-muted small">Total Bookings</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success">{{ $byStatus['confirmed']??0 }}</div><div class="text-muted small">Confirmed</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-warning">{{ $byStatus['pending_approval']??0 }}</div><div class="text-muted small">Pending Approval</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-danger">{{ $cancelRate }}%</div><div class="text-muted small">Cancellation Rate</div></div></div>
</div>
<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="card card-soft shadow-sm"><div class="card-body"><h6 class="fw-bold mb-3">{{ __('lang.monthly_bookings') }}</h6><canvas id="monthlyChart" height="90"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body"><h6 class="fw-bold mb-3">By Period</h6><canvas id="periodChart" height="180"></canvas></div></div></div>
</div>
<div class="card card-soft shadow-sm"><div class="card-body">
    <h6 class="fw-bold mb-3">By Venue</h6>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('lang.th_venue') }}</th><th class="text-end">{{ __('lang.th_bookings') }}</th><th class="text-end">{{ __('lang.th_revenue') }}</th></tr></thead>
        <tbody>@foreach($byVenue as $v)<tr><td class="small fw-semibold">{{ $v->unite?->name??'—' }}</td><td class="small text-end">{{ $v->count }}</td><td class="small text-end text-success">SAR {{ number_format($v->revenue,2) }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@include('dashboard.admin.reports._shared_charts')
<script>
barChart('monthlyChart',@json($months),@json(collect(range(1,12))->map(fn($m)=>(int)($monthly[$m]??0))->values()));
doughnutChart('periodChart',@json(collect($byPeriod)->keys()->map(fn($k)=>ucfirst(str_replace('_',' ',$k)))->values()),@json(collect($byPeriod)->values()));
</script>
@endsection
