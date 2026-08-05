<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekend | {{ __('lang.login_title') }}</title>

    {{-- Bootstrap 5 (RTL build when Arabic is active) --}}
    @if(app()->getLocale() === 'ar')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif

    {{-- Custom CSS --}}
    <link href="{{ asset('admin-assets/css/auth.css') }}" rel="stylesheet">

    <style>
        .lang-switch-top { position: absolute; top: 20px; {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 20px; }
        .lang-switch-top .btn { font-size: 12px; padding: 4px 12px; }
    </style>
</head>
<body>

{{-- Language switcher — visible before login, since a user who can't read
     the active language would otherwise be stuck on this very first screen --}}
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

<div class="auth-wrap">
    {{-- dotted decorations --}}
    <span class="dots dots-top"></span>
    <span class="dots dots-bottom"></span>

    <div class="auth-card card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">

            {{-- Logo (optional) --}}
            <div class="logo-box mb-3">
                <img src="{{ asset('admin-assets/images/logo.png') }}" alt="Weekend" onerror="this.style.display='none'">
            </div>

            <h4 class="fw-bold mb-1">{{ __('lang.welcome_back') }} <span class="brand">Weekend</span> 👋</h4>
            <p class="text-muted mb-4">{{ __('lang.login_quote') }}</p>

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ __('lang.email') }} <span class="text-danger">*</span></label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control"
                           placeholder="admin@weekend.com"
                           required>
                </div>

                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label mb-0">{{ __('lang.password') }} <span class="text-danger">*</span></label>
                        <a href="#" class="link-accent small text-decoration-none">{{ __('lang.forgot_password') }}</a>
                    </div>

                    <div class="input-group mt-2">
                        <button class="btn btn-outline-secondary toggle-pass" type="button" id="togglePass">
                            👁
                        </button>
                        <input type="password" name="password" class="form-control" id="password" required>
                    </div>
                </div>

                <button class="btn btn-accent w-100 mt-3" type="submit">
                    {{ __('lang.login_btn') }}
                </button>

                <p class="text-center text-muted small mt-3 mb-0">
                    {{ __('lang.admin_access_only') }}
                </p>
            </form>

        </div>
    </div>
</div>

<script>
    const btn = document.getElementById('togglePass');
    const input = document.getElementById('password');

    btn?.addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
    });
</script>

</body>
</html>
