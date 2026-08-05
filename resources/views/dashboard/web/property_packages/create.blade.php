
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Create Property Package</h2>
        @include('dashboard.web.property_packages.partials.form', ['route' => route('property-packages.store'), 'method' => 'POST'])
    </div>
@endsection
