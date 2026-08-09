<?php

namespace App\Http\Controllers\Unite;

use App\Http\Controllers\Controller;
use App\Models\Unite;
use App\Services\Availability\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(protected AvailabilityService $service) {}

    /**
     * GET /api/unites/{unite}/availability
     *
     * Query params:
     *   year  — int (default: current year)
     *   month — int 1-12 (default: current month)
     *
     * Response shape:
     * {
     *   "success": true,
     *   "data": {
     *     "unite_id": 1,
     *     "unite_name": "Elite Stadium A",
     *     "unite_type": "stadium",
     *     "year": 2026,
     *     "month": 6,
     *     "dates": [
     *       {
     *         "date": "2026-06-01",
     *         "day_of_week": "monday",
     *         "availability": "available",      // available | partially_available | fully_booked | unavailable | past
     *         "display_status": "available",     // available | unavailable | holiday | past — consolidated
     *                                             // 3-color mapping for calendar UIs (green/red/yellow),
     *                                             // see AvailabilityService::mapDisplayStatus()
     *         "is_past": false,
     *         "periods": [
     *           {
     *             "period_type": "morning",     // morning | evening | full_day
     *             "from_time": "08:00",
     *             "to_time": "12:00",
     *             "price": 150.00,
     *             "availability": "available"   // available | booked | past
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     */
    public function month(Request $request, int $uniteId): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2050'],
            'month' => ['nullable', 'integer', 'min:1',    'max:12'],
        ]);

        $unite = Unite::with(['slots', 'prices', 'offers'])->findOrFail($uniteId);

        if ($unite->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => __('lang.venue_not_currently_active'),
            ], 422);
        }

        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);

        $calendar = $this->service->monthCalendar($unite, $year, $month);

        return response()->json([
            'success' => true,
            'data' => $calendar,
        ]);
    }

    /**
     * GET /api/unites/{unite}/availability/date
     *
     * Query params:
     *   date — Y-m-d (required)
     *
     * Returns detailed period availability for a single date.
     * Useful for the booking screen when the user taps a specific day.
     */
    public function date(Request $request, int $uniteId): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        $unite = Unite::with(['slots', 'prices', 'offers'])->findOrFail($uniteId);

        if ($unite->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => __('lang.venue_not_currently_active'),
            ], 422);
        }

        [$year, $month] = explode('-', $request->date);

        $calendar = $this->service->monthCalendar($unite, (int) $year, (int) $month);

        $entry = collect($calendar['dates'])
            ->firstWhere('date', $request->date);

        if (! $entry) {
            return response()->json([
                'success' => false,
                'message' => __('lang.date_not_found_in_calendar'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $entry,
        ]);
    }

    /**
     * GET /api/unites/{unite}/availability/range
     *
     * Query params:
     *   start_date — Y-m-d (required)
     *   end_date   — Y-m-d (required, >= start_date)
     *
     * Checks availability across an arbitrary multi-day range — e.g.
     * start_date=2026-08-09&end_date=2026-08-12 — specifically for
     * venues that support full_day reservations (hall/lounge/camp).
     * full_day_range_available answers "can this whole range be booked
     * as one multi-day full_day reservation right now" — false for a
     * stadium (hourly-only) regardless of the dates requested, and false
     * for any range where even one day is already booked or the venue is
     * closed that day.
     */
    public function range(Request $request, int $uniteId): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $unite = Unite::with(['slots', 'prices', 'offers'])->findOrFail($uniteId);

        if ($unite->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => __('lang.venue_not_currently_active'),
            ], 422);
        }

        $start = \Carbon\Carbon::parse($request->start_date);
        $end = \Carbon\Carbon::parse($request->end_date);

        // Capped to avoid an unbounded range turning into an accidental
        // full-table scan / thousands of computed date entries.
        if ($start->diffInDays($end) > 90) {
            return response()->json([
                'success' => false,
                'message' => __('lang.date_range_too_large'),
            ], 422);
        }

        $result = $this->service->rangeAvailability($unite, $start, $end);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
