
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4> Ads </h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <a href="{{ route('ads.create') }}" class="btn btn-primary">Add New</a>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Media</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ads as $ad)
                    <tr>
                        <td>{{ $ad->title }}</td>
                        <td>{{ $ad->description }}</td>
                        <td>
                            @foreach($ad->media ?? [] as $media)
                                <img src="{{ asset( $media->media) }}" alt="Media" width="80" class="me-2 mb-2">
                            @endforeach
                        </td>
                        <td>
                            <a href="{{ route('ads.edit', $ad->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('ads.destroy', $ad->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this ad?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            <a href="{{ route('ads.show', $ad->id) }}" class="btn btn-info btn-sm">Show</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


    </div>
@endsection
