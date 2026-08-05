<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NearbyUniteController extends Controller
{
    /**
     * GET /api/unites/nearby?lat=24.7135&lng=46.6753&radius_km=10&limit=20&type=hall
     *
     * Returns active venues within radius_km, sorted nearest-first.
     * Uses Haversine formula with positional ? bindings — named : params
     * cannot be reused in the same PDO statement.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'in:hall,stadium,lounge,camp'],
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radius = (float) ($request->radius_km ?? 10);
        $limit = (int) ($request->limit ?? 20);
        $type = $request->type;

        // Haversine using ? positional bindings — each ? is a separate slot,
        // so we pass lat and lng as many times as the formula needs them.
        $haversine = <<<'SQL'
            (6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(latitude))
                * COS(RADIANS(longitude) - RADIANS(?))
                + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))))
            SQL;

        // SELECT bindings: lat, lng, lat  (3 params for the distance_km alias)
        // WHERE  bindings: lat, lng, lat  (3 params for the HAVING-style WHERE)
        // Total: 6 positional ? params
        $typeClause = $type ? 'AND type = ?' : '';
        $typeBindings = $type ? [$type] : [];

        $sql = "
            SELECT *,
                   {$haversine} AS distance_km
            FROM unites
            WHERE latitude  IS NOT NULL
              AND longitude IS NOT NULL
              AND status    = 'active'
              {$typeClause}
            HAVING distance_km <= ?
            ORDER BY distance_km ASC
            LIMIT {$limit}
        ";

        // Binding order: (SELECT haversine: lat,lng,lat) + type? + (HAVING: radius)
        $bindings = array_merge(
            [$lat, $lng, $lat],   // SELECT distance_km
            $typeBindings,         // optional type filter
            [$radius],             // HAVING distance_km <= radius
        );

        $rows = collect(DB::select($sql, $bindings));

        // Fetch images and prices in two bulk queries — no N+1
        $ids = $rows->pluck('id');
        $images = DB::table('unite_images')
            ->whereIn('unite_id', $ids)
            ->select('unite_id', 'image')
            ->get()->groupBy('unite_id');

        $prices = DB::table('unite_prices')
            ->whereIn('unite_id', $ids)
            ->select('unite_id', 'day', 'price', 'morning_price', 'evening_price', 'full_price',
                'hourly_enabled', 'day_hour_price', 'night_hour_price',
                'day_start', 'day_end', 'min_booking_minutes')
            ->get()->groupBy('unite_id');

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'type' => $row->type,
            'location_name' => $row->location_name,
            'latitude' => (float) $row->latitude,
            'longitude' => (float) $row->longitude,
            'distance_km' => round((float) $row->distance_km, 3),
            'status' => $row->status,
            'images' => ($images[$row->id] ?? collect())
                ->map(fn ($i) => asset($i->image))->values()->all(),
            'prices' => ($prices[$row->id] ?? collect())->values()->all(),
        ]);

        return response()->json([
            'success' => true,
            'meta' => [
                'lat' => $lat,
                'lng' => $lng,
                'radius_km' => $radius,
                'count' => $data->count(),
            ],
            'data' => $data->values(),
        ]);
    }
}
