@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | Dashboard')

@section('content')
    @php $me = auth('admin')->user(); @endphp
    <div class="mb-4">
        <h3 class="fw-bold mb-1">{{ __('lang.analytics_dashboard') }}</h3>
        <div class="text-muted">{{ __('lang.monitor_admin_users_roles_permissions') }}</div>
    </div>

    {{-- Cards --}}
    <div class="row g-3">
        @if($me && $me->can('users.view'))
        <div class="col-md-4">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">{{ __('lang.total_users') }}</div>
                    <div class="fs-2 fw-bold">{{ $total_admins }}</div>
                    <a class="text-decoration-none" href="{{ route('admin.users.index') }}">→ {{ __('lang.view_more') }}</a>
                </div>
            </div>
        </div>
        @endif

        @if($me && $me->can('roles.view'))
        <div class="col-md-4">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">{{ __('lang.total_roles') }}</div>
                    <div class="fs-2 fw-bold">{{ $total_roles }}</div>
                    <a class="text-decoration-none" href="{{ route('admin.roles.index') }}">→ {{ __('lang.view_more') }}</a>
                </div>
            </div>
        </div>
        @endif

        @if($me && $me->can('permissions.view'))
        <div class="col-md-4">
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">{{ __('lang.total_permissions') }}</div>
                    <div class="fs-2 fw-bold">{{ $total_permissions }}</div>
                    <a class="text-decoration-none" href="{{ route('admin.permissions.index') }}">→ {{ __('lang.view_more') }}</a>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Placeholder sections like charts --}}
    <div class="row g-3 mt-2">
        <div class="col-12">
            {{-- ── Provider Statistics Widget ─────────────────────────────── --}}
            <div class="card card-soft shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0">📊 {{ __('lang.provider_statistics') }}</h5>
                            <div class="text-muted small">{{ __('lang.provider_statistics_sub') }}</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <select id="statProviderSelect" class="form-select form-select-sm" style="width:200px">
                                <option value="">{{ __('lang.select_provider') }}</option>
                                @foreach(\App\Models\User::where('type','provider')->orderBy('name')->get() as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                                @endforeach
                            </select>
                            <select id="statMonthSelect" class="form-select form-select-sm" style="width:120px">
                                @foreach(range(1,12) as $m)
                                    <option value="{{ $m }}" {{ $m == now()->month ? 'selected':'' }}>{{ now()->setMonth($m)->locale(app()->getLocale())->isoFormat('MMMM') }}</option>
                                @endforeach
                            </select>
                            <select id="statYearSelect" class="form-select form-select-sm" style="width:90px">
                                @foreach(range(now()->year, 2025) as $y)
                                    <option value="{{ $y }}" {{ $y == now()->year ? 'selected':'' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Summary cards --}}
                    <div class="row g-3 mb-4" id="statCards">
                        <div class="col-4">
                            <div class="border rounded-3 p-3 text-center">
                                <div style="font-size:28px">💰</div>
                                <div class="fw-bold fs-5" id="statSales">—</div>
                                <div class="text-muted small">{{ __('lang.sales') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-3 text-center">
                                <div style="font-size:28px">📅</div>
                                <div class="fw-bold fs-5" id="statBookings">—</div>
                                <div class="text-muted small">{{ __('lang.bookings') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-3 text-center">
                                <div style="font-size:28px">🏢</div>
                                <div class="fw-bold fs-5" id="statUnits">—</div>
                                <div class="text-muted small">{{ __('lang.units') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts row --}}
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-semibold mb-0">{{ __('lang.your_earnings') }}</h6>
                                    <span class="badge bg-light text-dark border" id="statBestMonth">—</span>
                                </div>
                                <canvas id="statChart" height="120"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="border rounded-3 p-3">
                                <h6 class="fw-semibold mb-2">{{ __('lang.most_profitable_places') }}</h6>
                                <div id="statPlaces"><div class="text-muted small text-center py-4">{{ __('lang.select_provider_to_view_data') }}</div></div>
                            </div>
                        </div>
                    </div>

                    {{-- Earnings summary --}}
                    <div class="mt-3 border rounded-3 p-3" id="statEarningsSummary" style="display:none">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-muted small">{{ __('lang.best_month') }} <span id="statBestMonthName" class="text-warning fw-bold"></span></div>
                                <div class="small">📈 <span id="statIncreasePercent"></span>% {{ __('lang.increase_earnings_this_month') }}</div>
                            </div>
                            <div class="col-auto">
                                <div class="fw-bold text-success" style="font-size:22px"><span id="statEarningsPct"></span>%</div>
                                <div class="text-muted small text-center">{{ __('lang.earnings') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const TOKEN  = '{{ session("api_token","") }}';
    const APIURL = '{{ rtrim(config("app.url"),"/") }}/api/provider/statistics';
    let chart = null;

    async function loadStats() {
        const provider = document.getElementById('statProviderSelect').value;
        const month    = document.getElementById('statMonthSelect').value;
        const year     = document.getElementById('statYearSelect').value;
        if (!provider) return;

        try {
            const res  = await fetch(`/admin/api/provider-statistics/${provider}?month=${month}&year=${year}`);
            const data = await res.json();
            if (!data.success) return;
            renderStats(data.statistics);
        } catch(e) { console.error(e); }
    }

    function renderStats(s) {
        // Summary cards
        document.getElementById('statSales').textContent    = Number(s.summary_cards.sales.value).toLocaleString('ar-SA') + ' ر.س';
        document.getElementById('statBookings').textContent = s.summary_cards.bookings.value;
        document.getElementById('statUnits').textContent    = s.summary_cards.units.occupied + '/' + s.summary_cards.units.total;

        // Best month badge
        const bm = s.earnings_summary.best_month;
        document.getElementById('statBestMonth').textContent = bm.name_ar;

        // Earnings summary
        const eSec = document.getElementById('statEarningsSummary');
        eSec.style.display = '';
        document.getElementById('statBestMonthName').textContent  = bm.name_ar;
        document.getElementById('statIncreasePercent').textContent = bm.increase_percentage;
        document.getElementById('statEarningsPct').textContent    = s.earnings_summary.total_earnings_percentage;

        // Monthly chart
        const labels = s.monthly_earnings.chart_data.map(d => d.month_ar);
        const values = s.monthly_earnings.chart_data.map(d => d.value);
        const hm     = s.monthly_earnings.highest_value_month - 1;
        const bgColors = values.map((_, i) => i === hm ? '#1a5c4a' : '#2d8c6f');

        if (chart) chart.destroy();
        chart = new Chart(document.getElementById('statChart'), {
            type: 'bar',
            data: { labels, datasets: [{ data: values, backgroundColor: bgColors, borderRadius: 6 }] },
            options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}, responsive:true }
        });

        // Most profitable places
        const container = document.getElementById('statPlaces');
        if (!s.most_profitable_places.places.length) {
            container.innerHTML = '<div class="text-muted small text-center py-3">لا توجد بيانات لهذا الشهر</div>';
            return;
        }
        container.innerHTML = s.most_profitable_places.places.map(p => `
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold">${p.name_ar}</span>
                    <span class="text-muted">${p.earnings.toLocaleString('ar-SA')} ر.س</span>
                </div>
                <div class="progress" style="height:8px;border-radius:4px">
                    <div class="progress-bar" style="width:${Math.round(p.profit_percentage*100)}%;background:#2d8c6f"></div>
                </div>
            </div>`).join('');
    }

    ['statProviderSelect','statMonthSelect','statYearSelect'].forEach(id =>
        document.getElementById(id).addEventListener('change', loadStats)
    );
})();
</script>
@endpush
