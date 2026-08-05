@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')

    <div class="container">
        <h2>Edit Ad Package - {{ $adPackage->name }}</h2>

        <form action="{{ route('ad-packages.update', $adPackage->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('dashboard.web.ad_packages.partials.form', ['button' => 'Update Ad Package'])
        </form>
    </div>
@endsection
