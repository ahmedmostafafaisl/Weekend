@extends('dashboard.master')

@section('title', __('lang.company_name'))
@section('content')
    <div class="container">
        <h2>Edit Property Package</h2>
        @include('dashboard.web.property_packages.partials.form', [
            'route' => route('property-packages.update', $propertyPackage->id),
            'method' => 'PUT',
            'propertyPackage' => $propertyPackage
        ])
            </div>
@endsection
