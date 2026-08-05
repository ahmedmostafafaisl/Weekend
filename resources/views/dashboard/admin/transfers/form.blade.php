@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | ' . ($transfer ? __('lang.edit_transfer') : __('lang.new_transfer')))
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.transfers.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <h4 class="fw-bold mb-0">{{ $transfer ? __('lang.edit_transfer').' #'.$transfer->id : __('lang.new_transfer') }}</h4>
</div>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card card-soft shadow-sm" style="max-width:640px">
    <div class="card-body">
        <form action="{{ $transfer ? route('admin.transfers.update', $transfer) : route('admin.transfers.store') }}" method="POST">
            @csrf @if($transfer) @method('PUT') @endif

            @if(!$transfer)
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.provider') }} <span class="text-danger">*</span></label>
                <select name="user_id" class="form-select" required>
                    <option value="">{{ __('lang.select_provider_ellipsis') }}</option>
                    @foreach($providers as $p)
                        <option value="{{ $p->id }}" {{ old('user_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} — {{ $p->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.policy') }}</label>
                <select name="transfer_policy_id" class="form-select" id="policy_select" onchange="calcNet()">
                    <option value="">{{ __('lang.no_policy') }}</option>
                    @foreach($policies as $pl)
                        <option value="{{ $pl->id }}"
                            data-tax="{{ $pl->tax_rate }}"
                            data-fee="{{ $pl->platform_fee_rate }}"
                            {{ old('transfer_policy_id') == $pl->id ? 'selected' : '' }}>
                            {{ $pl->title }} ({{ __('lang.tax_label') }} {{ $pl->tax_rate }}% + {{ __('lang.fee_label') }} {{ $pl->platform_fee_rate }}%)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.amount_sar') }} <span class="text-danger">*</span></label>
                <input type="number" name="amount" id="amount_input" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required onkeyup="calcNet()">
                <div class="text-muted small mt-1" id="net_preview"></div>
            </div>
            @else
            {{-- Edit mode: only status, method, reference, notes --}}
            <div class="mb-3">
                <div class="small text-muted">{{ __('lang.provider') }}: <strong>{{ $transfer->provider?->name }}</strong></div>
                <div class="small text-muted">{{ __('lang.th_amount') }}: <strong>SAR {{ number_format($transfer->amount,2) }}</strong> → {{ __('lang.net_to_provider') }}: <strong>SAR {{ number_format($transfer->net_amount,2) }}</strong></div>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.method') }} <span class="text-danger">*</span></label>
                <select name="method" class="form-select" required>
                    @foreach(['bank_transfer' => __('lang.bank_transfer'),'cash' => __('lang.cash'),'check' => __('lang.check'),'digital_wallet' => __('lang.digital_wallet')] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('method', $transfer->method ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            @if($transfer)
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.status') }} <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach(['pending'=>__('lang.pending'),'processing'=>__('lang.processing'),'completed'=>__('lang.completed'),'failed'=>__('lang.failed'),'cancelled'=>__('lang.cancelled')] as $s=>$sLabel)
                        <option value="{{ $s }}" {{ old('status', $transfer->status) === $s ? 'selected' : '' }}>{{ $sLabel }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.scheduled_date') }}</label>
                <input type="date" name="scheduled_date" class="form-control" value="{{ old('scheduled_date', $transfer?->scheduled_date?->format('Y-m-d')) }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('lang.reference') }}</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference', $transfer->reference ?? '') }}" placeholder="{{ __('lang.reference') }}">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('lang.notes') }}</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $transfer->notes ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-accent">{{ $transfer ? __('lang.save_changes') : __('lang.create_transfer') }}</button>
            <a href="{{ route('admin.transfers.index') }}" class="btn btn-secondary ms-2">{{ __('lang.cancel') }}</a>
        </form>
    </div>
</div>

@if(!$transfer)
<script>
function calcNet() {
    const amount = parseFloat(document.getElementById('amount_input').value) || 0;
    const sel = document.getElementById('policy_select');
    const opt = sel.options[sel.selectedIndex];
    const tax = parseFloat(opt.dataset.tax || 0);
    const fee = parseFloat(opt.dataset.fee || 0);
    const taxAmt = +(amount * tax / 100).toFixed(2);
    const feeAmt = +(amount * fee / 100).toFixed(2);
    const net    = +(amount - taxAmt - feeAmt).toFixed(2);
    document.getElementById('net_preview').textContent =
        amount > 0 ? `Tax: ${taxAmt} SAR | Fee: ${feeAmt} SAR | Net to provider: ${net} SAR` : '';
}
</script>
@endif
@endsection
