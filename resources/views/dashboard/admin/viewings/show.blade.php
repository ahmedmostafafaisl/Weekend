@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | ' . __('lang.viewing_appointments'))

@section('content')
@php $me = auth('admin')->user(); @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">{{ __('lang.viewing_appointments') }} #{{ $viewing->id }}</h4>
        <a href="{{ route('admin.viewings.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.general_information') }}</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.name') }}</div>
                            <div>{{ $viewing->unite->name ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.booked_by') }}</div>
                            <div>{{ $viewing->user->name ?? '—' }} <span class="text-muted small">({{ $viewing->user->phone ?? '—' }})</span></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.viewing_date') }}</div>
                            <div>{{ $viewing->viewing_date?->format('Y-m-d') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.booking_package_when') }}</div>
                            <div>
                                {{ __('lang.'.($viewing->viewingTime->day_of_week ?? 'sunday')) }}
                                · {{ $viewing->viewingTime->start_time ?? '' }} → {{ $viewing->viewingTime->end_time ?? '' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.status') }}</div>
                            @php($badgeClass = match($viewing->status) { 'confirmed' => 'bg-success', 'cancelled' => 'bg-danger', 'completed' => 'bg-primary', default => 'bg-warning text-dark' })
                            <span class="badge {{ $badgeClass }}">{{ __('lang.'.$viewing->status) }}</span>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.number_of_people') }}</div>
                            <div>{{ $viewing->attendees->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($viewing->deposit_required)
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.viewing_deposit') }}</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.viewing_deposit_amount') }}</div>
                            <div>{{ number_format($viewing->deposit_amount, 2) }} SAR</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.viewing_deposit_refundable') }}</div>
                            <div>{{ $viewing->deposit_refundable ? __('lang.yes') : __('lang.no') }}</div>
                        </div>
                        @if($viewing->payment)
                        <div class="col-6">
                            <div class="text-muted small">{{ __('lang.status') }}</div>
                            <div>{{ __('lang.'.$viewing->payment->status) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-6">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('lang.attendees') }}</h6>

                    <table class="table table-sm align-middle mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.name') }}</th>
                                <th>{{ __('lang.phone') }}</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($viewing->attendees as $attendee)
                                <tr>
                                    <td class="small fw-semibold">{{ $attendee->name }}</td>
                                    <td class="small text-muted">{{ $attendee->phone ?? '—' }}</td>
                                    <td>
                                        @if($attendee->id === $viewing->user_id)
                                            <span class="badge bg-light text-dark border">{{ __('lang.primary_booker') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendee->id !== $viewing->user_id && $me && $me->can('unite_viewings.update'))
                                            <form action="{{ route('admin.viewings.attendees.remove', [$viewing->id, $attendee->id]) }}" method="POST"
                                                  onsubmit="return confirm('{{ __('lang.remove_attendee_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">✕</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($me && $me->can('unite_viewings.update'))
                    <form action="{{ route('admin.viewings.attendees.add', $viewing->id) }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="number" name="user_id" class="form-control form-control-sm"
                               placeholder="{{ __('lang.attendee_user_id_placeholder') }}" required>
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('lang.add_attendee') }}</button>
                    </form>
                    @error('user_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
