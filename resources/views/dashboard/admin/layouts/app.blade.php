<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin')</title>

    @if(app()->getLocale() === 'ar')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
@else
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
@endif

    <style>
        :root{
            --accent:#6f00ff;
            --bg:#f6f5fb;
            --sidebar-bg:#ffffff;
            --sidebar-hover:#f3f0ff;
            --sidebar-active:#f3f0ff;
            --sidebar-active-text:var(--accent);
            --sidebar-text:#334155;
            --sidebar-text-muted:#94a3b8;
            --sidebar-label:#94a3b8;
            --sidebar-divider:#eee;
            --sidebar-border:#eee;
            --sidebar-width:260px;
            --sidebar-width-ar:280px;
        }
        body{ background:var(--bg); }

        /* ── Sidebar shell ── */
        .sidebar{
            width: var(--sidebar-width);
            height: 100vh;
            position: sticky; top:0;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex-shrink: 0;
            z-index: 1045;
            transition: transform .25s ease;
        }
        html[lang="ar"] .sidebar{ width: var(--sidebar-width-ar); border-right:none; border-left: 1px solid var(--sidebar-border); }

        /* ── Mobile off-canvas sidebar ──────────────────────────────────────
           BUG FIX: the sidebar previously had no responsive handling at all —
           on any viewport narrower than ~992px it would be squeezed by the
           flexbox layout instead of collapsing cleanly, leaving both the
           sidebar and main content unreadable (this is exactly the broken
           layout reported on mobile). Below the breakpoint, the sidebar is
           now pulled out of the flex flow, fixed to the viewport, and slid
           off-screen by default — toggled via the hamburger button added to
           the topbar, with a tap-to-close backdrop behind it. */
        @media (max-width: 991.98px){
            .sidebar{
                position: fixed;
                top: 0; bottom: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                box-shadow: 0 0 24px rgba(0,0,0,.15);
            }
            html[lang="ar"] .sidebar{
                left: auto; right: 0;
                transform: translateX(100%);
            }
            .sidebar.is-open{ transform: translateX(0); }
            /* BUG FIX: html[lang="ar"] .sidebar (specificity 0,2,1) was more
               specific than .sidebar.is-open (0,2,0) above, so on the Arabic
               side the JS-toggled "open" class never actually changed the
               transform — only the backdrop (no such conflict) would show.
               This explicit override always wins for the Arabic+open case. */
            html[lang="ar"] .sidebar.is-open{ transform: translateX(0); }

            .sidebar-backdrop{
                position: fixed; inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1040;
                opacity: 0;
                visibility: hidden;
                transition: opacity .2s ease;
            }
            .sidebar-backdrop.is-open{ opacity: 1; visibility: visible; }

            .sidebar-toggle-btn{
                display: inline-flex !important;
            }

            main.flex-grow-1{ padding: 1rem !important; width: 100%; }

            .topbar{ flex-wrap: wrap; gap: 10px; }
            .topbar .search-box{ order: 3; width: 100%; }
            .topbar input.form-control{ font-size: 13px; }
        }

        /* Hamburger toggle — hidden on desktop, shown only inside the mobile media query above */
        .sidebar-toggle-btn{
            display: none;
            align-items: center; justify-content: center;
            width: 38px; height: 38px;
            border: 1px solid var(--sidebar-divider);
            border-radius: 8px;
            background: #fff;
            color: var(--sidebar-text);
            font-size: 18px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .sidebar-toggle-btn:hover{ background: var(--sidebar-hover); color: var(--accent); }

        /* ── Sidebar brand bar ── */
        .sidebar-brand{
            padding: 20px 16px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .sidebar-brand-icon{
            width:34px; height:34px;
            background: var(--accent);
            border-radius: 9px;
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-weight:800; font-size:15px;
        }
        .sidebar-brand-name{
            color: var(--accent);
            font-weight:800;
            font-size:16px;
            letter-spacing:.3px;
        }

        /* ── Scrollable nav ── */
        .sidebar-scroll{
            overflow-y:auto;
            scrollbar-width:none;
        }
        .sidebar-scroll::-webkit-scrollbar{ display:none; }

        /* ── Section labels ── */
        .sidebar-section-label{
            font-size:10px;
            font-weight:600;
            letter-spacing:.08em;
            text-transform:uppercase;
            color: var(--sidebar-label);
            padding: 10px 12px 4px;
        }

        /* ── Divider ── */
        .sidebar-divider{
            border-top: 1px solid var(--sidebar-divider);
            margin: 8px 12px;
        }

        /* ── Nav links ── */
        .nav-link-dark{
            display:flex;
            align-items:center;
            gap:10px;
            color: var(--sidebar-text);
            border-radius:10px;
            padding:9px 12px;
            font-size:14px;
            text-decoration:none;
            transition: background .15s, color .15s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            width: 100%;
        }
        /* Bootstrap's .nav.flex-column doesn't set a width on its flex
           children by default, so each .nav-link-dark sizes to its content
           and overflow/ellipsis never engages — width:100% on the link
           plus this on its parent <nav> closes that gap. */
        .sidebar-scroll nav.flex-column{ width: 100%; }
        .nav-link-dark i{ font-size:17px; opacity:.75; flex-shrink:0; color: var(--sidebar-text-muted); }
        /* Arabic labels run longer per word at the same font-size — drop
           slightly so the full label fits on one line instead of wrapping
           into the row below and visually colliding with it. */
        html[lang="ar"] .nav-link-dark{ font-size:13px; gap:8px; }
        html[lang="ar"] .sidebar-section-label{ font-size:9.5px; }
        .nav-link-dark:hover{ background:var(--sidebar-hover); color:var(--accent); }
        .nav-link-dark:hover i{ color:var(--accent); opacity:1; }
        .nav-link-dark.active{
            background: var(--sidebar-active);
            color: var(--sidebar-active-text);
            font-weight:600;
        }
        .nav-link-dark.active i{ opacity:1; color: var(--sidebar-active-text); }

        /* ── Bottom section ── */
        .sidebar-bottom{
            border-top: 1px solid var(--sidebar-divider);
            flex-shrink:0;
        }

        /* ── Avatar ── */
        .sidebar-avatar{
            width:38px; height:38px;
            border-radius:50%;
            background: var(--accent);
            color:#fff;
            font-weight:700;
            font-size:14px;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0;
        }
        .sidebar-user-name{
            color: var(--sidebar-text);
            font-size:13px;
            font-weight:600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .sidebar-user-role{
            color: var(--sidebar-label);
            font-size:11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Language switcher ── */
        .sidebar-lang-btn{
            background: #fff;
            border: 1px solid var(--sidebar-divider);
            border-radius:8px;
            color: var(--sidebar-text);
            font-size:13px;
            font-weight:500;
            padding:7px 0;
            cursor:pointer;
            transition: background .15s, color .15s, border-color .15s;
        }
        .sidebar-lang-btn.active{
            background: var(--accent);
            border-color: var(--accent);
            color:#fff;
        }
        .sidebar-lang-btn:hover:not(.active){ background: var(--sidebar-hover); color:var(--accent); border-color: var(--accent); }

        /* ── Bottom action buttons ── */
        .sidebar-action-btn{
            background: #fff;
            border: 1px solid var(--sidebar-divider);
            border-radius:8px;
            color: var(--sidebar-text);
            font-size:12px;
            padding:8px 6px;
            cursor:pointer;
            transition: background .15s;
            display:flex; align-items:center; justify-content:center; gap:4px;
        }
        .sidebar-action-btn:hover{ background: var(--sidebar-hover); color:var(--accent); border-color: var(--accent); }
        .sidebar-logout-btn{
            background: #fff;
            border: 1px solid rgba(239,68,68,.25);
            border-radius:8px;
            color: #dc2626;
            font-size:12px;
            padding:8px 6px;
            cursor:pointer;
            transition: background .15s;
            display:flex; align-items:center; justify-content:center; gap:4px;
        }
        .sidebar-logout-btn:hover{ background: rgba(239,68,68,.08); color:#dc2626; }

        /* ── Main content ── */
        .topbar{
            background:#fff;
            border:1px solid #eee;
            border-radius: 14px;
            padding: 12px 16px;
        }
        .card-soft{ border:0; border-radius: 14px; }
        .btn-accent{
            background: var(--accent);
            color:#fff; border:0;
            border-radius: 10px;
            padding: 10px 14px;
        }
        .btn-accent:hover{ opacity:.92; color:#fff; }
        .pagination .page-link{ border-radius:10px; padding:6px 12px; }
        .pagination .page-item.active .page-link{
            background:var(--accent); border-color:var(--accent);
        }
    </style>

    @stack('css')
</head>
<body>

<div class="d-flex">
    {{-- Mobile backdrop — tap to close the sidebar --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" id="adminSidebar">
        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">W</div>
            <div class="sidebar-brand-name">Weekend</div>
        </div>

        @include('dashboard.admin.partials.sidebar')
    </aside>

    {{-- Main --}}
    <main class="flex-grow-1 p-4">
        {{-- Topbar --}}
        <div class="topbar d-flex align-items-center justify-content-between mb-4">
            <button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" aria-label="Toggle menu">☰</button>

            <div class="d-flex align-items-center gap-2 w-50 search-box">
                <input class="form-control" placeholder="{{ __('lang.search_ctrl_k') }}" />
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="d-flex gap-1">

</div>
               <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle text-truncate" style="max-width:140px"
            data-bs-toggle="dropdown">
        {{ auth('admin')->user()->name }}
    </button>

    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="dropdown-item text-danger">
                    {{ __('lang.logout') }}
                </button>
            </form>
        </li>
    </ul>
</div>

            </div>
        </div>

        @yield('content')
    </main>
</div>

@stack('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const sidebar  = document.getElementById('adminSidebar');
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

        // Close automatically after tapping a nav link, so mobile users
        // aren't left staring at an open menu after navigating away.
        sidebar?.querySelectorAll('a, button[type="submit"]').forEach(el => {
            el.addEventListener('click', () => {
                if (window.innerWidth < 992) closeSidebar();
            });
        });

        // If the viewport is resized past the mobile breakpoint while the
        // sidebar happens to be open, reset it so desktop layout isn't stuck
        // in the "open" transform state.
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) closeSidebar();
        });
    })();
</script>

</body>
</html>
