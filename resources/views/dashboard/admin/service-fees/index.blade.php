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

    {{-- Edit and save every category in one step — a single form wrapping
         the whole table, submitted once, rather than a separate save per
         row. This is valid to wrap around the entire <table> (unlike
         wrapping individual <td> cells, which isn't valid HTML and was the
         root of an earlier bug here), so no JS workaround is needed either
         — a plain @method('PUT') form works correctly now that there's
         only one form for the whole page. --}}
    <form action="{{ route('admin.service-fees.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.service_fee_category') }}</th>
                                <th style="width:220px">{{ __('lang.service_fee_amount') }}</th>
                                <th style="width:120px">{{ __('lang.enabled') }}</th>
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
                                            <input type="number" step="0.01" min="0"
                                                   name="fees[{{ $key }}][amount]"
                                                   class="form-control" value="{{ $fee->amount ?? 0 }}">
                                            <span class="input-group-text">SAR</span>
                                        </div>
                                    </td>
                                    <td>
                                        {{-- A genuine checkbox for this choice, per the request —
                                             replaces the previous switch-styled toggle. --}}
                                        <input type="checkbox" class="form-check-input" style="width:1.3em;height:1.3em"
                                               name="fees[{{ $key }}][is_active]" value="1"
                                               {{ ($fee->is_active ?? false) ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-accent">{{ __('lang.save') }}</button>
        </div>
    </form>
@endsection
