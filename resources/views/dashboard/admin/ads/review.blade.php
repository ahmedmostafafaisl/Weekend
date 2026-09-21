@extends('dashboard.admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">{{ __('lang.review_ad') }}</h4>
        <a href="{{ route('admin.ads.pending') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Thumbnail --}}
            @if($ad->thumbnail)
            <div class="card card-soft shadow-sm mb-3">
                <div class="card-body">
                    <div class="small fw-semibold text-muted mb-2">{{ __('lang.thumbnail') }}</div>
                    <img src="{{ asset($ad->thumbnail) }}"
                         alt="Thumbnail"
                         class="rounded w-100"
                         style="max-height:320px;object-fit:cover"
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
                $mediaItems = array_values(array_filter($mediaItems));
            @endphp
            @if(count($mediaItems))
            <div class="card card-soft shadow-sm mb-3">
                <div class="card-body">
                    <div class="small fw-semibold text-muted mb-2">
                        {{ __('lang.media') }}
                        <span class="badge bg-secondary ms-1">{{ count($mediaItems) }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($mediaItems as $m)
                            @php $ext = strtolower(pathinfo($m, PATHINFO_EXTENSION)); @endphp
                            @if(in_array($ext, ['mp4','mov','webm','avi','mkv']))
                                <video controls
                                       style="height:160px;width:240px;object-fit:cover;border-radius:8px"
                                       class="border">
                                    <source src="{{ asset($m) }}">
                                </video>
                            @else
                                <a href="{{ asset($m) }}" target="_blank">
                                    <img src="{{ asset($m) }}"
                                         alt="Media"
                                         style="height:160px;width:200px;object-fit:cover;border-radius:8px"
                                         class="border"
                                         onerror="this.style.display='none'">
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Ad details --}}
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ $ad->title }}</h6>

                    @if($ad->description)
                        <p class="text-muted small">{{ $ad->description }}</p>
                    @endif

                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted small">{{ __('lang.submitted_by') }}</td>
                            <td>{{ $ad->user?->name ?? '—' }}
                                @if($ad->user)
                                    <span class="text-muted small">({{ $ad->user->email }})</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">{{ __('lang.type') }}</td>
                            <td>{{ $ad->type }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">{{ __('lang.city') }}</td>
                            <td>{{ $ad->city ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">{{ __('lang.th_audience') }}</td>
                            <td>{{ __('lang.'.($ad->target_audience ?? 'both')) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">{{ __('lang.created_at') }}</td>
                            <td>{{ $ad->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.ads.approve', $ad->id) }}" method="POST"
                          onsubmit="return confirm('{{ __('lang.approve_confirm') }}')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 mb-3">✓ {{ __('lang.approve') }}</button>
                    </form>

                    <hr>

                    <form action="{{ route('admin.ads.reject', $ad->id) }}" method="POST">
                        @csrf
                        <label class="form-label small">{{ __('lang.rejection_note') }}</label>
                        <textarea class="form-control mb-2" name="note" rows="3" required
                                  placeholder="{{ __('lang.rejection_note_required') }}"></textarea>
                        @error('note')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-outline-danger w-100">✕ {{ __('lang.reject') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
