@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unite Offers')

@section('content')
@php $me = auth('admin')->user(); @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unite_offers') }}</h4>
        <div class="text-muted">Unite #{{ $unite->id }} • {{ $unite->name ?: '—' }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('unites.show', $unite->id) }}" class="btn btn-outline-secondary">{{ __('lang.back_to_unite') }}</a>

        @if($me && $me->can('unites.create'))
            <a href="{{ route('admin.unite-offers.create', $unite->id) }}" class="btn btn-accent">
                + {{ __('lang.create_offer') }}
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
                    <th>{{ __('lang.th_start') }}</th>
                    <th>{{ __('lang.th_end') }}</th>
                    @if($unite->type === 'stadium')
                        <th>{{ __('lang.day_rate') }}</th>
                        <th>{{ __('lang.night_rate') }}</th>
                    @else
                        <th>{{ __('lang.th_morning') }}</th>
                        <th>{{ __('lang.th_evening') }}</th>
                        <th>{{ __('lang.th_full_day') }}</th>
                    @endif
                    <th>{{ __('lang.th_status') }}</th>
                    <th class="text-end">{{ __('lang.th_actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($offers as $offer)
                    <tr>
                        <td>{{ $offer->id }}</td>
                        <td>{{ $offer->name ?: '—' }}</td>
                        <td>{{ $offer->start ?: '—' }}</td>
                        <td>{{ $offer->end ?: '—' }}</td>
                        @if($unite->type === 'stadium')
                            <td>{{ $offer->day_hour_price ?? '—' }}</td>
                            <td>{{ $offer->night_hour_price ?? '—' }}</td>
                        @else
                            <td>{{ $offer->morning_price ?? '—' }}</td>
                            <td>{{ $offer->evening_price ?? '—' }}</td>
                            <td>{{ $offer->full_day_price ?? '—' }}</td>
                        @endif
                        <td>
                            <span class="badge {{ $offer->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ __('lang.'.$offer->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($me && $me->can('unites.view'))
                                <a href="{{ route('admin.unite-offers.show', [$unite->id, $offer->id]) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('lang.view') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.update'))
                                <a href="{{ route('admin.unite-offers.edit', [$unite->id, $offer->id]) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('lang.edit') }}
                                </a>
                            @endif

                            @if($me && $me->can('unites.delete'))
                                <form action="{{ route('admin.unite-offers.destroy', [$unite->id, $offer->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.delete_confirm_offer') }}')">
                                        {{ __('lang.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">{{ __('lang.no_offers_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
