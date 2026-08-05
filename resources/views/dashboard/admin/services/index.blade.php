@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Services')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.services') }}</h4>
        <div class="text-muted">{{ __('lang.manage_all_services') }}</div>
    </div>

    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createServiceModal">
        + {{ __('lang.create_service') }}
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
                        <th>{{ __('lang.group') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                        <th>{{ __('lang.sort_order') }}</th>
                        <th class="text-end">{{ __('lang.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td>{{ $service->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $service->name }}</div>
                                <div class="text-muted small">{{ $service->description }}</div>
                            </td>
                            <td>{{ $service->group?->label ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $service->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ __('lang.'.$service->status) }}
                                </span>
                            </td>
                            <td>{{ $service->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.services.show', $service->id) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>

                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editServiceModal"
                                        data-id="{{ $service->id }}"
                                        data-service_group_id="{{ $service->service_group_id }}"
                                        data-name="{{ $service->name }}"
                                        data-description="{{ $service->description }}"
                                        data-status="{{ $service->status }}"
                                        data-sort_order="{{ $service->sort_order }}">
                                    {{ __('lang.edit') }}
                                </button>

                                <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_service') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ __('lang.no_services_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('admin.services.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('lang.group') }}</label>
                    <select class="form-select" name="service_group_id" required>
                        <option value="">{{ __('lang.select_group') }}</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" required>
                </div>

                <div class="col-12">
                    <label class="form-label">{{ __('lang.description') }}</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>

                <div class="col-6">
                    <label class="form-label">{{ __('lang.status') }}</label>
                    <select class="form-select" name="status" required>
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

{{-- Edit Modal --}}
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="editServiceForm">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('lang.group') }}</label>
                    <select class="form-select" name="service_group_id" id="editServiceGroup" required>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" id="editServiceName" required>
                </div>

                <div class="col-12">
                    <label class="form-label">{{ __('lang.description') }}</label>
                    <textarea class="form-control" name="description" id="editServiceDescription" rows="3"></textarea>
                </div>

                <div class="col-6">
                    <label class="form-label">{{ __('lang.status') }}</label>
                    <select class="form-select" name="status" id="editServiceStatus" required>
                        <option value="active">{{ __('lang.active') }}</option>
                        <option value="inactive">{{ __('lang.inactive') }}</option>
                    </select>
                </div>

                <div class="col-6">
                    <label class="form-label">{{ __('lang.sort_order') }}</label>
                    <input class="form-control" type="number" name="sort_order" id="editServiceSort">
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
document.getElementById('editServiceModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editServiceForm').action = `{{ url('/admin/services') }}/${id}`;
    document.getElementById('editServiceGroup').value = btn.getAttribute('data-service_group_id') || '';
    document.getElementById('editServiceName').value = btn.getAttribute('data-name') || '';
    document.getElementById('editServiceDescription').value = btn.getAttribute('data-description') || '';
    document.getElementById('editServiceStatus').value = btn.getAttribute('data-status') || 'active';
    document.getElementById('editServiceSort').value = btn.getAttribute('data-sort_order') || 0;
});
</script>
@endpush
@endsection
