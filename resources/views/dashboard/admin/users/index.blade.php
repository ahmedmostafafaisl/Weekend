@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Users')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.users') }}</h4>
        <div class="text-muted">{{ __('lang.manage_customers_providers') }}</div>
    </div>

    @if($me && $me->can('users.create'))
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createUserModal">
           {{ __('lang.create_user') }}
        </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">

        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('lang.search_name_email_phone') }}">
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
                    <th>{{ __('lang.user') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th>{{ __('lang.th_type') }}</th>
                    <th>{{ __('lang.nation') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>{{ $u->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $u->name }}</div>
                            <div class="text-muted small">{{ $u->email }} @if($u->phone) • {{ $u->phone }} @endif</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $u->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ __('lang.'.$u->status) }}
                                </span>
                                <form action="{{ route('admin.users.toggle-status', $u->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <div class="form-check form-switch mb-0" title="{{ $u->status === 'active' ? __('lang.deactivate') : __('lang.activate') }}">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               {{ $u->status === 'active' ? 'checked' : '' }}
                                               onchange="this.form.submit()"
                                               style="cursor:pointer">
                                    </div>
                                </form>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ __('lang.'.$u->type) }}
                                @if($u->type === 'provider' && $u->provider_type)
                                    • {{ __('lang.'.$u->provider_type) }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $u->nation === 'saudi' ? __('lang.saudi') : $u->nation }}</span>
                        </td>
                        <td class="text-end">
                            @if($me && $me->can('users.view'))
                               <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-outline-secondary">
                                       {{ __('lang.view') }}
                               </a>
                            @endif

                            @if($me && $me->can('users.update'))
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-id="{{ $u->id }}"
                                        data-name="{{ $u->name }}"
                                        data-email="{{ $u->email }}"
                                        data-phone="{{ $u->phone }}"
                                        data-status="{{ $u->status }}"
                                        data-type="{{ $u->type }}"
                                        data-provider_type="{{ $u->provider_type }}"
                                        data-nation="{{ $u->nation }}"
                                        data-id_number="{{ $u->id_number }}"
                                        data-birth_date="{{ optional($u->birth_date)->format('Y-m-d') }}"
                                        data-ownership="{{ (string)($u->ownership ?? '0') }}"
                                        data-delegation="{{ $u->delegation }}"
                                        data-commercial_register_number="{{ $u->commercial_register_number }}"
                                        data-organization_name="{{ $u->organization_name }}"
                                        data-commercial_name="{{ $u->commercial_name }}"
                                        data-photo="{{ $u->photo }}"
                                        data-front_identity="{{ $u->front_identity }}"
                                        data-back_identity="{{ $u->back_identity }}"
                                        data-sak_image="{{ $u->sak_image }}"
                                        data-commercial_register_image="{{ $u->commercial_register_image }}"
                                >
                                    {{ __('lang.edit') }}
                                </button>
                            @endif

                            @if($me && $me->can('users.delete'))
                                <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('{{ __('lang.delete_confirm_user') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('lang.no_users_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('vendor.pagination.weekend') }}
    </div>
</div>

