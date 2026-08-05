@extends('dashboard.master')

@section('title', __('lang.company_name'))



@section('content')
        <div class="container">
    <h2>Offer Details</h2>

    <ul>
        <li>Unite: {{ $offer->unite_id }}</li>
        <li>Start: {{ $offer->start }}</li>
        <li>End: {{ $offer->end }}</li>
        <li>Morning Price: {{ $offer->morning_price }}</li>
        <li>Evening Price: {{ $offer->evening_price }}</li>
        <li>Full Day Price: {{ $offer->full_day_price }}</li>
        <li>Status: {{ $offer->status }}</li>
    </ul>


            <a href="{{ route('unite_offers.index') }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('unite_offers.edit', $offer->id) }}" class="btn btn-primary">Edit</a>

        </div>
@endsection
