@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Stadium Types')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.stadium_types') }}</h4>
        <div class="text-muted">{{ __('lang.manage_all_stadium_types') }}</div>
    </div>

    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createStadiumTypeModal">
        + {{ __('lang.create_stadium_type') }}
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
                    <th>{{ __('lang.image') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($stadiumTypes as $stadiumType)
                    <tr>
                        <td>{{ $stadiumType->id }}</td>

                        <td>
                            <div class="fw-semibold">{{ $stadiumType->name }}</div>
                        </td>

                        <td class="text-muted">
                            {{ $stadiumType->description ?? '-' }}
                        </td>

                        <td>
                            @if($stadiumType->image)
                                <img src="{{ asset('storage/' . $stadiumType->image) }}"
                                     class="img-thumbnail"
                                     style="max-width: 80px;">
                            @else
                                —
                            @endif
                        </td>

                        <td class="text-end">
                            <a href="{{ route('admin.stadium_types.show', $stadiumType->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                {{ __('lang.view') }}
                            </a>

                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editStadiumTypeModal"
                                    data-id="{{ $stadiumType->id }}"
                                    data-name="{{ $stadiumType->name }}"
                                    data-description="{{ $stadiumType->description }}">
                                {{ __('lang.edit') }}
                            </button>

                            <form action="{{ route('admin.stadium_types.destroy', $stadiumType->id) }}"
                                  method="POST"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('{{ __('lang.delete_confirm_stadium_type') }}')">
                                    {{ __('lang.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            {{ __('lang.no_stadium_types_found') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>



    </div>
</div>

{{-- CREATE MODAL --}}
<div class="modal fade" id="createStadiumTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content"
              method="POST"
              action="{{ route('admin.stadium_types.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_stadium_type') }}</h5>
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

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.image') }}</label>
                    <input class="form-control" type="file" name="image">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.save') }}</button>
            </div>

        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="editStadiumTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content"
              method="POST"
              id="editStadiumTypeForm"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_stadium_type') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.name') }}</label>
                    <input class="form-control" name="name" id="editStadiumTypeName">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.description') }}</label>
                    <textarea class="form-control"
                              name="description"
                              id="editStadiumTypeDescription"
                              rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.image') }}</label>
                    <input class="form-control" type="file" name="image">
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
document.getElementById('editStadiumTypeModal')
    .addEventListener('show.bs.modal', function (event) {

    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editStadiumTypeForm').action =
        `{{ url('/admin/stadium_types') }}/${id}`;

    document.getElementById('editStadiumTypeName').value =
        btn.getAttribute('data-name') || '';

    document.getElementById('editStadiumTypeDescription').value =
        btn.getAttribute('data-description') || '';
});
</script>
@endpush
