@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Promo Code ' . $promoCode->code)

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.promo-codes.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <div>
        <h4 class="fw-bold mb-0 font-monospace">{{ $promoCode->code }}</h4>
        <div class="text-muted small">{{ $promoCode->description ?? __('lang.no_description') }}</div>
    </div>
    @if($promoCode->is_active)
        <span class="badge bg-success">{{ __('lang.active') }}</span>
    @else
        <span class="badge bg-danger">{{ __('lang.inactive') }}</span>
    @endif
</div>

{{-- Stats row --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-soft shadow-sm text-center py-3">
            <div class="fw-bold fs-4">{{ $promoCode->usages_count ?? $promoCode->usages()->count() }}</div>
            <div class="text-muted small">{{ __('lang.total_uses') }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft shadow-sm text-center py-3">
            @if($promoCode->discount_type === 'percentage')
                <div class="fw-bold fs-4">{{ $promoCode->discount_value }}%</div>
            @else
                <div class="fw-bold fs-4">{{ number_format($promoCode->discount_value, 2) }}</div>
            @endif
            <div class="text-muted small">{{ __('lang.discount_value') }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft shadow-sm text-center py-3">
            <div class="fw-bold fs-4">{{ number_format($promoCode->usages()->sum('discount_amount'), 2) }}</div>
            <div class="text-muted small">{{ __('lang.discount') }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft shadow-sm text-center py-3">
            <div class="fw-bold fs-4">{{ $promoCode->max_uses ?? '∞' }}</div>
            <div class="text-muted small">{{ __('lang.max_uses') }}</div>
        </div>
    </div>
</div>

{{-- Details + usages --}}
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.code_details') }}</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">{{ __('lang.discount_type') }}</td><td class="small">{{ ucfirst($promoCode->discount_type) }}</td></tr>
                    <tr><td class="text-muted small">{{ __('lang.value') }}</td>
                        <td class="small">
                            {{ $promoCode->discount_type === 'percentage' ? $promoCode->discount_value.'%' : number_format($promoCode->discount_value, 2).' SAR' }}
                        </td>
                    </tr>
                    @if($promoCode->max_discount)
                    <tr><td class="text-muted small">{{ __('lang.max_discount_cap') }}</td><td class="small">{{ number_format($promoCode->max_discount, 2) }}</td></tr>
                    @endif
                    @if($promoCode->min_amount)
                    <tr><td class="text-muted small">{{ __('lang.min_order') }}</td><td class="small">{{ number_format($promoCode->min_amount, 2) }}</td></tr>
                    @endif
                    <tr><td class="text-muted small">{{ __('lang.max_uses') }}</td><td class="small">{{ $promoCode->max_uses ?? __('lang.unlimited_placeholder') }}</td></tr>
                    <tr><td class="text-muted small">{{ __('lang.per_user') }}</td><td class="small">{{ $promoCode->max_uses_per_user ?? __('lang.unlimited_placeholder') }}</td></tr>
                    <tr><td class="text-muted small">{{ __('lang.starts_at') }}</td><td class="small">{{ $promoCode->starts_at?->format('d M Y') ?? '—' }}</td></tr>
                    <tr><td class="text-muted small">{{ __('lang.expires_at') }}</td><td class="small">{{ $promoCode->expires_at?->format('d M Y') ?? __('lang.no_expiry') }}</td></tr>
                    <tr><td class="text-muted small">{{ __('lang.created_at') }}</td><td class="small">{{ $promoCode->created_at->format('d M Y') }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.usage_history') }}</h6>
                @if($usages->count())
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.th_user') }}</th>
                                <th>{{ __('lang.original_amount') }}</th>
                                <th>{{ __('lang.discount') }}</th>
                                <th>{{ __('lang.th_amount') }}</th>
                                <th>{{ __('lang.payment') }}</th>
                                <th>{{ __('lang.th_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($usages as $usage)
                            <tr>
                                <td class="small">{{ $usage->user?->name ?? '—' }}</td>
                                <td class="small">{{ number_format($usage->original_amount, 2) }}</td>
                                <td class="small text-success fw-semibold">-{{ number_format($usage->discount_amount, 2) }}</td>
                                <td class="small fw-semibold">{{ number_format($usage->final_amount, 2) }}</td>
                                <td class="small font-monospace">
                                    @if($usage->payment)
                                        <a href="{{ route('admin.payments.show', $usage->payment_id) }}" class="text-decoration-none">
                                            {{ $usage->payment->reference_id }}
                                        </a>
                                    @else —
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $usage->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">{{ $usages->links() }}</div>
                @else
                    <div class="text-muted small">{{ __('lang.no_usages') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
