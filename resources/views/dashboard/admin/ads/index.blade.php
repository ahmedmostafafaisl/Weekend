@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Ads')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.ads') }}</h4>
        <div class="text-muted small">{{ __('lang.ads_subtitle') }}</div>
    </div>
    <div class="d-flex gap-2">
        @can('ads.review')
            <a href="{{ route('admin.ads.pending') }}" class="btn btn-outline-warning">
                {{ __('lang.pending_ads') }}
                @php($pendingCount = \App\Models\Ad::where('approval_status', 'pending')->count())
                @if($pendingCount)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
        @endcan
        <a href="{{ route('admin.ads.create') }}" class="btn btn-accent">{{ __('lang.new_ad') }}</a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="{{ __('lang.title_user_city_placeholder') }}" value="{{ request('search') }}">
    </div>
    <div class="col-md-2">
        <select name="target_audience" class="form-select form-select-sm">
            <option value="">{{ __('lang.any_audience') }}</option>
            @foreach(['both'=>__('lang.everyone'),'men'=>__('lang.men_only'),'women'=>__('lang.women_only')] as $v=>$l)
                <option value="{{ $v }}" {{ request('target_audience')===$v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="target_user_type" class="form-select form-select-sm">
            <option value="">{{ __('lang.any_user_type') }}</option>
            @foreach(['all'=>__('lang.all_users'),'customers'=>__('lang.customers'),'providers'=>__('lang.providers')] as $v=>$l)
                <option value="{{ $v }}" {{ request('target_user_type')===$v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('lang.any_status') }}</option>
            <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
            <option value="inactive" {{ request('status')==='inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('lang.filter') }}</button>
        <a href="{{ route('admin.ads.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('lang.clear') }}</a>
    </div>
</form>

<div class="card card-soft shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('lang.th_ad') }}</th>
                        <th>{{ __('lang.owner') }}</th>
                        <th>{{ __('lang.city') }}</th>
                        <th>{{ __('lang.audience') }}</th>
                        <th>{{ __('lang.show_to') }}</th>
                        <th>{{ __('lang.th_status') }}</th>
                        <th>{{ __('lang.expires') }}</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($ads as $ad)
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold small">{{ $ad->title }}</div>
                        @if($ad->description)
                            <div class="text-muted" style="font-size:11px;max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{{ $ad->description }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="small">{{ $ad->user?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:10px">{{ $ad->user?->type ? __('lang.'.$ad->user->type) : '' }}</div>
                    </td>
                    <td class="small">{{ $ad->city ?? '—' }}</td>
                    <td>
                        @php $audIcon = ['both'=>'👥','men'=>'👨','women'=>'👩'][$ad->target_audience ?? 'both']; @endphp
                        <span class="badge bg-light text-dark border" style="font-size:10px">
                            {{ $audIcon }} {{ __('lang.'.($ad->target_audience ?? 'both')) }}
                        </span>
                    </td>
                    <td>
                        @php $typeColor = ['all'=>'secondary','customers'=>'primary','providers'=>'success'][$ad->target_user_type ?? 'all']; @endphp
                        <span class="badge bg-{{ $typeColor }}" style="font-size:10px">
                            {{ ($ad->target_user_type ?? 'all') === 'all' ? __('lang.all_users') : __('lang.'.($ad->target_user_type ?? 'all')) }}
                        </span>
                    </td>
                    <td>
                        @if($ad->is_active && $ad->expires_at?->isFuture())
                            <span class="badge bg-success">{{ __('lang.active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('lang.inactive') }}</span>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $ad->expires_at?->format('d M Y H:i') ?? '—' }}
                    </td>
                  <td class="pe-3">
    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.ads.show', $ad) }}"
           class="btn btn-sm btn-outline-info">
            <i class="fas fa-eye me-1"></i> {{ __('lang.view') }}
        </a>

        <a href="{{ route('admin.ads.edit', $ad) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit me-1"></i> {{ __('lang.edit') }}
        </a>

        <form action="{{ route('admin.ads.destroy', $ad) }}"
              method="POST"
              onsubmit="return confirm('{{ __('lang.delete_confirm_ad') }}')"
              class="d-inline">
            @csrf
            @method('DELETE')

            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash me-1"></i> {{ __('lang.delete') }}
            </button>
        </form>
    </div>
</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('lang.no_ads_found') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $ads->links() }}</div>

@endsection
