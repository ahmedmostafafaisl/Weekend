{{--
    Shared partial — Deposit & Insurance card
    Variables available:
      $unite             — Unite model (null on create)
      $insurancePolicies — collection of InsurancePolicy models
      $readonly          — bool: true = show only (no form inputs)
--}}
@php
    $readonly          = $readonly ?? false;
    $insurancePolicies = $insurancePolicies ?? collect();
@endphp

<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.deposit_insurance') }}</h6>

        @if($readonly)
            {{-- ── Show mode ── --}}
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small mb-1">{{ __('lang.deposit_required') }}</div>
                    @if($unite->reservation_deposit)
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                            {{ __('lang.yes') }} —
                            {{ number_format($unite->reservation_deposit_amount, 2) }}
                            ({{ $unite->reservation_deposit_type === 'amount' ? __('lang.fixed_amount') : __('lang.percentage_symbol') }})
                        </span>
                    @else
                        <span class="badge bg-secondary">{{ __('lang.no_deposit') }}</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="text-muted small mb-1">{{ __('lang.insurance_required') }}</div>
                    @if($unite->insurance)
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                            {{ __('lang.yes') }} — {{ number_format($unite->insurance_amount, 2) }}
                        </span>
                    @else
                        <span class="badge bg-secondary">{{ __('lang.no_insurance') }}</span>
                    @endif
                </div>

                @if($unite->insurancePolicy)
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('lang.insurance_policy') }}</div>
                        <div class="fw-semibold">{{ $unite->insurancePolicy->name }}</div>
                        @if($unite->insurancePolicy->description)
                            <div class="text-muted small">{{ $unite->insurancePolicy->description }}</div>
                        @endif
                    </div>
                @endif
            </div>

        @else
            {{-- ── Edit / Create mode ── --}}
            <div class="row g-3">

                {{-- Deposit toggle --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('lang.deposit_required') }}</label>
                    <select class="form-select" name="reservation_deposit" id="depositToggle">
                        <option value="0" {{ old('reservation_deposit', $unite->reservation_deposit ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                        <option value="1" {{ old('reservation_deposit', $unite->reservation_deposit ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                    </select>
                </div>

                <div class="col-md-3" id="depositTypeWrap">
                    <label class="form-label">{{ __('lang.deposit_type') }}</label>
                    <select class="form-select" name="reservation_deposit_type">
                        <option value="amount"
                            {{ old('reservation_deposit_type', $unite->reservation_deposit_type ?? 'amount') === 'amount' ? 'selected' : '' }}>
                            {{ __('lang.fixed_amount') }}
                        </option>
                        <option value="percentage"
                            {{ old('reservation_deposit_type', $unite->reservation_deposit_type ?? '') === 'percentage' ? 'selected' : '' }}>
                            {{ __('lang.percentage_symbol') }}
                        </option>
                    </select>
                </div>

                <div class="col-md-3" id="depositAmountWrap">
                    <label class="form-label">{{ __('lang.deposit_amount') }}</label>
                    <input class="form-control" name="reservation_deposit_amount"
                           type="number" step="0.01" min="0"
                           value="{{ old('reservation_deposit_amount', $unite->reservation_deposit_amount ?? '') }}"
                           placeholder="0.00">
                    @error('reservation_deposit_amount')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3"></div>{{-- spacer --}}

                {{-- Insurance toggle --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('lang.insurance_required') }}</label>
                    <select class="form-select" name="insurance" id="insuranceToggle">
                        <option value="0" {{ old('insurance', $unite->insurance ?? 0) == 0 ? 'selected' : '' }}>{{ __('lang.no') }}</option>
                        <option value="1" {{ old('insurance', $unite->insurance ?? 0) == 1 ? 'selected' : '' }}>{{ __('lang.yes') }}</option>
                    </select>
                </div>

                <div class="col-md-3" id="insuranceAmountWrap">
                    <label class="form-label">{{ __('lang.insurance_amount') }}</label>
                    <input class="form-control" name="insurance_amount"
                           type="number" step="0.01" min="0"
                           value="{{ old('insurance_amount', $unite->insurance_amount ?? '') }}"
                           placeholder="0.00">
                    @error('insurance_amount')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                @if($insurancePolicies->count())
                    <div class="col-md-6" id="insurancePolicyWrap">
                        <label class="form-label">{{ __('lang.insurance_policy') }}</label>
                        <select class="form-select" name="insurance_policy_id">
                            <option value="">{{ __('lang.select_policy') }}</option>
                            @foreach($insurancePolicies as $policy)
                                <option value="{{ $policy->id }}"
                                    {{ old('insurance_policy_id', $unite->insurance_policy_id ?? '') == $policy->id ? 'selected' : '' }}>
                                    {{ $policy->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('insurance_policy_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

            </div>
        @endif
    </div>
</div>

@unless($readonly)
@push('js')
<script>
(function () {
    const depositToggle  = document.getElementById('depositToggle');
    const insuranceToggle = document.getElementById('insuranceToggle');

    function applyDeposit() {
        const on = depositToggle?.value === '1';
        ['depositTypeWrap','depositAmountWrap'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = on ? '' : 'none';
        });
    }

    function applyInsurance() {
        const on = insuranceToggle?.value === '1';
        ['insuranceAmountWrap','insurancePolicyWrap'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = on ? '' : 'none';
        });
    }

    depositToggle?.addEventListener('change', applyDeposit);
    insuranceToggle?.addEventListener('change', applyInsurance);
    applyDeposit();
    applyInsurance();
})();
</script>
@endpush
@endunless
