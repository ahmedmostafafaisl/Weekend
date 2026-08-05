@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Service Groups')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.service_groups') }}</h4>
        <div class="text-muted">{{ __('lang.manage_service_categories') }}</div>
    </div>

    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createGroupModal">
        + {{ __('lang.create') }}
    </button>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>{{ __('lang.th_hash') }}</th>
                        <th>{{ __('lang.th_name') }}</th>
                        <th>{{ __('lang.label') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                        <th>{{ __('lang.sort_order') }}</th>
                        <th class="text-end">{{ __('lang.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>{{ $group->id }}</td>
                            <td>{{ $group->name }}</td>
                            <td>{{ $group->label }}</td>
                            <td>{{ __('lang.'.$group->status) }}</td>
                            <td>{{ $group->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.service-groups.show', $group->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.view') }}</a>

                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editGroupModal"
                                        data-id="{{ $group->id }}"
                                        data-name="{{ $group->name }}"
                                        data-label="{{ $group->label }}"
                                        data-status="{{ $group->status }}"
                                        data-sort_order="{{ $group->sort_order }}">
                                    {{ __('lang.edit') }}
                                </button>

                                <form action="{{ route('admin.service-groups.destroy', $group->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_group') }}')">{{ __('lang.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">{{ __('lang.no_groups_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('admin.service-groups.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_service_group') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" required>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('lang.label') }}</label>
                    <input class="form-control" name="label" required>
                </div>
                <div class="col-6">
                    <label class="form-label">{{ __('lang.status') }}</label>
                    <select class="form-select" name="status">
                        <option value="active">{{ __('lang.active') }}</option>
                        <option value="inactive">{{ __('lang.inactive') }}</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">{{ __('lang.sort_order') }}</label>
                    <input class="form-control" type="number" name="sort_order" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.save') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="editGroupForm">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_service_group') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" id="editGroupName" required>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('lang.label') }}</label>
                    <input class="form-control" name="label" id="editGroupLabel" required>
                </div>
                <div class="col-6">
                    <label class="form-label">{{ __('lang.status') }}</label>
                    <select class="form-select" name="status" id="editGroupStatus">
                        <option value="active">{{ __('lang.active') }}</option>
                        <option value="inactive">{{ __('lang.inactive') }}</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">{{ __('lang.sort_order') }}</label>
                    <input class="form-control" type="number" name="sort_order" id="editGroupSort">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.update') }}</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
document.getElementById('editGroupModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editGroupForm').action = `{{ url('/admin/service-groups') }}/${id}`;
    document.getElementById('editGroupName').value = btn.getAttribute('data-name') || '';
    document.getElementById('editGroupLabel').value = btn.getAttribute('data-label') || '';
    document.getElementById('editGroupStatus').value = btn.getAttribute('data-status') || 'active';
    document.getElementById('editGroupSort').value = btn.getAttribute('data-sort_order') || 0;
});
</script>
@endpush
@endsection
