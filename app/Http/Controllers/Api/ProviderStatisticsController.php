<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unite;
use App\Models\UniteReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderStatisticsController extends Controller
{
    /**
     * GET /api/provider/statistics?year=2026&month=5
     *
     * Returns statistics for the authenticated provider (Sanctum).
     * Matches the JSON shape shown in the mobile app designs.
     */
    public function __invoke(Request $request)
    {
        // Admin can view any provider's stats via ?provider_id=X
        $user = $request->has('_provider_user')
            ? $request->get('_provider_user')
            : $request->user();
        $year = (int) ($request->input('year', now()->year));
        $month = (int) ($request->input('month', now()->month));

        // All unite IDs belonging to this provider
        $uniteIds = Unite::whereHas('department', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->toArray();

        if (empty($uniteIds)) {
            return response()->json([
                'success' => true,
                'statistics' => $this->emptyStatistics($year, $month),
            ]);
        }

        // ── Summary cards ──────────────────────────────────────────────────────
        $sales = (float) DB::table('payments')
            ->live()
            ->where('status', 'paid')
            ->whereIn('reservation_id', UniteReservation::whereIn('unite_id', $uniteIds)->select('id'))
            ->whereYear('created_at', $year)
            ->sum('amount');

        $bookings = UniteReservation::whereIn('unite_id', $uniteIds)
            ->where('status', 'confirmed')
            ->whereYear('reservation_date', $year)
            ->count();

        $totalUnits = count($uniteIds);
        $occupiedUnits = UniteReservation::whereIn('unite_id', $uniteIds)
            ->where('status', 'confirmed')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->distinct('unite_id')
            ->count('unite_id');

        // ── Monthly earnings (12-month chart) ──────────────────────────────────
        $monthlyRaw = DB::table('payments')
            ->live()
            ->where('status', 'paid')
            ->whereIn('reservation_id', UniteReservation::whereIn('unite_id', $uniteIds)->select('id'))
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as m, ROUND(SUM(amount), 2) as total')
            ->groupBy('m')
            ->pluck('total', 'm');

        $arMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'ابريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'اغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $chartData = collect(range(1, 12))->map(fn ($m) => [
            'month_ar' => $arMonths[$m],
            'month_number' => $m,
            'value' => (float) ($monthlyRaw[$m] ?? 0),
        ])->values()->all();

        // Month with highest earnings
        $highestMonth = $monthlyRaw->isEmpty() ? $month
            : (int) $monthlyRaw->sortDesc()->keys()->first();

        // ── Earnings summary ───────────────────────────────────────────────────
        $prevMonthSales = (float) DB::table('payments')
            ->live()
            ->where('status', 'paid')
            ->whereIn('reservation_id', UniteReservation::whereIn('unite_id', $uniteIds)->select('id'))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month === 1 ? 12 : $month - 1)
            ->sum('amount');

        $thisMonthSales = (float) ($monthlyRaw[$month] ?? 0);
        $totalYearSales = $monthlyRaw->sum();

        $totalEarningsPercentage = $totalYearSales > 0
            ? (int) round(($thisMonthSales / $totalYearSales) * 100)
            : 0;

        $increasePercentage = $prevMonthSales > 0
            ? (int) round((($thisMonthSales - $prevMonthSales) / $prevMonthSales) * 100)
            : ($thisMonthSales > 0 ? 100 : 0);

        // ── Most profitable places ─────────────────────────────────────────────
        $placesRaw = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->whereIn('unite_reservations.unite_id', $uniteIds)
            ->whereYear('payments.created_at', $year)
            ->whereMonth('payments.created_at', $month)
            ->selectRaw('unites.id, unites.name, ROUND(SUM(payments.amount),2) as earnings')
            ->groupBy('unites.id', 'unites.name')
            ->orderByDesc('earnings')
            ->limit(10)
            ->get();

        $maxEarnings = $placesRaw->max('earnings') ?: 1;
        $places = $placesRaw->map(fn ($p) => [
            'id' => $p->id,
            'name_ar' => $p->name,
            'earnings' => (float) $p->earnings,
            'currency' => 'SAR',
            'profit_percentage' => round($p->earnings / $maxEarnings, 2),
        ])->values()->all();

        return response()->json([
            'success' => true,
            'statistics' => [
                'summary_cards' => [
                    'sales' => [
                        'value' => $sales,
                        'currency' => 'SAR',
                        'label_ar' => 'المبيعات',
                    ],
                    'bookings' => [
                        'value' => $bookings,
                        'label_ar' => 'الحجوزات',
                    ],
                    'units' => [
                        'occupied' => $occupiedUnits,
                        'total' => $totalUnits,
                        'label_ar' => 'الوحدات',
                    ],
                ],
                'earnings_summary' => [
                    'total_earnings_percentage' => $totalEarningsPercentage,
                    'best_month' => [
                        'name_ar' => $arMonths[$highestMonth] ?? $arMonths[$month],
                        'month_number' => $highestMonth,
                        'increase_percentage' => max(0, $increasePercentage),
                    ],
                ],
                'monthly_earnings' => [
                    'selected_month' => $month,
                    'chart_data' => $chartData,
                    'highest_value_month' => $highestMonth,
                ],
                'most_profitable_places' => [
                    'selected_month' => $month,
                    'places' => $places,
                ],
            ],
        ]);
    }

    private function emptyStatistics(int $year, int $month): array
    {
        $arMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'ابريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'اغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return [
            'summary_cards' => ['sales' => ['value' => 0, 'currency' => 'SAR', 'label_ar' => 'المبيعات'], 'bookings' => ['value' => 0, 'label_ar' => 'الحجوزات'], 'units' => ['occupied' => 0, 'total' => 0, 'label_ar' => 'الوحدات']],
            'earnings_summary' => ['total_earnings_percentage' => 0, 'best_month' => ['name_ar' => $arMonths[$month], 'month_number' => $month, 'increase_percentage' => 0]],
            'monthly_earnings' => ['selected_month' => $month, 'chart_data' => collect(range(1, 12))->map(fn ($m) => ['month_ar' => $arMonths[$m], 'month_number' => $m, 'value' => 0])->values()->all(), 'highest_value_month' => $month],
            'most_profitable_places' => ['selected_month' => $month, 'places' => []],
        ];
    }
}
