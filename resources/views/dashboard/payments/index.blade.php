@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Payments')

@section('content')
@php $me = auth('admin')->user() ?? auth()->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.payments') }}</h4>
        <div class="text-muted small">{{ __('lang.all_transactions_reservations_subscriptions') }}</div>
    </div>
</div>

{{-- Summary cards --}}
@php
    $all       = $payments->total();
    $paid      = $payments->getCollection()->where('status','paid')->count();
    $pending   = $payments->getCollection()->where('status','pending')->count();
    $failed    = $payments->getCollection()->whereIn('status',['failed','refund_failed'])->count();
    $refunded  = $payments->getCollection()->where('status','refunded')->count();
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-lg-2">
        <div class="card card-soft shadow-sm text-center py-3">
            <div class="fw-bold fs-4">{{ $all }}</div>
            <div class="text-muted small">{{ __('lang.total') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <a href="?status=paid" class="card card-soft shadow-sm text-center py-3 d-block text-decoration-none">
            <div class="fw-bold fs-4 text-success">{{ $paid }}</div>
            <div class="text-muted small">{{ __('lang.paid') }}</div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <a href="?status=pending" class="card card-soft shadow-sm text-center py-3 d-block text-decoration-none">
            <div class="fw-bold fs-4 text-warning">{{ $pending }}</div>
            <div class="text-muted small">{{ __('lang.pending') }}</div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <a href="?status=failed" class="card card-soft shadow-sm text-center py-3 d-block text-decoration-none">
            <div class="fw-bold fs-4 text-danger">{{ $failed }}</div>
            <div class="text-muted small">{{ __('lang.failed') }}</div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <a href="?status=refunded" class="card card-soft shadow-sm text-center py-3 d-block text-decoration-none">
            <div class="fw-bold fs-4 text-secondary">{{ $refunded }}</div>
            <div class="text-muted small">{{ __('lang.refunded') }}</div>
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card card-soft shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-3">
                <input class="form-control" name="reference_id"
                       value="{{ request('reference_id') }}"
                       placeholder="{{ __('lang.reference_id_placeholder') }}">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">{{ __('lang.all_statuses') }}</option>
                    @foreach(['pending'=>__('lang.pending'),'paid'=>__('lang.paid'),'failed'=>__('lang.failed'),'refunded'=>__('lang.refunded'),'refund_failed'=>__('lang.refund_failed')] as $s=>$sLabel)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ $sLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="payment_type">
                    <option value="">{{ __('lang.all_types') }}</option>
                    <option value="geidea"  {{ request('payment_type') === 'geidea'  ? 'selected' : '' }}>{{ __('lang.geidea') }}</option>
                    <option value="cash"    {{ request('payment_type') === 'cash'    ? 'selected' : '' }}>{{ __('lang.cash_payment_type') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <input class="form-control" name="phone"
                       value="{{ request('phone') }}"
                       placeholder="{{ __('lang.phone_placeholder') }}">
            </div>
            <div class="col-md-1">
                <button class="btn btn-outline-secondary w-100">{{ __('lang.search') }}</button>
            </div>
            @if(request()->hasAny(['reference_id','status','payment_type','phone']))
                <div class="col-md-1">
                    <a href="{{ request()->url() }}" class="btn btn-outline-danger w-100">✕</a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.th_hash') }}</th>
                        <th>{{ __('lang.reference') }}</th>
                        <th>{{ __('lang.th_customer') }}</th>
                        <th>{{ __('lang.th_for') }}</th>
                        <th>{{ __('lang.th_type') }}</th>
                        <th>{{ __('lang.th_amount') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                        <th>{{ __('lang.th_date') }}</th>
                        <th class="text-end">{{ __('lang.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $p)
                    @php
                        $statusColor = match($p->status) {
                            'paid'         => 'success',
                            'pending'      => 'warning',
                            'failed'       => 'danger',
                            'refunded'     => 'secondary',
                            'refund_failed'=> 'danger',
                            default        => 'light',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $p->id }}</td>

                        <td>
                            <div class="fw-semibold small font-monospace">{{ $p->reference_id }}</div>
                            @if($p->payment_id)
                                <div class="text-muted" style="font-size:11px">{{ Str::limit($p->payment_id, 24) }}</div>
                            @endif
                        </td>

                        <td>
                            @if($p->user)
                                <div class="small fw-semibold">{{ $p->user->name }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $p->user->phone ?? $p->phone ?? '—' }}</div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        <td>
                            @if($p->reservation)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    {{ __('lang.reservation_word') }}
                                </span>
                                <div class="text-muted small">
                                    {{ optional($p->reservation->reservation_date)->format('d M Y') }}
                                </div>
                            @elseif($p->subscription)
                                <span class="badge bg-purple bg-opacity-10 text-secondary border">
                                    {{ __('lang.subscription_word') }}
                                </span>
                                <div class="text-muted small">{{ __('lang.'.$p->subscription->type) }}</div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $p->payment_type === 'cash' ? __('lang.cash_payment_type') : ucfirst($p->payment_type) }}
                            </span>
                        </td>

                        <td class="fw-semibold">{{ number_format($p->amount, 2) }}</td>

                        <td>
                            <span class="badge bg-{{ $statusColor }}{{ in_array($p->status,['pending']) ? ' text-dark' : '' }}">
                                {{ __('lang.'.$p->status) }}
                            </span>
                        </td>

                        <td class="text-muted small">{{ $p->created_at->format('d M Y') }}</td>

                        <td class="text-end">
                            <a href="{{ route('admin.payments.show', $p->id) }}"
                               class="btn btn-sm btn-outline-secondary">{{ __('lang.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">{{ __('lang.no_payments_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
        <div class="card-footer bg-transparent">
            {{ $payments->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection
