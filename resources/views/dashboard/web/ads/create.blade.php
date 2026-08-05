
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Create Ad</h2>
        <form action="{{ route('ads.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('dashboard.web.ads.partials.form', ['button' => 'Create'])
        </form>
    </div>
@endsection


