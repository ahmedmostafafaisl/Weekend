@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | ' . __('lang.homepage_settings'))

@push('css')
<style>
    .hp-card{border:0;border-radius:16px;overflow:hidden}
    .hp-card .card-header{background:#fff;border-bottom:1px solid #eef0f4;padding:16px 20px}
    .hp-card .card-body{padding:20px}
    .hp-section-icon{width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:#f3f0ff;color:var(--accent);font-size:19px;flex:0 0 auto}
    .hp-preview{width:100%;height:120px;object-fit:contain;border:1px dashed #d9dce4;border-radius:12px;background:#fafafa;padding:10px}
    .hp-slide-img{width:150px;height:100px;object-fit:cover;border-radius:12px;border:1px solid #ececf2}
    .hp-help{font-size:12px;color:#8a93a3;margin-top:5px}
    .form-label{font-weight:600;font-size:13px}
    .hp-switch{border:1px solid #ececf2;border-radius:12px;padding:12px 14px;height:100%;background:#fff}
    .hp-switch .form-check{margin:0}
    .hp-slide-row{border:1px solid #ececf2;border-radius:14px;padding:14px;background:#fff}
    .hp-sticky-save{position:sticky;bottom:14px;z-index:20;display:flex;justify-content:flex-end;pointer-events:none}
    .hp-sticky-save .btn{pointer-events:auto;box-shadow:0 8px 24px rgba(111,0,255,.22)}
    @media(max-width:767.98px){.hp-slide-img{width:100%;height:180px}.hp-sticky-save{position:static}}
</style>
@endpush

@section('content')
@php
    $selectedIds = old('featured_unite_ids', $settings->featured_unite_ids ?? []);
    $images = [
        ['logo',         'logo_path',         __('lang.logo_main'),         __('lang.logo_main_hint')],
        ['logo_light',   'logo_light_path',   __('lang.logo_dark'),         __('lang.logo_dark_hint')],
        ['app_image',    'app_image_path',     __('lang.app_image_label'),   __('lang.app_image_hint')],
        ['why_us_image', 'why_us_image_path',  __('lang.why_us_image_label'), __('lang.why_us_image_hint')],
    ];
    $sectionToggles = [
        'show_search'      => __('lang.show_search'),
        'show_categories'  => __('lang.show_categories'),
        'show_featured'    => __('lang.show_featured'),
        'show_stats'       => __('lang.show_stats'),
        'show_why_us'      => __('lang.show_why_us'),
        'show_app_section' => __('lang.show_app_section'),
    ];
@endphp

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.homepage_settings') }}</h4>
        <div class="text-muted small">{{ __('lang.homepage_settings_hint') }}</div>
    </div>
    <a href="{{ url('/') }}" target="_blank" class="btn btn-accent">
        <i class="ti ti-external-link me-1"></i> {{ __('lang.preview_site') }}
    </a>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="ti ti-device-mobile-download mt-1"></i>
    <div><strong>{{ __('lang.booking_app_only_title') }}</strong> {{ __('lang.booking_app_only_body') }}</div>
</div>

@if(session('success'))
    <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger shadow-sm">
        <div class="fw-bold mb-1">{{ __('lang.validation_error') }}</div>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.homepage.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- ── Branding & Images ───────────────────────────────────────── --}}
    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-branding"></i></span>
            <div>
                <div class="fw-bold">{{ __('lang.branding_section') }}</div>
                <small class="text-muted">{{ __('lang.branding_section_hint') }}</small>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label">{{ __('lang.site_name') }}</label>
                    <input type="text" class="form-control" name="site_name"
                           value="{{ old('site_name', $settings->site_name ?: 'Weekend') }}">
                </div>
                <div class="col-12"></div>

                @foreach($images as [$input, $column, $label, $help])
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        @if($settings->{$column})
                            <img src="{{ asset($settings->{$column}) }}"
                                 class="hp-preview mb-2" alt="{{ $label }}">
                        @else
                            <div class="hp-preview mb-2 d-flex align-items-center justify-content-center text-muted">
                                {{ __('lang.no_image') }}
                            </div>
                        @endif
                        <input class="form-control form-control-sm" type="file"
                               accept="image/*" name="{{ $input }}">
                        <div class="hp-help">{{ $help }}</div>
                        @if($settings->{$column})
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1"
                                       id="remove_{{ $input }}" name="remove_{{ $input }}">
                                <label class="form-check-label small text-danger"
                                       for="remove_{{ $input }}">
                                    {{ __('lang.remove_image') }}
                                </label>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Main Content (AR / EN tabs) ────────────────────────────── --}}
    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-layout-dashboard"></i></span>
            <div>
                <div class="fw-bold">{{ __('lang.homepage_main_content') }}</div>
                <small class="text-muted">{{ __('lang.homepage_main_content_hint') }}</small>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="contentLangTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#pane-ar" type="button" role="tab">
                        🇸🇦 {{ __('lang.arabic') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#pane-en" type="button" role="tab">
                        🇬🇧 {{ __('lang.english') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Arabic --}}
                <div class="tab-pane fade show active" id="pane-ar" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('lang.hero_badge') }}</label>
                            <input class="form-control" name="hero_badge" dir="rtl"
                                   value="{{ old('hero_badge', $settings->hero_badge) }}">
                        </div>
                        <div class="col-lg-8">
                            <label class="form-label">{{ __('lang.hero_title') }}</label>
                            <input class="form-control" name="hero_title" dir="rtl"
                                   value="{{ old('hero_title', $settings->hero_title) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.hero_subtitle') }}</label>
                            <textarea class="form-control" name="hero_subtitle" rows="3" dir="rtl">{{ old('hero_subtitle', $settings->hero_subtitle) }}</textarea>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('lang.search_title') }}</label>
                            <input class="form-control" name="search_title" dir="rtl"
                                   value="{{ old('search_title', $settings->search_title) }}">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('lang.featured_title') }}</label>
                            <input class="form-control" name="featured_title" dir="rtl"
                                   value="{{ old('featured_title', $settings->featured_title) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.featured_subtitle') }}</label>
                            <textarea class="form-control" name="featured_subtitle" rows="2" dir="rtl">{{ old('featured_subtitle', $settings->featured_subtitle) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- English --}}
                <div class="tab-pane fade" id="pane-en" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('lang.hero_badge') }} (EN)</label>
                            <input class="form-control" name="hero_badge_en" dir="ltr"
                                   value="{{ old('hero_badge_en', $settings->hero_badge_en) }}">
                        </div>
                        <div class="col-lg-8">
                            <label class="form-label">{{ __('lang.hero_title') }} (EN)</label>
                            <input class="form-control" name="hero_title_en" dir="ltr"
                                   value="{{ old('hero_title_en', $settings->hero_title_en) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.hero_subtitle') }} (EN)</label>
                            <textarea class="form-control" name="hero_subtitle_en" rows="3" dir="ltr">{{ old('hero_subtitle_en', $settings->hero_subtitle_en) }}</textarea>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('lang.search_title') }} (EN)</label>
                            <input class="form-control" name="search_title_en" dir="ltr"
                                   value="{{ old('search_title_en', $settings->search_title_en) }}">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('lang.featured_title') }} (EN)</label>
                            <input class="form-control" name="featured_title_en" dir="ltr"
                                   value="{{ old('featured_title_en', $settings->featured_title_en) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.featured_subtitle') }} (EN)</label>
                            <textarea class="form-control" name="featured_subtitle_en" rows="2" dir="ltr">{{ old('featured_subtitle_en', $settings->featured_subtitle_en) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">
            <div class="row g-3">
                <div class="col-lg-3">
                    <label class="form-label">{{ __('lang.featured_limit') }}</label>
                    <input class="form-control" type="number" min="4" max="24"
                           name="featured_limit"
                           value="{{ old('featured_limit', $settings->featured_limit ?: 8) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('lang.featured_unites') }}</label>
                    <select class="form-select" name="featured_unite_ids[]" multiple size="8">
                        @foreach($unites as $u)
                            <option value="{{ $u->id }}"
                                    @selected(in_array($u->id, $selectedIds))>
                                {{ $u->name }}{{ $u->location_name ? ' — '.$u->location_name : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="hp-help">{{ __('lang.featured_unites_hint') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Visible Sections ────────────────────────────────────────── --}}
    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-eye"></i></span>
            <div>
                <div class="fw-bold">{{ __('lang.sections_visibility') }}</div>
                <small class="text-muted">{{ __('lang.sections_visibility_hint') }}</small>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($sectionToggles as $key => $label)
                    <div class="col-lg-4 col-md-6">
                        <div class="hp-switch">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input class="form-check-input m-0" type="checkbox" role="switch"
                                       id="{{ $key }}" name="{{ $key }}" value="1"
                                       @checked(old($key, $settings->{$key}))>
                                <label class="form-check-label fw-semibold" for="{{ $key }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── App & Stores (AR / EN tabs) ────────────────────────────── --}}
    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-device-mobile"></i></span>
            <div>
                <div class="fw-bold">{{ __('lang.homepage_app_section') }}</div>
                <small class="text-muted">{{ __('lang.homepage_app_section_hint') }}</small>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#app-ar" type="button">
                        🇸🇦 {{ __('lang.arabic') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#app-en" type="button">
                        🇬🇧 {{ __('lang.english') }}
                    </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="app-ar">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('lang.app_title') }}</label>
                            <input class="form-control" name="app_title" dir="rtl"
                                   value="{{ old('app_title', $settings->app_title) }}">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('lang.app_text') }}</label>
                            <input class="form-control" name="app_text" dir="rtl"
                                   value="{{ old('app_text', $settings->app_text) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.footer_text') }}</label>
                            <textarea class="form-control" name="footer_text" rows="3" dir="rtl">{{ old('footer_text', $settings->footer_text) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="app-en">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('lang.app_title') }} (EN)</label>
                            <input class="form-control" name="app_title_en" dir="ltr"
                                   value="{{ old('app_title_en', $settings->app_title_en) }}">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('lang.app_text') }} (EN)</label>
                            <input class="form-control" name="app_text_en" dir="ltr"
                                   value="{{ old('app_text_en', $settings->app_text_en) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('lang.footer_text') }} (EN)</label>
                            <textarea class="form-control" name="footer_text_en" rows="3" dir="ltr">{{ old('footer_text_en', $settings->footer_text_en) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Google Play</label>
                    <input class="form-control" type="url" name="google_play_url"
                           value="{{ old('google_play_url', $settings->google_play_url) }}"
                           placeholder="https://play.google.com/...">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">App Store</label>
                    <input class="form-control" type="url" name="apple_store_url"
                           value="{{ old('apple_store_url', $settings->apple_store_url) }}"
                           placeholder="https://apps.apple.com/...">
                </div>
            </div>
        </div>
    </div>

    <div class="hp-sticky-save mb-4">
        <button class="btn btn-accent px-4 py-2" type="submit">
            <i class="ti ti-device-floppy me-1"></i> {{ __('lang.save_homepage_settings') }}
        </button>
    </div>
</form>

{{-- ── Hero Slider ─────────────────────────────────────────────────── --}}
<div class="card card-soft shadow-sm hp-card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="hp-section-icon"><i class="ti ti-photo"></i></span>
        <div>
            <div class="fw-bold">{{ __('lang.slider_section') }}</div>
            <small class="text-muted">{{ __('lang.slider_section_hint') }}</small>
        </div>
    </div>
    <div class="card-body">

        {{-- Add slide form --}}
        <form method="POST" action="{{ route('admin.homepage.slides.store') }}"
              enctype="multipart/form-data"
              class="border rounded-3 p-3 mb-4 bg-light bg-opacity-25">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">{{ __('lang.slide_image') }} <span class="text-danger">*</span></label>
                    <input type="file" accept="image/*" class="form-control" name="image" required>
                </div>
                <div class="col-lg-4">
                    <label class="form-label">{{ __('lang.slide_title') }}</label>
                    <input class="form-control" name="title">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">{{ __('lang.slide_subtitle') }}</label>
                    <input class="form-control" name="subtitle">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">{{ __('lang.slide_button_text') }}</label>
                    <input class="form-control" name="button_text">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">{{ __('lang.slide_button_url') }}</label>
                    <input class="form-control" name="button_url" placeholder="/ ...">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">{{ __('lang.slide_order') }}</label>
                    <input class="form-control" type="number" name="sort_order" value="0" min="0">
                </div>
                <div class="col-lg-2">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                               id="new_slide_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="new_slide_active">
                            {{ __('lang.slide_active') }}
                        </label>
                    </div>
                </div>
            </div>
            <button class="btn btn-accent mt-3" type="submit">
                <i class="ti ti-plus me-1"></i> {{ __('lang.slide_add') }}
            </button>
        </form>

        {{-- Existing slides --}}
        <div class="d-grid gap-3">
            @forelse($slides as $slide)
                <div class="hp-slide-row">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-2">
                            <img src="{{ asset($slide->image) }}" class="hp-slide-img" alt="">
                        </div>
                        <div class="col-lg-8">
                            <form method="POST"
                                  action="{{ route('admin.homepage.slides.update', $slide) }}"
                                  enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input class="form-control form-control-sm" name="title"
                                               value="{{ $slide->title }}"
                                               placeholder="{{ __('lang.slide_title') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <input class="form-control form-control-sm" name="subtitle"
                                               value="{{ $slide->subtitle }}"
                                               placeholder="{{ __('lang.slide_subtitle') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <input class="form-control form-control-sm" name="button_text"
                                               value="{{ $slide->button_text }}"
                                               placeholder="{{ __('lang.slide_button_text') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <input class="form-control form-control-sm" name="button_url"
                                               value="{{ $slide->button_url }}"
                                               placeholder="{{ __('lang.slide_button_url') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input class="form-control form-control-sm" type="number"
                                               name="sort_order" value="{{ $slide->sort_order }}" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check form-switch pt-1">
                                            <input class="form-check-input" type="checkbox"
                                                   id="slide_active_{{ $slide->id }}"
                                                   name="is_active" value="1"
                                                   @checked($slide->is_active)>
                                            <label class="form-check-label small"
                                                   for="slide_active_{{ $slide->id }}">
                                                {{ __('lang.slide_active') }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">
                                            {{ __('lang.slide_replace_image') }}
                                        </label>
                                        <input class="form-control form-control-sm" type="file"
                                               accept="image/*" name="image">
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary mt-2" type="submit">
                                    <i class="ti ti-check me-1"></i> {{ __('lang.slide_update') }}
                                </button>
                            </form>
                        </div>
                        <div class="col-lg-2 text-lg-end">
                            <form method="POST"
                                  action="{{ route('admin.homepage.slides.destroy', $slide) }}"
                                  onsubmit="return confirm('{{ __('lang.slide_delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    <i class="ti ti-trash me-1"></i> {{ __('lang.slide_delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    {{ __('lang.slide_empty') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
