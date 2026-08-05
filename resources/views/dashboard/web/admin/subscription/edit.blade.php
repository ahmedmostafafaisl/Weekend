@extends('dashboard.master')

@section('title', __('lang.company_name'))


@section('content')
    <div class="container">
        <h2>Edit Subscription</h2>
            <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary mb-3">Back</a>
        <form action="{{ route('subscriptions.update', $subscription) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('dashboard.web.admin.subscription.partials.form', ['button' => 'Update'])
        </form>
    </div>
@endsection
