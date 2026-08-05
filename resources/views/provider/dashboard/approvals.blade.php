@extends('provider.layouts.app')
@section('title','Weekend | '.__('lang.pending_approvals'))
@section('content')
<h4 class="fw-bold mb-4">⏳ {{ __('lang.pending_approvals') }}</h4>

@if($reservations->isEmpty())
    <div class="card card-soft shadow-sm p-4 text-center text-muted">{{ __('lang.no_pending_approvals') }}</div>
@else
<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-3">{{ __('lang.th_customer') }}</th><th>{{ __('lang.th_venue') }}</th><th>{{ __('lang.th_date') }}</th><th>{{ __('lang.th_period') }}</th><th>{{ __('lang.th_guests') }}</th><th>{{ __('lang.th_amount') }}</th><th>{{ __('lang.th_notes') }}</th><th class="pe-3"></th></tr>
                </thead>
                <tbody>
                @foreach($reservations as $res)
                <tr id="row-{{ $res->id }}">
                    <td class="ps-3">
                        <div class="fw-semibold small">{{ $res->user?->name }}</div>
                        <div class="text-muted" style="font-size:10px">{{ $res->user?->phone }}</div>
                    </td>
                    <td class="small">{{ $res->unite?->name }}</td>
                    <td class="small">{{ $res->reservation_date?->format('d M Y') }}</td>
                    <td><span class="badge bg-light text-dark border" style="font-size:10px">{{ __('lang.'.$res->period_type) }}</span></td>
                    <td class="small">{{ $res->guest_count ?? '—' }}</td>
                    <td class="small fw-semibold">SAR {{ number_format($res->price,2) }}</td>
                    <td class="small text-muted" style="max-width:150px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis" title="{{ $res->notes }}">{{ $res->notes ?? '—' }}</td>
                    <td class="pe-3">
                        <div class="d-flex gap-1">
                            <button onclick="handleApproval({{ $res->id }},'approve',this)"
                                    class="btn btn-sm btn-success py-0 px-2" style="font-size:11px">✅ {{ __('lang.accept') }}</button>
                            <button onclick="handleApproval({{ $res->id }},'reject',this)"
                                    class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px">❌ {{ __('lang.reject') }}</button>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $reservations->links() }}</div>
@endif

@push('js')
<script>
async function handleApproval(id, action, btn) {
    btn.disabled = true;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const r = await fetch(`/api/reservations/${id}/${action}`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
    });
    const data = await r.json();
    const row = document.getElementById('row-' + id);
    if (row) {
        row.innerHTML = `<td colspan="8" class="ps-3 py-2 text-muted small">${data.message ?? (action === 'approve' ? '{{ __('lang.accepted_customer_will_pay') }}' : '{{ __('lang.rejected_word') }}')}</td>`;
    }
}
</script>
@endpush
@endsection
