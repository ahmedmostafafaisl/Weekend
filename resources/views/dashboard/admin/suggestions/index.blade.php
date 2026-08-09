@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Suggestions')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.suggestions') }}</h4>
        <div class="text-muted">{{ __('lang.manage_all_suggestions') }}</div>
    </div>

    @if($me && $me->can('suggestions.create'))
    <button class="btn btn-accent"
            data-bs-toggle="modal"
            data-bs-target="#createSuggestionModal">
        + {{ __('lang.create_suggestion') }}
    </button>
    @endif
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>{{ __('lang.th_hash') }}</th>
                    <th>{{ __('lang.content') }}</th>
                    <th>{{ __('lang.user') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($suggestions as $suggestion)
                    <tr>
                        <td>{{ $suggestion->id }}</td>

                        <td>
                            <div class="fw-semibold">
                                {{ $suggestion->content ?? '-' }}
                            </div>
                        </td>

                        <td class="text-muted">
                            {{ $suggestion->user->name ?? '-' }}
                        </td>

                        <td class="text-end">

                            @if($me && $me->can('suggestions.view'))
                            <a href="{{ route('admin.suggestions.show', $suggestion->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                {{ __('lang.view') }}
                            </a>
                            @endif

                            @if($me && $me->can('suggestions.update'))
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editSuggestionModal"
                                    data-id="{{ $suggestion->id }}"
                                    data-content="{{ $suggestion->content }}"
                                    data-user-id="{{ $suggestion->user_id }}">
                                {{ __('lang.edit') }}
                            </button>
                            @endif

                            @if($me && $me->can('suggestions.delete'))
                            <form action="{{ route('admin.suggestions.destroy', $suggestion->id) }}"
                                  method="POST"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('{{ __('lang.delete_confirm_suggestion') }}')">
                                    {{ __('lang.delete') }}
                                </button>
                            </form>
                            @endif

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('lang.no_suggestions_found') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>



    </div>
</div>

{{-- CREATE MODAL --}}
@if($me && $me->can('suggestions.create'))
<div class="modal fade" id="createSuggestionModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content"
              method="POST"
              action="{{ route('admin.suggestions.store') }}">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.create_suggestion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.content') }}</label>
                    <textarea class="form-control" name="content" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.user') }}</label>
                    <select class="form-control" name="user_id" required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.save') }}</button>
            </div>

        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
@endif

@if($me && $me->can('suggestions.update'))
<div class="modal fade" id="editSuggestionModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content"
              method="POST"
              id="editSuggestionForm">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.edit_suggestion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.content') }}</label>

                    {{-- show old content --}}

                    <textarea class="form-control"
                              name="content"
                              id="editSuggestionContent"></textarea>

                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.user') }}</label>
                    <select class="form-control"
                            name="user_id"
                            id="editSuggestionUser">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-accent">{{ __('lang.update') }}</button>
            </div>

        </form>
    </div>
</div>
@endif

@endsection

@push('js')
<script>
document.getElementById('editSuggestionModal')
    .addEventListener('show.bs.modal', function (event) {

    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');

    document.getElementById('editSuggestionForm').action =
        `{{ url('/admin/suggestions') }}/${id}`;

    document.getElementById('editSuggestionContent').value =
        btn.getAttribute('data-content') || '';

    document.getElementById('editSuggestionUser').value =
        btn.getAttribute('data-user-id') || '';
});
</script>
@endpush
