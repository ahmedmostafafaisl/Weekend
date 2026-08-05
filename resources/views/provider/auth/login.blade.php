<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Weekend — {{ __('lang.provider_dashboard_login') }}</title>
    @if(app()->getLocale() === 'ar')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <style>
        :root{--accent:#6f00ff}
        body{background:#f6f5fb;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{border-radius:20px;border:.5px solid #e8e4f3;max-width:400px;width:100%}
        .btn-accent{background:var(--accent);color:#fff;border:none}
        .btn-accent:hover{background:#5900d4;color:#fff}
        .brand{color:var(--accent);font-weight:800;font-size:1.5rem}
        .lang-switch-top { position: absolute; top: 20px; {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 20px; }
        .lang-switch-top .btn { font-size: 12px; padding: 4px 12px; }
    </style>
</head>
<body>

{{-- Language switcher — visible before login, matching the admin login page --}}
<div class="lang-switch-top d-flex gap-2">
    <form method="POST" action="{{ route('locale.switch') }}">
        @csrf
        <input type="hidden" name="locale" value="en">
        <button type="submit" class="btn {{ app()->getLocale() === 'en' ? 'btn-accent' : 'btn-outline-secondary' }}">EN</button>
    </form>
    <form method="POST" action="{{ route('locale.switch') }}">
        @csrf
        <input type="hidden" name="locale" value="ar">
        <button type="submit" class="btn {{ app()->getLocale() === 'ar' ? 'btn-accent' : 'btn-outline-secondary' }}">عربي</button>
    </form>
</div>

<div class="card shadow-sm p-4 mx-3">
    <div class="text-center mb-4">
        <div class="brand">🏟 Weekend</div>
        <div class="text-muted small mt-1">{{ __('lang.provider_dashboard_login') }}</div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif
    @if(session('status'))
        <div class="alert alert-success py-2 small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-semibold small">{{ __('lang.email') }}</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email') }}" required autofocus
                   placeholder="provider@example.com">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small">{{ __('lang.password') }}</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small" for="remember">{{ __('lang.remember_me') }}</label>
        </div>
        <button type="submit" class="btn btn-accent w-100">{{ __('lang.login') }}</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('admin.login') }}" class="small text-muted">{{ __('lang.admin_login_arrow') }}</a>
    </div>
</div>
</body>
</html>