{{-- ================= Create Modal ================= --}}
@if($me && $me->can('users.create'))
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_user') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                    {{-- Basics --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.email') }}</label>
                        <input class="form-control" name="email" type="email" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.phone') }}</label>
                        <input class="form-control" name="phone" type="number">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('lang.password') }}</label>
                        <input class="form-control" name="password" type="password" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('lang.confirm_password') }}</label>
                        <input class="form-control" name="password_confirmation" type="password" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" required>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select" name="type" id="createUserType" required>
                            @foreach($types as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.provider_type') }}</label>
                        <select class="form-select" name="provider_type" id="createProviderType">
                            <option value="">—</option>
                            @foreach($providerTypes as $pt)
                                <option value="{{ $pt }}">{{ $pt }}</option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1">{{ __('lang.only_for_providers') }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.nation') }}</label>
                        <select class="form-select" name="nation" required>
                            @foreach($nations as $n)
                                <option value="{{ $n }}">{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.id_number') }}</label>
                        <input class="form-control" name="id_number">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.birth_date') }}</label>
                        <input class="form-control" name="birth_date" type="date">
                    </div>

                    {{-- Provider section --}}
                    <div class="col-12">
                        <hr class="my-1">
                        <div class="fw-semibold">{{ __('lang.provider_data') }}</div>
                        <div class="text-muted small">{{ __('lang.only_for_provider_type') }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.ownership') }}</label>
                        <select class="form-select" name="ownership" id="createOwnership">
                            <option value="0">0</option>
                            <option value="1">1 (مالك)</option>
                            <option value="2">2 (موكل)</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">{{ __('lang.delegation') }}</label>
                        <input class="form-control" name="delegation" id="createDelegation" placeholder="{{ __('lang.authorized_placeholder') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.commercial_register_number') }}</label>
                        <input class="form-control" name="commercial_register_number" id="createCrn">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.organization_name') }}</label>
                        <input class="form-control" name="organization_name" id="createOrgName">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.commercial_name') }}</label>
                        <input class="form-control" name="commercial_name" id="createCommercialName">
                    </div>

                    {{-- Files --}}
                    <div class="col-12">
                        <hr class="my-1">
                        <div class="fw-semibold">{{ __('lang.files') }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.photo') }}</label>
                        <input class="form-control" name="photo" type="file" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.front_identity') }}</label>
                        <input class="form-control" name="front_identity" type="file">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.back_identity') }}</label>
                        <input class="form-control" name="back_identity" type="file">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.sak_image') }}</label>
                        <input class="form-control" name="sak_image" type="file">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.commercial_register_image') }}</label>
                        <input class="form-control" name="commercial_register_image" type="file">
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

{{-- ================= Edit Modal ================= --}}
@if($me && $me->can('users.update'))
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="editUserForm" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_user') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                    {{-- Basics --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.name') }}</label>
                        <input class="form-control" name="name" id="editUserName" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.email') }}</label>
                        <input class="form-control" name="email" id="editUserEmail" type="email" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.phone') }}</label>
                        <input class="form-control" name="phone" id="editUserPhone" type="number">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('lang.password_leave_empty') }}</label>
                        <input class="form-control" name="password" type="password">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('lang.confirm_password') }}</label>
                        <input class="form-control" name="password_confirmation" type="password">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.status') }}</label>
                        <select class="form-select" name="status" id="editUserStatus" required>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.type') }}</label>
                        <select class="form-select" name="type" id="editUserType" required>
                            @foreach($types as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.provider_type') }}</label>
                        <select class="form-select" name="provider_type" id="editProviderType">
                            <option value="">—</option>
                            @foreach($providerTypes as $pt)
                                <option value="{{ $pt }}">{{ $pt }}</option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1">{{ __('lang.only_for_providers') }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.nation') }}</label>
                        <select class="form-select" name="nation" id="editUserNation" required>
                            @foreach($nations as $n)
                                <option value="{{ $n }}">{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.id_number') }}</label>
                        <input class="form-control" name="id_number" id="editUserIdNumber">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.birth_date') }}</label>
                        <input class="form-control" name="birth_date" id="editUserBirthDate" type="date">
                    </div>

                    {{-- Provider section --}}
                    <div class="col-12">
                        <hr class="my-1">
                        <div class="fw-semibold">{{ __('lang.provider_data') }}</div>
                        <div class="text-muted small">{{ __('lang.only_for_provider_type') }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('lang.ownership') }}</label>
                        <select class="form-select" name="ownership" id="editOwnership">
                            <option value="0">0</option>
                            <option value="1">1 (مالك)</option>
                            <option value="2">2 (موكل)</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">{{ __('lang.delegation') }}</label>
                        <input class="form-control" name="delegation" id="editDelegation">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.commercial_register_number') }}</label>
                        <input class="form-control" name="commercial_register_number" id="editCrn">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.organization_name') }}</label>
                        <input class="form-control" name="organization_name" id="editOrgName">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.commercial_name') }}</label>
                        <input class="form-control" name="commercial_name" id="editCommercialName">
                    </div>

                    {{-- Files --}}
                    <div class="col-12">
                        <hr class="my-1">
                        <div class="fw-semibold">{{ __('lang.files') }}</div>
                        <div class="text-muted small">{{ __('lang.uploading_replaces_file') }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.photo') }}</label>
                        <input class="form-control" name="photo" type="file" accept="image/*">
                        <div class="mt-1 small" id="viewPhotoWrap"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.front_identity') }}</label>
                        <input class="form-control" name="front_identity" type="file">
                        <div class="mt-1 small" id="viewFrontWrap"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.back_identity') }}</label>
                        <input class="form-control" name="back_identity" type="file">
                        <div class="mt-1 small" id="viewBackWrap"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.sak_image') }}</label>
                        <input class="form-control" name="sak_image" type="file">
                        <div class="mt-1 small" id="viewSakWrap"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('lang.commercial_register_image') }}</label>
                        <input class="form-control" name="commercial_register_image" type="file">
                        <div class="mt-1 small" id="viewCrImgWrap"></div>
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
function setLink(containerId, path){
    const el = document.getElementById(containerId);
    if (!el) return;
    if (!path) { el.innerHTML = ''; return; }
    el.innerHTML = `<a href="{{ asset('storage') }}/${path}" target="_blank">{{ __('lang.view_current') }}</a>`;
}

