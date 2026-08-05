
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Add New Ad Package</h2>

        <form action="{{ route('ad-packages.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('dashboard.web.ad_packages.partials.form', ['button' => 'Create Ad Package'])
        </form>
    </div>
@endsection
