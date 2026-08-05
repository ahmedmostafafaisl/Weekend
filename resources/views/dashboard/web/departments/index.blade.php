
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Backup Servers</h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <a href="{{ route('departments.create') }}" class="btn btn-primary">Add Department</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>User</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $department)
                    <tr>
                        <td>{{ $department->name }}</td>
                        <td>{{ $department->type }}</td>
                        <td>{{ $department->status }}</td>
                        <td>{{ $department->user?->name }}</td>
                        <td>
                            @foreach ($department->images as $image)
                                <img src="{{ asset($image->image) }}" width="60" height="60">
                            @endforeach
                        </td>
                        <td>
                            <a href="{{ route('departments.show', $department) }}" class="btn btn-info btn-sm">{{ __('lang.view') }}</a>
                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-warning btn-sm">{{ __('lang.edit') }}</a>
                            <form action="{{ route('departments.destroy', $department) }}" method="POST"
                                style="display:inline-block;" onsubmit="return confirm('{{ __('lang.delete_confirm_department', ['name' => $department->name]) }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">{{ __('lang.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
    </div>
@endsection
