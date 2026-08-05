@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Admin Users')

@section('content')

@php
    $me = auth('admin')->user();
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.admin_users') }}</h4>
        <div class="text-muted">{{ __('lang.manage_admins_single_role') }}</div>
    </div>

    @if($me && $me->can('admins.create'))
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createAdminModal">
            + {{ __('lang.create_admin') }}
        </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">

        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_name_email') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100">{{ __('lang.search') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>{{ __('lang.th_hash') }}</th>
                    <th>{{ __('lang.admin') }}</th>
                    <th>{{ __('lang.role_single') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($admins as $admin)
                    @php
                        $role = $admin->roles->first();
                        $roleName = $role->name ?? null;

                        $badgeClass = match($roleName) {
                            'super_admin' => 'bg-danger',
                            'admin' => 'bg-primary',
                            'manager' => 'bg-warning text-dark',
                            'viewer' => 'bg-secondary',
                            default => 'bg-light text-dark border'
                        };
                    @endphp

                    <tr>
                        <td>{{ $admin->id }}</td>

                        <td>
                            <div class="fw-semibold">{{ $admin->name }}</div>
                            <div class="text-muted small">{{ $admin->email }}</div>
                        </td>

                        <td>
                            @if($roleName)
                                <span class="badge {{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_',' ',$roleName)) }}
                                </span>
                            @else
                                <span class="badge bg-light text-dark border">{{ __('lang.no_role') }}</span>
                            @endif
                        </td>

                        <td class="text-end">
                            {{-- Edit --}}
                            @if($me && $me->can('admins.update'))
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAdminModal"
                                        data-id="{{ $admin->id }}"
                                        data-name="{{ $admin->name }}"
                                        data-email="{{ $admin->email }}"
                                        data-role="{{ $roleName ?? '' }}">
                                    {{ __('lang.edit') }}
                                </button>
                            @endif

                            {{-- Delete (prevent deleting yourself) --}}
                            @if($me && $me->can('admins.delete') && $admin->id !== $me->id)
                                <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('{{ __('lang.delete_confirm_admin') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">{{ __('lang.no_admins_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

{{ $admins->links('vendor.pagination.weekend') }}
    </div>
</div>

{{-- ✅ Create Modal --}}
@if($me && $me->can('admins.create'))
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.admins.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_admin') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.email') }}</label>
                        <input class="form-control" name="email" type="email" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.password') }}</label>
                        <input class="form-control" name="password" type="password" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.role_single') }}</label>
                        <select class="form-select" name="role" required>
                            <option value="" disabled selected>{{ __('lang.select_role') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
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

{{-- ✅ Edit Modal --}}
@if($me && $me->can('admins.update'))
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="editAdminForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_admin') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" id="editAdminName" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.email') }}</label>
                        <input class="form-control" name="email" id="editAdminEmail" type="email" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.password_leave_empty_to_keep') }}</label>
                        <input class="form-control" name="password" type="password">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.role_single') }}</label>
                        <select class="form-select" name="role" id="editAdminRole" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- ✅ JS only if update permission exists --}}
@if($me && $me->can('admins.update'))
<script>
document.getElementById('editAdminModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;

    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    const email = btn.getAttribute('data-email');
    const role = btn.getAttribute('data-role') || '';

    // matches /admin/admins routes
    document.getElementById('editAdminForm').action = `{{ url('/admin/admins') }}/${id}`;

    document.getElementById('editAdminName').value = name;
    document.getElementById('editAdminEmail').value = email;

    const roleSelect = document.getElementById('editAdminRole');
    [...roleSelect.options].forEach(o => o.selected = (o.value === role));
});
</script>
@endif

@endsection
