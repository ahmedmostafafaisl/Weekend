@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Promo Codes')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.promo_codes') }}</h4>
        <div class="text-muted small">{{ __('lang.promo_subtitle') }}</div>
    </div>
    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createModal">{{ __('lang.new_code') }}</button>
</div>

{{-- Filters --}}
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('lang.search_by_code') }}" value="{{ request('search') }}">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('lang.all_statuses') }}</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('lang.filter') }}</button>
        <a href="{{ route('admin.promo-codes.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.clear') }}</a>
    </div>
</form>

{{-- Table --}}
<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('lang.th_code') }}</th>
                    <th>{{ __('lang.discount') }}</th>
                    <th>{{ __('lang.constraints') }}</th>
                    <th>{{ __('lang.valid_window') }}</th>
                    <th>{{ __('lang.uses') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($promoCodes as $promo)
                @php
                    $expired = $promo->expires_at && $promo->expires_at->isPast();
                    $notStarted = $promo->starts_at && $promo->starts_at->isFuture();
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.promo-codes.show', $promo) }}" class="fw-semibold font-monospace text-decoration-none">
                            {{ $promo->code }}
                        </a>
                        @if($promo->description)
                            <div class="text-muted small">{{ Str::limit($promo->description, 40) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($promo->discount_type === 'percentage')
                            <span class="badge bg-info text-dark">{{ $promo->discount_value }}%</span>
                        @else
                            <span class="badge bg-primary">{{ number_format($promo->discount_value, 2) }} {{ __('lang.off_suffix') }}</span>
                        @endif
                        @if($promo->max_discount)
                            <div class="text-muted" style="font-size:11px">{{ __('lang.max_prefix') }} {{ number_format($promo->max_discount, 2) }}</div>
                        @endif
                    </td>
                    <td class="text-muted small">
                        @if($promo->min_amount) {{ __('lang.min_prefix') }} {{ number_format($promo->min_amount, 2) }}<br>@endif
                        @if($promo->max_uses) {{ __('lang.max_uses_label') }} {{ $promo->max_uses }}<br>@endif
                        @if($promo->max_uses_per_user) {{ __('lang.per_user_label') }} {{ $promo->max_uses_per_user }}@endif
                    </td>
                    <td class="small text-muted">
                        {{ $promo->starts_at?->format('d M Y') ?? '—' }}<br>
                        → {{ $promo->expires_at?->format('d M Y') ?? __('lang.no_expiry') }}
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold">{{ $promo->usages_count }}</span>
                        @if($promo->max_uses)
                            <span class="text-muted"> / {{ $promo->max_uses }}</span>
                        @endif
                    </td>
                    <td>
                        @if($expired)
                            <span class="badge bg-secondary">{{ __('lang.expired_word') }}</span>
                        @elseif($notStarted)
                            <span class="badge bg-warning text-dark">{{ __('lang.pending') }}</span>
                        @elseif($promo->is_active)
                            <span class="badge bg-success">{{ __('lang.active') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('lang.inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            {{-- show button --}}
                            <a href="{{ route('admin.promo-codes.show', $promo) }}" class="btn btn-sm btn-outline-primary">{{ __('lang.show_word') }}</a>

                            <button class="btn btn-sm btn-outline-secondary"
                                onclick="openEdit({{ $promo->id }}, '{{ $promo->code }}', '{{ $promo->description }}', '{{ $promo->discount_type }}', {{ $promo->discount_value }}, {{ $promo->min_amount ?? 'null' }}, {{ $promo->max_discount ?? 'null' }}, {{ $promo->max_uses ?? 'null' }}, {{ $promo->max_uses_per_user ?? 'null' }}, '{{ $promo->starts_at?->format('Y-m-d') ?? '' }}', '{{ $promo->expires_at?->format('Y-m-d') ?? '' }}', {{ $promo->is_active ? 'true' : 'false' }})">
                                {{ __('lang.edit') }}
                            </button>
                            <form action="{{ route('admin.promo-codes.toggle', $promo) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm {{ $promo->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    {{ $promo->is_active ? __('lang.disable_word') : __('lang.enable_word') }}
                                </button>
                            </form>
                            <form action="{{ route('admin.promo-codes.destroy', $promo) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('{{ __('lang.delete_confirm_promo_code') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('lang.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('lang.no_promo_codes_yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $promoCodes->links() }}</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.promo-codes.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">{{ __('lang.new_promo_code') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    @include('dashboard.admin.promo-codes._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                    <button type="submit" class="btn btn-accent">{{ __('lang.create') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">{{ __('lang.edit_promo_code') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    @include('dashboard.admin.promo-codes._form', ['editing' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                    <button type="submit" class="btn btn-accent">{{ __('lang.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
function openEdit(id, code, description, type, value, min, maxDisc, maxUses, maxPerUser, starts, expires, active) {
    const f = document.getElementById('editForm');
    f.action = '/admin/promo-codes/' + id;

    const set = (n, v) => { const el = f.querySelector('[name="'+n+'"]'); if(el) el.value = v ?? ''; };
    set('code', code);
    set('description', description);
    set('discount_type', type);
    set('discount_value', value);
    set('min_amount', min);
    set('max_discount', maxDisc);
    set('max_uses', maxUses);
    set('max_uses_per_user', maxPerUser);
    set('starts_at', starts);
    set('expires_at', expires);

    const cb = f.querySelector('[name="is_active"]');
    if (cb) cb.checked = active;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush

@endsection
