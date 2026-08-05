@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Edit Ad</h2>
        <form action="{{ route('ads.update', $ad->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('dashboard.web.ads.partials.form', ['button' => 'Update'])
        </form>
    </div>
@endsection
