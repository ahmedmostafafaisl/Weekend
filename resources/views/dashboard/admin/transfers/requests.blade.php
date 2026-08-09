@extends('dashboard.admin.layouts.app')
@section('title','Weekend | Transfer Requests')
@section('content')
@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Transfer Requests</h4><div class="text-muted small">{{ __('lang.providers_payouts') }}</div></div>
    <a href="{{ route('admin.transfers.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('lang.sidebar_transfers') }} ←</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('lang.filter_any_status') }}</option>
            @foreach(['pending','approved','rejected'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s ? 'selected':'' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-secondary">{{ __('lang.filter') }}</button>
        <a href="{{ route('admin.transfers.requests') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.clear') }}</a>
    </div>
</form>

<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-3">{{ __('lang.th_provider') }}</th><th>{{ __('lang.th_amount') }}</th><th>{{ __('lang.th_method') }}</th><th>{{ __('lang.th_notes') }}</th><th>{{ __('lang.th_status') }}</th><th>{{ __('lang.th_created') }}</th><th class="pe-3"></th></tr>
                </thead>
                <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="ps-3"><div class="small fw-semibold">{{ $req->provider?->name }}</div><div class="text-muted" style="font-size:10px">{{ $req->provider?->email }}</div></td>
                    <td class="small fw-semibold">SAR {{ number_format($req->requested_amount,2) }}</td>
                    <td><span class="badge bg-light text-dark border" style="font-size:10px">{{ str_replace('_',' ',ucfirst($req->preferred_method)) }}</span></td>
                    <td class="small text-muted" style="max-width:200px">{{ Str::limit($req->notes,60) ?? '—' }}</td>
                    <td>
                        @php $c=['pending'=>'warning','approved'=>'success','rejected'=>'danger'] @endphp
                        <span class="badge bg-{{ $c[$req->status] ?? 'secondary' }}">{{ ucfirst($req->status) }}</span>
                    </td>
                    <td class="small text-muted">{{ $req->created_at->format('d M Y') }}</td>
@php $me = auth('admin')->user(); @endphp
                    <td class="pe-3">
                        @if($req->status === 'pending' && $me && $me->can('transfers.update'))
                        <div class="d-flex gap-1">
                            <form action="{{ route('admin.transfers.approve-request', $req) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                <input type="text" name="admin_response" class="form-control form-control-sm py-0" placeholder="Note (optional)" style="width:130px;font-size:11px">
                                <button class="btn btn-sm btn-success py-0 px-2" style="font-size:11px">{{ __('lang.approve') }}</button>
                            </form>
                            <form action="{{ route('admin.transfers.reject-request', $req) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                <input type="text" name="admin_response" class="form-control form-control-sm py-0" placeholder="Reason" style="width:100px;font-size:11px" required>
                                <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px">{{ __('lang.reject') }}</button>
                            </form>
                        </div>
                        @elseif($req->status !== 'pending')
                        <span class="text-muted small">{{ $req->admin_response }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No transfer requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $requests->links() }}</div>
@endsection
