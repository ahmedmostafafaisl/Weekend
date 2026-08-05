@extends('dashboard.admin.layouts.app')
@section('title','Weekend | Transfers')
@section('content')
@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.provider_transfers') }}</h4>
        <div class="text-muted small">{{ __('lang.manage_fund_transfers') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.transfers.requests') }}" class="btn btn-outline-secondary btn-sm">📋 {{ __('lang.transfer_requests') }}</a>
        <a href="{{ route('admin.transfers.policy.index') }}" class="btn btn-outline-secondary btn-sm">⚙️ {{ __('lang.policies') }}</a>
        <a href="{{ route('admin.transfers.create') }}" class="btn btn-accent">{{ __('lang.new_transfer') }}</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-soft text-center py-3 shadow-sm">
            <div class="fw-bold fs-5 text-warning">SAR {{ number_format($stats['total_pending'],2) }}</div>
            <div class="text-muted small">{{ __('lang.pending') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-soft text-center py-3 shadow-sm">
            <div class="fw-bold fs-5 text-success">SAR {{ number_format($stats['total_completed'],2) }}</div>
            <div class="text-muted small">{{ __('lang.completed') }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-4">
        <select name="provider_id" class="form-select form-select-sm">
            <option value="">{{ __('lang.filter_all_providers') }}</option>
            @foreach($providers as $p)
                <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('lang.filter_any_status') }}</option>
            @foreach(['pending','processing','completed','failed','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('lang.filter') }}</button>
        <a href="{{ route('admin.transfers.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.clear') }}</a>
    </div>
</form>

<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th><th>{{ __('lang.th_provider') }}</th><th>{{ __('lang.th_amount') }}</th><th>{{ __('lang.th_tax') }}</th><th>{{ __('lang.th_fee') }}</th><th>{{ __('lang.th_net') }}</th><th>{{ __('lang.th_method') }}</th><th>{{ __('lang.th_status') }}</th><th>{{ __('lang.th_scheduled') }}</th><th>{{ __('lang.th_ref') }}</th><th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transfers as $t)
                <tr>
                    <td class="ps-3 small text-muted">{{ $t->id }}</td>
                    <td><div class="small fw-semibold">{{ $t->provider?->name }}</div><div class="text-muted" style="font-size:10px">{{ $t->provider?->email }}</div></td>
                    <td class="small">{{ number_format($t->amount,2) }}</td>
                    <td class="small text-danger">-{{ number_format($t->tax_amount,2) }}</td>
                    <td class="small text-danger">-{{ number_format($t->platform_fee,2) }}</td>
                    <td class="small fw-semibold text-success">{{ number_format($t->net_amount,2) }}</td>
                    <td><span class="badge bg-light text-dark border" style="font-size:10px">{{ str_replace('_',' ',ucfirst($t->method)) }}</span></td>
                    <td>
                        @php $colors = ['pending'=>'warning','processing'=>'info','completed'=>'success','failed'=>'danger','cancelled'=>'secondary']; @endphp
                        <span class="badge bg-{{ $colors[$t->status] ?? 'secondary' }}">{{ ucfirst($t->status) }}</span>
                    </td>
                    <td class="small text-muted">{{ $t->scheduled_date?->format('d M Y') ?? '—' }}</td>
                    <td class="small text-muted">{{ $t->reference ?? '—' }}</td>
                  <td class="pe-3">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.transfers.edit', $t) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit me-1"></i> {{ __('lang.edit') }}
        </a>

        <form action="{{ route('admin.transfers.destroy', $t) }}"
              method="POST"
              class="d-inline"
              onsubmit="return confirm('{{ __('lang.delete_confirm_transfer') }}')">
            @csrf
            @method('DELETE')

            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash me-1"></i> {{ __('lang.delete') }}
            </button>
        </form>
    </div>
</td>
                </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">{{ __('lang.no_transfers_found') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $transfers->links() }}</div>
@endsection
