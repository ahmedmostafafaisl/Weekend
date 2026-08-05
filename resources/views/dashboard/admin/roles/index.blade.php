@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Roles')

@section('content')

@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.roles') }}</h4>
        <div class="text-muted">{{ __('lang.create_update_roles_permissions') }}</div>
    </div>

    @if($me && $me->can('roles.create'))
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            + {{ __('lang.create_role') }}
        </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">

        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_role') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100">{{ __('lang.search') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th style="width:70px">#</th>
                    <th style="width:220px">{{ __('lang.role') }}</th>
                    <th>{{ __('lang.permissions_label') }}</th>
                    <th class="text-end" style="width:170px">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($roles as $role)
                    @php
                        $perms = $role->permissions;
                        $count = $perms->count();
                        $preview = $perms->take(5);
                        $restCount = max(0, $count - $preview->count());
                    @endphp

                    <tr>
                        <td>{{ $role->id }}</td>

                        <td>
                            <div class="fw-semibold">{{ $role->name }}</div>
                            <div class="text-muted small">{{ $count }} {{ __('lang.permissions_count') }}</div>
                        </td>

                        <td>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                @forelse($preview as $p)
                                    <span class="badge text-bg-light">{{ $p->name }}</span>
                                @empty
                                    <span class="text-muted small">{{ __('lang.no_permissions') }}</span>
                                @endforelse

                                @if($restCount > 0)
                                    <span class="badge bg-light text-dark border">+{{ $restCount }} {{ __('lang.more_suffix') }}</span>
                                @endif

                                @if($count > 0)
                                    <button class="btn btn-sm btn-outline-secondary ms-2"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewPermsModal"
                                            data-role="{{ $role->name }}"
                                            data-perms="{{ $perms->pluck('name')->implode('|') }}">
                                        {{ __('lang.view_all') }}
                                    </button>
                                @endif
                            </div>
                        </td>

                        <td class="text-end">
                            @if($me && $me->can('roles.update'))
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoleModal"
                                        data-id="{{ $role->id }}"
                                        data-name="{{ $role->name }}"
                                        data-permissions="{{ $perms->pluck('id')->implode(',') }}">
                                    {{ __('lang.edit') }}
                                </button>
                            @endif

                            @if($me && $me->can('roles.delete'))
                                <form action="{{ route('admin.roles.destroy', $role) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('{{ __('lang.delete_confirm_role') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">{{ __('lang.no_roles_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

{{ $roles->links('vendor.pagination.weekend') }}
    </div>
</div>

{{-- ================= View All Permissions Modal ================= --}}
<div class="modal fade" id="viewPermsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.role_permissions') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="text-muted mb-2">
                    {{ __('lang.role_colon') }} <span class="fw-semibold" id="vpRoleName">—</span>
                </div>

                <div id="vpPerms" class="d-flex flex-wrap gap-1"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">{{ __('lang.close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ================= Create Role Modal ================= --}}
@if($me && $me->can('roles.create'))
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_role') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('lang.role_name') }}</label>
                    <input class="form-control" name="name" required>
                </div>

                <label class="form-label">{{ __('lang.permissions') }}</label>
                <div class="row g-2">
                    @foreach($permissions as $perm)
                        <div class="col-md-4">
                            <label class="border rounded-3 p-2 w-100 d-flex gap-2 align-items-center">
                                <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}">
                                <span>{{ $perm->name }}</span>
                            </label>
                        </div>
                    @endforeach
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

{{-- ================= Edit Role Modal ================= --}}
@if($me && $me->can('roles.update'))
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="editRoleForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_role') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('lang.role_name') }}</label>
                    <input class="form-control" name="name" id="editRoleName" required>
                </div>

                <label class="form-label">{{ __('lang.permissions') }}</label>
                <div class="row g-2">
                    @foreach($permissions as $perm)
                        <div class="col-md-4">
                            <label class="border rounded-3 p-2 w-100 d-flex gap-2 align-items-center">
                                <input class="edit-perm" type="checkbox" name="permission_ids[]" value="{{ $perm->id }}">
                                <span>{{ $perm->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
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
/** View all perms */
document.getElementById('viewPermsModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const role = btn.getAttribute('data-role') || '—';
    const raw = btn.getAttribute('data-perms') || '';
    const list = raw ? raw.split('|').filter(Boolean) : [];

    document.getElementById('vpRoleName').textContent = role;

    const wrap = document.getElementById('vpPerms');
    wrap.innerHTML = '';

    if (!list.length) {
        wrap.innerHTML = '<span class="text-muted small">{{ __('lang.no_permissions') }}</span>';
        return;
    }

    list.forEach(name => {
        const span = document.createElement('span');
        span.className = 'badge text-bg-light';
        span.textContent = name;
        wrap.appendChild(span);
    });
});

/** Edit role modal fill */
document.getElementById('editRoleModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name') || '';
    const perms = (btn.getAttribute('data-permissions') || '').split(',').filter(Boolean);

    const form = document.getElementById('editRoleForm');
    if (form) form.action = `{{ url('/admin/roles') }}/${id}`;

    const nameInput = document.getElementById('editRoleName');
    if (nameInput) nameInput.value = name;

    document.querySelectorAll('.edit-perm').forEach(cb => cb.checked = false);
    perms.forEach(pid => {
        const cb = document.querySelector(`.edit-perm[value="${pid}"]`);
        if (cb) cb.checked = true;
    });
});
</script>
@endpush

@endsection
