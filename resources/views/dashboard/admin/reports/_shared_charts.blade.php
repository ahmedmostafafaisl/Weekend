{{--
    _shared_charts.blade.php
    Included by every admin report view BEFORE the view's own <script> block.
    Provides: Chart.js CDN, shared defaults, and the three helper functions
    used by all report views:  barChart()  lineChart()  doughnutChart()
--}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ── Shared chart defaults ────────────────────────────────────────────── */
const ACCENT   = '#6f00ff';
const PALETTE  = [
    '#6f00ff', '#a855f7', '#22c55e', '#f59e0b',
    '#ef4444', '#3b82f6', '#ec4899', '#14b8a6',
    '#f97316', '#8b5cf6', '#06b6d4', '#84cc16',
];

Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", "Segoe UI Arabic", sans-serif';
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#888';

/* ── Helper: vertical bar chart ──────────────────────────────────────── */
function barChart(canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: label ?? '',
                data,
                backgroundColor: ACCENT + '33',
                borderColor: ACCENT,
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: !!label } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { maxTicksLimit: 6 },
                    grid: { color: '#f0f0f0' },
                },
                x: { grid: { display: false } }
            }
        }
    });
}

/* ── Helper: line chart ──────────────────────────────────────────────── */
function lineChart(canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: label ?? '',
                data,
                borderColor: ACCENT,
                backgroundColor: ACCENT + '18',
                pointBackgroundColor: ACCENT,
                pointRadius: 4,
                fill: true,
                tension: 0.35,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: !!label } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { maxTicksLimit: 6 },
                    grid: { color: '#f0f0f0' },
                },
                x: { grid: { display: false } }
            }
        }
    });
}

/* ── Helper: doughnut / pie chart ────────────────────────────────────── */
function doughnutChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: PALETTE.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 12 }
                }
            }
        }
    });
}
</script>
