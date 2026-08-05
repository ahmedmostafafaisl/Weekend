@extends('dashboard.master')

@section('title', __('lang.company_name'))


@section('content')
    <div class="container">
        <h3>User Details</h3>
        <ul class="list-group">
            <li class="list-group-item"><strong>Name:</strong> {{ $user->name }}</li>
            <li class="list-group-item"><strong>Email:</strong> {{ $user->email }}</li>
            <li class="list-group-item"><strong>Phone:</strong> {{ $user->phone }}</li>
            <li class="list-group-item"><strong>Status:</strong> {{ ucfirst($user->status) }}</li>
        </ul>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mt-3">Back</a>
    </div>
@endsection



