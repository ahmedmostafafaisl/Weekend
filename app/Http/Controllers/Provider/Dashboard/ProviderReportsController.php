<?php

namespace App\Http\Controllers\Provider\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ProviderTransfer;
use App\Models\Unite;
use App\Models\UniteReservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderReportsController extends Controller
{
    private function uniteIds($user): array
    {
        return Unite::whereHas('department', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')->toArray();
    }

    // ── Overview ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->uniteIds($user);
        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);
        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth()->endOfDay();

        $kpi = [
            'revenue' => (float) DB::table('payments')
                ->live()
                ->where('status', 'paid')->whereBetween('created_at', [$from, $to])
                ->whereIn('reservation_id', UniteReservation::whereIn('unite_id', $unitIds)->select('id'))
                ->sum('amount'),
            'bookings' => UniteReservation::whereIn('unite_id', $unitIds)
                ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])->count(),
            'pending_approvals' => UniteReservation::whereIn('unite_id', $unitIds)->where('status', 'pending_approval')->count(),
            'active_venues' => Unite::whereIn('id', $unitIds)->where('status', 'active')->count(),
            'total_transfers' => (float) ProviderTransfer::where('user_id', $user->id)->where('status', 'completed')->sum('net_amount'),
            'pending_transfers' => (float) ProviderTransfer::where('user_id', $user->id)->where('status', 'pending')->sum('net_amount'),
            'avg_rating' => round((float) DB::table('unite_ratings')->whereIn('unite_id', $unitIds)->avg('rating') ?? 0, 1),
            'total_views' => DB::table('unite_views')->whereIn('unite_id', $unitIds)->count(),
        ];

        // 12-month trend
        $trend = DB::table('payments')
            ->live()
            ->where('status', 'paid')->whereYear('created_at', $year)
            ->whereIn('reservation_id', UniteReservation::whereIn('unite_id', $unitIds)->select('id'))
            ->selectRaw('MONTH(created_at) as m, SUM(amount) as t')
            ->groupBy('m')->pluck('t', 'm');

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $availableYears = range(now()->year, max(2024, now()->year - 3));
        $availableMonths = array_combine(range(1, 12), $monthNames);

        return view('provider.reports.index', compact(
            'kpi', 'trend', 'year', 'month', 'monthNames', 'availableYears', 'availableMonths'
        ));
    }

    // ── Revenue ───────────────────────────────────────────────────────────────
    public function revenue(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->uniteIds($user);
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $resIds = UniteReservation::whereIn('unite_id', $unitIds)->select('id');

        $monthly = DB::table('payments')
            ->live()
            ->where('status', 'paid')->whereBetween('created_at', [$from, $to])
            ->whereIn('reservation_id', $resIds)
            ->selectRaw('MONTH(created_at) as m, SUM(amount) as total')
            ->groupBy('m')->pluck('total', 'm');

        $byVenue = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->whereIn('unites.id', $unitIds)
            ->selectRaw('unites.id, unites.name, unites.type, SUM(payments.amount) as revenue, COUNT(payments.id) as bookings')
            ->groupBy('unites.id', 'unites.name', 'unites.type')
            ->orderByDesc('revenue')->get();

        $byPeriod = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->whereIn('unite_reservations.unite_id', $unitIds)
            ->selectRaw('unite_reservations.period_type, SUM(payments.amount) as total, COUNT(payments.id) as count')
            ->groupBy('unite_reservations.period_type')->get();

        $total = DB::table('payments')->live()->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])->whereIn('reservation_id', $resIds)->sum('amount');

        $prevTotal = DB::table('payments')->live()->where('status', 'paid')
            ->whereYear('created_at', $year - 1)->whereIn('reservation_id', UniteReservation::whereIn('unite_id', $unitIds)->select('id'))->sum('amount');

        $growthPct = $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100, 1) : null;

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('provider.reports.revenue', compact(
            'year', 'monthly', 'byVenue', 'byPeriod', 'total', 'prevTotal', 'growthPct', 'availableYears'
        ));
    }

    // ── Reservations ──────────────────────────────────────────────────────────
    public function reservations(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->uniteIds($user);
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $byStatus = UniteReservation::whereIn('unite_id', $unitIds)
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

        $monthly = UniteReservation::whereIn('unite_id', $unitIds)
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('MONTH(reservation_date) as m, COUNT(*) as count')->groupBy('m')->pluck('count', 'm');

        $byPeriod = UniteReservation::whereIn('unite_id', $unitIds)
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('period_type, COUNT(*) as count')->groupBy('period_type')->pluck('count', 'period_type');

        $byVenue = UniteReservation::whereIn('unite_id', $unitIds)
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->with('unite:id,name,type')
            ->selectRaw('unite_id, COUNT(*) as count, SUM(price) as revenue')
            ->groupBy('unite_id')->orderByDesc('count')->get();

        $total = UniteReservation::whereIn('unite_id', $unitIds)
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])->count();
        $cancelRate = $total > 0 ? round((($byStatus['cancelled'] ?? 0) / $total) * 100, 1) : 0;

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('provider.reports.reservations', compact(
            'year', 'byStatus', 'monthly', 'byPeriod', 'byVenue', 'total', 'cancelRate', 'availableYears'
        ));
    }

    // ── Venues ────────────────────────────────────────────────────────────────
    public function venues(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->uniteIds($user);
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $venues = Unite::whereIn('id', $unitIds)
            ->withCount([
                'views',
                'ratings',
                'reservations as confirmed_bookings' => fn ($q) => $q->where('status', 'confirmed')
                    ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()]),
            ])
            ->withAvg('ratings as avg_rating', 'rating')
            ->get();

        $revenueByVenue = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->whereIn('unite_reservations.unite_id', $unitIds)
            ->selectRaw('unite_reservations.unite_id, SUM(payments.amount) as revenue')
            ->groupBy('unite_reservations.unite_id')->pluck('revenue', 'unite_id');

        $availableYears = range(now()->year, max(2024, now()->year - 3));

        return view('provider.reports.venues', compact('year', 'venues', 'revenueByVenue', 'availableYears'));
    }

    // ── CSV Export ────────────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $unitIds = $this->uniteIds($user);
        $type = $request->type ?? 'revenue';
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        return response()->streamDownload(function () use ($type, $unitIds, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if ($type === 'revenue') {
                fputcsv($out, ['Reference', 'Date', 'Venue', 'Period', 'Amount', 'Gateway', 'Customer']);
                $resIds = UniteReservation::whereIn('unite_id', $unitIds)->select('id');
                Payment::paid()->whereBetween('created_at', [$from, $to])
                    ->whereIn('reservation_id', $resIds)
                    ->with(['reservation.unite', 'reservation.user'])
                    ->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $p) {
                            fputcsv($out, [$p->reference_id, $p->created_at->format('Y-m-d'),
                                $p->reservation?->unite?->name ?? '—', $p->reservation?->period_type ?? '—',
                                $p->amount, $p->payment_type, $p->reservation?->user?->name ?? '—']);
                        }
                    });
            } else {
                fputcsv($out, ['ID', 'Date', 'Venue', 'Period', 'Price', 'Status', 'Customer', 'Guests', 'Notes']);
                UniteReservation::whereIn('unite_id', $unitIds)
                    ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
                    ->with(['unite', 'user'])->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $r) {
                            fputcsv($out, [$r->id, $r->reservation_date, $r->unite?->name, $r->period_type,
                                $r->price, $r->status, $r->user?->name, $r->guest_count ?? 0, $r->notes ?? '—']);
                        }
                    });
            }
            fclose($out);
        }, "provider_{$type}_{$year}.csv", ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"provider_{$type}_{$year}.csv\""]);
    }
}
