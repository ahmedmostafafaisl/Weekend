@extends('dashboard.admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">{{ __('lang.ads_pending_review') }}</h4>
        <span class="badge bg-warning text-dark">{{ $ads->count() }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card card-soft shadow-sm">
        <div class="card-body">
            @if($ads->isEmpty())
                <div class="text-muted text-center py-4">{{ __('lang.no_ads_pending_review') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lang.title') }}</th>
                                <th>{{ __('lang.submitted_by') }}</th>
                                <th>{{ __('lang.created_at') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ads as $ad)
                                <tr>
                                    <td class="fw-semibold">{{ $ad->title }}</td>
                                    <td>{{ $ad->user?->name ?? '—' }}</td>
                                    <td class="text-muted small">{{ $ad->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.ads.review', $ad->id) }}" class="btn btn-sm btn-outline-primary">
                                            {{ __('lang.review_ad') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
