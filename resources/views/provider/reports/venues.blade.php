@extends('provider.layouts.app')
@section('title','Weekend | Venues Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">🏢 Venues Report</h4><div class="text-muted small">{{ $year }}</div></div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2"><select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:110px">@foreach($availableYears as $y)<option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>@endforeach</select></form>
        <a href="{{ route('provider.reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.overview') }}</a>
    </div>
</div>
<div class="row g-3">
@foreach($venues as $v)
<div class="col-md-6">
    <div class="card card-soft shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div><div class="fw-bold">{{ $v->name }}</div><div class="text-muted small">{{ ucfirst($v->type) }}</div></div>
                <span class="badge {{ $v->status==='active'?'bg-success':'bg-secondary' }}">{{ $v->status }}</span>
            </div>
            <div class="row g-2 text-center small">
                <div class="col-3"><div class="fw-bold text-success">SAR {{ number_format($revenueByVenue[$v->id]??0,0) }}</div><div class="text-muted" style="font-size:10px">{{ __('lang.th_revenue') }}</div></div>
                <div class="col-3"><div class="fw-bold text-primary">{{ $v->confirmed_bookings }}</div><div class="text-muted" style="font-size:10px">{{ __('lang.th_bookings') }}</div></div>
                <div class="col-3"><div class="fw-bold text-info">{{ number_format($v->views_count) }}</div><div class="text-muted" style="font-size:10px">Views</div></div>
                <div class="col-3"><div class="fw-bold text-warning">⭐ {{ number_format($v->avg_rating??0,1) }}</div><div class="text-muted" style="font-size:10px">Rating ({{ $v->ratings_count }})</div></div>
            </div>
        </div>
    </div>
</div>
@endforeach
</div>
@endsection
