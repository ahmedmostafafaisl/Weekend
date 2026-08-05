@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Reservation Reviewers')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.reviewers') }}</h4>
        <div class="text-muted small">{{ __('lang.reviewers_subtitle') }}</div>
    </div>
    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addReviewerModal">
        + {{ __('lang.add_reviewer') }}
    </button>
</div>

{{-- How scopes work --}}
<div class="alert alert-light border mb-4 small">
    <strong>{{ __('lang.how_it_works') }}</strong>
    {{ __('lang.how_it_works_body') }}
</div>

{{-- Current reviewers --}}
@if($reviewers->count())
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body p-0">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">{{ __('lang.th_admin') }}</th>
                    <th>{{ __('lang.scope') }}</th>
                    <th>{{ __('lang.venues_types') }}</th>
                    <th class="pe-3"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($reviewers as $reviewer)
                @php
                    $scopes     = $reviewer->reviewerScopes;
                    $typeScopes = $scopes->whereNotNull('unite_type')->whereNull('unite_id');
                    $unitScopes = $scopes->whereNotNull('unite_id');
                    $scopeLabel = $scopes->isEmpty()
                        ? 'all'
                        : ($unitScopes->isNotEmpty() ? 'unites' : 'types');
                @endphp
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold small">{{ $reviewer->name }}</div>
                        <div class="text-muted" style="font-size:11px">{{ $reviewer->email }}</div>
                    </td>
                    <td>
                        @if($scopes->isEmpty())
                            <span class="badge bg-success">{{ __('lang.scope_all') }}</span>
                        @elseif($scopeLabel === 'types')
                            <span class="badge bg-info text-dark">{{ __('lang.scope_by_type') }}</span>
                        @else
                            <span class="badge bg-primary">{{ __('lang.scope_specific') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @if($scopes->isEmpty())
                                <span class="text-muted small">{{ __('lang.no_restriction') }}</span>
                            @elseif($scopeLabel === 'types')
                                @foreach($typeScopes as $s)
                                    <span class="badge bg-light text-dark border">{{ __('lang.'.$s->unite_type) }}</span>
                                @endforeach
                            @else
                                @foreach($unitScopes as $s)
                                    <span class="badge bg-light text-dark border" style="font-size:11px">
                                        {{ $s->unite?->name ?? '#'.$s->unite_id }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </td>
                    <td class="pe-3">
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-outline-secondary"
                                onclick="openEdit({{ $reviewer->id }}, '{{ $reviewer->name }}', '{{ $scopeLabel }}',
                                    {{ $typeScopes->pluck('unite_type')->toJson() }},
                                    {{ $unitScopes->pluck('unite_id')->toJson() }})">
                                {{ __('lang.edit_scope') }}
                            </button>
                            <form action="{{ route('admin.reviewers.destroy', $reviewer) }}" method="POST"
                                  onsubmit="return confirm('{{ str_replace(':name', $reviewer->name, __('lang.remove_confirm_reviewer')) }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('lang.remove_word') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
    <div class="text-muted small mb-4">{{ __('lang.no_reviewers_yet') }}</div>
@endif

{{-- Add Reviewer Modal --}}
<div class="modal fade" id="addReviewerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.reviewers.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('lang.add_reviewer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.admin_to_promote') }} <span class="text-danger">*</span></label>
                        <select name="admin_id" class="form-select" required>
                            <option value="">{{ __('lang.select_an_admin') }}</option>
                            @foreach($allAdmins as $a)
                                <option value="{{ $a->id }}">{{ $a->name }} — {{ $a->email }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('lang.role_replaced_with_reviewer') }}</div>
                    </div>
                    @include('dashboard.admin.reviewers._scope_fields', ['prefix' => 'add'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                    <button type="submit" class="btn btn-accent">{{ __('lang.add_reviewer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Scope Modal --}}
<div class="modal fade" id="editScopeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editScopeForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('lang.edit_scope_dash') }} <span id="editReviewerName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('dashboard.admin.reviewers._scope_fields', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                    <button type="submit" class="btn btn-accent">{{ __('lang.save_scope') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
function openEdit(id, name, scopeType, types, uniteIds) {
    document.getElementById('editReviewerName').textContent = name;
    document.getElementById('editScopeForm').action = '/admin/reviewers/' + id;

    // Set scope_type radio
    document.querySelectorAll('[name="scope_type"]').forEach(r => {
        if (r.closest('#editScopeModal')) r.checked = (r.value === scopeType);
    });

    // Trigger change to show/hide panels
    const radio = document.querySelector('#editScopeModal [name="scope_type"][value="'+scopeType+'"]');
    if (radio) radio.dispatchEvent(new Event('change'));

    // Check the right type checkboxes
    document.querySelectorAll('#editScopeModal [name="types[]"]').forEach(cb => {
        cb.checked = types.includes(cb.value);
    });

    // Check the right unite checkboxes
    document.querySelectorAll('#editScopeModal [name="unite_ids[]"]').forEach(cb => {
        cb.checked = uniteIds.includes(parseInt(cb.value));
    });

    new bootstrap.Modal(document.getElementById('editScopeModal')).show();
}

// scope panel toggling is handled in _scope_fields.blade.php
</script>
@endpush

@endsection
