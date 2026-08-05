@php
    $r  = request()->route()->getName();
    $me = auth('admin')->user();

    $showDashboard        = $me && $me->can('dashboard.view');
    $showAdmins           = $me && $me->can('admins.view');
    $showRoles            = $me && $me->can('roles.view');
    $showPerms            = $me && $me->can('permissions.view');
    $showUsers            = $me && $me->can('users.view');
    $showDepartments      = $me && $me->can('departments.view');
    $showPropertyPackages = $me && $me->can('property_packages.view');
    $showAdPackages       = $me && $me->can('ad_packages.view');
    $showSubscriptions    = $me && $me->can('subscriptions.view');
    $showUnits            = $me && $me->can('units.view');
    $showPayments         = $me && $me->can('payments.view');
    $showBroadcast        = $me && $me->hasRole(['super_admin','admin']);
    $showReservations     = $me && $me->can('reservations.view');
    $showReviewers        = $me && $me->hasRole(['super_admin','admin']);
    $showPromoCodes       = true;
    $showServiceFees      = $me && $me->can('service_fees.view');

    $initials = collect(explode(' ', $me?->name ?? 'A'))->map(fn($w) => strtoupper($w[0]))->take(2)->implode('');
@endphp

{{-- ── Scrollable nav area ───────────────────────────────────────────────── --}}
<div class="sidebar-scroll flex-grow-1 overflow-y-auto px-3 pt-2 pb-2">

    {{-- Main section --}}
    @if($showDashboard)
    <div class="sidebar-section-label">{{ __('lang.sidebar_dashboard') }}</div>
    <nav class="nav flex-column gap-1 mb-2">
        <a class="nav-link-dark {{ $r === 'admin.dashboard' ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}">
            <i class="ti ti-layout-dashboard"></i> {{ __('lang.sidebar_dashboard') }}
        </a>
        @if(Route::has('admin.analytics.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.analytics') ? 'active' : '' }}"
           href="{{ route('admin.analytics.index') }}">
            <i class="ti ti-chart-line"></i> {{ __('lang.sidebar_analytics') }}
        </a>
        @endif
        @if(Route::has('admin.reports.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.reports') ? 'active' : '' }}"
           href="{{ route('admin.reports.index') }}">
            <i class="ti ti-report-analytics"></i> {{ __('lang.sidebar_reports') }}
        </a>
        @endif
        @if(Route::has('admin.ads.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.ads') && $r !== 'admin.ads.pending' ? 'active' : '' }}"
           href="{{ route('admin.ads.index') }}">
            <i class="ti ti-speakerphone"></i> {{ __('lang.sidebar_ads') }}
        </a>
        @endif
        @can('ads.review')
            @if(Route::has('admin.ads.pending'))
            <a class="nav-link-dark {{ $r === 'admin.ads.pending' ? 'active' : '' }}"
               href="{{ route('admin.ads.pending') }}">
                <i class="ti ti-clipboard-check"></i> {{ __('lang.sidebar_pending_ads') }}
                @php($pendingCount = \App\Models\Ad::where('approval_status', 'pending')->count())
                @if($pendingCount)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
            @endif
        @endcan
        @if(Route::has('admin.transfers.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.transfers') ? 'active' : '' }}"
           href="{{ route('admin.transfers.index') }}">
            <i class="ti ti-transfer"></i> {{ __('lang.sidebar_transfers') }}
        </a>
        @endif
    </nav>
    @endif

    {{-- Access Control section --}}
    @if($showAdmins || $showRoles || $showPerms || $showUsers || $showDepartments || $showPropertyPackages || $showAdPackages || $showSubscriptions)
    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">{{ __('lang.sidebar_access_control') }}</div>
    <nav class="nav flex-column gap-1 mb-2">
        @if($showUsers)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.users') ? 'active' : '' }}"
           href="{{ route('admin.users.index') }}">
            <i class="ti ti-users"></i> {{ __('lang.sidebar_users') }}
        </a>
        @endif
        @if($showAdmins)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.admins') ? 'active' : '' }}"
           href="{{ route('admin.admins.index') }}">
            <i class="ti ti-user-shield"></i> {{ __('lang.sidebar_admins') }}
        </a>
        @endif
        @if($showRoles)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.roles') ? 'active' : '' }}"
           href="{{ route('admin.roles.index') }}">
            <i class="ti ti-shield-half"></i> {{ __('lang.sidebar_roles') }}
        </a>
        @endif
        @if($showPerms)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.permissions') ? 'active' : '' }}"
           href="{{ route('admin.permissions.index') }}">
            <i class="ti ti-key"></i> {{ __('lang.sidebar_permissions') }}
        </a>
        @endif
    </nav>
    @endif

    {{-- Organization section --}}
    @if($showDepartments || $showPropertyPackages || $showAdPackages || $showSubscriptions || $showBroadcast || $showReservations || $showReviewers || $showPayments || $showPromoCodes || $showUnits)
    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">{{ __('lang.sidebar_organization') }}</div>
    <nav class="nav flex-column gap-1 mb-2">
        @if($showUnits)
        <a class="nav-link-dark {{ str_starts_with($r,'unites') ? 'active' : '' }}"
           href="{{ route('unites.index') }}">
            <i class="ti ti-building-estate"></i> {{ __('lang.sidebar_units') }}
        </a>
        @endif
        @if($showDepartments)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.departments') ? 'active' : '' }}"
           href="{{ route('admin.departments.index') }}">
            <i class="ti ti-sitemap"></i> {{ __('lang.sidebar_departments') }}
        </a>
        @endif
        @if($showReservations && Route::has('admin.reservations.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.reservations') ? 'active' : '' }}"
           href="{{ route('admin.reservations.index') }}">
            <i class="ti ti-calendar-event"></i> {{ __('lang.sidebar_reservations') }}
        </a>
        @endif
        @if($showPayments)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.payments') ? 'active' : '' }}"
           href="{{ route('admin.payments.index') }}">
            <i class="ti ti-credit-card"></i> {{ __('lang.sidebar_payments') }}
        </a>
        @endif
        @if($showServiceFees && Route::has('admin.service-fees.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.service-fees') ? 'active' : '' }}"
           href="{{ route('admin.service-fees.index') }}">
            <i class="ti ti-receipt"></i> {{ __('lang.sidebar_service_fees') }}
        </a>
        @endif
        @if($showPropertyPackages)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.property-packages') ? 'active' : '' }}"
           href="{{ route('admin.property-packages.index') }}">
            <i class="ti ti-package"></i> {{ __('lang.sidebar_property_packages') }}
        </a>
        @endif
        @if($showAdPackages)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.ad-packages') ? 'active' : '' }}"
           href="{{ route('admin.ad-packages.index') }}">
            <i class="ti ti-ad"></i> {{ __('lang.sidebar_ad_packages') }}
        </a>
        @endif
        @if($showSubscriptions)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.subscriptions') ? 'active' : '' }}"
           href="{{ route('admin.subscriptions.index') }}">
            <i class="ti ti-rosette"></i> {{ __('lang.sidebar_subscriptions') }}
        </a>
        @endif
        @if($showPromoCodes)
        <a class="nav-link-dark {{ str_starts_with($r,'admin.promo-codes') ? 'active' : '' }}"
           href="{{ route('admin.promo-codes.index') }}">
            <i class="ti ti-discount"></i> {{ __('lang.sidebar_promo_codes') }}
        </a>
        @endif
        @if($showBroadcast && Route::has('admin.broadcast.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.broadcast') ? 'active' : '' }}"
           href="{{ route('admin.broadcast.index') }}">
            <i class="ti ti-broadcast"></i> {{ __('lang.sidebar_broadcast') }}
        </a>
        @endif
        @if($showReviewers && Route::has('admin.reviewers.index'))
        <a class="nav-link-dark {{ str_starts_with($r,'admin.reviewers') ? 'active' : '' }}"
           href="{{ route('admin.reviewers.index') }}">
            <i class="ti ti-eye"></i> {{ __('lang.sidebar_reviewers') }}
        </a>
        @endif
        <a class="nav-link-dark {{ str_starts_with($r,'admin.service-groups') ? 'active' : '' }}"
           href="{{ route('admin.service-groups.index') }}">
            <i class="ti ti-apps"></i> {{ __('lang.sidebar_service_groups') }}
        </a>
        <a class="nav-link-dark {{ str_starts_with($r,'admin.services') ? 'active' : '' }}"
           href="{{ route('admin.services.index') }}">
            <i class="ti ti-tools"></i> {{ __('lang.sidebar_services') }}
        </a>
        <a class="nav-link-dark {{ str_starts_with($r,'admin.stadium_types') ? 'active' : '' }}"
           href="{{ route('admin.stadium_types.index') }}">
            <i class="ti ti-topology-star"></i> {{ __('lang.sidebar_stadium_types') }}
        </a>
        <a class="nav-link-dark {{ str_starts_with($r,'admin.insurance_policies') ? 'active' : '' }}"
           href="{{ route('admin.insurance_policies.index') }}">
            <i class="ti ti-shield-check"></i> {{ __('lang.sidebar_insurance_policies') }}
        </a>
        <a class="nav-link-dark {{ str_starts_with($r,'admin.suggestions') ? 'active' : '' }}"
           href="{{ route('admin.suggestions.index') }}">
            <i class="ti ti-bulb"></i> {{ __('lang.sidebar_suggestions') }}
        </a>
    </nav>
    @endif

