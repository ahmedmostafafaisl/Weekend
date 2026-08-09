@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | Viewing Appointments')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ __('lang.viewing_appointments') }}</h4>
            <div class="text-muted small">{{ __('lang.viewing_appointments_subtitle') }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card card-soft shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.viewings.index') }}" class="row g-2 align-items-end">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="col-md-4">
                    <label class="form-label small">{{ __('lang.venue') }}</label>
                    <select name="unite_id" class="form-select form-select-sm">
                        <option value="">{{ __('lang.all') }}</option>
                        @foreach($unitesWithViewings as $unite)
                            <option value="{{ $unite->id }}" {{ (string) request('unite_id') === (string) $unite->id ? 'selected' : '' }}>
                                {{ $unite->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('lang.date_from') }}</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('lang.date_to') }}</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-accent flex-grow-1">{{ __('lang.filter') }}</button>
                    @if(request('unite_id') || request('date_from') || request('date_to'))
                        <a href="{{ route('admin.viewings.index', request('status') ? ['status' => request('status')] : []) }}"
                           class="btn btn-sm btn-outline-secondary">{{ __('lang.clear') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="btn-group mb-3">
        @php($otherFilters = request()->except('status'))
        <a href="{{ route('admin.viewings.index', $otherFilters) }}" class="btn btn-sm {{ ! request('status') ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ __('lang.all') }}</a>
        <a href="{{ route('admin.viewings.index', $otherFilters + ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">{{ __('lang.pending') }}</a>
        <a href="{{ route('admin.viewings.index', $otherFilters + ['status' => 'confirmed']) }}" class="btn btn-sm {{ request('status') === 'confirmed' ? 'btn-success' : 'btn-outline-success' }}">{{ __('lang.confirmed') }}</a>
        <a href="{{ route('admin.viewings.index', $otherFilters + ['status' => 'cancelled']) }}" class="btn btn-sm {{ request('status') === 'cancelled' ? 'btn-danger' : 'btn-outline-danger' }}">{{ __('lang.cancelled') }}</a>
        <a href="{{ route('admin.viewings.index', $otherFilters + ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('lang.completed') }}</a>
    </div>

    <div class="card card-soft shadow-sm">
        <div class="card-body">
            @if($viewings->isEmpty())
                <div class="text-muted text-center py-4">{{ __('lang.no_viewing_appointments') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.name') }}</th>
                                <th>{{ __('lang.booked_by') }}</th>
                                <th>{{ __('lang.viewing_date') }}</th>
                                <th>{{ __('lang.booking_package_when') }}</th>
                                <th>{{ __('lang.number_of_people') }}</th>
                                <th>{{ __('lang.status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($viewings as $viewing)
                                <tr>
                                    <td class="small fw-semibold">{{ $viewing->unite->name ?? '—' }}</td>
                                    <td class="small">{{ $viewing->user->name ?? '—' }}</td>
                                    <td class="small text-muted">{{ $viewing->viewing_date?->format('Y-m-d') }}</td>
                                    <td class="small text-muted">
                                        {{ __('lang.'.($viewing->viewingTime->day_of_week ?? 'sunday')) }}
                                        · {{ $viewing->viewingTime->start_time ?? '' }} → {{ $viewing->viewingTime->end_time ?? '' }}
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-light text-dark border">{{ $viewing->attendees->count() }}</span>
                                    </td>
                                    <td>
                                        @php($badgeClass = match($viewing->status) { 'confirmed' => 'bg-success', 'cancelled' => 'bg-danger', 'completed' => 'bg-primary', default => 'bg-warning text-dark' })
                                        <span class="badge {{ $badgeClass }}">{{ __('lang.'.$viewing->status) }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.viewings.show', $viewing->id) }}" class="btn btn-sm btn-outline-primary">{{ __('lang.view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $viewings->links() }}</div>
            @endif
        </div>
    </div>
@endsection
