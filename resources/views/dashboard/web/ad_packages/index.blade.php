
@extends('dashboard.master')

@section('title', __('lang.company_name'))


@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4> Ad Packages</h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <a href="{{ route('ad-packages.create') }}" class="btn btn-primary">Add New Package</a>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adPackages as $package)
                    <tr>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->type }}</td>
                        <td>{{ $package->count }}</td>
                        <td>{{ $package->duration }}</td>
                        <td>{{ $package->price }}</td>
                        <td>{{ ucfirst($package->status) }}</td>
                        <td>
                            @if ($package->image)
                                <img src="{{ asset( $package->image) }}" width="50">
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('ad-packages.show', $package->id) }}" class="btn btn-sm btn-info">View</a>
                            <a href="{{ route('ad-packages.edit', $package->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('ad-packages.destroy', $package->id) }}" method="POST"
                                style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Delete this package?')"
                                    class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
    </div>

@endsection

