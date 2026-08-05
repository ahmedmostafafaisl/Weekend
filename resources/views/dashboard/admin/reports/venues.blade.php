@extends('dashboard.admin.layouts.app')
@section('title','Weekend | Venues Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">🏢 {{ __('lang.venues_report') }}</h4></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2"><select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">@foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach</select></form>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-success">{{ $byStatus['active']??0 }}</div><div class="text-muted small">{{ __('lang.active_venues') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5 text-secondary">{{ ($byStatus['inactive']??0) + ($byStatus['pending']??0) }}</div><div class="text-muted small">{{ __('lang.inactive_pending_venues') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $byType->sum('count') }}</div><div class="text-muted small">{{ __('lang.total_venues') }}</div></div></div>
    <div class="col-md-3"><div class="card card-soft shadow-sm text-center py-3"><div class="fw-bold fs-5">{{ $highestRated->count() }}</div><div class="text-muted small">{{ __('lang.rated_venues') }}</div></div></div>
</div>
<div class="row g-4 mb-4">
    <div class="col-lg-4"><div class="card card-soft shadow-sm"><div class="card-body"><h6 class="fw-bold mb-3">{{ __('lang.by_type') }}</h6><canvas id="typeChart" height="200"></canvas></div></div></div>
    <div class="col-lg-8"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.most_booked') }}</h6>
        <div class="table-responsive"><table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>{{ __('lang.th_venue') }}</th><th>{{ __('lang.th_type') }}</th><th class="text-end">{{ __('lang.th_bookings') }}</th></tr></thead>
            <tbody>@foreach($occupancy as $v)<tr>
                <td class="small fw-semibold">{{ $v->name }}</td>
                <td><span class="badge bg-light text-dark border" style="font-size:10px">{{ ucfirst($v->type) }}</span></td>
                <td class="small text-end">{{ $v->bookings }}</td>
            </tr>@endforeach</tbody>
        </table></div>
    </div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-6"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.most_viewed') }}</h6>
        @foreach($mostViewed as $v)
        <div class="d-flex justify-content-between small py-1 border-bottom">
            <span>{{ $v->name }}</span><span class="fw-semibold">{{ number_format($v->views_count) }} views</span>
        </div>@endforeach
    </div></div></div>
    <div class="col-lg-6"><div class="card card-soft shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.highest_rated') }}</h6>
        @foreach($highestRated as $v)
        <div class="d-flex justify-content-between small py-1 border-bottom">
            <span>{{ $v->name }}</span>
            <span>⭐ {{ number_format($v->avg_rating,1) }} <span class="text-muted">({{ $v->ratings_count }})</span></span>
        </div>@endforeach
    </div></div></div>
</div>
@include('dashboard.admin.reports._shared_charts')
<script>
doughnutChart('typeChart',
    @json($byType->unique('type')->pluck('type')->map(fn($t)=>ucfirst($t))->values()),
    @json($byType->groupBy('type')->map->sum('count')->values()));
</script>
@endsection
