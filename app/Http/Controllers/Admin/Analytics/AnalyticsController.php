<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\UniteReservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // ── Date range — default: current year ────────────────────────────────
        $year = (int) ($request->year ?? now()->year);
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        // ── 1. KPI summary cards ──────────────────────────────────────────────
        $totalRevenue = Payment::where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $totalReservations = UniteReservation::whereBetween('reservation_date', [
            $from->toDateString(), $to->toDateString(),
        ])->count();

        $confirmedReservations = UniteReservation::where('status', 'confirmed')
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $cancelledReservations = UniteReservation::where('status', 'cancelled')
            ->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $totalDiscounts = Payment::whereNotNull('discount_amount')
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('discount_amount');

        $totalRefunded = Payment::whereIn('status', ['refunded'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // ── 2. Monthly revenue — 12 bars ──────────────────────────────────────
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyData = collect(range(1, 12))->map(fn ($m) => [
            'month' => Carbon::create($year, $m, 1)->format('M'),
            'revenue' => round((float) ($monthlyRevenue[$m] ?? 0), 2),
        ]);

        // ── 3. Monthly reservations count ─────────────────────────────────────
        $monthlyReservations = UniteReservation::whereBetween('reservation_date', [
            $from->toDateString(), $to->toDateString(),
        ])
            ->selectRaw('MONTH(reservation_date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyResData = collect(range(1, 12))->map(fn ($m) => [
            'month' => Carbon::create($year, $m, 1)->format('M'),
            'count' => (int) ($monthlyReservations[$m] ?? 0),
        ]);

        // ── 4. Reservations by venue type ─────────────────────────────────────
        $byType = UniteReservation::whereBetween('reservation_date', [
            $from->toDateString(), $to->toDateString(),
        ])
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->selectRaw('unites.type, COUNT(*) as total')
            ->groupBy('unites.type')
            ->pluck('total', 'type');

        $typeData = collect(['stadium', 'hall', 'lounge', 'camp'])->map(fn ($t) => [
            'type' => ucfirst($t),
            'count' => (int) ($byType[$t] ?? 0),
        ]);

        // ── 5. Top 5 venues by revenue ────────────────────────────────────────
        $topVenues = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')
            ->whereBetween('payments.created_at', [$from, $to])
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->selectRaw('
                unites.id,
                unites.name,
                unites.type,
                COUNT(payments.id)  as booking_count,
                SUM(payments.amount) as revenue
            ')
            ->groupBy('unites.id', 'unites.name', 'unites.type')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // ── 6. Promo code stats ───────────────────────────────────────────────
        $promoStats = DB::table('promo_code_usages')
            ->join('promo_codes', 'promo_codes.id', '=', 'promo_code_usages.promo_code_id')
            ->whereBetween('promo_code_usages.created_at', [$from, $to])
            ->selectRaw('
                promo_codes.code,
                promo_codes.discount_type,
                promo_codes.discount_value,
                COUNT(*) as uses,
                SUM(promo_code_usages.discount_amount) as total_discount
            ')
            ->groupBy('promo_codes.id', 'promo_codes.code', 'promo_codes.discount_type', 'promo_codes.discount_value')
            ->orderByDesc('total_discount')
            ->limit(10)
            ->get();

        // ── 7. Payment status breakdown ───────────────────────────────────────
        $paymentBreakdown = Payment::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // ── 8. Period type popularity ─────────────────────────────────────────
        $periodBreakdown = UniteReservation::whereBetween('reservation_date', [
            $from->toDateString(), $to->toDateString(),
        ])
            ->selectRaw('period_type, COUNT(*) as count')
            ->groupBy('period_type')
            ->orderByDesc('count')
            ->pluck('count', 'period_type');

        // ── Available years for year picker ───────────────────────────────────
        $availableYears = collect(range(now()->year - 2, now()->year + 1))->reverse()->values();

        return view('dashboard.admin.analytics.index', compact(
            'year',
            'totalRevenue', 'totalReservations', 'confirmedReservations',
            'cancelledReservations', 'totalDiscounts', 'totalRefunded',
            'monthlyData', 'monthlyResData',
            'typeData', 'topVenues',
            'promoStats', 'paymentBreakdown', 'periodBreakdown',
            'availableYears'
        ));
    }
}
