@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | إعدادات الصفحة الرئيسية')

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
@endphp

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h4 class="fw-bold mb-1">إعدادات الصفحة الرئيسية</h4>
        <div class="text-muted small">تحكم بالشعار، صور الواجهة، السلايدر، الأقسام، الوحدات المميزة وروابط التطبيق من مكان واحد.</div>
    </div>
    <a href="{{ url('/') }}" target="_blank" class="btn btn-accent">
        <i class="ti ti-external-link me-1"></i> معاينة الموقع
    </a>
</div>
<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="ti ti-device-mobile-download mt-1"></i>
    <div><strong>الحجز من التطبيق فقط:</strong> جميع أزرار «احجز» في الصفحة الرئيسية تحول المستخدم إلى متجر التطبيق. تأكد من إضافة رابط Google Play وApp Store في قسم التطبيق والمتاجر أدناه.</div>
</div>

@if(session('success'))
    <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger shadow-sm">
        <div class="fw-bold mb-1">يوجد خطأ في البيانات:</div>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.homepage.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-branding"></i></span>
            <div><div class="fw-bold">هوية الموقع والصور</div><small class="text-muted">هذه الصور تظهر مباشرة في الواجهة العامة.</small></div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label">اسم الموقع</label>
                    <input type="text" class="form-control" name="site_name" value="{{ old('site_name', $settings->site_name ?: 'ويكند') }}">
                </div>
                <div class="col-12"></div>

                @foreach([
                    ['logo','logo_path','الشعار الرئيسي','يستخدم في الهيدر والخلفيات الفاتحة.'],
                    ['logo_light','logo_light_path','الشعار للخلفية الداكنة','يستخدم في الفوتر أو فوق الصور الداكنة.'],
                    ['app_image','app_image_path','صورة قسم التطبيق','صورة الجوال أو لقطة التطبيق في قسم التحميل.'],
                    ['why_us_image','why_us_image_path','صورة قسم لماذا ويكند؟','الصورة الكبيرة بجانب مميزات المنصة.'],
                ] as [$input,$column,$label,$help])
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        @if($settings->{$column})
                            <img src="{{ asset($settings->{$column}) }}" class="hp-preview mb-2" alt="{{ $label }}">
                        @else
                            <div class="hp-preview mb-2 d-flex align-items-center justify-content-center text-muted">لا توجد صورة</div>
                        @endif
                        <input class="form-control form-control-sm" type="file" accept="image/*" name="{{ $input }}">
                        <div class="hp-help">{{ $help }}</div>
                        @if($settings->{$column})
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="remove_{{ $input }}" name="remove_{{ $input }}">
                                <label class="form-check-label small text-danger" for="remove_{{ $input }}">حذف الصورة الحالية</label>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-layout-dashboard"></i></span>
            <div><div class="fw-bold">المحتوى الرئيسي</div><small class="text-muted">النصوص التي تظهر أعلى الصفحة وفي قسم الوحدات.</small></div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-4"><label class="form-label">العبارة الصغيرة أعلى العنوان</label><input class="form-control" name="hero_badge" value="{{ old('hero_badge',$settings->hero_badge) }}"></div>
                <div class="col-lg-8"><label class="form-label">العنوان الرئيسي</label><input class="form-control" name="hero_title" value="{{ old('hero_title',$settings->hero_title) }}"></div>
                <div class="col-12"><label class="form-label">الوصف الرئيسي</label><textarea class="form-control" name="hero_subtitle" rows="3">{{ old('hero_subtitle',$settings->hero_subtitle) }}</textarea></div>
                <div class="col-lg-4"><label class="form-label">عنوان البحث</label><input class="form-control" name="search_title" value="{{ old('search_title',$settings->search_title) }}"></div>
                <div class="col-lg-4"><label class="form-label">عنوان الوحدات المميزة</label><input class="form-control" name="featured_title" value="{{ old('featured_title',$settings->featured_title) }}"></div>
                <div class="col-lg-4"><label class="form-label">عدد الوحدات</label><input class="form-control" type="number" min="4" max="24" name="featured_limit" value="{{ old('featured_limit',$settings->featured_limit ?: 8) }}"></div>
                <div class="col-12"><label class="form-label">وصف قسم الوحدات</label><textarea class="form-control" name="featured_subtitle" rows="2">{{ old('featured_subtitle',$settings->featured_subtitle) }}</textarea></div>
                <div class="col-12">
                    <label class="form-label">الوحدات المميزة المحددة</label>
                    <select class="form-select" name="featured_unite_ids[]" multiple size="8">
                        @foreach($unites as $u)
                            <option value="{{ $u->id }}" @selected(in_array($u->id, $selectedIds))>{{ $u->name }}{{ $u->location_name ? ' — '.$u->location_name : '' }}</option>
                        @endforeach
                    </select>
                    <div class="hp-help">إذا لم يتم اختيار أي وحدة، تعرض الصفحة أحدث الوحدات النشطة تلقائيًا.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-eye"></i></span>
            <div><div class="fw-bold">الأقسام الظاهرة في الصفحة</div><small class="text-muted">فعّل أو أخفِ أي قسم بدون تعديل الكود.</small></div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach([
                    'show_search'=>'شريط البحث',
                    'show_categories'=>'التصنيفات',
                    'show_featured'=>'الوحدات المميزة',
                    'show_stats'=>'الإحصائيات',
                    'show_why_us'=>'لماذا ويكند؟',
                    'show_app_section'=>'تحميل التطبيق',
                ] as $key=>$label)
                    <div class="col-lg-4 col-md-6">
                        <div class="hp-switch">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="{{ $key }}" name="{{ $key }}" value="1" @checked(old($key,$settings->{$key}))>
                                <label class="form-check-label fw-semibold" for="{{ $key }}">{{ $label }}</label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card card-soft shadow-sm hp-card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="hp-section-icon"><i class="ti ti-device-mobile"></i></span>
            <div><div class="fw-bold">التطبيق والمتاجر</div><small class="text-muted">هذه الروابط تستخدم أيضًا عند ضغط الزائر على زر الحجز؛ ويتم إخفاء زر المتجر عندما يكون رابطه فارغًا.</small></div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6"><label class="form-label">عنوان قسم التطبيق</label><input class="form-control" name="app_title" value="{{ old('app_title',$settings->app_title) }}"></div>
                <div class="col-lg-6"><label class="form-label">وصف التطبيق</label><input class="form-control" name="app_text" value="{{ old('app_text',$settings->app_text) }}"></div>
                <div class="col-lg-6"><label class="form-label">رابط Google Play</label><input class="form-control" type="url" name="google_play_url" value="{{ old('google_play_url',$settings->google_play_url) }}" placeholder="https://play.google.com/..."></div>
                <div class="col-lg-6"><label class="form-label">رابط App Store</label><input class="form-control" type="url" name="apple_store_url" value="{{ old('apple_store_url',$settings->apple_store_url) }}" placeholder="https://apps.apple.com/..."></div>
                <div class="col-12"><label class="form-label">نص الفوتر</label><textarea class="form-control" name="footer_text" rows="3">{{ old('footer_text',$settings->footer_text) }}</textarea></div>
            </div>
        </div>
    </div>

    <div class="hp-sticky-save mb-4">
        <button class="btn btn-accent px-4 py-2" type="submit"><i class="ti ti-device-floppy me-1"></i> حفظ إعدادات الصفحة الرئيسية</button>
    </div>
