@extends('dashboard.master')

@section('title', __('lang.company_name'))


@section('content')
    <div class="container">
        <h2>Property Package Details</h2>

        <div class="card">
            <div class="card-header">
                <strong>{{ $propertyPackage->name }}</strong>
            </div>
            <div class="card-body">
                <p><strong>Description:</strong> {{ $propertyPackage->description ?? 'N/A' }}</p>
                <p><strong>Type:</strong> {{ ucfirst($propertyPackage->type) }}</p>

                @if($propertyPackage->type === 'time')
                    <p><strong>Duration (days):</strong> {{ $propertyPackage->duration ?? 'N/A' }}</p>
                @elseif($propertyPackage->type === 'percentage')
                    <p><strong>Percentage:</strong> {{ $propertyPackage->percentage ?? 'N/A' }}%</p>
                @endif

                <p><strong>Price:</strong> {{ $propertyPackage->price ?? 'Free' }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $propertyPackage->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($propertyPackage->status) }}
                    </span>
                </p>

                @if($propertyPackage->image)
                    <p><strong>Image:</strong><br>
                        <img src="{{ asset('storage/' . $propertyPackage->image) }}" alt="Package Image" width="150">
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('property-packages.edit', $propertyPackage->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('property-packages.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@endsection
