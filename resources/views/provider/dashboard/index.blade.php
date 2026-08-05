@extends('provider.layouts.app')
@section('title','Weekend | Provider Dashboard')
@section('content')
<h4 class="fw-bold mb-4">Dashboard</h4>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'My Venues',       'value'=>$stats['total_venues'],       'color'=>'primary',  'icon'=>'🏢'],
        ['label'=>'Pending Approval', 'value'=>$stats['pending_approvals'],  'color'=>'warning',  'icon'=>'⏳'],
        ['label'=>'Confirmed Today',  'value'=>$stats['confirmed_today'],    'color'=>'success',  'icon'=>'✅'],
        ['label'=>__('lang.kpi_revenue'),'value'=>'SAR '.number_format($stats['revenue_this_month'],0),'color'=>'info','icon'=>'💰'],
    ] as $s)
    <div class="col-6 col-md-3">
        <div class="card card-soft text-center py-3 shadow-sm">
            <div class="fs-4">{{ $s['icon'] }}</div>
            <div class="fw-bold fs-5 text-{{ $s['color'] }}">{{ $s['value'] }}</div>
            <div class="text-muted small">{{ $s['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Provider Self-Statistics Widget ────────────────────────────────────────── --}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">📊 إحصائياتك</h6>
            <div class="d-flex gap-2">
                <select id="psMonthSelect" class="form-select form-select-sm" style="width:120px">
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ $m == now()->month ? 'selected':'' }}>
                            {{ now()->setMonth($m)->locale('ar')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>
                <select id="psYearSelect" class="form-select form-select-sm" style="width:90px">
                    @foreach(range(now()->year, 2025) as $y)
                        <option value="{{ $y }}" {{ $y == now()->year ? 'selected':'' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="border rounded-3 p-2 text-center">
                    <div class="fw-bold" id="psSales">—</div>
                    <div class="text-muted" style="font-size:11px">المبيعات</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border rounded-3 p-2 text-center">
                    <div class="fw-bold" id="psBookings">—</div>
                    <div class="text-muted" style="font-size:11px">الحجوزات</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border rounded-3 p-2 text-center">
                    <div class="fw-bold" id="psUnits">—</div>
                    <div class="text-muted" style="font-size:11px">الوحدات</div>
                </div>
            </div>
        </div>

        {{-- Earnings summary banner --}}
        <div class="border rounded-3 p-2 mb-3 d-flex justify-content-between align-items-center"
             style="background:#f6f5fb">
            <div>
                <div class="text-muted small">أكبر ربح في شهر
                    <span id="psBestMonth" class="fw-bold" style="color:var(--accent)">—</span>
                </div>
                <div class="small">📈 <span id="psIncrease">0</span>% زيادة أرباح لهذا الشهر</div>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5 text-success"><span id="psEarningsPct">0</span>%</div>
                <div class="text-muted" style="font-size:11px">أرباح</div>
            </div>
        </div>

        {{-- Chart + places --}}
        <div class="row g-3">
            <div class="col-lg-7">
                <canvas id="psChart" height="130"></canvas>
            </div>
            <div class="col-lg-5">
                <h6 class="fw-semibold small mb-2">الأماكن الأكثر ربحًا</h6>
                <div id="psPlaces">
                    <div class="text-muted small text-center py-3">جاري التحميل…</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Pending approvals --}}
    <div class="col-lg-6">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ __('lang.pending_approvals_title') }}</h6>
                    <a href="{{ route('provider.approvals') }}" class="small text-muted">View all →</a>
                </div>
                @forelse($pendingApprovals as $res)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold small">{{ $res->user?->name }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $res->unite?->name }} · {{ $res->reservation_date?->format('d M Y') }} · {{ ucfirst(str_replace('_',' ',$res->period_type)) }}</div>
                            <div class="text-muted" style="font-size:11px">SAR {{ number_format($res->price,2) }}{{ $res->guest_count ? ' · '.$res->guest_count.' guests' : '' }}</div>
                        </div>
                        <div class="d-flex gap-1">
                            <form action="/api/reservations/{{ $res->id }}/approve" method="POST" class="d-inline approve-form">
                                @csrf
                                <button class="btn btn-sm btn-success py-0 px-2" style="font-size:11px">{{ __('lang.accept') }}</button>
                            </form>
                            <form action="/api/reservations/{{ $res->id }}/reject" method="POST" class="d-inline reject-form">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px">{{ __('lang.reject') }}</button>
                            </form>
                        </div>
                    </div>
                    @if($res->notes)
                        <div class="text-muted mt-1" style="font-size:11px">📝 {{ $res->notes }}</div>
                    @endif
                </div>
                @empty
                    <div class="text-muted small">No pending approvals.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Upcoming bookings --}}
    <div class="col-lg-6">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.upcoming_bookings') }}</h6>
                @forelse($upcomingBookings as $res)
                <div class="border rounded p-2 mb-2">
                    <div class="fw-semibold small">{{ $res->user?->name }}</div>
                    <div class="text-muted" style="font-size:11px">{{ $res->unite?->name }} · {{ $res->reservation_date?->format('d M Y') }} · {{ ucfirst(str_replace('_',' ',$res->period_type)) }}</div>
                    <div class="text-muted" style="font-size:11px">SAR {{ number_format($res->price,2) }}{{ $res->guest_count ? ' · '.$res->guest_count.' guests' : '' }}</div>
                </div>
                @empty
                    <div class="text-muted small">No upcoming bookings.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Provider self-statistics widget
