@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Payment ' . $payment->reference_id)

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <div>
        <h4 class="fw-bold mb-0 font-monospace">{{ $payment->reference_id }}</h4>
        <div class="text-muted small">{{ __('lang.payment_detail') }}</div>
    </div>
    @php
        $statusColor = match($payment->status) {
            'paid'         => 'success',
            'pending'      => 'warning',
            'failed'       => 'danger',
            'refunded'     => 'secondary',
            'refund_failed'=> 'danger',
            default        => 'light',
        };
    @endphp
    <span class="badge bg-{{ $statusColor }}{{ $payment->status === 'pending' ? ' text-dark' : '' }} fs-6 px-3 py-2">
        {{ __('lang.'.$payment->status) }}
    </span>
</div>

<div class="row g-4">
    {{-- LEFT --}}
    <div class="col-lg-7">

        {{-- Core details --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.payment_information') }}</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.reference') }}</div>
                        <div class="fw-semibold font-monospace">{{ $payment->reference_id }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.gateway_order_id') }}</div>
                        <div class="small text-break">{{ $payment->payment_id ?: '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.th_amount') }}</div>
                        <div class="fw-bold fs-5">{{ number_format($payment->amount, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.th_type') }}</div>
                        <div><span class="badge bg-light text-dark border">{{ $payment->payment_type === 'cash' ? __('lang.cash_payment_type') : ucfirst($payment->payment_type) }}</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.phone_label') }}</div>
                        <div>{{ $payment->phone ?: '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.created_at') }}</div>
                        <div>{{ $payment->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('lang.last_updated') }}</div>
                        <div>{{ $payment->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Linked record --}}
        @if($payment->reservation)
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.linked_reservation') }}</h6>
                @php $res = $payment->reservation; @endphp
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.reservation_id') }}</div>
                        <div class="fw-semibold">#{{ $res->id }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.th_date') }}</div>
                        <div>{{ optional($res->reservation_date)->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.th_period') }}</div>
                        <div>{{ __('lang.'.$res->period_type) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.time_label') }}</div>
                        <div>{{ $res->from_time }} – {{ $res->to_time }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.th_price') }}</div>
                        <div class="fw-semibold">{{ number_format($res->price, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.status') }}</div>
                        @php $rc = match($res->status) { 'confirmed'=>'success','pending'=>'warning','cancelled'=>'danger',default=>'secondary' }; @endphp
                        <span class="badge bg-{{ $rc }}{{ $res->status==='pending'?' text-dark':'' }}">{{ __('lang.'.$res->status) }}</span>
                    </div>
                    @if($res->unite)
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('lang.venue_link') }}</div>
                            <div class="fw-semibold">{{ $res->unite->name }}</div>
                            <div class="text-muted small">{{ __('lang.'.$res->unite->type) }} · {{ $res->unite->department->name ?? '—' }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($payment->subscription)
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.linked_subscription') }}</h6>
                @php $sub = $payment->subscription; @endphp
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.subscription_id') }}</div>
                        <div class="fw-semibold">#{{ $sub->id }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.th_type') }}</div>
                        <div><span class="badge bg-light text-dark border">{{ __('lang.'.$sub->type) }}</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('lang.status') }}</div>
                        @php $sc = match($sub->status) { 'active'=>'success','pending'=>'warning','cancelled'=>'danger',default=>'secondary' }; @endphp
                        <span class="badge bg-{{ $sc }}{{ $sub->status==='pending'?' text-dark':'' }}">{{ __('lang.'.$sub->status) }}</span>
                    </div>
                    @if($sub->start_date)
                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('lang.start_label') }}</div>
                            <div>{{ $sub->start_date }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('lang.end_label') }}</div>
                            <div>{{ $sub->end_date ?? '—' }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Line items --}}
        @if($payment->items->count())
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.line_items') }}</h6>
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('lang.th_item') }}</th><th>{{ __('lang.unit_price') }}</th><th>{{ __('lang.th_qty') }}</th><th class="text-end">{{ __('lang.total') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($payment->items as $item)
                        <tr>
                            <td>
                                <div class="fw-semibold small">{{ $item->name }}</div>
                                @if($item->item_number)<div class="text-muted" style="font-size:11px">{{ $item->item_number }}</div>@endif
                            </td>
                            <td class="small">{{ number_format($item->price, 2) }}</td>
                            <td class="small">{{ $item->quantity }}</td>
                            <td class="text-end fw-semibold small">{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">{{ __('lang.total') }}</td>
                            <td class="text-end fw-bold">{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

    </div>{{-- /left --}}

    {{-- RIGHT --}}
    <div class="col-lg-5">

        {{-- Customer --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.customer') }}</h6>
                @if($payment->user)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        @if($payment->user->photo)
                            <img src="{{ asset($payment->user->photo) }}" class="rounded-circle" width="48" height="48" style="object-fit:cover">
                        @else
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold"
                                 style="width:48px;height:48px;font-size:18px">
                                {{ strtoupper(substr($payment->user->name,0,1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="fw-semibold">{{ $payment->user->name }}</div>
                            <div class="text-muted small">{{ $payment->user->email }}</div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.phone_label') }}</div>
                            <div class="small">{{ $payment->user->phone ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.th_type') }}</div>
                            <div><span class="badge bg-light text-dark border">{{ __('lang.'.$payment->user->type) }}</span></div>
                        </div>
                    </div>
                @else
                    <div class="text-muted small">{{ __('lang.no_user_linked') }}</div>
                @endif
            </div>
        </div>

        {{-- Status timeline --}}
        <div class="card card-soft shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.status') }}</h6>
                @php
                    $timeline = [
                        ['status'=>'pending',  'label'=>__('lang.payment_initiated'),     'icon'=>'🕐', 'done' => true],
                        ['status'=>'paid',     'label'=>__('lang.payment_confirmed'),     'icon'=>'✅', 'done' => in_array($payment->status,['paid','refunded','refund_failed'])],
                        ['status'=>'refunded', 'label'=>__('lang.refunded_to_customer'),  'icon'=>'↩️', 'done' => $payment->status === 'refunded'],
                    ];
                    if (in_array($payment->status, ['failed','refund_failed'])) {
                        $timeline[] = ['status'=>'failed','label'=>__('lang.transaction_failed'),'icon'=>'❌','done'=>true];
                    }
                @endphp
                <div class="d-flex flex-column gap-2">
                    @foreach($timeline as $step)
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:18px;width:28px;text-align:center;">{{ $step['icon'] }}</span>
                        <div class="{{ $step['done'] ? 'text-dark fw-semibold' : 'text-muted' }} small">
                            {{ $step['label'] }}
                        </div>
                        @if($step['done'])
                            <span class="badge bg-success ms-auto">{{ __('lang.done_status') }}</span>
                        @else
                            <span class="badge bg-light text-muted border ms-auto">{{ __('lang.pending') }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Refund notice --}}
        @if($payment->status === 'refund_failed')
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">{{ __('lang.refund_failed_notice') }}</div>
            <div class="small">
                {{ __('lang.manual_refund_help') }}
                <code>{{ $payment->payment_id }}</code>
            </div>
        </div>
        @endif

    </div>{{-- /right --}}
</div>

@endsection
