@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | ' . $unite->name)

@push('css')
<style>
/* ── Calendar card — clean grid style (day number + status dot per cell) ── */
.cal-nav-btn{
    width:32px; height:32px; border:none; background:transparent;
    color:var(--accent); font-size:20px; line-height:1; cursor:pointer;
    display:flex; align-items:center; justify-content:center; border-radius:8px;
}
.cal-nav-btn:hover{ background:var(--sidebar-hover, #f3f0ff); }
.cal-month-title{ font-size:18px; font-weight:700; color:var(--accent); }

.cal-grid{ display:grid; grid-template-columns:repeat(7,1fr); gap:2px; text-align:center; }
.cal-dow{ font-size:12px; font-weight:600; color:var(--accent); padding:8px 2px; }
.cal-day{ padding:10px 2px 12px; position:relative; }
.cal-day-num{
    font-size:15px; color:#adb5bd; width:34px; height:34px; margin:0 auto;
    display:flex; align-items:center; justify-content:center; border-radius:50%;
}
.cal-day.is-current .cal-day-num{ color:#1e1b2e; cursor:pointer; }
.cal-day.is-current .cal-day-num:hover{ background:var(--sidebar-hover, #f3f0ff); }
.cal-day.is-selected .cal-day-num{ background:var(--accent); color:#fff; }
.cal-day-dot{ width:6px; height:6px; border-radius:50%; margin:4px auto 0; }
.cal-day-dot.dot-available{ background:#3B6D11; }
.cal-day-dot.dot-holiday{ background:#BA7517; }
.cal-day-dot.dot-unavailable{ background:#A32D2D; }
.cal-day-dot.dot-muted{ background:#dee2e6; }

.cal-legend{ display:flex; gap:16px; flex-wrap:wrap; justify-content:center; margin-top:14px; }
.cal-legend-item{ display:flex; align-items:center; gap:6px; font-size:12px; color:#6c757d; }
.cal-legend-dot{ width:9px; height:9px; border-radius:50%; }

/* ── Slot panel — appears below the grid once a date is selected ── */
#cal-slots-panel{ overflow-x:auto; white-space:nowrap; padding-bottom:4px; }
.cal-slot-chip{
    display:inline-flex; flex-direction:column; align-items:center; gap:2px;
    min-width:78px; padding:8px 10px; margin-inline-end:8px;
    border:1px solid #e8e4f3; border-radius:10px; font-size:12px; background:#fff;
    vertical-align:top; white-space:normal;
}
.cal-slot-chip.slot-available{ border-color:#C0DD97; background:#EAF3DE; color:#27500A; }
.cal-slot-chip.slot-booked{ border-color:#F5C2C2; background:#FCEBEB; color:#7B1F1F; }
.cal-slot-chip.slot-past{ border-color:#e8e4f3; background:#f8f9fa; color:#adb5bd; }
</style>
@endpush

@section('content')
@php $me = auth('admin')->user() ?? auth()->user(); @endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('unites.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
        <div>
            <h4 class="fw-bold mb-0">{{ $unite->name }}</h4>
            <div class="text-muted small">{{ $unite->department->name ?? '' }}</div>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
            {{ __('lang.'.$unite->type) }}
        </span>
        <span class="badge {{ $unite->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
            {{ __('lang.'.$unite->status) }}
        </span>
        @if($unite->requires_approval)
            <span class="badge bg-warning text-dark">{{ __('lang.approval_mode') }}</span>
        @endif
    </div>
    <div class="d-flex gap-2 align-items-center">
        {{-- Approval toggle --}}
        <form action="{{ route('unites.toggle-approval', $unite) }}" method="POST" class="d-inline">
            @csrf @method('PATCH')
            <div class="form-check form-switch mb-0 d-flex align-items-center gap-2" title="{{ $unite->requires_approval ? __('lang.disable_approval_mode') : __('lang.enable_approval_mode') }}">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="approvalToggle"
                       {{ $unite->requires_approval ? 'checked' : '' }}
                       onchange="this.form.submit()"
                       style="width:2.5rem;height:1.25rem;cursor:pointer">
                <label class="form-check-label small text-muted" for="approvalToggle">
                    {{ __('lang.approval_mode') }}
                </label>
            </div>
        </form>
        <a href="{{ route('unites.edit', $unite->id) }}" class="btn btn-accent">{{ __('lang.edit_unite') }}</a>
    </div>
</div>

<div class="row g-4">

    {{-- ── LEFT COLUMN ── --}}
    <div class="col-lg-7">

        {{-- General Info --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.general_information') }}</h6>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted small">{{ __('lang.location') }}</div>
                        <div>{{ $unite->location_name ?? '—' }}</div>
                    </div>
                       <div class="col-6">
                        <div class="text-muted small">{{ __('lang.city') }}</div>
                        <div>{{ $unite->city ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('lang.families_singles') }}</div>
                        <div>{{ $unite->families_and_singles ? __('lang.'.$unite->families_and_singles) : '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('lang.refund_policy') }}</div>
                        <div>{{ $unite->refund_policy ? __('lang.'.$unite->refund_policy) : '—' }}</div>
                    </div>
                    @if($unite->description)
                    <div class="col-12">
                        <div class="text-muted small">{{ __('lang.description') }}</div>
                        <div>{{ $unite->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Deposit & Insurance --}}
        @include('dashboard.web.unites._deposit_insurance', [
            'unite'    => $unite,
            'readonly' => true,
        ])

        {{-- Type-specific Details --}}
        @if($unite->type === 'stadium' && $unite->detail)
            @php $d = $unite->detail; @endphp
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.stadium_details') }}</h6>
                    <div class="row g-3">
                        <div class="col-4"><div class="text-muted small">{{ __('lang.width') }}</div><div>{{ $d->width ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-muted small">{{ __('lang.length') }}</div><div>{{ $d->length ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-muted small">{{ __('lang.category') }}</div><div>{{ $d->customize_Category ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-muted small">{{ __('lang.place') }}</div><div>{{ $d->customize_Place ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-muted small">{{ __('lang.cafeteria') }}</div><div>{{ $d->cafeteria ? 'Yes' : 'No' }}</div></div>
                        <div class="col-12"><div class="text-muted small">{{ __('lang.amenities') }}</div><div>{{ $d->amenities ?? '—' }}</div></div>
                    </div>
                </div>
            </div>
        @endif

        @if($unite->type === 'hall' && $unite->detail)
            @php $d = $unite->detail; @endphp
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.hall_details') }}</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-4"><div class="text-muted small">{{ __('lang.max_capacity') }}</div><div>{{ $d->max_capacity ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-muted small">{{ __('lang.kusha') }}</div><div>{{ $d->kusha ? 'Yes' : 'No' }}</div></div>
                        <div class="col-4"><div class="text-muted small">{{ __('lang.buffet') }}</div><div>{{ $d->buffet ? 'Yes' : 'No' }}</div></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="fw-semibold small mb-2">{{ __('lang.women_section') }}</div>
                                <div class="text-muted small">{{ __('lang.capacity') }}: {{ $d->women_seating_capacity ?? '—' }}</div>
                                <div class="text-muted small">{{ __('lang.tables') }}: {{ $d->women_tables_count ?? '—' }} | {{ __('lang.chairs') }}: {{ $d->women_chairs_count ?? '—' }}</div>
                                <div class="text-muted small">{{ __('lang.buffet') }}: {{ $d->women_buffet ? __('lang.yes') : __('lang.no') }}</div>
                                <div class="text-muted small">{{ __('lang.buffet_details') }}: {{ $d->women_buffet_details ?? '—' }}</div>

                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="fw-semibold small mb-2">{{ __('lang.men_section') }}</div>
                                <div class="text-muted small">{{ __('lang.capacity') }}: {{ $d->men_seating_capacity ?? '—' }}</div>
                                <div class="text-muted small">{{ __('lang.tables') }}: {{ $d->men_tables_count ?? '—' }} | {{ __('lang.chairs') }}: {{ $d->men_chairs_count ?? '—' }}</div>
                                <div class="text-muted small">{{ __('lang.buffet') }}: {{ $d->men_buffet ? __('lang.yes') : __('lang.no') }}</div>
                                <div class="text-muted small">{{ __('lang.buffet_details') }}: {{ $d->men_buffet_details ?? '—' }}</div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($unite->type === 'camp' && $unite->detail)
            @php $d = $unite->detail; @endphp
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.camp_details') }}</h6>
                    <div class="row g-3">
                        <div class="col-3"><div class="text-muted small">{{ __('lang.width') }}</div><div>{{ $d->width ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.length') }}</div><div>{{ $d->length ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.seating') }}</div><div>{{ $d->seating_capacity ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.bathrooms') }}</div><div>{{ $d->bathroom_number ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.tv') }}</div><div>{{ $d->television ? 'Yes' : 'No' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.fireplace') }}</div><div>{{ $d->fireplace ? 'Yes' : 'No' }}</div></div>
                    </div>
                </div>
            </div>
        @endif

        @if($unite->type === 'lounge' && $unite->detail)
            @php $d = $unite->detail; @endphp
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.lounge_details') }}</h6>
                    <div class="row g-3">
                        <div class="col-3"><div class="text-muted small">{{ __('lang.area') }}</div><div>{{ $d->area ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.bedrooms') }}</div><div>{{ $d->bedroom_number ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.single_beds') }}</div><div>{{ $d->single_bed ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.double_beds') }}</div><div>{{ $d->big_bed ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.bathrooms') }}</div><div>{{ $d->bathroom_number ?? '—' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.pool') }}</div><div>{{ $d->pool ? 'Yes' : 'No' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.kitchen') }}</div><div>{{ $d->kitchen ? 'Yes' : 'No' }}</div></div>
                        <div class="col-3"><div class="text-muted small">{{ __('lang.council') }}</div><div>{{ $d->council ? $d->council_number.' '.__('lang.units') : __('lang.no') }}</div></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Features --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ __('lang.features') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->features->count() }}</span>
                </div>
                @if($unite->features->count())
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>{{ __('lang.th_hash') }}</th><th>{{ __('lang.name') }}</th><th>{{ __('lang.description') }}</th><th>{{ __('lang.status') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($unite->features as $i => $f)
                            <tr>
                                <td class="text-muted small">{{ $i+1 }}</td>
                                <td class="fw-semibold small">{{ $f->name }}</td>
                                <td class="text-muted small">{{ $f->description ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $f->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ __('lang.'.$f->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted small">{{ __('lang.no_features') }}</div>
                @endif
            </div>
        </div>

        {{-- Offers --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ __('lang.offers') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->offers->count() }}</span>
                </div>
                @if($unite->offers->count())
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.th_hash') }}</th><th>{{ __('lang.name') }}</th><th>{{ __('lang.th_period') }}</th>
                                @if($unite->type === 'stadium')
                                    <th>{{ __('lang.day_rate') }}</th><th>{{ __('lang.night_rate') }}</th>
                                @else
                                    <th>{{ __('lang.th_morning') }}</th><th>{{ __('lang.th_evening') }}</th><th>{{ __('lang.th_full_day') }}</th>
                                @endif
                                <th>{{ __('lang.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unite->offers as $i => $offer)
                            <tr>
                                <td class="text-muted small">{{ $i+1 }}</td>
                                <td class="small">{{ $offer->name ?? '—' }}</td>
                                <td class="small text-muted">{{ $offer->start }} → {{ $offer->end }}</td>
                                @if($unite->type === 'stadium')
                                    <td class="small">{{ $offer->day_hour_price ?? '—' }}</td>
                                    <td class="small">{{ $offer->night_hour_price ?? '—' }}</td>
                                @else
                                    <td class="small">{{ $offer->morning_price ?? '—' }}</td>
                                    <td class="small">{{ $offer->evening_price ?? '—' }}</td>
                                    <td class="small">{{ $offer->full_day_price ?? '—' }}</td>
                                @endif
                                <td>
                                    <span class="badge {{ $offer->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ __('lang.'.$offer->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted small">{{ __('lang.no_offers') }}</div>
                @endif
            </div>
        </div>


        {{-- ── Booking Packages ── --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">🎁 {{ __('lang.booking_packages') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->bookingPackages->count() }}</span>
                </div>
                @if(! $unite->package_booking_enabled)
                    <div class="text-muted small">{{ __('lang.package_booking_disabled_for_venue') }}</div>
                @elseif($unite->bookingPackages->count())
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.name') }}</th>
                                <th>{{ __('lang.booking_type') }}</th>
                                <th>{{ __('lang.booking_package_when') }}</th>
                                <th>{{ __('lang.th_price') }}</th>
                                <th>{{ __('lang.included_services') }}</th>
                                <th>{{ __('lang.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unite->bookingPackages as $pkg)
                            <tr>
                                <td class="small">{{ $pkg->name ?? '—' }}</td>
                                <td class="small">
                                    <span class="badge {{ $pkg->booking_type === 'days' ? 'bg-info text-dark' : 'bg-light text-dark border' }}">
                                        {{ $pkg->booking_type === 'days' ? __('lang.booking_type_days') : __('lang.booking_type_hours') }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    @if($pkg->booking_type === 'days')
                                        {{ __('lang.'.$pkg->day_from) }} → {{ __('lang.'.$pkg->day_to) }}
                                        <span class="text-muted">({{ $pkg->duration_days }} {{ __('lang.days_unit') }})</span>
                                    @else
                                        @php($hoursDayLabels = ['week_day'=>__('lang.weekday'),'thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday')])
                                        {{ $hoursDayLabels[$pkg->day] ?? $pkg->day }} · {{ $pkg->start_time }} → {{ $pkg->end_time }}
                                    @endif
                                </td>
                                <td class="small fw-semibold">{{ number_format($pkg->price, 2) }}</td>
                                <td class="small text-muted">
                                    {{ $pkg->services && count($pkg->services) ? implode(', ', $pkg->services) : '—' }}
                                </td>
                                <td>
                                    <span class="badge {{ $pkg->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ __('lang.'.$pkg->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted small">{{ __('lang.no_booking_packages') }}</div>
                @endif
            </div>
        </div>


        {{-- ── Time Slots ── --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">📅 {{ __('lang.time_slots') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->slots->count() }}</span>
                </div>
                @if($unite->slots->count())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('lang.day_type') }}</th>
                                    @if(in_array($unite->type, ['stadium', 'hall']))
                                        <th>{{ __('lang.th_full_price') }}</th>
                                    @else
                                        <th>{{ __('lang.th_morning') }}</th><th>{{ __('lang.th_evening') }}</th><th>{{ __('lang.th_full_day') }}</th>
                                    @endif
                                    <th>{{ __('lang.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unite->slots as $sl)
                                <tr>
                                    <td class="small fw-semibold">{{ ucfirst($sl->day_of_week ?? '—') }}</td>
                                    @if(in_array($unite->type, ['stadium', 'hall']))
                                        <td class="small text-muted">{{ $sl->full_start }} – {{ $sl->full_end }}</td>
                                    @else
                                        <td class="small text-muted">
                                            @if($sl->morning_start){{ substr($sl->morning_start,0,5) }} – {{ substr($sl->morning_end,0,5) }}@else —@endif
                                        </td>
                                        <td class="small text-muted">
                                            @if($sl->evening_start){{ substr($sl->evening_start,0,5) }} – {{ substr($sl->evening_end,0,5) }}@else —@endif
                                        </td>
                                        <td class="small text-muted">
                                            @if($sl->full_start){{ substr($sl->full_start,0,5) }} – {{ substr($sl->full_end,0,5) }}@else —@endif
                                        </td>
                                    @endif
                                    <td>
                                        @php $sc = ['available'=>'success','booked'=>'warning','unavailable'=>'secondary'] @endphp
                                        <span class="badge bg-{{ $sc[$sl->status] ?? 'secondary' }} bg-opacity-75" style="font-size:10px">
                                            {{ __('lang.'.($sl->status ?? 'available')) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted small">{{ __('lang.no_slots') }}</div>
                @endif
            </div>
        </div>

        {{-- ── Pricing ── --}}
        @php $hasHourly = $unite->prices->where('hourly_enabled', true)->count() > 0; @endphp
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">💰 {{ __('lang.pricing') }}</h6>
                    <div class="d-flex align-items-center gap-2">
                        @if($hasHourly)
                            <span class="badge bg-success bg-opacity-75" style="font-size:10px">⏱ {{ __('lang.hourly_available') }}</span>
                        @endif
                        <span class="badge bg-light text-dark border">{{ $unite->prices->count() }} {{ __('lang.rows') }}</span>
                    </div>
                </div>
                @if($unite->prices->count())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('lang.day_type') }}</th>
                                    @if(in_array($unite->type, ['stadium', 'hall']))
                                        <th>{{ __('lang.th_full_price') }}</th>
                                    @else
                                        <th>{{ __('lang.th_morning') }}</th><th>{{ __('lang.th_evening') }}</th><th>{{ __('lang.th_full_day') }}</th>
                                    @endif
                                    @if($hasHourly)
                                        <th class="text-center">⏱ {{ __('lang.hourly') }}</th>
                                        <th>{{ __('lang.day_rate') }}</th>
                                        <th>{{ __('lang.night_rate') }}</th>
                                        <th>{{ __('lang.day_window') }}</th>
                                        <th>{{ __('lang.min') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unite->prices as $pr)
                                <tr>
                                    <td class="small fw-semibold">
                                        {{ ['thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday'),'week_day'=>__('lang.weekday')][$pr->day] ?? ucfirst($pr->day) }}
                                    </td>
                                    @if($unite->type === 'stadium')
                                        <td class="small">{{ $pr->price ? 'SAR '.number_format($pr->price,2) : '—' }}</td>
                                    @elseif($unite->type === 'hall')
                                        <td class="small">{{ $pr->full_price ? 'SAR '.number_format($pr->full_price,2) : '—' }}</td>
                                    @else
                                        <td class="small">{{ $pr->morning_price ? number_format($pr->morning_price,2) : '—' }}</td>
                                        <td class="small">{{ $pr->evening_price ? number_format($pr->evening_price,2) : '—' }}</td>
                                        <td class="small">{{ $pr->full_price    ? number_format($pr->full_price,2)    : '—' }}</td>
                                    @endif
                                    @if($hasHourly)
                                        <td class="text-center">
                                            @if($pr->hourly_enabled)
                                                <span class="badge bg-success bg-opacity-75" style="font-size:10px">ON</span>
                                            @else
                                                <span class="badge bg-light text-muted border" style="font-size:10px">—</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $pr->hourly_enabled && $pr->day_hour_price ? number_format($pr->day_hour_price,2).'/hr' : '—' }}</td>
                                        <td class="small">
                                            @if($pr->hourly_enabled && $pr->night_hour_price)
                                                {{ number_format($pr->night_hour_price,2) }}/hr
                                            @elseif($pr->hourly_enabled)
                                                <span class="text-muted small">= day</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $pr->hourly_enabled ? substr($pr->day_start??'06:00',0,5).'–'.substr($pr->day_end??'18:00',0,5) : '—' }}</td>
                                        <td class="small text-muted">{{ $pr->hourly_enabled ? ($pr->min_booking_minutes??60).'m' : '—' }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted small">{{ __('lang.no_pricing') }}</div>
                @endif
            </div>
        </div>

        {{-- ── Hourly Pricing Detail (only when enabled) ── --}}
        @if($hasHourly)
        <div class="card card-soft shadow-sm mb-4" style="border-left:4px solid #6f00ff">
            <div class="card-body">
                <h6 class="fw-bold mb-3">⏱ {{ __('lang.hourly_booking_rates') }}</h6>
                <div class="row g-3">
                    @foreach($unite->prices->where('hourly_enabled', true) as $pr)
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <div class="fw-semibold small mb-2">
                                {{ ['thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday'),'week_day'=>__('lang.weekday')][$pr->day] ?? ucfirst($pr->day) }}
                            </div>
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:11px">☀️ {{ __('lang.day_rate_label') }}</div>
                                    <div class="fw-bold text-success">SAR {{ number_format($pr->day_hour_price, 2) }}<span class="text-muted fw-normal">/hr</span></div>
                                    <div class="text-muted" style="font-size:10px">{{ substr($pr->day_start??'06:00',0,5) }} – {{ substr($pr->day_end??'18:00',0,5) }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:11px">🌙 {{ __('lang.night_rate_label') }}</div>
                                    <div class="fw-bold text-primary">SAR {{ number_format($pr->night_hour_price ?? $pr->day_hour_price, 2) }}<span class="text-muted fw-normal">/hr</span></div>
                                    <div class="text-muted" style="font-size:10px">{{ substr($pr->day_end??'18:00',0,5) }} – {{ substr($pr->day_start??'06:00',0,5) }}</div>
                                </div>
                            </div>
                            <div class="text-muted text-center mt-2" style="font-size:11px">
                                {{ __('lang.minimum_booking_colon') }}: <strong>{{ $pr->min_booking_minutes ?? 60 }} {{ __('lang.minutes') }}</strong>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ── Packages ── (not applicable to stadiums) --}}
        @unless($unite->type === 'stadium')
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">📦 {{ __('lang.packages') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->packages->count() }}</span>
                </div>
                @if($unite->packages->count())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>{{ __('lang.th_hash') }}</th><th>{{ __('lang.name') }}</th><th>{{ __('lang.th_price') }}</th><th>{{ __('lang.th_men_capacity') }}</th><th>{{ __('lang.th_women_capacity') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach($unite->packages as $i => $pkg)
                                <tr>
                                    <td class="text-muted small">{{ $i+1 }}</td>
                                    <td class="small fw-semibold">{{ $pkg->name }}</td>
                                    <td class="small">{{ $pkg->price ? 'SAR '.number_format($pkg->price,2) : '—' }}</td>
                                    <td class="small">{{ $pkg->men_capacity ?? '—' }}</td>
                                    <td class="small">{{ $pkg->women_capacity ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted small">{{ __('lang.no_packages') }}</div>
                @endif
            </div>
        </div>
        @endunless

        {{-- ── AVAILABILITY CALENDAR ── --}}
        <div class="card card-soft shadow-sm mb-4" id="cal-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button type="button" class="cal-nav-btn" id="cal-prev">‹</button>
                    <div class="cal-month-title" id="cal-month-title">—</div>
                    <button type="button" class="cal-nav-btn" id="cal-next">›</button>
                </div>
                <span id="cal-loading" class="text-muted small d-none d-block text-center mb-2">
                    <span class="spinner-border spinner-border-sm me-1"></span> {{ __('lang.loading') }}
                </span>
                <div id="cal-error" class="alert alert-warning d-none small mb-3"></div>

                <div class="cal-grid mb-1" id="cal-dow-row"></div>
                <div class="cal-grid" id="cal-days-grid"></div>

                <div class="cal-legend">
                    <span class="cal-legend-item"><span class="cal-legend-dot" style="background:#3B6D11"></span> {{ __('lang.available') }}</span>
                    <span class="cal-legend-item"><span class="cal-legend-dot" style="background:#BA7517"></span> {{ __('lang.unavailable') }}</span>
                    <span class="cal-legend-item"><span class="cal-legend-dot" style="background:#A32D2D"></span> {{ __('lang.fully_booked') }}</span>
                </div>

                {{-- Slot panel — appears once a date is tapped, showing periods for that day --}}
                <div id="cal-slots-wrap" class="mt-3 pt-3 border-top d-none">
                    <div class="small text-muted mb-2" id="cal-slots-date"></div>
                    <div id="cal-slots-panel"></div>
                </div>
            </div>
        </div>

    </div>{{-- /left --}}

    {{-- ── RIGHT COLUMN ── --}}
    <div class="col-lg-5">

        {{-- Images --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ __('lang.images') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->images->count() }}</span>
                </div>
                @if($unite->images->count())
                    <div class="row g-2">
                        @foreach($unite->images as $img)
                            <div class="col-4">
                                <img src="{{ asset($img->image) }}"
                                     class="rounded w-100"
                                     style="height:80px;object-fit:cover;"
                                     onerror="this.style.opacity='.3'">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted small">{{ __('lang.no_images') }}</div>
                @endif
            </div>
        </div>

        {{-- Reservations list --}}
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ __('lang.reservations') }}</h6>
                    <span class="badge bg-light text-dark border">{{ $unite->reservations->count() }}</span>
                </div>
                @if($unite->reservations->count())
                    <div class="d-flex flex-column gap-2">
                        @foreach($unite->reservations->sortByDesc('reservation_date') as $res)
                            @php
                                $col = match($res->status) {
                                    'confirmed' => 'success',
                                    'pending'   => 'warning',
                                    'cancelled' => 'danger',
                                    default     => 'secondary',
                                };
                            @endphp
                            <div class="border rounded p-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold small">{{ $res->user->name ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ is_string($res->reservation_date)
                                                ? $res->reservation_date
                                                : $res->reservation_date?->format('d M Y') }}
                                            · {{ __('lang.'.$res->period_type) }}
                                        </div>
                                        <div class="text-muted small">{{ $res->from_time }} – {{ $res->to_time }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-{{ $col }}">{{ __('lang.'.$res->status) }}</span>
                                        <div class="small fw-semibold mt-1">{{ number_format($res->price, 2) }}</div>
                                        @if($res->payment)
                                            <span class="badge {{ $res->payment->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} mt-1">
                                                {{ __('lang.'.$res->payment->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted small">{{ __('lang.no_reservations') }}</div>
                @endif
            </div>
        </div>

    </div>{{-- /right --}}
</div>

{{-- ── Ratings & Reviews ── --}}
<div class="card card-soft shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">{{ __('lang.ratings_and_reviews') }}</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold fs-5">{{ number_format($unite->ratings_avg_rating ?? 0, 1) }}</span>
                <span class="text-warning" style="font-size:14px">
                    @php $avg = round($unite->ratings_avg_rating ?? 0); @endphp
                    @for($i = 1; $i <= 5; $i++)
                        {{ $i <= $avg ? '★' : '☆' }}
                    @endfor
                </span>
                <span class="text-muted small">({{ $unite->ratings_count ?? 0 }})</span>
            </div>
        </div>

        @forelse($unite->ratings as $rating)
            <div class="d-flex gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:38px;height:38px;font-weight:600;color:var(--accent)">
                    {{ mb_substr($rating->user->name ?? '?', 0, 1) }}
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-semibold small">{{ $rating->user->name ?? __('lang.deleted_user') }}</span>
                        <span class="text-muted" style="font-size:11px">{{ $rating->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="text-warning" style="font-size:12px">
                        @for($i = 1; $i <= 5; $i++)
                            {{ $i <= $rating->rating ? '★' : '☆' }}
                        @endfor
                    </div>
                    @if($rating->review)
                        <div class="small text-muted mt-1">{{ $rating->review }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-muted small text-center py-3">{{ __('lang.no_ratings_yet') }}</div>
        @endforelse
    </div>
</div>

@endsection

@push('js')
@php
    $__dowLabels = [
        'saturday' => __('lang.saturday'), 'sunday' => __('lang.sunday'),
        'monday' => __('lang.monday'), 'tuesday' => __('lang.tuesday'),
        'wednesday' => __('lang.wednesday'), 'thursday' => __('lang.thursday'),
        'friday' => __('lang.friday'),
    ];
    $__monthLabels = [
        __('lang.january'), __('lang.february'), __('lang.march'), __('lang.april'),
        __('lang.may'), __('lang.june'), __('lang.july'), __('lang.august'),
        __('lang.september'), __('lang.october'), __('lang.november'), __('lang.december'),
    ];
    $__periodLabels = [
        'morning' => __('lang.morning'), 'evening' => __('lang.evening'),
        'full_day' => __('lang.full_day'), 'custom' => __('lang.custom'),
    ];
@endphp
<script>
(function () {
    const UNITE_ID = {{ $unite->id }};
    // BUG FIX: this used to be built from config('app.url') — a
    // server-rendered value that silently drifts out of sync with
    // reality if APP_URL in .env doesn't exactly match the scheme the
    // site is actually served over. If APP_URL was set to
    // "http://weekend.com" while the page was actually loaded via
    // "https://weekend.com", every fetch() call below would try to reach
    // an http:// endpoint from an https:// page — which browsers block
    // outright as mixed content (visible in devtools as "Mixed Block"),
    // never even reaching the server. window.location.origin always
    // matches whatever scheme+host the page was ACTUALLY loaded with, so
    // this is immune to that class of misconfiguration entirely.
    const API_BASE = window.location.origin + '/api';
    const cache    = {};   // "year-month" → dates array

    const monthTitleEl = document.getElementById('cal-month-title');
    const dowRowEl      = document.getElementById('cal-dow-row');
    const daysGridEl    = document.getElementById('cal-days-grid');
    const loadingEl     = document.getElementById('cal-loading');
    const errorEl       = document.getElementById('cal-error');
    const slotsWrapEl   = document.getElementById('cal-slots-wrap');
    const slotsDateEl   = document.getElementById('cal-slots-date');
    const slotsPanelEl  = document.getElementById('cal-slots-panel');

    // Saturday-first week, matching the Saudi calendar convention shown in
    // the reference design.
    const DOW_KEYS = ['saturday','sunday','monday','tuesday','wednesday','thursday','friday'];
    const DOW_LABELS = @json($__dowLabels);
    const MONTH_LABELS = @json($__monthLabels);
    const PERIOD_LABELS = @json($__periodLabels);

    // availability (per-day) → dot class
    const DOT_CLASS = {
        available: 'dot-available',
        partially_available: 'dot-available',
        fully_booked: 'dot-unavailable',
        unavailable: 'dot-holiday',
        past: 'dot-muted',
    };

    let viewYear  = {{ now()->year }};
    let viewMonth = {{ now()->month }};
    let selectedDate = null;

    async function fetchMonth(year, month) {
        const key = `${year}-${month}`;
        if (cache[key]) return cache[key];

        loadingEl.classList.remove('d-none');
        errorEl.classList.add('d-none');

        try {
            const res  = await fetch(`${API_BASE}/unites/${UNITE_ID}/availability?year=${year}&month=${month}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'API error');
            cache[key] = json.data.dates;
            return cache[key];
        } catch (e) {
            errorEl.textContent = e.message;
            errorEl.classList.remove('d-none');
            return [];
        } finally {
            loadingEl.classList.add('d-none');
        }
    }

    function renderDowRow() {
        dowRowEl.innerHTML = DOW_KEYS.map(k => `<div class="cal-dow">${DOW_LABELS[k]}</div>`).join('');
    }

    async function renderMonth(year, month) {
        monthTitleEl.textContent = `${MONTH_LABELS[month - 1]} ${year}`;

        const dates = await fetchMonth(year, month);
        const byDate = {};
        dates.forEach(d => byDate[d.date] = d);

        const firstOfMonth = new Date(year, month - 1, 1);
        // JS getDay(): 0=Sunday..6=Saturday. Convert to Saturday-first index (0=Sat..6=Fri).
        const jsDow = firstOfMonth.getDay();
        const leadingBlanks = (jsDow + 1) % 7;

        const daysInMonth = new Date(year, month, 0).getDate();

        const cells = [];
        for (let i = 0; i < leadingBlanks; i++) cells.push(null);
        for (let day = 1; day <= daysInMonth; day++) cells.push(day);
        while (cells.length % 7 !== 0) cells.push(null);

        daysGridEl.innerHTML = cells.map(day => {
            if (!day) return `<div class="cal-day"><div class="cal-day-num">&nbsp;</div></div>`;

            const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const info = byDate[dateStr];
            const isPast = info?.is_past;
            const dotClass = info ? (DOT_CLASS[info.availability] ?? 'dot-muted') : null;
            const isSelected = selectedDate === dateStr;

            return `
                <div class="cal-day ${!isPast ? 'is-current' : ''} ${isSelected ? 'is-selected' : ''}" data-date="${dateStr}">
                    <div class="cal-day-num">${day}</div>
                    ${dotClass ? `<div class="cal-day-dot ${dotClass}"></div>` : '<div class="cal-day-dot dot-muted"></div>'}
                </div>`;
        }).join('');

        daysGridEl.querySelectorAll('.cal-day.is-current').forEach(el => {
            el.addEventListener('click', () => selectDate(el.dataset.date));
        });
    }

    function renderSlots(entry) {
        if (!entry || !entry.periods || !entry.periods.length) {
            slotsPanelEl.innerHTML = `<div class="text-muted small">{{ __('lang.no_slots_configured_for_day') }}</div>`;
            return;
        }

        slotsPanelEl.innerHTML = entry.periods.map(p => {
            const stateClass = p.availability === 'booked' ? 'slot-booked'
                              : p.availability === 'past' ? 'slot-past'
                              : 'slot-available';
            return `
                <div class="cal-slot-chip ${stateClass}">
                    <div class="fw-semibold">${PERIOD_LABELS[p.period_type] ?? p.period_type}</div>
                    <div>${p.from_time} – ${p.to_time}</div>
                    ${p.price > 0 ? `<div class="fw-semibold">${Number(p.price).toFixed(2)}</div>` : ''}
                </div>`;
        }).join('');
    }

    async function selectDate(dateStr) {
        selectedDate = dateStr;
        renderMonth(viewYear, viewMonth); // re-render to show the selection highlight

        slotsWrapEl.classList.remove('d-none');
        slotsDateEl.textContent = dateStr;
        slotsPanelEl.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

        try {
            const res  = await fetch(`${API_BASE}/unites/${UNITE_ID}/availability/date?date=${dateStr}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            renderSlots(json.data);
        } catch (e) {
            slotsPanelEl.innerHTML = `<div class="text-danger small">${e.message}</div>`;
        }
    }

    document.getElementById('cal-prev').addEventListener('click', () => {
        viewMonth--;
        if (viewMonth < 1) { viewMonth = 12; viewYear--; }
        renderMonth(viewYear, viewMonth);
    });
    document.getElementById('cal-next').addEventListener('click', () => {
        viewMonth++;
        if (viewMonth > 12) { viewMonth = 1; viewYear++; }
        renderMonth(viewYear, viewMonth);
    });

    document.addEventListener('DOMContentLoaded', function () {
        renderDowRow();
        renderMonth(viewYear, viewMonth);
    });
})();
</script>
@endpush
