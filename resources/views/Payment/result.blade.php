@php
$messages = [
    'tabby' => __('lang.tabby_unable_message'),
    'tamara' => __('lang.tamara_unable_message'),
    'clickpay' => __('lang.clickpay_unable_message'),
];
@endphp
@extends('layouts.payment.app')

@section('content')
            <link rel="stylesheet" href="{{ asset('css/payment-success.css') }}">

            <div class="container payment-success-container">
                <div class="text-center">
        @if($payment_type === 'tabby')
            <img src="{{ asset('images/tabby.svg') }}" alt="Tabby" class="tabby-logo mb-3">
        @elseif($payment_type === 'tamara')
            <img src="{{ asset('images/tamara.png') }}" alt="Tamara" class="tabby-logo mb-3">
        @endif

                    <div class="checkmark-circle mb-4">
                        @if($status == 'paid' || $status == 'success')
                            <img src="{{ asset('images/success.webp') }}" alt="Success" class="checkmark">
                        @else
                            <img src="{{ asset('images/close.png') }}" alt="Failed" class="checkmark">
                        @endif
                    </div>

                    @if($status == 'paid' || $status == 'success')
                        <h4 class="fw-bold">{{ __('lang.payment_successful') }}</h4>
                        <p class="text-muted">{{ __('lang.payment_successful_body') }}</p>
                    @elseif($status == 'failed')
                        <h4 class="fw-bold">{{ __('lang.payment_cancelled_title') }}</h4>
                        <p class="text-muted">{{ __('lang.payment_cancelled_body') }}</p>
                    @elseif($status == 'failed')
                            <h4 class="fw-bold">{{ __('lang.payment_failed_title') }}</h4>
                        <p class="text-muted">
                            {{ $messages[$payment_type] ?? __('lang.payment_failed_generic') }}
                        </p>
                    @else
                        <h4 class="fw-bold">{{ __('lang.something_wrong') }}</h4>
                        <p class="text-muted">{{ __('lang.something_wrong_body') }}</p>
                    @endif
                </div>

                {{-- @if($status=='success') --}}
                    <div class="card price-card mt-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">{{ __('lang.price_details') }}</h5>
                            <div class="d-flex justify-content-between">
                                <span>{{ __('lang.subtotal') }} </span>
                                <span>{{ number_format($priceWithoutTax, 2) ?? 0 }} SAR</span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span>{{ __('lang.vat_15') }}</span>
                                <span>{{ number_format($taxAmount, 2) ?? 0 }} SAR</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>{{ __('lang.total_incl_vat') }}</span>
                                <span>{{ number_format($payment->amount ?? 0, 2) ?? 0 }} SAR</span>
                            </div>
                        </div>
                    </div>

                    <div class="card payment-card mt-4 mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">{{ __('lang.payment_details') }}</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
    @if($payment_type === 'tabby')
        <img src="{{ asset('images/tabby.svg') }}" alt="Tabby" class="tabby-icon">
    @elseif($payment_type === 'tamara')
        <img src="{{ asset('images/tamara.png') }}" alt="Tamara" class="tabby-icon">
    @elseif($payment_type === 'clickpay'||$payment_type === 'E-Commerce'||$payment_type === 'E-COMMERCE')
        <img src="{{ asset('images/clickpay.png') }}" alt="ClickPay" class="tabby-icon">
    @endif
                                <span>{{ $payment->phone ?? 0 }}</span>
                                </div>
                            <span>{{ number_format( $payment->amount ?? 0, 2) }} SAR</span>

                            </div>
                        </div>
                    </div>
                {{-- @endif --}}


            </div>
@endsection
