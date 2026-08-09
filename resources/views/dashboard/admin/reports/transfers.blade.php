@extends('dashboard.admin.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | Transfers Report')
@section('content')
@php $me = auth('admin')->user(); @endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">💸 {{ __('lang.transfers_report') }}</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2"><select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">@foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach</select></form>
        @if($me && $me->can('reports.export'))
        <a href="{{ route('admin.reports.export',['type'=>'transfers','year'=>$year]) }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.export_csv') }}</a>
        @endif
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5"> {{ number_format($totals['gross'],2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.gross') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success"> {{ number_format($totals['net_paid'],2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.net_paid_to_providers') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-danger"> {{ number_format($totals['tax'],2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.tax_collected') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-warning"> {{ number_format($totals['pending'],2) }} {{ __('lang.SAR') }}</div><div class="text-muted small">{{ __('lang.pending') }}</div></div></div>
</div>
<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.monthly_transfers_paid') }}</h6>
        <canvas id="transferMonthly" height="90"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.by_method') }}</h6>
        <canvas id="methodChart" height="180"></canvas>
    </div></div></div>
</div>
<div class="card card-soft shadow-sm"><div class="card-body">
    <h6 class="fw-bold mb-3">{{ __('lang.top_providers_transfer') }}</h6>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('lang.th_provider') }}</th><th class="text-end">{{ __('lang.net_received') }}</th><th class="text-end">{{ __('lang.transfers') }}</th></tr></thead>
        <tbody>@foreach($topProviders as $t)<tr>
            <td class="small fw-semibold">{{ $t->provider?->name }}</td>
            <td class="small text-end fw-semibold text-success">SAR {{ number_format($t->total,2) }}</td>
            <td class="small text-end">{{ $t->count }}</td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@include('dashboard.admin.reports._shared_charts')
<script>
barChart('transferMonthly',@json($months),
    @json(collect(range(1,12))->map(fn($m)=>round((float)($monthly[$m]??0),2))->values()));
doughnutChart('methodChart',
    @json($byMethod->pluck('method')->map(fn($m)=>ucfirst(str_replace('_',' ',$m)))->values()),
    @json($byMethod->pluck('total')->values()));
</script>
@endsection
