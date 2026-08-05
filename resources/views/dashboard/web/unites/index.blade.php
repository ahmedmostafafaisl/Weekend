@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Unites')

@section('content')
@php $me = auth('admin')->user() ?? auth()->user(); @endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.unites') }}</h4>
        <div class="text-muted">{{ __('lang.manage_halls_stadiums_lounges_camps') }}</div>
    </div>
    <a href="{{ route('unites.create') }}" class="btn btn-accent">{{ __('lang.add_unit') }}</a>
</div>

{{-- Filters --}}
<div class="card card-soft shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-4">
                <input class="form-control" name="{{ __('lang.search') }}" value="{{ request('search') }}" placeholder="{{ __('lang.search_by_name') }}">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="">{{ __('lang.all_types') }}</option>
                    @foreach(['stadium','hall','lounge','camp'] as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ __('lang.'.$t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">{{ __('lang.all_statuses') }}</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>{{ __('lang.active') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100">{{ __('lang.search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.th_hash') }}</th>
                        <th>{{ __('lang.th_name') }}</th>
                        <th>{{ __('lang.th_type') }}</th>
                        <th>{{ __('lang.department') }}</th>
                        <th>{{ __('lang.city') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                        <th>{{ __('lang.approval') }}</th>
                        <th>{{ __('lang.images') }}</th>
                        <th>{{ __('lang.features') }}</th>
                        <th>{{ __('lang.offers') }}</th>
                        <th>{{ __('lang.reservations_count') }}</th>
                        <th class="text-end">{{ __('lang.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($unites as $unite)
                    @php
                        $activeOffers  = $unite->offers->where('status','active')->count();
                        $confirmed     = $unite->reservations->where('status','confirmed')->count();
                        $pending       = $unite->reservations->where('status','pending')->count();
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $loop->iteration }}</td>

                        <td>
                            <div class="fw-semibold">{{ $unite->name }}</div>
                            <div class="text-muted small">{{ Str::limit($unite->description, 40) }}</div>
                        </td>

                        <td>
                            @php
                                $typeColor = match($unite->type) {
                                    'stadium' => 'primary',
                                    'hall'    => 'info',
                                    'lounge'  => 'purple',
                                    'camp'    => 'warning',
                                    default   => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $typeColor === 'purple' ? 'secondary' : $typeColor }} bg-opacity-10
                                text-{{ $typeColor === 'purple' ? 'secondary' : $typeColor }} border
                                border-{{ $typeColor === 'purple' ? 'secondary' : $typeColor }} border-opacity-25">
                                {{ ucfirst($unite->type) }}
                            </span>
                        </td>

                        <td class="small">{{ $unite->department->name ?? '—' }}</td>

                        <td>
                            <span class="badge {{ $unite->city ? 'bg-info' : 'bg-secondary' }}">
                                {{ $unite->city ?? '—' }}
                            </span>
                        </td>
                        <td>
                            @if($unite->status === 'active')
                                <span class="badge bg-success">{{ __('lang.active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('lang.inactive') }}</span>
                            @endif
                        </td>

                        <td>
                            @if($unite->requires_approval)
                                <span class="badge bg-warning text-dark" title="Provider must approve each booking">
                                    Approval
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        <td>
                            @if($unite->images->count())
                                <div class="d-flex align-items-center gap-1">
                                    @foreach($unite->images->take(2) as $img)
                                        <img src="{{ asset($img->image) }}"
                                             width="32" height="32"
                                             class="rounded"
                                             style="object-fit:cover"
                                             onerror="this.style.display='none'">
                                    @endforeach
                                    @if($unite->images->count() > 2)
                                        <span class="badge bg-light text-dark border small">+{{ $unite->images->count()-2 }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if($unite->features->count())
                                <span class="badge bg-secondary">{{ $unite->features->count() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if($activeOffers)
                                <span class="badge bg-warning text-dark">{{ $activeOffers }} active</span>
                            @elseif($unite->offers->count())
                                <span class="badge bg-light text-dark border">{{ $unite->offers->count() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if($unite->reservations->count())
                                @if($confirmed) <span class="badge bg-success me-1">{{ $confirmed }} ✓</span> @endif
                                @if($pending)   <span class="badge bg-warning text-dark">{{ $pending }} ⏳</span> @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <a href="{{ route('unites.show', $unite->id) }}"
                               class="btn btn-sm btn-outline-secondary">{{ __('lang.view') }}</a>
                            <a href="{{ route('unites.edit', $unite->id) }}"
                               class="btn btn-sm btn-outline-primary">{{ __('lang.edit') }}</a>
                            <form action="{{ route('unites.destroy', $unite->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('{{ __('lang.delete_confirm_unite', ['name' => $unite->name]) }}')">{{ __('lang.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">{{ __('lang.no_unites_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
