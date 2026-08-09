@extends('dashboard.admin.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | Revenue Report')
@section('content')
@php $me = auth('admin')->user(); @endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">💰 {{ __('lang.Revenue Report') }}</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">
                @foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach
            </select>
        </form>
        @if($me && $me->can('reports.export'))
        <a href="{{ route('admin.reports.export', ['type'=>'revenue','year'=>$year]) }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.export_csv') }}</a>
        @endif
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success"> {{ number_format($thisYearRevenue,2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.total_revenue') }} {{ $year }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-secondary"> {{ number_format($prevYearRevenue,2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.total_revenue') }} {{ $year-1 }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3">
        <div class="fw-bold fs-5 {{ $growthPct > 0 ? 'text-success' : 'text-danger' }}">{{ $growthPct !== null ? ($growthPct > 0 ? '+' : '').$growthPct.'%' : '—' }}</div>
        <div class="text-muted small">{{ __('lang.yoy_growth') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $byGateway->count() }}</div><div class="text-muted small">{{ __('lang.active_gateways') }}</div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm"><div class="card-body">
            <h6 class="fw-bold mb-3">{{ __('lang.monthly_revenue_sar') }}</h6>
            <canvas id="monthlyChart" height="90"></canvas>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm"><div class="card-body">
            <h6 class="fw-bold mb-3">{{ __('lang.by_gateway') }}</h6>
            <canvas id="gatewayChart" height="180"></canvas>
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm"><div class="card-body">
            <h6 class="fw-bold mb-3">{{ __('lang.by_venue_type') }}</h6>
            <canvas id="typeChart" height="180"></canvas>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm"><div class="card-body">
            <h6 class="fw-bold mb-3">{{ __('lang.top_venues') }}</h6>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>{{ __('lang.th_venue') }}</th><th>{{ __('lang.th_type') }}</th><th class="text-end">{{ __('lang.th_revenue') }}</th><th class="text-end">{{ __('lang.th_bookings') }}</th></tr></thead>
                <tbody>
                @foreach($topVenues as $v)
                <tr>
                    <td class="small fw-semibold">{{ $v->name }}</td>
                    <td><span class="badge bg-light text-dark border" style="font-size:10px">{{ ucfirst($v->type) }}</span></td>
                    <td class="small text-end fw-semibold text-success">SAR {{ number_format($v->revenue,2) }}</td>
                    <td class="small text-end">{{ $v->bookings }}</td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>

@include('dashboard.admin.reports._shared_charts')
<script>
barChart('monthlyChart',
    @json($months),
    @json(collect(range(1,12))->map(fn($m)=>round((float)($monthly[$m]??0),2))->values())
);
doughnutChart('gatewayChart',
    @json($byGateway->pluck('payment_type')->map(fn($t)=>ucfirst($t))->values()),
    @json($byGateway->pluck('total')->values())
);
doughnutChart('typeChart',
    @json($byVenueType->pluck('type')->map(fn($t)=>ucfirst($t))->values()),
    @json($byVenueType->pluck('total')->values())
);
</script>
@endsection
