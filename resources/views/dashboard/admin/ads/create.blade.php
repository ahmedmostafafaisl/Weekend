@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Create Ad')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.ads.index') }}" class="btn btn-sm btn-outline-secondary">← {{ __('lang.back') }}</a>
    <h4 class="fw-bold mb-0">{{ __('lang.create_ad') }}</h4>
</div>
<div class="card card-soft shadow-sm" style="max-width:780px">
    <div class="card-body">
        <form action="{{ route('admin.ads.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('dashboard.admin.ads.partials.form', ['button' => __('lang.create_ad')])
        </form>
    </div>
</div>
@endsection
