@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Slots')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_slots') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }} • {{ $unite->type }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('unites.show', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back_to_unite') }}</a>

        @if($me && $me->can('unites.create'))
            <a href="{{ route('admin.unite-slots.create', $unite->id) }}" class="btn btn-accent">
                + {{ __('lang.create_slot') }}
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
                    <th>{{ __('lang.th_date') }}</th>
                    <th>{{ __('lang.th_morning') }}</th>
                    <th>{{ __('lang.th_evening') }}</th>
                    <th>{{ __('lang.th_full') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($slots as $slot)
                    <tr>
                        <td>{{ $slot->id }}</td>
                        <td>{{ optional($slot->slot_date)->format('Y-m-d') }}</td>
                        <td>
                            {{ $slot->morning_start && $slot->morning_end ? $slot->morning_start . ' - ' . $slot->morning_end : '—' }}
                        </td>
                        <td>
                            {{ $slot->evening_start && $slot->evening_end ? $slot->evening_start . ' - ' . $slot->evening_end : '—' }}
                        </td>
                        <td>
                            {{ $slot->full_start && $slot->full_end ? $slot->full_start . ' - ' . $slot->full_end : '—' }}
                        </td>
                        <td>
                            <span class="badge
                                {{ $slot->status === 'available' ? 'bg-success' : '' }}
                                {{ $slot->status === 'booked' ? 'bg-warning text-dark' : '' }}
                                {{ $slot->status === 'unavailable' ? 'bg-secondary' : '' }}">
                                {{ __('lang.'.$slot->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($me && $me->can('unites.view'))
                                <a href="{{ route('admin.unite-slots.show', [$unite->id, $slot->id]) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.update'))
                                <a href="{{ route('admin.unite-slots.edit', [$unite->id, $slot->id]) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('lang.edit') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.delete'))
                                <form action="{{ route('admin.unite-slots.destroy', [$unite->id, $slot->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_slot') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('lang.no_slots_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
