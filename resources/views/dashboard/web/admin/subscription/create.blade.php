
@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Create Subscription</h2>
            <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary mb-3">Back</a>

        <form action="{{ route('subscriptions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('dashboard.web.admin.subscription.partials.form', ['button' => 'Create'])
        </form>
    </div>
@endsection
