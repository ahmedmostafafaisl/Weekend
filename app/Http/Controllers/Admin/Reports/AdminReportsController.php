<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ProviderTransfer;
use App\Models\Subscription;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportsController extends Controller
{
    // ── Overview (landing page) ───────────────────────────────────────────────
    public function index(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);
        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth()->endOfDay();

        // KPIs
        $kpi = [
            'revenue' => (float) Payment::paid()->whereBetween('created_at', [$from, $to])->sum('amount'),
            'reservations' => UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])->count(),
            'new_users' => User::whereBetween('created_at', [$from, $to])->count(),
            'active_venues' => Unite::where('status', 'active')->count(),
            'subscriptions' => Subscription::where('status', 'active')->count(),
            'pending_approvals' => UniteReservation::where('status', 'pending_approval')->count(),
            'transfers_paid' => (float) ProviderTransfer::where('status', 'completed')->whereBetween('transferred_at', [$from, $to])->sum('net_amount'),
            'total_providers' => User::where('type', 'provider')->count(),
        ];

        // 12-month revenue trend for current year
        $trend = Payment::paid()
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as m, SUM(amount) as t')
            ->groupBy('m')->pluck('t', 'm');

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $availableYears = range(now()->year, max(2024, now()->year - 3));
        $availableMonths = array_combine(range(1, 12), $monthNames);

        return view('dashboard.admin.reports.index', compact(
            'kpi', 'trend', 'year', 'month', 'monthNames', 'availableYears', 'availableMonths'
        ));
    }

    // ── Revenue Report ────────────────────────────────────────────────────────
    public function revenue(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $groupBy = $request->group_by ?? 'month'; // month | week | day | gateway
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        // Monthly breakdown
        $monthly = Payment::paid()->whereBetween('created_at', [$from, $to])
            ->selectRaw('MONTH(created_at) as m, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('m')->pluck('total', 'm');

        // By gateway
        $byGateway = Payment::paid()->whereBetween('created_at', [$from, $to])
            ->selectRaw('payment_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_type')->get();

        // By venue type
        $byVenueType = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->selectRaw('unites.type, SUM(payments.amount) as total, COUNT(payments.id) as count')
            ->groupBy('unites.type')->get();

        // Top 10 venues
        $topVenues = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->selectRaw('unites.id, unites.name, unites.type, SUM(payments.amount) as revenue, COUNT(payments.id) as bookings')
            ->groupBy('unites.id', 'unites.name', 'unites.type')
            ->orderByDesc('revenue')->limit(10)->get();

        // Comparison with previous year
        $prevYearRevenue = Payment::paid()->whereYear('created_at', $year - 1)->sum('amount');
        $thisYearRevenue = Payment::paid()->whereYear('created_at', $year)->sum('amount');
        $growthPct = $prevYearRevenue > 0 ? round((($thisYearRevenue - $prevYearRevenue) / $prevYearRevenue) * 100, 1) : null;

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('dashboard.admin.reports.revenue', compact(
            'year', 'monthly', 'byGateway', 'byVenueType', 'topVenues',
            'thisYearRevenue', 'prevYearRevenue', 'growthPct', 'availableYears'
        ));
    }

    // ── Reservations Report ───────────────────────────────────────────────────
    public function reservations(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        // Status breakdown
        $byStatus = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

        // Period type breakdown
        $byPeriod = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('period_type, COUNT(*) as count')->groupBy('period_type')->pluck('count', 'period_type');

        // Monthly count
        $monthly = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('MONTH(reservation_date) as m, COUNT(*) as count')->groupBy('m')->pluck('count', 'm');

        // Day of week heatmap
        $byDay = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('DAYNAME(reservation_date) as day, COUNT(*) as count')
            ->groupBy('day')->pluck('count', 'day');

        // Top venues by bookings
        $topVenues = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->with('unite:id,name,type')
            ->selectRaw('unite_id, COUNT(*) as count, SUM(price) as revenue')
            ->groupBy('unite_id')->orderByDesc('count')->limit(10)->get();

        // Guest count stats
        $guestStats = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('guest_count')
            ->selectRaw('AVG(guest_count) as avg, MAX(guest_count) as max, SUM(guest_count) as total')
            ->first();

        // Cancellation rate
        $total = UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])->count();
        $cancelled = (int) ($byStatus['cancelled'] ?? 0);
        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('dashboard.admin.reports.reservations', compact(
            'year', 'byStatus', 'byPeriod', 'monthly', 'byDay',
            'topVenues', 'guestStats', 'total', 'cancelRate', 'availableYears'
        ));
    }

    // ── Users Report ──────────────────────────────────────────────────────────
    public function users(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        // New registrations by month
        $monthly = User::whereBetween('created_at', [$from, $to])
            ->selectRaw('MONTH(created_at) as m, type, COUNT(*) as count')
            ->groupBy('m', 'type')->get()
            ->groupBy('m');

        // Totals
        $totals = User::selectRaw('type, status, COUNT(*) as count')->groupBy('type', 'status')->get();

        // Top spenders
        $topSpenders = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')
            ->whereBetween('payments.created_at', [$from, $to])
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->selectRaw('users.id, users.name, users.email, users.type, SUM(payments.amount) as spent, COUNT(payments.id) as transactions')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.type')
            ->orderByDesc('spent')->limit(10)->get();

        // Top providers by revenue
        $topProviders = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->join('departments', 'departments.id', '=', 'unites.department_id')
            ->join('users', 'users.id', '=', 'departments.user_id')
            ->selectRaw('users.id, users.name, SUM(payments.amount) as revenue, COUNT(payments.id) as bookings')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')->limit(10)->get();

        // Nation breakdown
        $byNation = User::selectRaw('nation, COUNT(*) as count')->groupBy('nation')->pluck('count', 'nation');

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('dashboard.admin.reports.users', compact(
            'year', 'monthly', 'totals', 'topSpenders', 'topProviders', 'byNation', 'availableYears'
        ));
    }

    // ── Subscriptions Report ──────────────────────────────────────────────────
    public function subscriptions(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $byStatus = Subscription::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

        $byType = Subscription::whereBetween('created_at', [$from, $to])
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as revenue')
            ->groupBy('type')->get();

        $monthly = Subscription::whereBetween('created_at', [$from, $to])
            ->selectRaw('MONTH(created_at) as m, COUNT(*) as count, SUM(amount) as revenue')
            ->groupBy('m')->pluck('revenue', 'm');

        $revenue = (float) Payment::paid()->whereBetween('created_at', [$from, $to])
            ->whereNotNull('subscription_id')->sum('amount');

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('dashboard.admin.reports.subscriptions', compact(
            'year', 'byStatus', 'byType', 'monthly', 'revenue', 'availableYears'
        ));
    }

    // ── Venues Report ─────────────────────────────────────────────────────────
    public function venues(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $byType = Unite::selectRaw('type, status, COUNT(*) as count')->groupBy('type', 'status')->get();
        $byStatus = Unite::selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

        // Occupancy rate per venue (confirmed / total capacity days)
        $occupancy = DB::table('unites')
            ->leftJoin('unite_reservations', fn ($j) => $j
                ->on('unites.id', '=', 'unite_reservations.unite_id')
                ->where('unite_reservations.status', 'confirmed')
                ->whereBetween('unite_reservations.reservation_date', [$from->toDateString(), $to->toDateString()])
            )
            ->selectRaw('unites.id, unites.name, unites.type, COUNT(unite_reservations.id) as bookings')
            ->groupBy('unites.id', 'unites.name', 'unites.type')
            ->orderByDesc('bookings')->limit(15)->get();

        // Most viewed venues
        $mostViewed = Unite::withCount('views')->orderByDesc('views_count')->limit(10)->get(['id', 'name', 'type']);

        // Highest rated
        $highestRated = Unite::withAvg('ratings as avg_rating', 'rating')
            ->withCount('ratings')
            ->having('ratings_count', '>=', 3)
            ->orderByDesc('avg_rating')->limit(10)->get(['id', 'name', 'type']);

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('dashboard.admin.reports.venues', compact(
            'year', 'byType', 'byStatus', 'occupancy', 'mostViewed', 'highestRated', 'availableYears'
        ));
    }

    // ── Transfers Report ──────────────────────────────────────────────────────
    public function transfers(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $byStatus = ProviderTransfer::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count, SUM(net_amount) as total')
            ->groupBy('status')->get()->keyBy('status');

        $byMethod = ProviderTransfer::whereBetween('created_at', [$from, $to])
            ->selectRaw('method, COUNT(*) as count, SUM(net_amount) as total')
            ->groupBy('method')->get();

        $monthly = ProviderTransfer::where('status', 'completed')
            ->whereBetween('transferred_at', [$from, $to])
            ->selectRaw('MONTH(transferred_at) as m, SUM(net_amount) as total')
            ->groupBy('m')->pluck('total', 'm');

        $topProviders = ProviderTransfer::where('status', 'completed')
            ->whereBetween('transferred_at', [$from, $to])
            ->with('provider:id,name')
            ->selectRaw('user_id, SUM(net_amount) as total, COUNT(*) as count')
            ->groupBy('user_id')->orderByDesc('total')->limit(10)->get();

        $totals = [
            'gross' => (float) ProviderTransfer::whereBetween('created_at', [$from, $to])->sum('amount'),
            'tax' => (float) ProviderTransfer::whereBetween('created_at', [$from, $to])->sum('tax_amount'),
            'fee' => (float) ProviderTransfer::whereBetween('created_at', [$from, $to])->sum('platform_fee'),
            'net_paid' => (float) ProviderTransfer::where('status', 'completed')->whereBetween('transferred_at', [$from, $to])->sum('net_amount'),
            'pending' => (float) ProviderTransfer::where('status', 'pending')->sum('net_amount'),
        ];

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('dashboard.admin.reports.transfers', compact(
            'year', 'byStatus', 'byMethod', 'monthly', 'topProviders', 'totals', 'availableYears'
        ));
    }

    // ── CSV Export ────────────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $type = $request->type ?? 'revenue';
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"weekend_{$type}_{$year}.csv\""];

        return response()->streamDownload(function () use ($type, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            match ($type) {
                'revenue' => $this->exportRevenue($out, $from, $to),
                'reservations' => $this->exportReservations($out, $from, $to),
                'users' => $this->exportUsers($out, $from, $to),
                'transfers' => $this->exportTransfers($out, $from, $to),
                default => null,
            };
            fclose($out);
        }, "weekend_{$type}_{$year}.csv", $headers);
    }

    private function exportRevenue($out, $from, $to): void
    {
        fputcsv($out, ['Reference', 'Date', 'Gateway', 'Amount', 'Status', 'Venue', 'Customer']);
        Payment::paid()->whereBetween('created_at', [$from, $to])
            ->with(['reservation.unite', 'reservation.user'])
            ->chunk(500, function ($payments) use ($out) {
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p->reference_id, $p->created_at->format('Y-m-d'),
                        $p->payment_type, $p->amount, $p->status,
                        $p->reservation?->unite?->name ?? '—',
                        $p->reservation?->user?->name ?? '—',
                    ]);
                }
            });
    }

    private function exportReservations($out, $from, $to): void
    {
        fputcsv($out, ['ID', 'Date', 'Venue', 'Type', 'Period', 'Price', 'Status', 'Customer', 'Guests']);
        UniteReservation::whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->with(['unite', 'user'])
            ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [$r->id, $r->reservation_date, $r->unite?->name, $r->unite?->type,
                        $r->period_type, $r->price, $r->status, $r->user?->name, $r->guest_count ?? 0]);
                }
            });
    }

    private function exportUsers($out, $from, $to): void
    {
        fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Type', 'Status', 'Nation', 'Joined']);
        User::whereBetween('created_at', [$from, $to])->chunk(500, function ($users) use ($out) {
            foreach ($users as $u) {
                fputcsv($out, [$u->id, $u->name, $u->email, $u->phone, $u->type, $u->status, $u->nation, $u->created_at->format('Y-m-d')]);
            }
        });
    }

    private function exportTransfers($out, $from, $to): void
    {
        fputcsv($out, ['ID', 'Provider', 'Amount', 'Tax', 'Fee', 'Net', 'Method', 'Status', 'Reference', 'Transferred At']);
        ProviderTransfer::whereBetween('created_at', [$from, $to])->with('provider')
            ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $t) {
                    fputcsv($out, [$t->id, $t->provider?->name, $t->amount, $t->tax_amount,
                        $t->platform_fee, $t->net_amount, $t->method, $t->status,
                        $t->reference ?? '—', $t->transferred_at?->format('Y-m-d') ?? '—']);
                }
            });
    }
}
