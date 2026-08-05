@extends('dashboard.admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">{{ __('lang.review_ad') }}</h4>
        <a href="{{ route('admin.ads.pending') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-soft shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ $ad->title }}</h6>

                    @if($ad->thumbnail)
                        <img src="{{ asset($ad->thumbnail) }}" class="img-fluid rounded mb-3" style="max-height:280px;object-fit:cover" alt="">
                    @endif

                    <p class="text-muted">{{ $ad->description }}</p>

                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted small">{{ __('lang.submitted_by') }}</td>
                            <td>{{ $ad->user?->name ?? '—' }} @if($ad->user)<span class="text-muted small">({{ $ad->user->email }})</span>@endif</td>
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
