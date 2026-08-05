@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Ad Package Details</h2>

        <div class="card">
            <div class="card-body">
                <p><strong>Name:</strong> {{ $adPackage->name }}</p>
                <p><strong>Description:</strong> {{ $adPackage->description }}</p>
                <p><strong>Type:</strong> {{ $adPackage->type }}</p>
                <p><strong>Count:</strong> {{ $adPackage->count }}</p>
                <p><strong>Duration:</strong> {{ $adPackage->duration }}</p>
                <p><strong>Price:</strong> {{ $adPackage->price }}</p>
                <p><strong>Status:</strong> {{ $adPackage->status }}</p>
                @if ($adPackage->image)
                    <p><strong>Image:</strong><br><img src="{{ asset( $adPackage->image) }}" width="150"></p>
                @endif
            </div>
        </div>

        <a href="{{ route('ad-packages.index') }}" class="btn btn-secondary mt-3">Back</a>
    </div>
@endsection


