@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Reservations')

@section('content')
@php $me = auth('admin')->user(); @endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.reservations') }}</h4>
        <div class="text-muted small">{{ __('lang.all_bookings_every_venue') }}</div>
    </div>
    <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}"
       class="btn btn-outline-secondary btn-sm">
        {{ __('lang.export_csv') }}
    </a>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['label' => __('lang.total'),            'value' => $totals->total,            'color' => 'secondary', 'filter' => null],
            ['label' => __('lang.confirmed'),        'value' => $totals->confirmed,        'color' => 'success',   'filter' => 'confirmed'],
            ['label' => __('lang.pending'),          'value' => $totals->pending,          'color' => 'warning',   'filter' => 'pending'],
            ['label' => __('lang.awaiting_approval'),'value' => $totals->pending_approval ?? 0, 'color' => 'info', 'filter' => 'pending_approval'],
            ['label' => __('lang.cancelled'),        'value' => $totals->cancelled,        'color' => 'danger',    'filter' => 'cancelled'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="col-6 col-md-3 col-lg-2">
        @if($card['filter'])
            <a href="{{ request()->fullUrlWithQuery(['status' => $card['filter'], 'page' => null]) }}"
               class="card card-soft shadow-sm text-center py-3 d-block text-decoration-none">
        @else
            <div class="card card-soft shadow-sm text-center py-3">
        @endif
            <div class="fw-bold fs-4 text-{{ $card['color'] }}">{{ number_format($card['value']) }}</div>
            <div class="text-muted small">{{ $card['label'] }}</div>
        @if($card['filter']) </a> @else </div> @endif
    </div>
    @endforeach
    <div class="col-6 col-md-3 col-lg-2">
        <div class="card card-soft shadow-sm text-center py-3">
            <div class="fw-bold fs-5 text-success">{{ number_format($revenue, 0) }}</div>
            <div class="text-muted small">{{ __('lang.revenue_sar_label') }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card card-soft shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET">
            {{-- Search --}}
            <div class="col-md-3">
                <input class="form-control form-control-sm" name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('lang.search_placeholder') }}">
            </div>

            {{-- Reservation status --}}
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">{{ __('lang.all_statuses') }}</option>
                    @foreach(['confirmed'=>__('lang.confirmed'),'pending'=>__('lang.pending'),'cancelled'=>__('lang.cancelled')] as $s=>$sLabel)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ $sLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment status --}}
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="payment_status">
                    <option value="">{{ __('lang.any_payment') }}</option>
                    @foreach(['paid'=>__('lang.paid'),'pending'=>__('lang.pending'),'failed'=>__('lang.failed'),'refunded'=>__('lang.refunded'),'refund_failed'=>__('lang.refund_failed')] as $s=>$sLabel)
                        <option value="{{ $s }}" {{ request('payment_status') === $s ? 'selected' : '' }}>
                            {{ $sLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Venue type --}}
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="type">
                    <option value="">{{ __('lang.all_types') }}</option>
                    @foreach(['stadium'=>__('lang.stadium'),'hall'=>__('lang.hall'),'lounge'=>__('lang.lounge'),'camp'=>__('lang.camp')] as $t=>$tLabel)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
                            {{ $tLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Department --}}
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="department_id">
                    <option value="">{{ __('lang.all_departments') }}</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Period --}}
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="period_type">
                    <option value="">{{ __('lang.any_period') }}</option>
                    @foreach(['morning'=>__('lang.morning'),'evening'=>__('lang.evening'),'full_day'=>__('lang.full_day'),'custom'=>__('lang.custom')] as $p=>$pLabel)
                        <option value="{{ $p }}" {{ request('period_type') === $p ? 'selected' : '' }}>
                            {{ $pLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date range --}}
            <div class="col-md-2">
                <div class="input-group input-group-sm">
                    <input type="date" class="form-control form-control-sm" name="date_from"
                           value="{{ request('date_from') }}" placeholder="{{ __('lang.th_from') }}">
                    <input type="date" class="form-control form-control-sm" name="date_to"
                           value="{{ request('date_to') }}" placeholder="{{ __('lang.th_to') }}">
                </div>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-accent btn-sm px-3">{{ __('lang.filter') }}</button>
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('lang.clear') }}</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>{{ __('lang.th_customer') }}</th>
                        <th>{{ __('lang.th_venue') }}</th>
                        <th>{{ __('lang.th_date') }}</th>
                        <th>{{ __('lang.th_period') }}</th>
                        <th>{{ __('lang.th_guests') }}</th>
                        <th>{{ __('lang.th_price') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                        <th>{{ __('lang.th_payment') }}</th>
                        <th class="pe-3">{{ __('lang.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reservations as $res)
                    @php
                        $resBadge = match($res->status) {
                            'confirmed'        => 'success',
                            'pending'          => 'warning',
                            'pending_approval' => 'info',
                            'cancelled'        => 'danger',
                            default            => 'secondary',
                        };
                        $payBadge = match($res->payment?->status) {
                            'paid'         => 'success',
                            'pending'      => 'warning',
                            'refunded'     => 'info',
                            'failed','refund_failed' => 'danger',
                            default        => 'secondary',
                        };
                        $isPast = $res->reservation_date?->isPast();
                    @endphp
                    <tr class="{{ $isPast ? 'text-muted' : '' }}">
                        <td class="ps-3 small text-muted">{{ $res->id }}</td>

                        {{-- Customer --}}
                        <td>
                            <div class="fw-semibold small">{{ $res->user?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $res->user?->email ?? '' }}</div>
                        </td>

                        {{-- Venue --}}
                        <td>
                            <div class="small fw-semibold">{{ $res->unite?->name ?? '—' }}</div>
                            <div class="d-flex gap-1 mt-1">
                                @if($res->unite)
                                    <span class="badge rounded-pill bg-light text-dark border" style="font-size:10px">
                                        {{ __('lang.'.$res->unite->type) }}
                                    </span>
                                @endif
                                <span class="text-muted" style="font-size:11px">
                                    {{ $res->unite?->department?->name ?? '' }}
                                </span>
                            </div>
                        </td>

                        {{-- Date --}}
                        <td class="small">
                            <div>{{ $res->reservation_date?->format('d M Y') ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px">
                                {{ $res->from_time ? substr($res->from_time,0,5).' – '.substr($res->to_time,0,5) : '' }}
                            </div>
                        </td>

                        {{-- Period --}}
                        <td>
                            <span class="badge bg-light text-dark border" style="font-size:10px">
                                {{ __('lang.'.$res->period_type) }}
                            </span>
                        </td>

                        {{-- Guests + notes --}}
                        <td>
                            @if($res->guest_count)
                                <span class="small">{{ $res->guest_count }} {{ __('lang.guests_word') }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                            @if($res->notes)
                                <div class="text-muted" style="font-size:10px;max-width:120px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"
                                     title="{{ $res->notes }}">
                                    {{ $res->notes }}
                                </div>
                            @endif
                        </td>

                        {{-- Price --}}
                        <td class="small fw-semibold">
                            {{ number_format((float)$res->price, 2) }}
                            @if($res->payment?->discount_amount)
                                <div class="text-success" style="font-size:10px">
                                    -{{ number_format((float)$res->payment->discount_amount, 2) }}
                                </div>
                            @endif
                        </td>

                        {{-- Reservation status --}}
                        <td>
                            <span class="badge bg-{{ $resBadge }}">{{ __('lang.'.$res->status) }}</span>
                        </td>

                        {{-- Payment status --}}
                        <td>
                            @if($res->payment)
                                <span class="badge bg-{{ $payBadge }}">
                                    {{ __('lang.'.$res->payment->status) }}
                                </span>
                                <div class="text-muted font-monospace" style="font-size:10px">
                                    {{ $res->payment->reference_id }}
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                @if($res->payment)
                                <a href="{{ route('admin.payments.show', $res->payment->id) }}"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2"
                                   style="font-size:11px">
                                    {{ __('lang.payment_link') }}
                                </a>
                                @else
                                <span class="btn btn-sm btn-outline-secondary py-0 px-2 disabled"
                                      style="font-size:11px;opacity:.45">
                                    {{ __('lang.no_payment') }}
                                </span>
                                @endif
                                <a href="{{ route('unites.show', $res->unite_id) }}"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2"
                                   style="font-size:11px">
                                    {{ __('lang.venue_link') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            {{ __('lang.no_reservations_match_filters') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        {{ __('lang.showing') }} {{ $reservations->firstItem() }}–{{ $reservations->lastItem() }}
        {{ __('lang.of_total') }} {{ number_format($reservations->total()) }} {{ __('lang.reservations_word') }}
    </div>
    {{ $reservations->links() }}
</div>

@endsection
