@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Permissions')

@section('content')

@php
    $me = auth('admin')->user();

    // Group permissions by module (part before dot)
    $groups = $permissions->getCollection()
        ->groupBy(function ($p) {
            return explode('.', $p->name)[0] ?? 'other';
        })
        ->sortKeys();

    // open first group by default (optional)
    $firstKey = $groups->keys()->first();
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.permissions') }}</h4>
        <div class="text-muted">{{ __('lang.manage_permissions_admin_guard') }}</div>
    </div>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" id="btnExpandAll">{{ __('lang.expand_all') }}</button>
        <button type="button" class="btn btn-outline-secondary" id="btnCollapseAll">{{ __('lang.collapse_all') }}</button>

        @if($me && $me->can('permissions.create'))
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                + {{ __('lang.create_permission') }}
            </button>
        @endif
    </div>
</div>

<div class="card card-soft shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-6">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_permission_example') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100">{{ __('lang.search') }}</button>
            </div>
            <div class="col-md-4 text-md-end d-flex justify-content-md-end align-items-center gap-2">
                <span class="text-muted small">
                    {{ __('lang.total_colon') }} <span class="fw-semibold">{{ $permissions->total() }}</span>
                </span>
            </div>
        </form>
    </div>
</div>

<div class="accordion" id="permAccordion">

    @forelse($groups as $module => $items)
        @php
            $collapseId = 'collapse_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $module);
            $headingId  = 'heading_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $module);
            $isOpen = ($module === $firstKey); // open first section by default
        @endphp

        <div class="accordion-item card-soft shadow-sm mb-3" style="border:0;border-radius:14px;overflow:hidden;">
            <h2 class="accordion-header" id="{{ $headingId }}">
                <button class="accordion-button {{ $isOpen ? '' : 'collapsed' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $collapseId }}"
                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $collapseId }}">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="text-capitalize fw-bold">
                            {{ str_replace('_',' ', $module) }}
                            <span class="text-muted small ms-2">({{ $items->count() }})</span>
                        </div>
                        <div class="me-3 text-muted small">
                            مثال: <span class="badge bg-light text-dark border">{{ $module }}.view</span>
                        </div>
                    </div>
                </button>
            </h2>

            <div id="{{ $collapseId }}"
                 class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}"
                 aria-labelledby="{{ $headingId }}"
                 data-bs-parent="#permAccordion">

                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="width:80px">#</th>
                                <th>{{ __('lang.th_name') }}</th>
                                <th class="text-end" style="width:180px">{{ __('lang.th_actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $perm)
                                <tr>
                                    <td>{{ $perm->id }}</td>
                                    <td class="fw-semibold">
                                        <span class="badge bg-light text-dark border">
                                            {{ $perm->name }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($me && $me->can('permissions.update'))
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editPermissionModal"
                                                    data-id="{{ $perm->id }}"
                                                    data-name="{{ $perm->name }}">
                                                {{ __('lang.edit') }}
                                            </button>
                                        @endif

                                        @if($me && $me->can('permissions.delete'))
                                            <form action="{{ route('admin.permissions.destroy', $perm) }}"
                                                  method="POST"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('{{ __('lang.delete_confirm_permission') }}')">
                                                    {{ __('lang.delete') }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    @empty
        <div class="text-center text-muted py-5">{{ __('lang.no_permissions_found') }}</div>
    @endforelse

</div>

<div class="d-flex justify-content-center">
{{ $permissions->links('vendor.pagination.weekend') }}
</div>

{{-- ================= Create Permission ================= --}}
@if($me && $me->can('permissions.create'))
<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_permission') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">{{ __('lang.permission_name') }}</label>
                <input class="form-control" name="name" required placeholder="{{ __('lang.create_permission_placeholder') }}">
                <div class="text-muted small mt-2">
                    {{ __('lang.permission_tip') }}
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                <button class="btn btn-accent">{{ __('lang.create') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ================= Edit Permission ================= --}}
@if($me && $me->can('permissions.update'))
<div class="modal fade" id="editPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="editPermissionForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_permission') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">{{ __('lang.permission_name') }}</label>
                <input class="form-control" name="name" id="editPermissionName" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                <button class="btn btn-accent">{{ __('lang.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('js')
<script>
/** Expand / Collapse all accordion sections */
document.getElementById('btnExpandAll')?.addEventListener('click', function () {
    document.querySelectorAll('#permAccordion .accordion-collapse').forEach(el => {
        bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
    });
});
document.getElementById('btnCollapseAll')?.addEventListener('click', function () {
    document.querySelectorAll('#permAccordion .accordion-collapse').forEach(el => {
        bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
    });
});
</script>

@if($me && $me->can('permissions.update'))
<script>
document.getElementById('editPermissionModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name') || '';

    document.getElementById('editPermissionName').value = name;

    const form = document.getElementById('editPermissionForm');
    form.action = `{{ url('/admin/permissions') }}/${id}`;
});
</script>
@endif
@endpush

@endsection
