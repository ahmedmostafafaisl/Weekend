@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Property Packages')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.property_packages') }}</h4>
        <div class="text-muted">{{ __('lang.manage_property_subscription_packages') }}</div>
    </div>

    @if($me && $me->can('property_packages.create'))
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createPackageModal">
            + {{ __('lang.create_property_package') }}
        </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_package_name_description') }}">
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
                    <th>{{ __('lang.package') }}</th>
                    <th>{{ __('lang.th_type') }}</th>
                    <th>{{ __('lang.th_price') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($packages as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->name }}</div>
                            <div class="text-muted small">{{ $p->description ?: '—' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ __('lang.'.$p->type) }}</span>
                            <div class="small text-muted mt-1">
                                @if($p->type === 'time')
                                    Duration: {{ $p->duration ?: '—' }} days
                                @elseif($p->type === 'percentage')
                                    Percentage: {{ $p->percentage ?: '—' }}%
                                @elseif($p->type === 'count')
                                    Count: {{ $p->count ?: '—' }} unites
                                @endif
                            </div>
                        </td>
                        <td>{{ $p->price ? number_format($p->price, 2) : '—' }}</td>
                        <td>
                            <span class="badge {{ $p->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ __('lang.'.$p->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($me && $me->can('property_packages.view'))
                                <a href="{{ route('admin.property-packages.show', $p->id) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('property_packages.update'))
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPackageModal"
                                        data-id="{{ $p->id }}"
                                        data-name="{{ $p->name }}"
                                        data-description="{{ $p->description }}"
                                        data-type="{{ $p->type }}"
                                        data-duration="{{ $p->duration }}"
                                        data-percentage="{{ $p->percentage }}"
                                        data-count="{{ $p->count }}"
                                        data-price="{{ $p->price }}"
                                        data-status="{{ $p->status }}"
                                        data-image="{{ $p->image }}">
                                    {{ __('lang.edit') }}
                                </button>
                            @endif

                            @if($me && $me->can('property_packages.delete'))
                                <form action="{{ route('admin.property-packages.destroy', $p->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_package') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('lang.no_packages_found_generic') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $packages->links('vendor.pagination.weekend') }}
    </div>
</div>

@if($me && $me->can('property_packages.create'))
<div class="modal fade" id="createPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.property-packages.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_property_package') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select" name="type" id="createPackageType" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ __('lang.description') }}</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.duration') }}</label>
                        <input class="form-control" name="duration" id="createPackageDuration" type="number" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.percentage') }}</label>
                        <input class="form-control" name="percentage" id="createPackagePercentage" type="number" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.count') }}</label>
                        <input class="form-control" name="count" id="createPackageCount" type="number" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.price') }}</label>
                        <input class="form-control" name="price" type="number" step="0.01" min="0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ __('lang.image') }}</label>
                        <input class="form-control" name="image" type="file" accept="image/*">
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

@if($me && $me->can('property_packages.update'))
<div class="modal fade" id="editPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="editPackageForm" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_property_package') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" id="editPackageName" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select" name="type" id="editPackageType" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ __('lang.description') }}</label>
                        <textarea class="form-control" name="description" id="editPackageDescription" rows="2"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.duration') }}</label>
                        <input class="form-control" name="duration" id="editPackageDuration" type="number" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.percentage') }}</label>
                        <input class="form-control" name="percentage" id="editPackagePercentage" type="number" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.count') }}</label>
                        <input class="form-control" name="count" id="editPackageCount" type="number" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.price') }}</label>
                        <input class="form-control" name="price" id="editPackagePrice" type="number" step="0.01" min="0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" id="editPackageStatus" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ __('lang.image') }}</label>
                        <input class="form-control" name="image" type="file" accept="image/*">
                        <div class="mt-1 small" id="viewPackageImageWrap"></div>
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

@push('js')
<script>
function togglePackageTypeFields(typeId, durationId, percentageId, countId) {
    const typeEl = document.getElementById(typeId);
    const durationEl = document.getElementById(durationId);
    const percentageEl = document.getElementById(percentageId);
    const countEl = document.getElementById(countId);

    if (!typeEl || !durationEl || !percentageEl || !countEl) return;

    durationEl.disabled = true;
    percentageEl.disabled = true;
    countEl.disabled = true;

    if (typeEl.value === 'time') {
        durationEl.disabled = false;
        percentageEl.value = '';
        countEl.value = '';
    } else if (typeEl.value === 'percentage') {
        percentageEl.disabled = false;
        durationEl.value = '';
        countEl.value = '';
    } else if (typeEl.value === 'count') {
        countEl.disabled = false;
        durationEl.value = '';
        percentageEl.value = '';
    }
}

function setPackageImageLink(path) {
    const el = document.getElementById('viewPackageImageWrap');
    if (!el) return;
    if (!path) { el.innerHTML = ''; return; }
    el.innerHTML = `<a href="{{ asset('storage') }}/${path}" target="_blank">{{ __('lang.view_current_image') }}</a>`;
}

document.getElementById('createPackageType')?.addEventListener('change', function () {
    togglePackageTypeFields('createPackageType', 'createPackageDuration', 'createPackagePercentage', 'createPackageCount');
});
togglePackageTypeFields('createPackageType', 'createPackageDuration', 'createPackagePercentage', 'createPackageCount');

document.getElementById('editPackageType')?.addEventListener('change', function () {
    togglePackageTypeFields('editPackageType', 'editPackageDuration', 'editPackagePercentage', 'editPackageCount');
});

document.getElementById('editPackageModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editPackageForm').action = `{{ url('/admin/property-packages') }}/${id}`;
    document.getElementById('editPackageName').value = btn.getAttribute('data-name') || '';
    document.getElementById('editPackageDescription').value = btn.getAttribute('data-description') || '';
    document.getElementById('editPackageType').value = btn.getAttribute('data-type') || 'time';
    document.getElementById('editPackageDuration').value = btn.getAttribute('data-duration') || '';
    document.getElementById('editPackagePercentage').value = btn.getAttribute('data-percentage') || '';
    document.getElementById('editPackageCount').value = btn.getAttribute('data-count') || '';
    document.getElementById('editPackagePrice').value = btn.getAttribute('data-price') || '';
    document.getElementById('editPackageStatus').value = btn.getAttribute('data-status') || 'active';

    setPackageImageLink(btn.getAttribute('data-image'));
    togglePackageTypeFields('editPackageType', 'editPackageDuration', 'editPackagePercentage', 'editPackageCount');
});
</script>

@if(request('edit_id'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const id = "{{ request('edit_id') }}";
    const btn = document.querySelector(`[data-bs-target="#editPackageModal"][data-id="${id}"]`);
    if (btn) btn.click();
});
</script>
@endif
@endpush

@endsection
