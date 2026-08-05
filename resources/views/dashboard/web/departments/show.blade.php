@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Department Details</h2>
        <p><strong>Name:</strong> {{ $department->name }}</p>
        <p><strong>Description:</strong> {{ $department->description }}</p>
        <p><strong>Type:</strong> {{ $department->type }}</p>
        <p><strong>Status:</strong> {{ $department->status }}</p>
        <p><strong>Location:</strong> {{ $department->location }}</p>
        <p><strong>Latitude:</strong> {{ $department->latitude }}</p>
        <p><strong>Longitude:</strong> {{ $department->longitude }}</p>
        <p><strong>User:</strong> {{ $department->user?->name }}</p>

        <div class="row">
            @foreach($department->images as $image)
                <div>
                    <img src="{{ asset( $image->image) }}" alt="Department Image" class="img-thumbnail"
                        style="max-height: 150px;">
                </div>
            @endforeach
        </div>

        <a href="{{ route('departments.index') }}" class="btn btn-secondary mt-3">Back</a>
    </div>
@endsection
