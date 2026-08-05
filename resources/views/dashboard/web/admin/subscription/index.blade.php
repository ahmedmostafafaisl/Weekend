@extends('dashboard.master')

@section('title', __('lang.company_name'))

@section('content')
    <div class="container">
        <h2>{{ __('lang.subscriptions') }}</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <a href="{{ route('subscriptions.create') }}" class="btn btn-primary mb-3">{{ __('lang.create_subscription') }}</a>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('lang.user') }}</th>
                    <th>{{ __('lang.th_type') }}</th>
                    <th>{{ __('lang.package_id') }}</th>
                    <th>{{ __('lang.th_amount') }}</th>
                    <th>{{ __('lang.start_date') }}</th>
                    <th>{{ __('lang.end_date') }}</th>
                    <th>{{ __('lang.percentage') }}</th>
                    <th>{{ __('lang.count') }}</th>
                    <th>{{ __('lang.th_status') }}</th>
                    <th>{{ __('lang.th_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subscriptions as $subscription)
                    <tr>
                        <td>{{ $subscription->id }}</td>
                        <td>{{ $subscription->user->name ?? 'N/A' }}</td>
                        <td>{{ $subscription->type }}</td>
                        <td>{{ $subscription->package_id }}</td>
                        <td>{{ $subscription->amount }}</td>
                        <td>{{ $subscription->start_date ?? '-'}}</td>
                        <td>{{ $subscription->end_date ?? '-'}}</td>
                        <td>{{ $subscription->percentage ?? '-'}}</td>
                        <td>{{ $subscription->count ?? '-'}}</td>
                        <td>{{ $subscription->status }}</td>
                        <td>
                            <a href="{{ route('subscriptions.show', $subscription->id) }}" class="btn btn-sm btn-info">Show</a>
                            <a href="{{ route('subscriptions.edit', $subscription->id) }}"
                                class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('subscriptions.destroy', $subscription->id) }}" method="POST"
                                class="d-inline-block" onsubmit="return confirm('Delete this subscription?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