(function(){
    const YEAR  = new Date().getFullYear();
    const MONTH = new Date().getMonth() + 1;

    async function loadSelfStats(month, year) {
        try {
            const res  = await fetch(`/provider/api/statistics?month=${month}&year=${year}`);
            const data = await res.json();
            if (!data.success) return;
            renderProviderStats(data.statistics);
        } catch(e) { console.error('Stats error', e); }
    }

    function renderProviderStats(s) {
        const el = id => document.getElementById(id);

        el('psSales').textContent    = Number(s.summary_cards.sales.value).toLocaleString('ar-SA') + ' ر.س';
        el('psBookings').textContent = s.summary_cards.bookings.value;
        el('psUnits').textContent    = s.summary_cards.units.occupied + '/' + s.summary_cards.units.total;

        const bm = s.earnings_summary.best_month;
        el('psBestMonth').textContent    = bm.name_ar;
        el('psIncrease').textContent     = bm.increase_percentage;
        el('psEarningsPct').textContent  = s.earnings_summary.total_earnings_percentage;

        const labels   = s.monthly_earnings.chart_data.map(d => d.month_ar);
        const values   = s.monthly_earnings.chart_data.map(d => d.value);
        const hm       = s.monthly_earnings.highest_value_month - 1;
        const bgColors = values.map((_, i) => i === hm ? '#1a5c4a' : '#2d8c6f');

        if (window._psChart) window._psChart.destroy();
        window._psChart = new Chart(el('psChart'), {
            type: 'bar',
            data: { labels, datasets: [{ data: values, backgroundColor: bgColors, borderRadius: 5 }] },
            options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}, responsive:true }
        });

        const places = document.getElementById('psPlaces');
        if (!s.most_profitable_places.places.length) {
            places.innerHTML = '<div class="text-muted small text-center py-2">لا توجد بيانات</div>';
        } else {
            places.innerHTML = s.most_profitable_places.places.map(p => `
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-semibold">${p.name_ar}</span>
                        <span class="text-muted">${p.earnings.toLocaleString('ar-SA')} ر.س</span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:3px">
                        <div class="progress-bar" style="width:${Math.round(p.profit_percentage*100)}%;background:#2d8c6f"></div>
                    </div>
                </div>`).join('');
        }
    }

    document.getElementById('psMonthSelect').addEventListener('change', function(){
        loadSelfStats(this.value, document.getElementById('psYearSelect').value);
    });
    document.getElementById('psYearSelect').addEventListener('change', function(){
        loadSelfStats(document.getElementById('psMonthSelect').value, this.value);
    });

    loadSelfStats(MONTH, YEAR);
})();

// Submit approve/reject forms via fetch to avoid page redirect to API URL
document.querySelectorAll('.approve-form, .reject-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.disabled = true;
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const res = await fetch(this.action, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json',
                      'Authorization': 'Bearer {{ session("api_token","") }}'}
        });
        const data = await res.json();
        if (data.success || data.message) {
            this.closest('.border.rounded').innerHTML =
                `<div class="small text-muted">${data.message ?? 'Done'}</div>`;
        }
    });
});
</script>
@endpush
@endsection