</div>{{-- /sidebar-scroll --}}

{{-- ── Fixed bottom: user info + lang switcher + action buttons ────────────── --}}
<div class="sidebar-bottom px-3 pb-3 pt-2">

    {{-- User card --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="sidebar-avatar">{{ $initials }}</div>
        <div class="lh-sm" style="min-width:0; overflow:hidden">
            <div class="sidebar-user-name">{{ $me?->name }}</div>
            <div class="sidebar-user-role">{{ $me?->getRoleNames()->first() ?? 'admin' }}</div>
        </div>
    </div>

    {{-- Language switcher --}}
    <div class="d-flex gap-2 mb-2">
        <form method="POST" action="{{ route('locale.switch') }}" class="flex-fill">
            @csrf
            <input type="hidden" name="locale" value="ar">
            <button type="submit"
                class="sidebar-lang-btn w-100 {{ app()->getLocale() === 'ar' ? 'active' : '' }}">
                عربي
            </button>
        </form>
        <form method="POST" action="{{ route('locale.switch') }}" class="flex-fill">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit"
                class="sidebar-lang-btn w-100 {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                EN
            </button>
        </form>
    </div>

    {{-- Dashboard + Logout --}}
    <div class="d-flex gap-2">

        <form method="POST" action="{{ route('admin.logout') }}" class="flex-fill">
            @csrf
            <button type="submit" class="sidebar-logout-btn w-100">
                <i class="ti ti-logout me-1"></i> {{ __('lang.logout') }}
            </button>
        </form>
    </div>

</div>
