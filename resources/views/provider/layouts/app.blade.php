<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Weekend Provider')</title>
    @if(app()->getLocale() === 'ar')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
@else
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
@endif
    <style>
        :root{--accent:#6f00ff;--bg:#f6f5fb}
        body{background:var(--bg)}
        .sidebar{
            width:240px;min-height:100vh;background:#fff;border-right:1px solid #eee;
            position:sticky;top:0;flex-shrink:0;z-index:1045;
            transition:transform .25s ease;
        }
        .brand{color:var(--accent);font-weight:800}
        .nav-link{color:#334155;border-radius:10px;padding:10px 12px}
        .nav-link:hover,.nav-link.active{background:#f3f0ff;color:var(--accent)}
        .card-soft{border-radius:16px;border:.5px solid #e8e4f3}
        .btn-accent{background:var(--accent);color:#fff;border:none}
        .btn-accent:hover{background:#5900d4;color:#fff}

        /* ── Mobile off-canvas sidebar ──────────────────────────────────────
           Same fixed-width-sidebar-in-a-plain-flexbox pattern that broke the
           admin panel on mobile — this layout had the identical gap: no
           @media queries anywhere, no flex-shrink guard, no way to hide the
           sidebar on a phone-width viewport. Below the breakpoint the
           sidebar leaves the flex flow, becomes fixed, and slides off-screen
           by default; a hamburger button (added to the markup below, since
           this layout has no existing topbar to place one in) toggles it. */
        @media (max-width: 991.98px){
            .sidebar{
                position:fixed;top:0;bottom:0;left:0;height:100vh;
                transform:translateX(-100%);
                box-shadow:0 0 24px rgba(0,0,0,.15);
            }
            html[lang="ar"] .sidebar{
                left:auto;right:0;
                transform:translateX(100%);
            }
            .sidebar.is-open{ transform:translateX(0); }
            /* Explicit override so the Arabic-locale rule above can never
               out-specificity this one — same fix applied to the admin panel
               after the same mistake was found there. */
            html[lang="ar"] .sidebar.is-open{ transform:translateX(0); }

            .sidebar-backdrop{
                position:fixed;inset:0;background:rgba(0,0,0,.4);
                z-index:1040;opacity:0;visibility:hidden;
                transition:opacity .2s ease;
            }
            .sidebar-backdrop.is-open{ opacity:1;visibility:visible; }

            .sidebar-toggle-btn{ display:inline-flex !important; }
            .flex-grow-1{ padding:1rem !important; }
        }
        .sidebar-toggle-btn{
            display:none;align-items:center;justify-content:center;
            width:38px;height:38px;border:1px solid #eee;border-radius:8px;
            background:#fff;color:#334155;font-size:18px;flex-shrink:0;
            cursor:pointer;margin-bottom:12px;
        }
        .sidebar-toggle-btn:hover{ background:#f3f0ff;color:var(--accent); }
    </style>
    @stack('css')
</head>
<body>
<div class="d-flex">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="sidebar d-flex flex-column p-3 gap-1" id="providerSidebar">
        <div class="brand fs-5 px-2 py-3">🏟 Weekend</div>
        <div class="text-muted small px-2 mb-2">{{ auth()->user()->name }}</div>
        <hr class="my-1">
        @php $r = request()->route()?->getName() ?? ''; @endphp
        <a class="nav-link {{ str_starts_with($r,'provider.dashboard') && !str_contains($r,'approval') && !str_contains($r,'venue') && !str_contains($r,'revenue') ? 'active' : '' }}"
           href="{{ route('provider.dashboard') }}">🏠 {{ __('lang.sidebar_provider_dashboard') }}</a>
        <a class="nav-link {{ str_contains($r,'approval') ? 'active' : '' }}"
           href="{{ route('provider.approvals') }}">
            📋 {{ __('lang.sidebar_pending_approvals') }}
            @php $pending = \App\Models\UniteReservation::whereHas('unite.department', fn($q)=>$q->where('user_id',auth()->id()))->where('status','pending_approval')->count(); @endphp
            @if($pending)<span class="badge bg-warning text-dark ms-1">{{ $pending }}</span>@endif
        </a>
        <a class="nav-link {{ str_contains($r,'venue') ? 'active' : '' }}"
           href="{{ route('provider.venues') }}">🏢 {{ __('lang.sidebar_my_venues') }}</a>
        <a class="nav-link {{ str_contains($r,'reports') ? 'active' : '' }}"
           href="{{ route('provider.reports.index') }}">📊 {{ __('lang.sidebar_reports') }}</a>
        <a class="nav-link {{ str_contains($r,'transfers') ? 'active' : '' }}"
           href="{{ route('provider.transfers') }}">💸 {{ __('lang.sidebar_transfers') }}</a>
        <a class="nav-link {{ str_contains($r,'revenue') ? 'active' : '' }}"
           href="{{ route('provider.revenue') }}">💰 {{ __('lang.sidebar_revenue') }}</a>
        <hr class="mt-auto mb-1">
        {{-- Language Switcher --}}
    <div class="d-flex gap-1 mb-2">
        <form method="POST" action="{{ route('locale.switch') }}" class="flex-fill">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit" class="btn btn-sm w-100 {{ app()->getLocale() === 'en' ? 'btn-accent' : 'btn-outline-secondary' }}">🇬🇧 EN</button>
        </form>
        <form method="POST" action="{{ route('locale.switch') }}" class="flex-fill">
            @csrf
            <input type="hidden" name="locale" value="ar">
            <button type="submit" class="btn btn-sm w-100 {{ app()->getLocale() === 'ar' ? 'btn-accent' : 'btn-outline-secondary' }}">🇸🇦 AR</button>
        </form>
    </div>
    <form action="{{ route('provider.logout') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-danger w-100">{{ __('lang.logout') }}</button>
        </form>
    </div>

    <div class="flex-grow-1 p-4" style="min-height:100vh">
        <button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" aria-label="Toggle menu">☰</button>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @yield('content')
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const sidebar  = document.getElementById('providerSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggle   = document.getElementById('sidebarToggleBtn');

        function openSidebar() {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-open');
        }
        function closeSidebar() {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-open');
        }

        toggle?.addEventListener('click', () => {
            sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
        });
        backdrop?.addEventListener('click', closeSidebar);

        sidebar?.querySelectorAll('a, button[type="submit"]').forEach(el => {
            el.addEventListener('click', () => {
                if (window.innerWidth < 992) closeSidebar();
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) closeSidebar();
        });
    })();
</script>
@stack('js')
</body>
</html>