</form>

<div class="card card-soft shadow-sm hp-card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="hp-section-icon"><i class="ti ti-photo"></i></span>
        <div><div class="fw-bold">سلايدر الواجهة</div><small class="text-muted">يمكن إضافة الصور وتعديلها وترتيبها أو استبدال صورة أي سلايد مباشرة من هنا.</small></div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.homepage.slides.store') }}" enctype="multipart/form-data" class="border rounded-3 p-3 mb-4 bg-light bg-opacity-25">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-lg-4"><label class="form-label">صورة السلايد</label><input type="file" accept="image/*" class="form-control" name="image" required></div>
                <div class="col-lg-4"><label class="form-label">العنوان</label><input class="form-control" name="title"></div>
                <div class="col-lg-4"><label class="form-label">الوصف</label><input class="form-control" name="subtitle"></div>
                <div class="col-lg-4"><label class="form-label">نص الزر</label><input class="form-control" name="button_text" placeholder="مثال: استكشف الآن"></div>
                <div class="col-lg-4"><label class="form-label">رابط الزر</label><input class="form-control" name="button_url" placeholder="/ أو رابط كامل"></div>
                <div class="col-lg-2"><label class="form-label">الترتيب</label><input class="form-control" type="number" name="sort_order" value="0" min="0"></div>
                <div class="col-lg-2"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="new_slide_active" name="is_active" value="1" checked><label class="form-check-label" for="new_slide_active">فعال</label></div></div>
            </div>
            <button class="btn btn-accent mt-3" type="submit"><i class="ti ti-plus me-1"></i> إضافة السلايد</button>
        </form>

        <div class="d-grid gap-3">
            @forelse($slides as $slide)
                <div class="hp-slide-row">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-2"><img src="{{ asset($slide->image) }}" class="hp-slide-img" alt=""></div>
                        <div class="col-lg-8">
                            <form method="POST" action="{{ route('admin.homepage.slides.update',$slide) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row g-2">
                                    <div class="col-md-6"><input class="form-control form-control-sm" name="title" value="{{ $slide->title }}" placeholder="العنوان"></div>
                                    <div class="col-md-6"><input class="form-control form-control-sm" name="subtitle" value="{{ $slide->subtitle }}" placeholder="الوصف"></div>
                                    <div class="col-md-4"><input class="form-control form-control-sm" name="button_text" value="{{ $slide->button_text }}" placeholder="نص الزر"></div>
                                    <div class="col-md-4"><input class="form-control form-control-sm" name="button_url" value="{{ $slide->button_url }}" placeholder="رابط الزر"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm" type="number" name="sort_order" value="{{ $slide->sort_order }}" min="0"></div>
                                    <div class="col-md-2"><div class="form-check form-switch pt-1"><input class="form-check-input" type="checkbox" id="slide_active_{{ $slide->id }}" name="is_active" value="1" @checked($slide->is_active)><label class="form-check-label small" for="slide_active_{{ $slide->id }}">فعال</label></div></div>
                                    <div class="col-12"><label class="form-label small mb-1">استبدال الصورة (اختياري)</label><input class="form-control form-control-sm" type="file" accept="image/*" name="image"></div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary mt-2" type="submit"><i class="ti ti-check me-1"></i> تحديث</button>
                            </form>
                        </div>
                        <div class="col-lg-2 text-lg-end">
                            <form method="POST" action="{{ route('admin.homepage.slides.destroy',$slide) }}" onsubmit="return confirm('هل تريد حذف هذا السلايد؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="ti ti-trash me-1"></i> حذف</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">لا توجد سلايدات حتى الآن. أضف أول صورة من النموذج أعلاه.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
