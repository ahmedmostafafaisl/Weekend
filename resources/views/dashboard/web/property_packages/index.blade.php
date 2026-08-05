
@extends('dashboard.master')

@section('title', __('lang.company_name'))
@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Property Packages</h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <a href="{{ route('property-packages.create') }}" class="btn btn-primary">Add New Package</a>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($propertyPackages as $package)
                    <tr>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->type }}</td>
                        <td>{{ $package->price }}</td>
                        <td>{{ $package->status }}</td>
                        <td>
                            @if($package->image)
                                <img src="{{ asset( $package->image) }}" height="50">
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('property-packages.edit', $package->id) }}" class="btn btn-primary btn-sm">Edit</a>

                            <form action="{{ route('property-packages.destroy', $package->id) }}" method="POST"
                                style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Are you sure?')" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
@endsection
