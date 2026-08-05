@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | Service Fees')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold mb-1">{{ __('lang.service_fees') }}</h4>
        <div class="text-muted small">{{ __('lang.service_fees_subtitle') }}</div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- BUG FIX: the previous version used one <form> per row with inputs
         associated via the form="id" attribute (a valid HTML5 pattern),
         but it produced a plain GET to the update URL instead of the
         expected spoofed-PUT POST — a compatibility quirk that's hard to
         diagnose without live browser access. Replaced with a single
         shared form whose action/method-spoof/amount/is_active fields get
         populated by JS immediately before submitting — a more
         conventional, battle-tested pattern with no attribute-association
         involved at all. --}}
    <form id="feeForm" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" name="amount" id="feeFormAmount">
        <input type="hidden" name="is_active" id="feeFormActive">
    </form>

    <div class="card card-soft shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('lang.service_fee_category') }}</th>
                            <th style="width:220px">{{ __('lang.service_fee_amount') }}</th>
                            <th style="width:120px">{{ __('lang.status') }}</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\ServiceFee::KEYS as $key)
                            @php($fee = $fees[$key] ?? null)
                            <tr>
                                <td class="fw-semibold">
                                    {{ app()->getLocale() === 'ar' ? ($fee->label_ar ?? $key) : ($fee->label_en ?? $key) }}
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" class="form-control fee-amount-input"
                                               data-key="{{ $key }}" value="{{ $fee->amount ?? 0 }}">
                                        <span class="input-group-text">SAR</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input fee-active-input" type="checkbox"
                                               data-key="{{ $key }}"
                                               {{ ($fee->is_active ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label small text-muted">
                                            {{ ($fee->is_active ?? false) ? __('lang.enabled') : __('lang.disabled') }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary save-fee-btn"
                                            data-key="{{ $key }}">{{ __('lang.save') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('js')
    <script>
        // Base URL without the trailing key — each button substitutes its
        // own data-key when clicked, then submits the one shared form.
        const feeUpdateBaseUrl = @json(route('admin.service-fees.update', '__KEY__'));

        document.querySelectorAll('.save-fee-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const key = this.dataset.key;
                const amountInput = document.querySelector(`.fee-amount-input[data-key="${key}"]`);
                const activeInput = document.querySelector(`.fee-active-input[data-key="${key}"]`);
                const form = document.getElementById('feeForm');

                form.action = feeUpdateBaseUrl.replace('__KEY__', key);
                document.getElementById('feeFormAmount').value = amountInput.value;
                document.getElementById('feeFormActive').value = activeInput.checked ? '1' : '0';
                form.submit();
            });
        });
    </script>
    @endpush
@endsection
