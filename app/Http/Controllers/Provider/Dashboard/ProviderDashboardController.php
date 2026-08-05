<?php

namespace App\Http\Controllers\Provider\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Unite;
use App\Models\UniteReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderDashboardController extends Controller
{
    private function providerUniteIds($user): array
    {
        return Unite::whereHas('department', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->toArray();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->providerUniteIds($user);

        $stats = [
            'total_venues' => count($unitIds),
            'pending_approvals' => UniteReservation::whereIn('unite_id', $unitIds)
                ->where('status', 'pending_approval')->count(),
            'confirmed_today' => UniteReservation::whereIn('unite_id', $unitIds)
                ->where('status', 'confirmed')
                ->whereDate('reservation_date', today())->count(),
            'revenue_this_month' => (float) DB::table('payments')
                ->live()
                ->where('status', 'paid')
                ->whereIn('reservation_id',
                    UniteReservation::whereIn('unite_id', $unitIds)->select('id')
                )
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        $pendingApprovals = UniteReservation::with(['unite', 'user', 'payment'])
            ->whereIn('unite_id', $unitIds)
            ->where('status', 'pending_approval')
            ->latest()->take(10)->get();

        $upcomingBookings = UniteReservation::with(['unite', 'user'])
            ->whereIn('unite_id', $unitIds)
            ->where('status', 'confirmed')
            ->whereDate('reservation_date', '>=', today())
            ->orderBy('reservation_date')
            ->take(10)->get();

        $venues = Unite::with(['images', 'prices'])
            ->whereHas('department', fn ($q) => $q->where('user_id', $user->id))
            ->withCount(['reservations as confirmed_count' => fn ($q) => $q->where('status', 'confirmed')])
            ->latest()->get();

        return view('provider.dashboard.index', compact(
            'stats', 'pendingApprovals', 'upcomingBookings', 'venues'
        ));
    }

    public function approvals(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->providerUniteIds($user);

        $reservations = UniteReservation::with(['unite', 'user', 'payment'])
            ->whereIn('unite_id', $unitIds)
            ->where('status', 'pending_approval')
            ->latest()
            ->paginate(20);

        return view('provider.dashboard.approvals', compact('reservations'));
    }

    public function venues(Request $request)
    {
        $user = $request->user();
        $venues = Unite::with(['images', 'prices', 'slots'])
            ->whereHas('department', fn ($q) => $q->where('user_id', $user->id))
            ->withCount([
                'reservations as confirmed_count' => fn ($q) => $q->where('status', 'confirmed'),
                'reservations as pending_approval_count' => fn ($q) => $q->where('status', 'pending_approval'),
            ])
            ->latest()
            ->paginate(20);

        return view('provider.dashboard.venues', compact('venues'));
    }

    public function transfers(Request $request)
    {
        $user = $request->user();
        $policy = \App\Models\TransferPolicy::where('is_active', true)->latest()->first();

        $transfers = \App\Models\ProviderTransfer::with('policy')
            ->where('user_id', $user->id)
            ->latest()->paginate(15);

        $myRequests = \App\Models\TransferRequest::where('user_id', $user->id)
            ->latest()->get();

        $summary = [
            'total_received' => \App\Models\ProviderTransfer::where('user_id', $user->id)->where('status', 'completed')->sum('net_amount'),
            'pending' => \App\Models\ProviderTransfer::where('user_id', $user->id)->where('status', 'pending')->sum('net_amount'),
        ];

        return view('provider.dashboard.transfers', compact('transfers', 'myRequests', 'policy', 'summary'));
    }

    public function requestTransfer(Request $request)
    {
        $request->validate([
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'preferred_method' => ['required', 'in:bank_transfer,cash,check,digital_wallet'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        \App\Models\TransferRequest::create([
            'user_id' => $request->user()->id,
            'requested_amount' => $request->requested_amount,
            'preferred_method' => $request->preferred_method,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return redirect()->route('provider.transfers')->with('success', __('lang.payout_request_submitted'));
    }

    public function revenue(Request $request)
    {
        $user = $request->user();
        $unitIds = $this->providerUniteIds($user);
        $year = (int) ($request->year ?? now()->year);

        $monthly = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')
            ->whereIn('payments.reservation_id',
                UniteReservation::whereIn('unite_id', $unitIds)->select('id')
            )
            ->whereYear('payments.created_at', $year)
            ->selectRaw('MONTH(payments.created_at) as month, SUM(payments.amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $topVenues = DB::table('payments')
            ->live()
            ->where('payments.status', 'paid')
            ->join('unite_reservations', 'unite_reservations.id', '=', 'payments.reservation_id')
            ->join('unites', 'unites.id', '=', 'unite_reservations.unite_id')
            ->whereIn('unite_reservations.unite_id', $unitIds)
            ->whereYear('payments.created_at', $year)
            ->selectRaw('unites.name, COUNT(payments.id) as bookings, SUM(payments.amount) as revenue')
            ->groupBy('unites.id', 'unites.name')
            ->orderByDesc('revenue')
            ->limit(5)->get();

        $totalRevenue = $monthly->sum();
        $years = range(now()->year, 2025);

        return view('provider.dashboard.revenue', compact(
            'monthly', 'topVenues', 'totalRevenue', 'year', 'years'
        ));
    }
}
