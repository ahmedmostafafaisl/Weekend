@extends('dashboard.master')

@section('title', __('lang.company_name'))
@section('content')
    <div class="container">
        <h2>Edit Unite Offer</h2>


        <form action="{{ route('unite_offers.update', $offer->id) }}" method="POST">
            @csrf @method('PUT')
            @include('dashboard.web.unites.offers.partials.form')
            <button type="submit" class="btn btn-success">Update</button>
        </form>
    </div>
@endsection
