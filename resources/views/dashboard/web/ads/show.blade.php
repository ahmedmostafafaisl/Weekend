@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Ad Details</h2>
        <p><strong>Title:</strong> {{ $ad->title }}</p>
        <p><strong>Description:</strong> {{ $ad->description }}</p>
        <p><strong>Media:</strong></p>
        @foreach($ad->media as $media)
            <img src="{{ asset( $media->media) }}" alt="Media" width="150" class="mb-2 me-2">
        @endforeach
        <a href="{{ route('ads.index') }}" class="btn btn-secondary mt-3">Back</a>
    </div>
@endsection

