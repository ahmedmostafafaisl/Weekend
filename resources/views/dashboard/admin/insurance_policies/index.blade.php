@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Insurance Policies')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.insurance_policies') }}</h4>
        <div class="text-muted">{{ __('lang.manage_all_insurance_policies') }}</div>
    </div>

    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createInsurancePolicyModal">
        + {{ __('lang.create_insurance') }}
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
                    <th>{{ __('lang.description') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($insurancePolicies as $insurancePolicy)
                    <tr>
                        <td>{{ $insurancePolicy->id }}</td>

                        <td>
                            <div class="fw-semibold">{{ $insurancePolicy->name }}</div>
                        </td>

                        <td class="text-muted">
                            {{ $insurancePolicy->description ?? '-' }}
                        </td>



                        <td class="text-end">
                            <a href="{{ route('admin.insurance_policies.show', $insurancePolicy->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                {{ __('lang.view') }}
                            </a>

                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editInsurancePolicyModal"
                                    data-id="{{ $insurancePolicy->id }}"
                                    data-name="{{ $insurancePolicy->name }}"
                                    data-description="{{ $insurancePolicy->description }}">
                                {{ __('lang.edit') }}
                            </button>

                            <form action="{{ route('admin.insurance_policies.destroy', $insurancePolicy->id) }}"
                                  method="POST"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('{{ __('lang.delete_confirm_insurance') }}')">
                                    {{ __('lang.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            {{ __('lang.no_insurance_policies_found') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>



    </div>
</div>

{{-- CREATE MODAL --}}
<div class="modal fade" id="createInsurancePolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content"
              method="POST"
              action="{{ route('admin.insurance_policies.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_insurance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.description') }}</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>


            </div>

            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.save') }}</button>
            </div>

        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="editInsurancePolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content"
              method="POST"
              id="editInsurancePolicyForm"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_insurance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" id="editInsurancePolicyName">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.description') }}</label>
                    <textarea class="form-control"
                              name="description"
                              id="editInsurancePolicyDescription"
                              rows="3"></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.update') }}</button>
            </div>

        </form>
    </div>
</div>

@endsection

@push('js')
<script>
document.getElementById('editInsurancePolicyModal')
    .addEventListener('show.bs.modal', function (event) {

    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editInsurancePolicyForm').action =
        `{{ url('/admin/insurance_policies') }}/${id}`;

    document.getElementById('editInsurancePolicyName').value =
        btn.getAttribute('data-name') || '';

    document.getElementById('editInsurancePolicyDescription').value =
        btn.getAttribute('data-description') || '';
});
</script>
@endpush
