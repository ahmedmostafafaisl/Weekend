@extends('dashboard.master')

@section('title', __('lang.company_name'))


@section('content')
    <div class="container">
        <h4>Edit Department</h4>
        <form action="{{ route('departments.update', $department) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('dashboard.web.departments.partials.form', ['button' => 'Update'])
        </form>
    </div>
@endsection
