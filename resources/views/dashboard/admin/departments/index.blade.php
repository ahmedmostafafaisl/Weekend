@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Departments')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.departments') }}</h4>
        <div class="text-muted">{{ __('lang.manage_providers_departments') }}</div>
    </div>

    @if($me && $me->can('departments.create'))
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
            + {{ __('lang.create_department') }}
        </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">

        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_name_description_location') }}">
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
                    <th>{{ __('lang.department') }}</th>
                    <th>{{ __('lang.th_provider') }}</th>
                    <th>{{ __('lang.th_type') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th>{{ __('lang.location') }}</th>
                    <th class="text-end">{{ __('lang.actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($departments as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $d->name }}</div>
                            <div class="text-muted small">{{ $d->description ?: '—' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $d->user?->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $d->user?->email ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $d->type }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $d->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $d->status }}
                            </span>
                        </td>
                        <td>{{ $d->location ?: '—' }}</td>
                        <td class="text-end">
                            @if($me && $me->can('departments.view'))
                                <a href="{{ route('admin.departments.show', $d->id) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('departments.update'))
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDepartmentModal"
                                        data-id="{{ $d->id }}"
                                        data-user_id="{{ $d->user_id }}"
                                        data-name="{{ $d->name }}"
                                        data-description="{{ $d->description }}"
                                        data-type="{{ $d->type }}"
                                        data-location="{{ $d->location }}"
                                        data-latitude="{{ $d->latitude }}"
                                        data-longitude="{{ $d->longitude }}"
                                        data-status="{{ $d->status }}"
                                        data-facebook="{{ $d->facebook }}"
                                        data-twitter="{{ $d->twitter }}"
                                        data-instagram="{{ $d->instagram }}"
                                        data-youtube="{{ $d->youtube }}"
                                        data-website="{{ $d->website }}"
                                        data-whatsapp="{{ $d->whatsapp }}"
                                        data-snapchat="{{ $d->snapchat }}"
                                        data-tiktok="{{ $d->tiktok }}"
                                >
                                    {{ __('lang.edit') }}
                                </button>
                            @endif

                            @if($me && $me->can('departments.delete'))
                                <form action="{{ route('admin.departments.destroy', $d->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('{{ __('lang.delete_confirm_department', ['name' => $d->name]) }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('lang.no_departments_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $departments->links('vendor.pagination.weekend') }}
    </div>
</div>

@if($me && $me->can('departments.create'))
<div class="modal fade" id="createDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.departments.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_department') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.select_provider') }}</label>
                        <select class="form-select" name="user_id">
                            <option value="">—</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} - {{ $u->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ __('lang.description') }}</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select" name="type" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.location') }}</label>
                        <input class="form-control" name="location">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.latitude') }}</label>
                        <input class="form-control" name="latitude">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.longitude') }}</label>
                        <input class="form-control" name="longitude">
                    </div>

                    <div class="col-12"><hr class="my-1"><div class="fw-semibold">{{ __('lang.social_links') }}</div></div>

                    <div class="col-md-6"><label class="form-label">{{ __('lang.facebook') }}</label><input class="form-control" name="facebook"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.twitter') }}</label><input class="form-control" name="twitter"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.instagram') }}</label><input class="form-control" name="instagram"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.youtube') }}</label><input class="form-control" name="youtube"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.website') }}</label><input class="form-control" name="website"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.whatsapp') }}</label><input class="form-control" name="whatsapp"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.snapchat') }}</label><input class="form-control" name="snapchat"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.tiktok') }}</label><input class="form-control" name="tiktok"></div>
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

@if($me && $me->can('departments.update'))
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="editDepartmentForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_department') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.provider') }}</label>
                        <select class="form-select" name="user_id" id="editDepartmentUserId">
                            <option value="">—</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} - {{ $u->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" id="editDepartmentName" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ __('lang.description') }}</label>
                        <textarea class="form-control" name="description" id="editDepartmentDescription" rows="2"></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select" name="type" id="editDepartmentType" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" id="editDepartmentStatus" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.location') }}</label>
                        <input class="form-control" name="location" id="editDepartmentLocation">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.latitude') }}</label>
                        <input class="form-control" name="latitude" id="editDepartmentLatitude">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.longitude') }}</label>
                        <input class="form-control" name="longitude" id="editDepartmentLongitude">
                    </div>

                    <div class="col-12"><hr class="my-1"><div class="fw-semibold">{{ __('lang.social_links') }}</div></div>

                    <div class="col-md-6"><label class="form-label">{{ __('lang.facebook') }}</label><input class="form-control" name="facebook" id="editDepartmentFacebook"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.twitter') }}</label><input class="form-control" name="twitter" id="editDepartmentTwitter"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.instagram') }}</label><input class="form-control" name="instagram" id="editDepartmentInstagram"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.youtube') }}</label><input class="form-control" name="youtube" id="editDepartmentYoutube"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.website') }}</label><input class="form-control" name="website" id="editDepartmentWebsite"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.whatsapp') }}</label><input class="form-control" name="whatsapp" id="editDepartmentWhatsapp"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.snapchat') }}</label><input class="form-control" name="snapchat" id="editDepartmentSnapchat"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('lang.tiktok') }}</label><input class="form-control" name="tiktok" id="editDepartmentTiktok"></div>
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
document.getElementById('editDepartmentModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editDepartmentForm').action = `{{ url('/admin/departments') }}/${id}`;
    document.getElementById('editDepartmentUserId').value = btn.getAttribute('data-user_id') || '';
    document.getElementById('editDepartmentName').value = btn.getAttribute('data-name') || '';
    document.getElementById('editDepartmentDescription').value = btn.getAttribute('data-description') || '';
    document.getElementById('editDepartmentType').value = btn.getAttribute('data-type') || 'stadium';
    document.getElementById('editDepartmentStatus').value = btn.getAttribute('data-status') || 'active';
    document.getElementById('editDepartmentLocation').value = btn.getAttribute('data-location') || '';
    document.getElementById('editDepartmentLatitude').value = btn.getAttribute('data-latitude') || '';
    document.getElementById('editDepartmentLongitude').value = btn.getAttribute('data-longitude') || '';
    document.getElementById('editDepartmentFacebook').value = btn.getAttribute('data-facebook') || '';
    document.getElementById('editDepartmentTwitter').value = btn.getAttribute('data-twitter') || '';
    document.getElementById('editDepartmentInstagram').value = btn.getAttribute('data-instagram') || '';
    document.getElementById('editDepartmentYoutube').value = btn.getAttribute('data-youtube') || '';
    document.getElementById('editDepartmentWebsite').value = btn.getAttribute('data-website') || '';
    document.getElementById('editDepartmentWhatsapp').value = btn.getAttribute('data-whatsapp') || '';
    document.getElementById('editDepartmentSnapchat').value = btn.getAttribute('data-snapchat') || '';
    document.getElementById('editDepartmentTiktok').value = btn.getAttribute('data-tiktok') || '';
});
</script>
@endpush


@if(request('edit_id'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const id = "{{ request('edit_id') }}";
    const btn = document.querySelector(`[data-bs-target="#editDepartmentModal"][data-id="${id}"]`);
    if (btn) {
        btn.click();
    }
});
</script>
@endif

@endsection
