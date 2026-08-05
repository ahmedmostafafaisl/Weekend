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
}
