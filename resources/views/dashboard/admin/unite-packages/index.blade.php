@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Packages')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_packages') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('unites.show', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back_to_unite') }}</a>

        @if($me && $me->can('unites.create'))
            <a href="{{ route('admin.unite-packages.create', $unite->id) }}" class="btn btn-accent">
                + {{ __('lang.create_package') }}
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
                    <th>{{ __('lang.th_men_capacity') }}</th>
                    <th>{{ __('lang.th_women_capacity') }}</th>
                    <th>{{ __('lang.th_price') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($packages as $package)
                    <tr>
                        <td>{{ $package->id }}</td>
                        <td>{{ $package->name ?: '—' }}</td>
                        <td>{{ $package->men_capacity ?? '—' }}</td>
                        <td>{{ $package->women_capacity ?? '—' }}</td>
                        <td>{{ $package->price ?? '—' }}</td>
                        <td class="text-end">
                            @if($me && $me->can('unites.view'))
                                <a href="{{ route('admin.unite-packages.show', [$unite->id, $package->id]) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.update'))
                                <a href="{{ route('admin.unite-packages.edit', [$unite->id, $package->id]) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('lang.edit') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.delete'))
                                <form action="{{ route('admin.unite-packages.destroy', [$unite->id, $package->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_package') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('lang.no_packages_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
