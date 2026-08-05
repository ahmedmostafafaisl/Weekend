@extends('dashboard.admin.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | Users Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">👤 {{ __('lang.users_report') }}</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2"><select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">@foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach</select></form>
        <a href="{{ route('admin.reports.export',['type'=>'users','year'=>$year]) }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.export_csv') }}</a>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>

@php
$customers = $totals->where('type','customer');
$providers = $totals->where('type','provider');
$totalCustomers = $customers->sum('count');
$totalProviders = $providers->sum('count');
$activeCustomers = $customers->where('status','active')->first()?->count ?? 0;
$activeProviders = $providers->where('status','active')->first()?->count ?? 0;
@endphp
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-primary">{{ $totalCustomers }}</div><div class="text-muted small">{{ __('lang.total_customers') }}</div><div class="text-success small">{{ $activeCustomers }} {{ __('lang.active') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-warning">{{ $totalProviders }}</div><div class="text-muted small">{{ __('lang.total_providers') }}</div><div class="text-success small">{{ $activeProviders }} {{ __('lang.active') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $byNation['saudi']??0 }}</div><div class="text-muted small">{{ __('lang.saudi') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $byNation['resident']??0 }}</div><div class="text-muted small">{{ __('lang.resident') }}</div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.new_registrations') }} — {{ $year }}</h6>
        <canvas id="regChart" height="90"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.top_spenders') }}</h6>
        @foreach($topSpenders as $u)
        <div class="d-flex justify-content-between small py-1 border-bottom">
            <div><div class="fw-semibold">{{ $u->name }}</div><div class="text-muted" style="font-size:10px">{{ ucfirst($u->type) }}</div></div>
            <span class="fw-semibold text-success">SAR {{ number_format($u->spent,0) }}</span>
        </div>
        @endforeach
    </div></div></div>
</div>

<div class="card card-soft shadow-sm"><div class="card-body">
    <h6 class="fw-bold mb-3">{{ __('lang.top_providers_revenue') }}</h6>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('lang.th_provider') }}</th><th class="text-end">{{ __('lang.th_revenue') }}</th><th class="text-end">{{ __('lang.th_bookings') }}</th></tr></thead>
        <tbody>@foreach($topProviders as $p)
        <tr><td class="small fw-semibold">{{ $p->name }}</td><td class="small text-end text-success">SAR {{ number_format($p->revenue,2) }}</td><td class="small text-end">{{ $p->bookings }}</td></tr>
        @endforeach</tbody>
    </table></div>
</div></div>

@include('dashboard.admin.reports._shared_charts')
<script>
const mLabels = @json($months);
@php
$custMonthly = collect(range(1,12))->map(fn($m) => (int)($monthly->get($m)?->where('type','customer')->first()?->count ?? 0))->values();
$provMonthly = collect(range(1,12))->map(fn($m) => (int)($monthly->get($m)?->where('type','provider')->first()?->count ?? 0))->values();
@endphp
new Chart(document.getElementById('regChart'),{type:'bar',data:{labels:mLabels,datasets:[
    {label:'Customers',data:@json($custMonthly),backgroundColor:'#6f00ffbb',borderRadius:4},
    {label:'Providers',data:@json($provMonthly),backgroundColor:'#f59e0bbb',borderRadius:4}
]},options:{scales:{y:{beginAtZero:true}},plugins:{legend:{display:true}}}});
</script>
@endsection
