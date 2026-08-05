<?php

namespace App\Http\Controllers\Admin\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\UniteReservation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $admin = auth('admin')->user();

        $query = UniteReservation::query()
            ->with(['user', 'unite.department', 'payment'])
            ->latest('reservation_date');

        // ── Reviewer scope ────────────────────────────────────────────────────
        // reviewer role = see only scoped unites/types (or all if no scope rows)
        $scopeFn = $admin->reservationScopeQuery();
        if ($scopeFn) {
            $scopeFn($query);
        }

        // ── Filters ───────────────────────────────────────────────────────────
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%"))
                    ->orWhereHas('unite', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('unite_reservations.status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('payment', fn ($q) => $q->where('status', $request->payment_status)
            );
        }

        if ($request->filled('type')) {
            $query->whereHas('unite', fn ($q) => $q->where('type', $request->type)
            );
        }

        if ($request->filled('department_id')) {
            $query->whereHas('unite', fn ($q) => $q->where('department_id', $request->department_id)
            );
        }

        if ($request->filled('period_type')) {
            $query->where('unite_reservations.period_type', $request->period_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('reservation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('reservation_date', '<=', $request->date_to);
        }

        // ── Summary counts ────────────────────────────────────────────────────
        // reorder() strips ORDER BY so MySQL doesn't reject aggregates without GROUP BY.
        // Table-qualify status to avoid ambiguity if a JOIN is present.
        $totals = (clone $query)->reorder()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN unite_reservations.status = "confirmed"        THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN unite_reservations.status = "pending"          THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN unite_reservations.status = "pending_approval" THEN 1 ELSE 0 END) as pending_approval,
            SUM(CASE WHEN unite_reservations.status = "cancelled"        THEN 1 ELSE 0 END) as cancelled
        ')->first();

        // Revenue: use a subquery so we don't JOIN (which causes ambiguous 'status').
        $revenue = \DB::table('payments')
            ->live()
            ->whereIn('reservation_id', (clone $query)->reorder()->select('unite_reservations.id'))
            ->where('payments.status', 'paid')
            ->sum('payments.amount');

        // ── CSV export ────────────────────────────────────────────────────────
        if ($request->boolean('export')) {
            return $this->exportCsv($query);
        }

        $reservations = $query->paginate(25)->withQueryString();
        $departments = Department::orderBy('name')->get(['id', 'name']);

        // Pass scope info to view so reviewer sees what they're scoped to
        $reviewerScopes = $admin->hasRole('reviewer')
            ? $admin->reviewerScopes()->with('unite')->get()
            : null;

        return view('dashboard.admin.reservations.index', compact(
            'reservations', 'totals', 'revenue', 'departments', 'reviewerScopes'
        ));
    }

    // ── CSV export ─────────────────────────────────────────────────────────────
    private function exportCsv($query): StreamedResponse
    {
        $filename = 'reservations-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID', 'Customer', 'Email', 'Phone',
                'Venue', 'Type', 'Department',
                'Date', 'Period', 'From', 'To',
                'Price', 'Status',
                'Payment Status', 'Payment Ref', 'Payment Amount',
                'Created At',
            ]);

            $query->with(['user', 'unite.department', 'payment'])
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $r->id,
                            $r->user?->name ?? '—',
                            $r->user?->email ?? '—',
                            $r->user?->phone ?? '—',
                            $r->unite?->name ?? '—',
                            $r->unite?->type ?? '—',
                            $r->unite?->department?->name ?? '—',
                            $r->reservation_date?->format('Y-m-d') ?? '—',
                            $r->period_type,
                            $r->from_time,
                            $r->to_time,
                            number_format((float) $r->price, 2),
                            $r->status,
                            $r->payment?->status ?? '—',
                            $r->payment?->reference_id ?? '—',
                            $r->payment ? number_format((float) $r->payment->amount, 2) : '—',
                            $r->created_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
