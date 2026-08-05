@php
    $me = auth('admin')->user();
    $isProvider = $user->type === 'provider';

    $storageUrl = fn($path) => $path ? asset('storage/'.$path) : null;

    $isImage = function($path){
        if(!$path) return false;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','webp','gif']);
    };
@endphp


@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | '.__('lang.user_details'))

@section('content')
@php
    $me = auth('admin')->user();
    $isProvider = $user->type === 'provider';
    $file = fn($path) => $path ? asset('storage/'.$path) : null;
@endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.user_details') }}</h4>
        <div class="text-muted">#{{ $user->id }} • {{ $user->email }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('lang.back') }}</a>

          @if($me && $me->can('users.update'))
             <a href="{{ route('admin.users.index', ['edit_id' => $user->id]) }}" class="btn btn-outline-primary">
                {{ __('lang.edit') }}
             </a>
          @endif

    </div>
</div>

<div class="row g-3">

    {{-- Left: Profile --}}
    <div class="col-lg-4">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center"
                         style="width:64px;height:64px;overflow:hidden;">
                        @if($user->photo)
                            <img src="{{ asset('storage/'.$user->photo) }}" alt="photo" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <span class="fw-bold text-muted">U</span>
                        @endif
                    </div>

                    <div>
                        <div class="fw-bold fs-5">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                        @if($user->phone)
                            <div class="text-muted small">{{ $user->phone }}</div>
                        @endif
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ __('lang.'.$user->status) }}
                    </span>
                    <span class="badge bg-light text-dark border">
                        {{ $user->type }} @if($user->provider_type) • {{ $user->provider_type }} @endif
                    </span>
                    <span class="badge bg-light text-dark border">{{ $user->nation }}</span>
                </div>

                <hr>

                <div class="small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('lang.email_verified') }}</span>
                        <span class="fw-semibold">
                            {{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d') : '—' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.created_at') }}</span>
                        <span class="fw-semibold">{{ optional($user->created_at)->format('Y-m-d') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">{{ __('lang.updated_at') }}</span>
                        <span class="fw-semibold">{{ optional($user->updated_at)->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Details --}}
    <div class="col-lg-8">
        <div class="card card-soft shadow-sm">
            <div class="card-body">

                <ul class="nav nav-pills gap-2 mb-3" id="userTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">
                            {{ __('lang.general') }}
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProvider" type="button"
                                @if(!$isProvider) disabled @endif>
                            {{ __('lang.provider') }}
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFiles" type="button">
                            {{ __('lang.files') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- General --}}
                    <div class="tab-pane fade show active" id="tabGeneral">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.id_number') }}</div>
                                <div class="fw-semibold">{{ $user->id_number ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('lang.birth_date') }}</div>
                                <div class="fw-semibold">{{ $user->birth_date ? $user->birth_date->format('Y-m-d') : '—' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Provider --}}
                    <div class="tab-pane fade" id="tabProvider">
                        @if(!$isProvider)
                            <div class="text-muted">{{ __('lang.not_a_provider') }}</div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ __('lang.ownership') }}</div>
                                    <div class="fw-semibold">{{ $user->ownership ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ __('lang.delegation') }}</div>
                                    <div class="fw-semibold">{{ $user->delegation ?? '—' }}</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-muted small">{{ __('lang.commercial_register_number') }}</div>
                                    <div class="fw-semibold">{{ $user->commercial_register_number ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ __('lang.organization_name') }}</div>
                                    <div class="fw-semibold">{{ $user->organization_name ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ __('lang.commercial_name') }}</div>
                                    <div class="fw-semibold">{{ $user->commercial_name ?? '—' }}</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Files --}}
                   <div class="tab-pane fade" id="tabFiles">
    <div class="row g-3">

        {{-- Photo --}}
        <div class="col-md-6">
            <div class="text-muted small mb-1">{{ __('lang.photo') }}</div>
            @if($user->photo)
                <div class="border rounded-3 p-2 bg-white">
                    @if($isImage($user->photo))
                        <img src="{{ $storageUrl($user->photo) }}" class="img-fluid rounded-3" style="max-height:220px;object-fit:cover;">
                    @endif

                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ $storageUrl($user->photo) }}">{{ __('lang.open') }}</a>
                    </div>
                </div>
            @else
                <div class="text-muted">—</div>
            @endif
        </div>

        {{-- Front Identity --}}
        <div class="col-md-6">
            <div class="text-muted small mb-1">{{ __('lang.front_identity') }}</div>
            @if($user->front_identity)
                <div class="border rounded-3 p-2 bg-white">
                    @if($isImage($user->front_identity))
                        <img src="{{ $storageUrl($user->front_identity) }}" class="img-fluid rounded-3" style="max-height:220px;object-fit:cover;">
                    @endif

                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ $storageUrl($user->front_identity) }}">{{ __('lang.open') }}</a>
                    </div>
                </div>
            @else
                <div class="text-muted">—</div>
            @endif
        </div>

        {{-- Back Identity --}}
        <div class="col-md-6">
            <div class="text-muted small mb-1">{{ __('lang.back_identity') }}</div>
            @if($user->back_identity)
                <div class="border rounded-3 p-2 bg-white">
                    @if($isImage($user->back_identity))
                        <img src="{{ $storageUrl($user->back_identity) }}" class="img-fluid rounded-3" style="max-height:220px;object-fit:cover;">
                    @endif

                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ $storageUrl($user->back_identity) }}">{{ __('lang.open') }}</a>
                    </div>
                </div>
            @else
                <div class="text-muted">—</div>
            @endif
        </div>

        {{-- Sak Image --}}
        <div class="col-md-6">
            <div class="text-muted small mb-1">{{ __('lang.sak_image') }}</div>
            @if($user->sak_image)
                <div class="border rounded-3 p-2 bg-white">
                    @if($isImage($user->sak_image))
                        <img src="{{ $storageUrl($user->sak_image) }}" class="img-fluid rounded-3" style="max-height:220px;object-fit:cover;">
                    @endif

                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ $storageUrl($user->sak_image) }}">{{ __('lang.open') }}</a>
                    </div>
                </div>
            @else
                <div class="text-muted">—</div>
            @endif
        </div>

        {{-- Commercial Register Image --}}
        <div class="col-md-6">
            <div class="text-muted small mb-1">{{ __('lang.commercial_register_image') }}</div>
            @if($user->commercial_register_image)
                <div class="border rounded-3 p-2 bg-white">
                    @if($isImage($user->commercial_register_image))
                        <img src="{{ $storageUrl($user->commercial_register_image) }}" class="img-fluid rounded-3" style="max-height:220px;object-fit:cover;">
                    @endif

                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ $storageUrl($user->commercial_register_image) }}">{{ __('lang.open') }}</a>
                    </div>
                </div>
            @else
                <div class="text-muted">—</div>
            @endif
        </div>

    </div>
</div>


                </div> {{-- tab content --}}

            </div>
        </div>
    </div>

</div>

@endsection