function toggleProviderSection(typeSelectId, fieldsIds){
    const typeEl = document.getElementById(typeSelectId);
    if (!typeEl) return;

    const isProvider = typeEl.value === 'provider';
    fieldsIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.disabled = !isProvider;
        if (!isProvider && el.tagName === 'SELECT') el.value = '';
        if (!isProvider && el.tagName === 'INPUT') el.value = '';
    });
}

// Create toggle
document.getElementById('createUserType')?.addEventListener('change', function(){
    toggleProviderSection('createUserType', [
        'createProviderType','createOwnership','createDelegation',
        'createCrn','createOrgName','createCommercialName'
    ]);
});
toggleProviderSection('createUserType', [
    'createProviderType','createOwnership','createDelegation',
    'createCrn','createOrgName','createCommercialName'
]);

// Edit modal fill
document.getElementById('editUserModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;

    const id = btn.getAttribute('data-id');
    document.getElementById('editUserForm').action = `{{ url('/admin/users') }}/${id}`;

    document.getElementById('editUserName').value = btn.getAttribute('data-name') || '';
    document.getElementById('editUserEmail').value = btn.getAttribute('data-email') || '';
    document.getElementById('editUserPhone').value = btn.getAttribute('data-phone') || '';
    document.getElementById('editUserStatus').value = btn.getAttribute('data-status') || 'active';
    document.getElementById('editUserType').value = btn.getAttribute('data-type') || 'customer';
    document.getElementById('editProviderType').value = btn.getAttribute('data-provider_type') || '';
    document.getElementById('editUserNation').value = btn.getAttribute('data-nation') || 'saudi';

    document.getElementById('editUserIdNumber').value = btn.getAttribute('data-id_number') || '';
    document.getElementById('editUserBirthDate').value = btn.getAttribute('data-birth_date') || '';
    document.getElementById('editOwnership').value = btn.getAttribute('data-ownership') || '0';
    document.getElementById('editDelegation').value = btn.getAttribute('data-delegation') || '';

    document.getElementById('editCrn').value = btn.getAttribute('data-commercial_register_number') || '';
    document.getElementById('editOrgName').value = btn.getAttribute('data-organization_name') || '';
    document.getElementById('editCommercialName').value = btn.getAttribute('data-commercial_name') || '';

    // show file links (can't prefill file inputs)
    setLink('viewPhotoWrap', btn.getAttribute('data-photo'));
    setLink('viewFrontWrap', btn.getAttribute('data-front_identity'));
    setLink('viewBackWrap', btn.getAttribute('data-back_identity'));
    setLink('viewSakWrap', btn.getAttribute('data-sak_image'));
    setLink('viewCrImgWrap', btn.getAttribute('data-commercial_register_image'));

    toggleProviderSection('editUserType', [
        'editProviderType','editOwnership','editDelegation',
        'editCrn','editOrgName','editCommercialName'
    ]);
});

document.getElementById('editUserType')?.addEventListener('change', function(){
    toggleProviderSection('editUserType', [
        'editProviderType','editOwnership','editDelegation',
        'editCrn','editOrgName','editCommercialName'
    ]);
});
</script>

@if(request('edit_id'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const id = "{{ request('edit_id') }}";
    const btn = document.querySelector(`[data-bs-target="#editUserModal"][data-id="${id}"]`);
    if (btn) {
        btn.click(); // يفتح المودال ويشغل event show.bs.modal
    }
});
</script>
@endif

@endpush

@endsection
