@extends('provider.layouts.app')
@php
$months = app()->getLocale() === 'ar'
    ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp
@section('title','Weekend | '.__('lang.revenue_word'))
@push('css')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">💰 {{ __('lang.revenue_word') }}</h4>
        <div class="text-muted small">SAR {{ number_format($totalRevenue, 2) }} {{ __('lang.total_in_year') }} {{ $year }}</div>
    </div>
    <form method="GET">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($years as $y)<option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>@endforeach
        </select>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.monthly_revenue_sar') }}</h6>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.top_venues') }}</h6>
                @foreach($topVenues as $v)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                    <div>
                        <div class="fw-semibold">{{ $v->name }}</div>
                        <div class="text-muted" style="font-size:10px">{{ $v->bookings }} {{ __('lang.bookings_count') }}</div>
                    </div>
                    <div class="fw-semibold text-success">SAR {{ number_format($v->revenue) }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
const months = @json($months);
const data = @json($monthly);
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: @json(__('lang.revenue_sar')),
            data: months.map((_,i) => data[i+1] ?? 0),
            backgroundColor: 'rgba(111,0,255,.7)',
            borderRadius: 6,
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endpush
@endsection
