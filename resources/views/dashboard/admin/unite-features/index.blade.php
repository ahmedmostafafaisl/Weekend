@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Features')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_features') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('unites.show', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back_to_unite') }}</a>

        @if($me && $me->can('unites.create'))
            <a href="{{ route('admin.unite-features.create', $unite->id) }}" class="btn btn-accent">
                + {{ __('lang.create_feature') }}
            </a>
        @endif
    </div>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>{{ __('lang.th_hash') }}</th>
                    <th>{{ __('lang.th_name') }}</th>
                    <th>{{ __('lang.th_description') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($features as $feature)
                    <tr>
                        <td>{{ $feature->id }}</td>
                        <td>{{ $feature->name }}</td>
                        <td>{{ $feature->description ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $feature->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ __('lang.'.$feature->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($me && $me->can('unites.view'))
                                <a href="{{ route('admin.unite-features.show', [$unite->id, $feature->id]) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.update'))
                                <a href="{{ route('admin.unite-features.edit', [$unite->id, $feature->id]) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('lang.edit') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.delete'))
                                <form action="{{ route('admin.unite-features.destroy', [$unite->id, $feature->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_feature') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ __('lang.no_features_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
