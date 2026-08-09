@extends('dashboard.admin.layouts.app')
@section('title','Weekend | Transfer Policies')
@section('content')
@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

@php $me = auth('admin')->user(); @endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Transfer Policies</h4><div class="text-muted small">Define fund transfer rules for providers</div></div>
    @if($me && $me->can('transfers.create'))
        <a href="{{ route('admin.transfers.policy.create') }}" class="btn btn-accent">{{ __('lang.new_policy') }}</a>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-3">Title</th><th>Transfer Days</th><th>Methods</th><th>Tax %</th><th>Platform Fee %</th><th>{{ __('lang.th_status') }}</th><th class="pe-3"></th></tr>
                </thead>
                <tbody>
                @forelse($policies as $p)
                <tr>
                    <td class="ps-3"><div class="fw-semibold small">{{ $p->title }}</div><div class="text-muted" style="font-size:11px;max-width:220px">{{ Str::limit($p->description,60) }}</div></td>
                    <td class="small">{{ $p->transfer_days }} days</td>
                    <td>
                        @foreach($p->transfer_methods ?? [] as $m)
                            <span class="badge bg-light text-dark border me-1" style="font-size:10px">{{ str_replace('_',' ',ucfirst($m)) }}</span>
                        @endforeach
                    </td>
                    <td class="small">{{ $p->tax_rate }}%</td>
                    <td class="small">{{ $p->platform_fee_rate }}%</td>
                    <td><span class="badge {{ $p->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="pe-3">
                        <div class="d-flex gap-1 justify-content-end">
                            @if($me && $me->can('transfers.update'))
                                <a href="{{ route('admin.transfers.policy.edit', $p) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px">{{ __('lang.edit') }}</a>
                            @endif
                            @if($me && $me->can('transfers.delete'))
                                <form action="{{ route('admin.transfers.policy.destroy', $p) }}" method="POST" onsubmit="return confirm('Delete this policy?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px">{{ __('lang.delete') }}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No transfer policies yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $policies->links() }}</div>
@endsection
