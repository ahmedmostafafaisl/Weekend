@extends('dashboard.admin.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title', 'Weekend | '.__('lang.analytics'))

@push('css')
<style>
.kpi-card { border-radius: 14px; padding: 1.1rem 1.25rem; border: 0; }
.kpi-label { font-size: 11px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: #888; margin-bottom: .25rem; }
.kpi-value { font-size: 26px; font-weight: 700; line-height: 1.1; }
.kpi-sub   { font-size: 12px; color: #888; margin-top: .25rem; }
canvas { max-height: 300px; }
.type-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.type-stadium { background: #E6F1FB; color: #0C447C; }
.type-hall    { background: #EEEDFE; color: #3C3489; }
.type-lounge  { background: #EAF3DE; color: #27500A; }
.type-camp    { background: #FAEEDA; color: #633806; }
</style>
@endpush

@section('content')
@php $me = auth('admin')->user(); @endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.analytics') }}</h4>
        <div class="text-muted small">{{ __('lang.platform_performance_for') }} {{ $year }}</div>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="year" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
            @foreach($availableYears as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card card-soft kpi-card">
            <div class="kpi-label">{{ __('lang.th_revenue') }}</div>
            <div class="kpi-value text-success">{{ number_format($totalRevenue, 0) }}</div>
            <div class="kpi-sub">{{ __('lang.sar_paid') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card card-soft kpi-card">
            <div class="kpi-label">{{ __('lang.reservations') }}</div>
            <div class="kpi-value">{{ number_format($totalReservations) }}</div>
            <div class="kpi-sub">{{ __('lang.total_bookings') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card card-soft kpi-card">
            <div class="kpi-label">{{ __('lang.confirmed') }}</div>
            <div class="kpi-value text-success">{{ number_format($confirmedReservations) }}</div>
            <div class="kpi-sub">
                @if($totalReservations > 0)
                    {{ round($confirmedReservations / $totalReservations * 100) }}% {{ __('lang.of_total') }}
                @else —
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card card-soft kpi-card">
            <div class="kpi-label">{{ __('lang.cancelled') }}</div>
            <div class="kpi-value text-danger">{{ number_format($cancelledReservations) }}</div>
            <div class="kpi-sub">
                @if($totalReservations > 0)
                    {{ round($cancelledReservations / $totalReservations * 100) }}% {{ __('lang.of_total') }}
                @else —
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card card-soft kpi-card">
            <div class="kpi-label">{{ __('lang.discounts') }}</div>
            <div class="kpi-value text-warning">{{ number_format($totalDiscounts, 0) }}</div>
            <div class="kpi-sub">{{ __('lang.sar_given_away') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card card-soft kpi-card">
            <div class="kpi-label">{{ __('lang.refunded') }}</div>
            <div class="kpi-value text-secondary">{{ number_format($totalRefunded, 0) }}</div>
            <div class="kpi-sub">{{ __('lang.sar_returned') }}</div>
        </div>
    </div>
</div>

{{-- Monthly revenue + reservations charts --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.monthly_revenue') }}</h6>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.bookings_by_venue_type') }}</h6>
                <canvas id="typeChart"></canvas>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    @foreach($typeData as $t)
                    <span class="type-badge type-{{ strtolower($t['type']) }}">
                        {{ $t['type'] }}: {{ $t['count'] }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Monthly reservations + period breakdown --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.monthly_reservations') }}</h6>
                <canvas id="reservationsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.by_period_type') }}</h6>
                @if($periodBreakdown->isEmpty())
                    <div class="text-muted small">{{ __('lang.no_data_for_period') }}</div>
                @else
                    @php $periodTotal = $periodBreakdown->sum(); @endphp
                    @foreach($periodBreakdown as $period => $count)
                    @php $pct = $periodTotal > 0 ? round($count / $periodTotal * 100) : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ __('lang.'.$period) }}</span>
                            <span class="fw-semibold">{{ $count }} <span class="text-muted">({{ $pct }}%)</span></span>
                        </div>
                        <div class="progress" style="height:6px;border-radius:3px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:var(--accent)"></div>
                        </div>
                    </div>
                    @endforeach
                @endif

                <hr class="my-3">
                <h6 class="fw-bold mb-3">{{ __('lang.payment_status_chart') }}</h6>
                @foreach(['paid','pending','failed','refunded','refund_failed'] as $st)
                @php
                    $row = $paymentBreakdown[$st] ?? null;
                    $stColor = match($st) { 'paid'=>'success','pending'=>'warning','refunded'=>'info', default=>'danger' };
                @endphp
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                    <span><span class="badge bg-{{ $stColor }}">{{ __('lang.'.$st) }}</span></span>
                    <span class="text-muted">{{ $row ? number_format($row->count) : 0 }} {{ __('lang.payments_word') }}</span>
                    <span class="fw-semibold">{{ $row ? number_format((float)$row->total, 0) : 0 }} SAR</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Top venues + promo codes --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.top_venues_by_revenue') }}</h6>
                @if($topVenues->isEmpty())
                    <div class="text-muted small">{{ __('lang.no_paid_bookings_yet') }}</div>
                @else
                @php $maxRev = $topVenues->max('revenue'); @endphp
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('lang.th_hash') }}</th><th>{{ __('lang.th_venue') }}</th><th>{{ __('lang.th_bookings') }}</th><th>{{ __('lang.revenue_sar') }}</th><th style="width:120px"></th></tr>
                    </thead>
                    <tbody>
                    @foreach($topVenues as $i => $v)
                    @php $pct = $maxRev > 0 ? round($v->revenue / $maxRev * 100) : 0; @endphp
                    <tr>
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $v->name }}</div>
                            <span class="type-badge type-{{ $v->type }}">{{ __('lang.'.$v->type) }}</span>
                        </td>
                        <td class="small text-center">{{ $v->booking_count }}</td>
                        <td class="fw-semibold small">{{ number_format((float)$v->revenue, 0) }}</td>
                        <td>
                            <div class="progress" style="height:5px;border-radius:3px">
                                <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ __('lang.promo_code_performance') }}</h6>
                    <span class="text-muted small">{{ __('lang.total_discounts') }}: <strong>{{ number_format($totalDiscounts, 0) }} SAR</strong></span>
                </div>
                @if($promoStats->isEmpty())
                    <div class="text-muted small">{{ __('lang.no_promo_codes_used') }}</div>
                @else
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('lang.code') }}</th><th>{{ __('lang.uses') }}</th><th>{{ __('lang.discount_given') }}</th></tr>
                    </thead>
                    <tbody>
                    @foreach($promoStats as $p)
                    <tr>
                        <td>
                            <div class="fw-semibold small font-monospace">{{ $p->code }}</div>
                            <div class="text-muted" style="font-size:10px">
                                {{ $p->discount_type === 'percentage' ? $p->discount_value.'%' : $p->discount_value.' '.__('lang.discount_off_suffix') }}
                            </div>
                        </td>
                        <td class="small text-center">{{ $p->uses }}</td>
                        <td class="small fw-semibold text-success">{{ number_format((float)$p->total_discount, 0) }} SAR</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const accent  = '#6f00ff';
const months  = @json($monthlyData->pluck('month'));
const revenue = @json($monthlyData->pluck('revenue'));
const resCounts = @json($monthlyResData->pluck('count'));
const typeLabels = @json($typeData->pluck('type'));
const typeCounts = @json($typeData->pluck('count'));

Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#888';

// ── Monthly revenue bar chart ──────────────────────────────────────────────
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: @json(__('lang.revenue_sar')),
            data: revenue,
            backgroundColor: accent + '33',
            borderColor: accent,
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0f0f0' },
                ticks: {
                    callback: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v
                }
            },
            x: { grid: { display: false } }
        }
    }
});

// ── Venue type doughnut ────────────────────────────────────────────────────
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeCounts,
            backgroundColor: ['#378ADD', '#7F77DD', '#639922', '#BA7517'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
        }
    }
});

// ── Monthly reservations line chart ───────────────────────────────────────
new Chart(document.getElementById('reservationsChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Reservations',
            data: resCounts,
            borderColor: accent,
            backgroundColor: accent + '15',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: accent,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
