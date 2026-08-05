@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.department_details'))

@section('content')
@php $me = auth('admin')->user(); @endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.department_details') }}</h4>
        <div class="text-muted">#{{ $department->id }} • {{ $department->name }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

        <button type="button"
                class="btn btn-outline-dark"
                id="showUnitsBtn">
            {{ __('lang.show_unites') }}
        </button>

        @if($me && $me->can('departments.update'))
            <a href="{{ route('admin.departments.index', ['edit_id' => $department->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
            </a>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="fw-bold fs-5">{{ $department->name }}</div>
                <div class="text-muted small">{{ $department->description ?: '—' }}</div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark border">{{ $department->type }}</span>
                    <span class="badge {{ $department->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ __('lang.'.$department->status) }}
                    </span>
                </div>

                <hr>

                <div class="small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('lang.provider') }}</span>
                        <span class="fw-semibold">{{ $department->user?->name ?? '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.provider_email') }}</span>
                        <span class="fw-semibold">{{ $department->user?->email ?? '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.location') }}</span>
                        <span class="fw-semibold">{{ $department->location ?: '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.latitude') }}</span>
                        <span class="fw-semibold">{{ $department->latitude ?: '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.longitude') }}</span>
                        <span class="fw-semibold">{{ $department->longitude ?: '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.created_at') }}</span>
                        <span class="fw-semibold">{{ optional($department->created_at)->format('Y-m-d') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.updated_at') }}</span>
                        <span class="fw-semibold">{{ optional($department->updated_at)->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <ul class="nav nav-pills gap-2 mb-3" id="departmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="tab-general-button"
                                data-bs-toggle="tab"
                                data-bs-target="#tabGeneral"
                                type="button"
                                role="tab">
                            {{ __('lang.general') }}
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="tab-social-button"
                                data-bs-toggle="tab"
                                data-bs-target="#tabSocial"
                                type="button"
                                role="tab">
                            {{ __('lang.social') }}
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="tab-units-button"
                                data-bs-toggle="tab"
                                data-bs-target="#tabUnits"
                                type="button"
                                role="tab">
                            {{ __('lang.units') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabGeneral" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.name') }}</div>
                                <div class="fw-semibold">{{ $department->name }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.type') }}</div>
                                <div class="fw-semibold">{{ $department->type }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.status') }}</div>
                                <div class="fw-semibold">{{ __('lang.'.$department->status) }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.provider') }}</div>
                                <div class="fw-semibold">{{ $department->user?->name ?? '—' }}</div>
                            </div>

                            <div class="col-md-12">
                                <div class="text-muted small">{{ __('lang.description') }}</div>
                                <div class="fw-semibold">{{ $department->description ?: '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabSocial" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.facebook') }}</div>
                                <div class="fw-semibold">{{ $department->facebook ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.twitter') }}</div>
                                <div class="fw-semibold">{{ $department->twitter ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.instagram') }}</div>
                                <div class="fw-semibold">{{ $department->instagram ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.youtube') }}</div>
                                <div class="fw-semibold">{{ $department->youtube ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.website') }}</div>
                                <div class="fw-semibold">{{ $department->website ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.whatsapp') }}</div>
                                <div class="fw-semibold">{{ $department->whatsapp ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.snapchat') }}</div>
                                <div class="fw-semibold">{{ $department->snapchat ?: '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.tiktok') }}</div>
                                <div class="fw-semibold">{{ $department->tiktok ?: '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabUnits" role="tabpanel">
                        @if($unites->isEmpty())
                            <div class="text-muted">{{ __('lang.no_units_found') }}</div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ __('lang.th_hash') }}</th>
                                        <th>{{ __('lang.th_name') }}</th>
                                        <th>{{ __('lang.th_type') }}</th>
                                        <th>{{ __('lang.th_status') }}</th>
                                        <th>{{ __('lang.th_location') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($unites as $unit)
                                        <tr>
                                            <td>{{ $unit->id }}</td>
                                            <td>{{ $unit->name ?: '—' }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $unit->type }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $unit->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ __('lang.'.$unit->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $unit->location_name ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $unites->links('vendor.pagination.weekend') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
document.getElementById('showUnitsBtn')?.addEventListener('click', function () {
    const trigger = document.getElementById('tab-units-button');
    if (trigger) {
        const tab = new bootstrap.Tab(trigger);
        tab.show();
    }
});
</script>
@endpush

@endsection
