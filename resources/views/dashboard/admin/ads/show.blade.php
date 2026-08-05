@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Ad — ' . $ad->title)
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.ads.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <h4 class="fw-bold mb-0">{{ $ad->title }}</h4>
    @if($ad->is_active && $ad->expires_at?->isFuture())
        <span class="badge bg-success">{{ __('lang.active') }}</span>
    @else
        <span class="badge bg-secondary">{{ __('lang.inactive') }}</span>
    @endif
</div>

<div class="row g-4">
    <div class="col-md-5">
        {{-- Thumbnail --}}
        @if($ad->thumbnail)
        <div class="card card-soft shadow-sm mb-3">
            <div class="card-body">
                <div class="small fw-semibold text-muted mb-2">{{ __('lang.thumbnail') }}</div>
                <img src="{{ asset($ad->thumbnail) }}" alt="Thumbnail"
                     style="max-width:100%;border-radius:8px;object-fit:cover"
                     onerror="this.style.display='none'">
            </div>
        </div>
        @endif

        {{-- Media --}}
        @php
            $mediaItems = [];
            $raw = $ad->media ?? null;
            if (is_array($raw)) {
                $mediaItems = $raw;
            } elseif (is_string($raw) && str_starts_with(trim($raw), '[')) {
                $mediaItems = json_decode($raw, true) ?: [$raw];
            } elseif (!empty($raw)) {
                $mediaItems = [$raw];
            }
        @endphp
        @if(count(array_filter($mediaItems)))
        <div class="card card-soft shadow-sm mb-3">
            <div class="card-body">
                <div class="small fw-semibold text-muted mb-2">{{ __('lang.media') }}</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(array_filter($mediaItems) as $m)
                    <img src="{{ asset($m) }}" alt="Media"
                         style="height:120px;width:150px;object-fit:cover;border-radius:8px"
                         class="border" onerror="this.style.display='none'">
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-7">
        <div class="card card-soft shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.details') }}</h6>
                <table class="table table-sm table-borderless mb-0 small">
                    <tr><td class="text-muted" style="width:130px">{{ __('lang.owner') }}</td><td>{{ $ad->user?->name }} <span class="text-muted">({{ $ad->user?->type ? __('lang.'.$ad->user->type) : '' }})</span></td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_email') }}</td><td>{{ $ad->user?->email }}</td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_city') }}</td><td>{{ $ad->city ?? __('lang.all_cities') }}</td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_audience') }}</td><td>{{ __('lang.'.($ad->target_audience ?? 'both')) }}</td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_show_to') }}</td><td>{{ ($ad->target_user_type ?? 'all') === 'all' ? __('lang.all_users') : __('lang.'.($ad->target_user_type ?? 'all')) }}</td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_expires') }}</td><td>{{ $ad->expires_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_views') }}</td><td>{{ $ad->views()->count() }}</td></tr>
                    <tr><td class="text-muted">{{ __('lang.th_comments') }}</td><td>{{ $ad->comments()->count() }} {{ __('lang.total_word') }} · {{ $ad->visibleComments()->count() }} {{ __('lang.visible_word') }}</td></tr>
                </table>
            </div>
        </div>

        @if($ad->description)
        <div class="card card-soft shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2">{{ __('lang.description') }}</h6>
                <p class="small text-muted mb-0">{{ $ad->description }}</p>
            </div>
        </div>
        @endif

        <div class="d-flex gap-2">
            <a href="{{ route('admin.ads.edit', $ad) }}" class="btn btn-accent btn-sm">{{ __('lang.edit') }}</a>
            <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('{{ __('lang.delete_confirm_ad') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">{{ __('lang.delete') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
