@extends('dashboard.admin.layouts.app')
@section('title','Weekend | '.__('lang.reports_overview'))
@push('css')<style>
.kpi{border-radius:14px;padding:1.1rem 1.25rem;background:#fff;border:.5px solid #e8e4f3}
.kpi-l{font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:4px}
.kpi-v{font-size:28px;font-weight:700;color:#1e1b4b;line-height:1.1}
.kpi-sub{font-size:12px;color:#888}
</style>@endpush
@section('content')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@php $monthNames = $months; @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">📊 {{ __('lang.reports_overview') }}</h4>
    <div class="text-muted small">{{ $monthNames[$month-1] }} {{ $year }}</div></div>
    <form method="GET" class="d-flex gap-2">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:100px">
            @foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach
        </select>
        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()" style="width:130px">
            @foreach($availableMonths as $n=>$l)<option value="{{ $n }}" {{ $n==$month?'selected':'' }}>{{ $l }}</option>@endforeach
        </select>
    </form>
</div>

<div class="row g-3 mb-4">
    @foreach([
        [__('lang.kpi_revenue'),'SAR '.number_format($kpi['revenue'],2),'text-success','💰'],
        [__('lang.kpi_bookings'),$kpi['reservations'],'text-primary','📅'],
        [__('lang.kpi_new_users'),$kpi['new_users'],'text-info','👤'],
        [__('lang.kpi_pending_approvals'),$kpi['pending_approvals'],'text-warning','⏳'],
        [__('lang.kpi_active_venues'),$kpi['active_venues'],'text-success','🏢'],
        [__('lang.kpi_active_subs'),$kpi['subscriptions'],'text-purple','📦'],
        [__('lang.kpi_transfers_paid'),$kpi['transfers_paid'] > 0 ? 'SAR '.number_format($kpi['transfers_paid'],0) : '—','text-success','💸'],
        [__('lang.kpi_total_providers'),$kpi['total_providers'],'text-secondary','🏪'],
    ] as [$l,$v,$c,$i])
    <div class="col-6 col-md-3">
        <div class="kpi shadow-sm"><div class="kpi-l">{{ $i }} {{ $l }}</div><div class="kpi-v {{ $c }}">{{ $v }}</div></div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.monthly_revenue') }} — {{ $year }}</h6>
                <canvas id="trendChart" height="80"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h6 class="fw-bold mb-3">{{ __('lang.quick_links') }}</h6>
                @foreach([
                    [__('lang.reports_revenue'),'admin.reports.revenue','💰','success'],
                    [__('lang.reports_reservations'),'admin.reports.reservations','📅','primary'],
                    [__('lang.reports_users'),'admin.reports.users','👤','info'],
                    [__('lang.reports_venues'),'admin.reports.venues','🏢','warning'],
                    [__('lang.reports_subscriptions'),'admin.reports.subscriptions','📦','secondary'],
                    [__('lang.reports_transfers'),'admin.reports.transfers','💸','dark'],
                ] as [$label,$route,$icon,$color])
                <a href="{{ route($route) }}" class="btn btn-outline-{{ $color }} btn-sm text-start mb-2">{{ $icon }} {{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('dashboard.admin.reports._shared_charts')
<script>
lineChart('trendChart',
    @json($months),
    @json(collect(range(1,12))->map(fn($m) => round((float)($trend[$m]??0),2))->values())
);
</script>
@endsection
