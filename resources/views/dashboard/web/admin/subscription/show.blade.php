@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>Subscription Details</h2>
        <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary mb-3">Back</a>

        <table class="table table-bordered">
            <tr>
                <th>User</th>
                <td>{{ $subscription->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Type</th>
                <td>{{ $subscription->type }}</td>
            </tr>
            <tr>
                <th>Package</th>
                <td>{{ $subscription->package->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td>{{ $subscription->amount }}</td>
            </tr>
            <tr>
                <th>Start Date</th>
                <td>{{ $subscription->start_date }}</td>
            </tr>
            <tr>
                <th>End Date</th>
                <td>{{ $subscription->end_date }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $subscription->status }}</td>
            </tr>
        </table>
    </div>
@endsection
