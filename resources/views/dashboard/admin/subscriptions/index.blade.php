@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.subscriptions'))

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.subscriptions') }}</h4>
        <div class="text-muted">{{ __('lang.manage_subscriptions') }}</div>
    </div>

    @if($me && $me->can('subscriptions.create'))
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createSubscriptionModal">
            + {{ __('lang.create_subscription') }}
        </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_by_user_name_email') }}">
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
                    <th>{{ __('lang.th_user') }}</th>
                    <th>{{ __('lang.th_type') }}</th>
                    <th>{{ __('lang.th_package') }}</th>
                    <th>{{ __('lang.th_amount') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($subscriptions as $s)
                    <tr>
                        <td>{{ $s->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $s->user?->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $s->user?->email ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $s->type }}</span>
                        </td>
                        <td>
                            @if($s->type === 'property')
                                {{ $s->propertyPackage?->name ?? '—' }}
                            @else
                                {{ $s->adPackage?->name ?? '—' }}
                            @endif
                        </td>
                        <td>{{ number_format((float)$s->amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $s->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ __('lang.'.$s->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($me && $me->can('subscriptions.view'))
                                <a href="{{ route('admin.subscriptions.show', $s->id) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('subscriptions.update'))
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editSubscriptionModal"
                                        data-id="{{ $s->id }}"
                                        data-user_id="{{ $s->user_id }}"
                                        data-type="{{ $s->type }}"
                                        data-package_id="{{ $s->package_id }}"
                                        data-status="{{ $s->status }}">
                                    {{ __('lang.edit') }}
                                </button>
                            @endif

                            @if($me && $me->can('subscriptions.delete'))
                                <form action="{{ route('admin.subscriptions.destroy', $s->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_subscription') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('lang.no_subscriptions_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $subscriptions->links('vendor.pagination.weekend') }}
    </div>
</div>

@if($me && $me->can('subscriptions.create'))
<div class="modal fade" id="createSubscriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.subscriptions.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_subscription') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.user') }}</label>
                        <select class="form-select" name="user_id" required>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} - {{ $u->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select subscription-type" name="type" required>
                            <option value="property">property</option>
                            <option value="ad">ad</option>
                        </select>
                    </div>

                    <div class="col-md-6 property-package-wrap">
                        <label class="form-label">{{ __('lang.property_packages') }}</label>
                        <select class="form-select property-package-select" name="package_id">
                            <option value="">—</option>
                            @foreach($propertyPackages as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 ad-package-wrap d-none">
                        <label class="form-label">{{ __('lang.ad_packages') }}</label>
                        <select class="form-select ad-package-select" data-name="package_id">
                            <option value="">—</option>
                            @foreach($adPackages as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status">
                            <option value="active">{{ __('lang.active') }}</option>
                            <option value="inactive">{{ __('lang.inactive') }}</option>
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

@if($me && $me->can('subscriptions.update'))
<div class="modal fade" id="editSubscriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="editSubscriptionForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_subscription') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.user') }}</label>
                        <select class="form-select" name="user_id" id="editSubscriptionUserId" required>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} - {{ $u->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select subscription-type" name="type" id="editSubscriptionType" required>
                            <option value="property">property</option>
                            <option value="ad">ad</option>
                        </select>
                    </div>

                    <div class="col-md-6 property-package-wrap">
                        <label class="form-label">{{ __('lang.property_packages') }}</label>
                        <select class="form-select property-package-select" name="package_id" id="editPropertyPackageId">
                            <option value="">—</option>
                            @foreach($propertyPackages as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 ad-package-wrap d-none">
                        <label class="form-label">{{ __('lang.ad_packages') }}</label>
                        <select class="form-select ad-package-select" id="editAdPackageId" data-name="package_id">
                            <option value="">—</option>
                            @foreach($adPackages as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" id="editSubscriptionStatus">
                            <option value="active">{{ __('lang.active') }}</option>
                            <option value="inactive">{{ __('lang.inactive') }}</option>
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

@push('js')
<script>
function toggleSubscriptionPackageFields(container) {
    const typeSelect = container.querySelector('.subscription-type');
    const propertyWrap = container.querySelector('.property-package-wrap');
    const adWrap = container.querySelector('.ad-package-wrap');
    const propertySelect = container.querySelector('.property-package-select');
    const adSelect = container.querySelector('.ad-package-select');

    if (!typeSelect || !propertyWrap || !adWrap || !propertySelect || !adSelect) return;

    if (typeSelect.value === 'property') {
        propertyWrap.classList.remove('d-none');
        adWrap.classList.add('d-none');

        propertySelect.setAttribute('name', 'package_id');
        adSelect.removeAttribute('name');
        adSelect.value = '';
    } else {
        adWrap.classList.remove('d-none');
        propertyWrap.classList.add('d-none');

        adSelect.setAttribute('name', 'package_id');
        propertySelect.removeAttribute('name');
        propertySelect.value = '';
    }
}

document.querySelectorAll('#createSubscriptionModal, #editSubscriptionModal').forEach(modal => {
    modal.addEventListener('change', function (e) {
        if (e.target.classList.contains('subscription-type')) {
            toggleSubscriptionPackageFields(modal);
        }
    });
    toggleSubscriptionPackageFields(modal);
});

document.getElementById('editSubscriptionModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editSubscriptionForm').action = `{{ url('/admin/subscriptions') }}/${id}`;
    document.getElementById('editSubscriptionUserId').value = btn.getAttribute('data-user_id') || '';
    document.getElementById('editSubscriptionType').value = btn.getAttribute('data-type') || 'property';
    document.getElementById('editSubscriptionStatus').value = btn.getAttribute('data-status') || 'active';

    document.getElementById('editPropertyPackageId').value = '';
    document.getElementById('editAdPackageId').value = '';

    if ((btn.getAttribute('data-type') || '') === 'property') {
        document.getElementById('editPropertyPackageId').value = btn.getAttribute('data-package_id') || '';
    } else {
        document.getElementById('editAdPackageId').value = btn.getAttribute('data-package_id') || '';
    }

    toggleSubscriptionPackageFields(document.getElementById('editSubscriptionModal'));
});
</script>

@if(request('edit_id'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const id = "{{ request('edit_id') }}";
    const btn = document.querySelector(`[data-bs-target="#editSubscriptionModal"][data-id="${id}"]`);
    if (btn) btn.click();
});
</script>
@endif
@endpush

@endsection
