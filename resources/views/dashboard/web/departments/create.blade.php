
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h4>Create Department</h4>
        <form action="{{ route('departments.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('dashboard.web.departments.partials.form', ['button' => 'Create'])
        </form>
    </div>
@endsection
