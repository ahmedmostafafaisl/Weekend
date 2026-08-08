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

    <div class="btn-group mb-3">
        <a href="{{ route('admin.viewings.index') }}" class="btn btn-sm {{ ! request('status') ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ __('lang.all') }}</a>
        <a href="{{ route('admin.viewings.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">{{ __('lang.pending') }}</a>
        <a href="{{ route('admin.viewings.index', ['status' => 'confirmed']) }}" class="btn btn-sm {{ request('status') === 'confirmed' ? 'btn-success' : 'btn-outline-success' }}">{{ __('lang.confirmed') }}</a>
        <a href="{{ route('admin.viewings.index', ['status' => 'cancelled']) }}" class="btn btn-sm {{ request('status') === 'cancelled' ? 'btn-danger' : 'btn-outline-danger' }}">{{ __('lang.cancelled') }}</a>
        <a href="{{ route('admin.viewings.index', ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('lang.completed') }}</a>
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
