@extends('provider.layouts.app')
@section('title','Weekend | '.__('lang.my_transfers'))
@section('content')
@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">💸 {{ __('lang.my_transfers') }}</h4><div class="text-muted small">{{ __('lang.funds_received_from_platform') }}</div></div>
    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#requestModal">+ {{ __('lang.request_payout') }}</button>
</div>

{{-- Transfer Policy Banner --}}
@if($policy)
<div class="card card-soft shadow-sm mb-4" style="border-left:4px solid #6f00ff">
    <div class="card-body py-3">
        <div class="fw-semibold small mb-1">📋 {{ __('lang.transfer_policy_colon') }} {{ $policy->title }}</div>
        <div class="d-flex flex-wrap gap-3 small text-muted">
            <span>⏱ {{ __('lang.transferred_within_days') }} <strong>{{ $policy->transfer_days }} {{ __('lang.days_suffix') }}</strong></span>
            <span>🏦 {{ __('lang.methods_colon') }} <strong>{{ implode(', ', array_map(fn($m) => __('lang.'.$m), $policy->transfer_methods ?? [])) }}</strong></span>
            <span>🧾 {{ __('lang.tax_rate') }}: <strong>{{ $policy->tax_rate }}%</strong></span>
            <span>💼 {{ __('lang.platform_fee') }}: <strong>{{ $policy->platform_fee_rate }}%</strong></span>
        </div>
    </div>
</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-soft text-center py-3 shadow-sm">
            <div class="fw-bold fs-5 text-success">SAR {{ number_format($summary['total_received'],2) }}</div>
            <div class="text-muted small">{{ __('lang.total_received') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-soft text-center py-3 shadow-sm">
            <div class="fw-bold fs-5 text-warning">SAR {{ number_format($summary['pending'],2) }}</div>
            <div class="text-muted small">{{ __('lang.pending') }}</div>
        </div>
    </div>
</div>

{{-- Transfers Table --}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.transfer_history') }}</h6>
        @forelse($transfers as $t)
        <div class="border rounded p-3 mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">SAR {{ number_format($t->net_amount,2) }} <span class="text-muted small">net</span></div>
                    <div class="text-muted small">{{ __('lang.gross_word') }} {{ number_format($t->amount,2) }} — {{ __('lang.tax_word') }} {{ number_format($t->tax_amount,2) }} — {{ __('lang.fee_word') }} {{ number_format($t->platform_fee,2) }}</div>
                    <div class="text-muted small mt-1">
                        {{ __('lang.'.$t->method) }}
                        @if($t->reference) · {{ __('lang.ref_colon') }} <code>{{ $t->reference }}</code> @endif
                        @if($t->scheduled_date) · {{ __('lang.scheduled_colon') }} {{ $t->scheduled_date->format('d M Y') }} @endif
                        @if($t->transferred_at) · {{ __('lang.transferred_colon') }} {{ $t->transferred_at->format('d M Y') }} @endif
                    </div>
                </div>
                @php $c=['pending'=>'warning','processing'=>'info','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'] @endphp
                <span class="badge bg-{{ $c[$t->status] ?? 'secondary' }}">{{ __('lang.'.$t->status) }}</span>
            </div>
            @if($t->notes)<div class="text-muted small mt-1">📝 {{ $t->notes }}</div>@endif
        </div>
        @empty
        <div class="text-muted small text-center py-3">{{ __('lang.no_transfers_yet') }}</div>
        @endforelse
        <div class="mt-3">{{ $transfers->links() }}</div>
    </div>
</div>

{{-- Transfer Requests --}}
<div class="card card-soft shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.my_payout_requests') }}</h6>
        @forelse($myRequests as $req)
        <div class="border rounded p-3 mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">SAR {{ number_format($req->requested_amount,2) }}</div>
                    <div class="text-muted small">{{ __('lang.'.$req->preferred_method) }} · {{ $req->created_at->format('d M Y') }}</div>
                    @if($req->admin_response)<div class="text-muted small mt-1">{{ __('lang.admin_response_colon') }} {{ $req->admin_response }}</div>@endif
                </div>
                @php $c=['pending'=>'warning','approved'=>'success','rejected'=>'danger'] @endphp
                <span class="badge bg-{{ $c[$req->status] ?? 'secondary' }}">{{ __('lang.'.$req->status) }}</span>
            </div>
        </div>
        @empty
        <div class="text-muted small text-center py-3">{{ __('lang.no_payout_requests_yet') }}</div>
        @endforelse
    </div>
</div>

{{-- Request Modal --}}
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header"><h5 class="modal-title fw-bold">{{ __('lang.request_payout') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('provider.transfers.request') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.amount_sar') }} <span class="text-danger">*</span></label>
                        <input type="number" name="requested_amount" class="form-control" step="0.01" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.preferred_method') }} <span class="text-danger">*</span></label>
                        <select name="preferred_method" class="form-select" required>
                            @foreach(['bank_transfer'=>__('lang.bank_transfer'),'cash'=>__('lang.cash'),'check'=>__('lang.check'),'digital_wallet'=>__('lang.digital_wallet')] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    @if($policy)
                    <div class="alert alert-light small mb-0">
                        {{ __('lang.transfers_processed_within') }} <strong>{{ $policy->transfer_days }} {{ __('lang.days_suffix') }}</strong>.
                        {{ __('lang.tax_rate') }} {{ $policy->tax_rate }}% + {{ __('lang.platform_fee') }} {{ $policy->platform_fee_rate }}% {{ __('lang.will_be_deducted') }}
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                    <button type="submit" class="btn btn-accent">{{ __('lang.submit_request') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
