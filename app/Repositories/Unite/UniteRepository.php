<?php

namespace App\Repositories\Unite;

use App\Models\Unite;
use App\Models\UniteCouncil;
use App\Models\UniteDetail;
use App\Models\UniteFeature;
use App\Models\UniteNewFeature;
use App\Models\UniteOffer;
use App\Models\UnitePackage;
use App\Models\UniteReservation;
use App\Models\UniteSlot;
use App\Repositories\Interfaces\UniteRepositoryInterface;
use App\Services\Availability\AvailabilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UniteRepository implements UniteRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        $q = Unite::with([
            'detail',
            'images',
            'features',
            'offers',
            'reservations.user',
            'reservations.payment',
            'slots',
            'prices',
            'packages',
            'bookingPackages',
            'viewingTimes',
            'newFeatures',
            'councils',
            'services',
            'department',
        ]);

        if (! empty($filters['search'])) {
            $q->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        return $q->latest()->get();
    }

    /**
     * Search and filter venues for the customer-facing API.
     *
     * Supported filters:
     *   type              — stadium | hall | lounge | camp
     *   city              — partial match on location_name
     *   date              — Y-m-d (pre-computes availability for the day)
     *   period_type       — morning | evening | full_day (used with date and price filters)
     *   min_price         — numeric (lower bound on any period price)
     *   max_price         — numeric (upper bound on any period price)
     *   min_capacity      — numeric (compared against the type's capacity field)
     *   families_and_singles — families | singles | both
     *   min_rating        — numeric 0–5 (venues with avg rating >= this value)
     *   sort_by           — price | rating | distance
     *   lat, lng          — required when sort_by=distance; decimal degrees
     *   status            — default: active only
     *   per_page          — paginate results (default 20)
     */
    public function search(array $filters = [], int $perPage = 20)
    {
        $q = Unite::with([
            'images',
            'slots',
            'prices',
            'offers',
            'department',
            'detail',
        ])
            ->withCount(['ratings'])
            ->withAvg('ratings', 'rating')
            ->where('status', 'active');

        // ── Basic filters ─────────────────────────────────────────────────────

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        if (! empty($filters['city'])) {
            $q->where('location_name', 'like', '%'.$filters['city'].'%');
        }

        if (! empty($filters['families_and_singles'])) {
            $value = $filters['families_and_singles'];

            if ($value === 'both') {
                $q->where('families_and_singles', 'both');
            } else {
                $q->whereIn('families_and_singles', [$value, 'both']);
            }
        }

        // ── Capacity filter — joins type-specific detail table ────────────────

        if (! empty($filters['min_capacity'])) {
            $cap = (int) $filters['min_capacity'];

            $q->where(function ($query) use ($cap) {
                $query
                    ->where(fn ($q) => $q->where('type', 'hall')
                        ->whereHas('detail', fn ($d) => $d->where('max_capacity', '>=', $cap)))
                    ->orWhere(fn ($q) => $q->where('type', 'camp')
                        ->whereHas('detail', fn ($d) => $d->where('seating_capacity', '>=', $cap)))
                    ->orWhere(fn ($q) => $q->where('type', 'stadium')
                        ->whereHas('detail', fn ($d) =>
                            // Stadiums don't have a single capacity — use length*width as proxy
                            $d->whereRaw('CAST(width AS UNSIGNED) * CAST(length AS UNSIGNED) >= ?', [$cap])
                        ))
                    ->orWhere('type', 'lounge'); // lounges have rooms, not capacity
            });
        }

        // ── Price filter — against the friday price (peak day proxy) ─────────

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $max = (float) $filters['max_price'];

            $q->whereHas('prices', function ($query) use ($max, $filters) {
                // Filter on the relevant period price
                $period = $filters['period_type'] ?? null;

                $query->where(function ($q) use ($max, $period) {
                    if ($period === 'morning') {
                        $q->where('morning_price', '<=', $max)->whereNotNull('morning_price');
                    } elseif ($period === 'evening') {
                        $q->where('evening_price', '<=', $max)->whereNotNull('evening_price');
                    } else {
                        // full_day or no period — check any of the price columns
                        $q->where(fn ($inner) => $inner->where('full_price', '<=', $max)
                            ->orWhere('price', '<=', $max)
                            ->orWhere('morning_price', '<=', $max)
                            ->orWhere('evening_price', '<=', $max)
                        );
                    }
                });
            });
        }

        // ── Min-price filter ─────────────────────────────────────────────────

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $min = (float) $filters['min_price'];
            $period = $filters['period_type'] ?? null;

            $q->whereHas('prices', function ($query) use ($min, $period) {
                $query->where(function ($q) use ($min, $period) {
                    if ($period === 'morning') {
                        $q->where('morning_price', '>=', $min)->whereNotNull('morning_price');
                    } elseif ($period === 'evening') {
                        $q->where('evening_price', '>=', $min)->whereNotNull('evening_price');
                    } else {
                        $q->where(fn ($inner) => $inner->where('full_price', '>=', $min)
                            ->orWhere('price', '>=', $min)
                            ->orWhere('morning_price', '>=', $min)
                            ->orWhere('evening_price', '>=', $min)
                        );
                    }
                });
            });
        }

        // ── Min-rating filter ────────────────────────────────────────────────

        if (! empty($filters['min_rating'])) {
            // Same fix as all2()'s rating filter — see that method for the
            // full explanation of why a correlated subquery is more robust
            // than having() on a withAvg()-computed alias with no groupBy().
            $q->whereRaw(
                '(select avg(rating) from unite_ratings where unite_ratings.unite_id = unites.id) >= ?',
                [(float) $filters['min_rating']]
            );
        }

        // ── Sorting ──────────────────────────────────────────────────────────
        //
        // sort_by=price   → cheapest venue first (MIN of any price column)
        // sort_by=rating  → highest rated first (requires withAvg already in scope)
        // sort_by=distance → nearest first via Haversine; requires lat & lng
        // default         → newest first (created_at DESC)

        $sortBy = $filters['sort_by'] ?? null;

        if ($sortBy === 'rating') {
            $q->orderByDesc('ratings_avg_rating');
        } elseif ($sortBy === 'price') {
            // Subquery: MIN across all price columns for this venue, used for ordering only
            $q->orderByRaw(
                '(SELECT LEAST(
                    COALESCE(MIN(price),         999999),
                    COALESCE(MIN(morning_price), 999999),
                    COALESCE(MIN(evening_price), 999999),
                    COALESCE(MIN(full_price),    999999)
                  ) FROM unite_prices WHERE unite_prices.unite_id = unites.id) ASC'
            );
        } elseif ($sortBy === 'distance'
            && ! empty($filters['lat'])
            && ! empty($filters['lng'])) {
            // Haversine formula — same as NearbyUniteController, adapted for
            // use inside Eloquent orderByRaw so it composes with the rest of
            // the WHERE/HAVING filters already applied above.
            $lat = (float) $filters['lat'];
            $lng = (float) $filters['lng'];

            $q->orderByRaw(
                '(6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(latitude))
                    * COS(RADIANS(longitude) - RADIANS(?))
                    + SIN(RADIANS(?)) * SIN(RADIANS(latitude))))) ASC',
                [$lat, $lng, $lat]
            );
        } else {
            $q->latest();
        }

        $unites = $q->paginate($perPage);

        // When the caller sorted by distance, attach the computed km value so
        // UniteResource can expose it — avoids a second Haversine pass.
        if ($sortBy === 'distance'
            && ! empty($filters['lat'])
            && ! empty($filters['lng'])) {
            $lat = (float) $filters['lat'];
            $lng = (float) $filters['lng'];

            foreach ($unites->items() as $u) {
                if ($u->latitude !== null && $u->longitude !== null) {
                    $u->distance_km = round(
                        6371 * acos(min(1, cos(deg2rad($lat)) * cos(deg2rad((float) $u->latitude))
                            * cos(deg2rad((float) $u->longitude) - deg2rad($lng))
                            + sin(deg2rad($lat)) * sin(deg2rad((float) $u->latitude)))),
                        3
                    );
                } else {
                    $u->distance_km = null;
                }
            }
        }

        // ── Availability pre-computation ──────────────────────────────────────
        // If a date (and optionally period_type) is requested, compute the
        // availability for that date and attach it to each venue.
        // Venues that are fully booked or unavailable on the requested date
        // are excluded from the results.

        if (! empty($filters['date'])) {
            $date = $filters['date'];
            $period = $filters['period_type'] ?? null;
            $service = app(AvailabilityService::class);

            [$year, $month] = explode('-', $date);

            $items = collect();

            foreach ($unites->items() as $unite) {
                try {
                    $calendar = $service->monthCalendar($unite, (int) $year, (int) $month);
                    $dayEntry = collect($calendar['dates'])->firstWhere('date', $date);

                    $availability = $dayEntry['availability'] ?? 'unavailable';

                    // Exclude fully booked and unavailable venues
                    if (in_array($availability, ['fully_booked', 'unavailable', 'past'], true)) {
                        continue;
                    }

                    // If a period was requested, check that specific period is available
                    if ($period) {
                        $periodEntry = collect($dayEntry['periods'] ?? [])
                            ->firstWhere('period_type', $period);

                        if (! $periodEntry || $periodEntry['availability'] !== 'available') {
                            continue;
                        }
                    }

                    $unite->date_availability = $dayEntry;
                } catch (\Throwable) {
                    $unite->date_availability = null;
                }

                $items->push($unite);
            }

            // Return a simple collection (date filter changes the count so pagination is approximate)
            return $items;
        }

        return $unites;
    }

    /**
     * @return array{unites: Collection, min_price: ?float, max_price: ?float}
     */
    public function all2(array $filters = [], ?int $userId = null): array
    {
        $query = Unite::with([
            'detail',
            'images',
            'features',
            'offers',

            'prices',
            'services',
            'favorites' => function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
        ])
            ->withCount(['ratings', 'views'])
            ->withAvg('ratings', 'rating');

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        // Dedicated city filter — distinct from 'search' above, which
        // covers name/description/location_name but never checked city
        // specifically. Matches either field with a partial (LIKE) match,
        // since a city name is commonly embedded within location_name
        // (e.g. "الرياض - العليا") rather than stored identically in both.
        if (! empty($filters['city'])) {
            $city = $filters['city'];

            $query->where(function ($q) use ($city) {
                $q->where('location_name', 'like', "%{$city}%")
                    ->orWhere('city', 'like', "%{$city}%");
            });
        }

        if (! empty($filters['filter_by_type'])) {
            $query->where('type', $filters['filter_by_type']);
        }

        $hasPriceFrom = isset($filters['price_from']) && $filters['price_from'] !== '';
        $hasPriceTo = isset($filters['price_to']) && $filters['price_to'] !== '';

        if ($hasPriceFrom || $hasPriceTo) {
            $priceFrom = $hasPriceFrom ? (float) $filters['price_from'] : 0;
            $priceTo = $hasPriceTo ? (float) $filters['price_to'] : 999999999;

            $query->whereHas('prices', function ($q) use ($priceFrom, $priceTo) {
                $q->where(function ($sub) use ($priceFrom, $priceTo) {
                    $sub->whereBetween('price', [$priceFrom, $priceTo])
                        ->orWhereBetween('morning_price', [$priceFrom, $priceTo])
                        ->orWhereBetween('evening_price', [$priceFrom, $priceTo])
                        ->orWhereBetween('full_price', [$priceFrom, $priceTo]);
                });
            });
        }

        // BUG FIX: accepted in the controller's allowed-filters list but
        // never actually applied to the query — same "accepted, silently
        // ignored" pattern found and fixed for filter_by in an earlier
        // session. 'both' venues also satisfy a customer specifically
        // searching for 'families' or 'singles'.
        if (! empty($filters['families_and_singles'])) {
            $value = $filters['families_and_singles'];

            if ($value === 'both') {
                $query->where('families_and_singles', 'both');
            } else {
                $query->whereIn('families_and_singles', [$value, 'both']);
            }
        }

        if (! empty($filters['rating'])) {
            $rating = (float) $filters['rating'];

            $query->whereRaw(
                '(select avg(rating) from unite_ratings where unite_ratings.unite_id = unites.id) >= ?',
                [$rating]
            );
        }

        if (! empty($filters['services']) && is_array($filters['services'])) {
            $services = $filters['services'];

            $query->whereHas('features', function ($q) use ($services) {
                $q->whereIn('name', $services);
            });
        }

        $unites = $query->get();

        if (! empty($filters['nearest_to_lat_long']) && ! empty($filters['lat']) && ! empty($filters['long'])) {
            $lat = (float) $filters['lat'];
            $long = (float) $filters['long'];

            $unites = $unites->sortBy(function ($unite) use ($lat, $long) {
                if (is_null($unite->latitude) || is_null($unite->longitude)) {
                    return PHP_FLOAT_MAX;
                }

                return sqrt(
                    pow($unite->latitude - $lat, 2) +
                    pow($unite->longitude - $long, 2)
                );
            })->values();
        }

        // filter_by was already accepted by the controller's allowed-filters
        // list but never actually implemented anywhere — this gives it the
        // 4 sort modes: price_asc, price_desc, rating_asc, rating_desc.
        if (! empty($filters['filter_by'])) {
            $sortMode = $filters['filter_by'];

            if (in_array($sortMode, ['price_asc', 'price_desc'], true)) {
                // Each venue's own cheapest price across every price row/
                // column it has — the same "starting from" metric a venue
                // card would display, so sorting by it matches what the
                // user actually sees rather than some other hidden value.
                $unites = $unites->sortBy(function ($unite) {
                    $prices = $unite->prices
                        ->flatMap(fn ($row) => collect([
                            $row->price, $row->morning_price, $row->evening_price, $row->full_price,
                        ]))
                        ->filter(fn ($v) => $v !== null);

                    return $prices->isNotEmpty() ? (float) $prices->min() : PHP_FLOAT_MAX;
                }, SORT_REGULAR, $sortMode === 'price_desc')->values();
            } elseif (in_array($sortMode, ['rating_asc', 'rating_desc'], true)) {
                // ratings_avg_rating already comes from withAvg() above — no
                // extra query needed, it's just an attribute on each model.
                $unites = $unites->sortBy(
                    fn ($unite) => (float) ($unite->ratings_avg_rating ?? 0),
                    SORT_REGULAR,
                    $sortMode === 'rating_desc'
                )->values();
            }
        }

        $allPrices = $unites
            ->flatMap(fn ($unite) => $unite->prices)
            ->flatMap(fn ($priceRow) => collect([
                $priceRow->price,
                $priceRow->morning_price,
                $priceRow->evening_price,
                $priceRow->full_price,
            ]))
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        return [
            'unites' => $unites,
            'min_price' => $allPrices->isNotEmpty() ? $allPrices->min() : null,
            'max_price' => $allPrices->isNotEmpty() ? $allPrices->max() : null,
        ];
    }

    public function find(int $id): ?Unite
    {
        return Unite::with([
            'detail',
            'images',
            'features',
            'offers',

            'slots',
            'prices',
            'packages',
            'bookingPackages',
            'viewingTimes',
            'newFeatures',
            'councils',
            'favorites',
            'department.user.receivedVendorRatings',
            'department.unites.images',
            'department.unites.prices',
            'services',
            // Most recent 20 reviews with the reviewer's name — used by the
            // venue show page's "Ratings & Reviews" section.
            'ratings' => fn ($q) => $q->with('user')->latest()->limit(20),
        ])
            ->withCount(['ratings', 'views'])
            ->withAvg('ratings', 'rating')
            ->find($id);
    }

    public function create(array $data): Unite
    {
        return DB::transaction(function () use ($data) {
            $detail = $data[$data['type']] ?? [];
            unset($data['stadium'], $data['hall'], $data['lounge'], $data['camp']);

            $unite = Unite::create($data);

            $this->storeDetails($unite, $data['type'], $detail);
            $this->storeImages($unite, $data['images'] ?? []);
            $this->storeFeatures($unite, $data['features'] ?? []);
            $this->storeOffers($unite, $data['offers'] ?? []);
            $this->storeReservations($unite, $data['reservations'] ?? []);
            $this->storeSlots($unite, $data['slots'] ?? [], $data['type'] ?? 'stadium');
            $this->storePrices($unite, $data['prices'] ?? [], $data['type'] ?? 'stadium');
            $this->storePackages($unite, $data['packages'] ?? []);
            $this->storeBookingPackages($unite, $data['booking_packages'] ?? []);
            $this->storeViewingTimes($unite, $data['viewing_times'] ?? []);
            $this->storeNewFeatures($unite, $data['new_features'] ?? []);
            $this->syncServices($unite, $data['service_ids'] ?? []);
            $unite = $unite->fresh([
                'detail',
                'images',
                'features',
                'offers',

                'slots',
                'prices',
                'services',
                'packages',
                'bookingPackages',
                'viewingTimes',
                'newFeatures',
                'councils',
            ]);
            // all_men_count / all_women_count are now computed accessors on
            // UniteDetail itself (see App\Models\UniteDetail) — no manual
            // assignment needed here anymore. Kept as a no-op comment for
            // anyone diffing this method against the pre-refactor version.

            return $unite;
        });
    }

    public function update(Unite $unite, array $data): Unite
    {
        return DB::transaction(function () use ($unite, $data) {
            $detail = $data[$data['type']] ?? [];
            unset($data['stadium'], $data['hall'], $data['lounge'], $data['camp']);

            $unite->update($data);

            $this->storeDetails($unite, $data['type'], $detail, true);

            $unite->features()->delete();
            $unite->offers()->delete();
            $unite->reservations()->delete();
            $unite->slots()->delete();
            $unite->prices()->delete();
            $unite->packages()->delete();
            $unite->bookingPackages()->delete();
            $unite->viewingTimes()->delete();
            $unite->newFeatures()->delete();

            $this->storeFeatures($unite, $data['features'] ?? []);
            $this->storeOffers($unite, $data['offers'] ?? []);
            $this->storeReservations($unite, $data['reservations'] ?? []);
            $this->storeSlots($unite, $data['slots'] ?? [], $data['type'] ?? 'stadium');
            $this->storePrices($unite, $data['prices'] ?? [], $data['type'] ?? 'stadium');
            $this->storePackages($unite, $data['packages'] ?? []);
            $this->storeBookingPackages($unite, $data['booking_packages'] ?? []);
            $this->storeViewingTimes($unite, $data['viewing_times'] ?? []);
            $this->storeNewFeatures($unite, $data['new_features'] ?? []);
            $this->syncServices($unite, $data['service_ids'] ?? []);

            // Image management: delete images not in keep_image_ids, then add new uploads
            $keepIds = $data['keep_image_ids'] ?? null;
            if ($keepIds !== null) {
                // Delete images that were removed (not in keep list)
                $unite->images()
                    ->whereNotIn('id', array_map('intval', (array) $keepIds))
                    ->each(function ($img) {
                        // Remove file from disk if it exists in public/
                        $fullPath = public_path($img->image);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                        $img->delete();
                    });
            }

            if (! empty($data['images'])) {
                $this->storeImages($unite, $data['images']);
            }

            $unite = $unite->fresh([
                'detail',
                'images',
                'features',
                'offers',

                'slots',
                'prices',
                'services',
                'packages',
                'bookingPackages',
                'viewingTimes',
                'newFeatures',
                'councils',
            ]);
            // all_men_count / all_women_count are now computed accessors on
            // UniteDetail itself (see App\Models\UniteDetail) — no manual
            // assignment needed here anymore. Kept as a no-op comment for
            // anyone diffing this method against the pre-refactor version.

            return $unite;
        });
    }

    protected function storeDetails(Unite $unite, string $type, array $detail, bool $updating = false): void
    {
        // No longer branches on 4 model classes — UniteDetail is a single
        // model shared by all unite types. $type is kept as a parameter for
        // backward compatibility with existing call sites, but is unused
        // internally now.
        $councilTypes = $detail['councils'] ?? [];
        unset($detail['councils']);

        $detail['unite_id'] = $unite->id;

        if ($updating) {
            UniteDetail::updateOrCreate(
                ['unite_id' => $unite->id],
                $detail
            );
        } else {
            UniteDetail::create($detail);
        }

        $this->storeCouncils($unite, $councilTypes);
    }

    /**
     * One UniteCouncil row per entry in $councilTypes — each can carry its
     * own optional type, replacing the old single flat council_type
     * string that could only describe one shared type for however many
     * councils council_number said existed.
     */
    protected function storeCouncils(Unite $unite, array $councilTypes): void
    {
        $unite->councils()->delete();

        foreach ($councilTypes as $councilType) {
            UniteCouncil::create([
                'unite_id' => $unite->id,
                'type' => $councilType ?: null,
            ]);
        }
    }

    protected function storeImages(Unite $unite, array $images): void
    {
        foreach ($images as $imageData) {

            if ($imageData instanceof \Illuminate\Http\UploadedFile) {
                $image = $imageData;
            } else {
                $image = $imageData['image'] ?? null;
            }

            if (! $image) {
                continue;
            }

            $path = public_path("unites/images/{$unite->id}");

            if (! file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time().'_'.$image->getClientOriginalName();

            $image->move($path, $imageName);

            $unite->images()->create([
                'image' => "unites/images/{$unite->id}/{$imageName}",
            ]);
        }
    }

    protected function storeFeatures(Unite $unite, array $features): void
    {
        foreach ($features as $feature) {
            UniteFeature::create([
                'unite_id' => $unite->id,
                'name' => $feature['name'] ?? '',
                'description' => $feature['description'] ?? null,
                'status' => $feature['status'] ?? 'active',
            ]);
        }
    }

    protected function storeOffers(Unite $unite, array $offers): void
    {
        foreach ($offers as $offer) {
            $name = (! empty($offer['name']))
                ? $offer['name']
                : 'Offer '.($offer['start'] ?? now()->format('Y-m-d'));

            UniteOffer::create([
                'unite_id' => $unite->id,
                'name' => $name,
                'start' => $offer['start'] ?? null,
                'end' => $offer['end'] ?? null,
                'morning_price' => $offer['morning_price'] ?? null,
                'evening_price' => $offer['evening_price'] ?? null,
                'full_day_price' => $offer['full_day_price'] ?? null,
                'status' => $offer['status'] ?? 'active',
            ]);
        }
    }

    protected function storeReservations(Unite $unite, array $reservations): void
    {
        foreach ($reservations as $res) {
            UniteReservation::create([
                'unite_id' => $unite->id,
                'user_id' => $res['user_id'] ?? null,
                'reservation_date' => $res['reservation_date'] ?? null,
                'period_type' => $res['period_type'] ?? null,
                'from_time' => $res['from_time'] ?? null,
                'to_time' => $res['to_time'] ?? null,
                'price' => $res['price'] ?? null,
                'status' => $res['status'] ?? 'pending',
            ]);
        }
    }

    /**
     * Sunday through Wednesday — the 4 individual days that 'week_day'
     * expands into. Matches UnitePrice's day-category grouping, and the
     * identical expansion already implemented in UniteSlotRepository for
     * the dedicated admin/unites/{unite}/slots CRUD endpoints — this is a
     * separate code path (the inline bulk slots section on the unite
     * create/edit form) that needed the same fix independently.
     */
    private const SLOT_WEEK_DAYS = ['sunday', 'monday', 'tuesday', 'wednesday'];

    protected function storeSlots(Unite $unite, array $slots, string $type): void
    {
        foreach ($slots as $slot) {
            $payload = [
                'day_of_week' => $slot['day_of_week'] ?? null,
                'status' => $slot['status'] ?? 'available',
            ];

            if ($type === 'stadium') {
                $payload['morning_start'] = null;
                $payload['morning_end'] = null;
                $payload['evening_start'] = null;
                $payload['evening_end'] = null;
                $payload['full_start'] = $slot['full_start'] ?? null;
                $payload['full_end'] = $slot['full_end'] ?? null;
            } else {
                $payload['morning_start'] = $slot['morning_start'] ?? null;
                $payload['morning_end'] = $slot['morning_end'] ?? null;
                $payload['evening_start'] = $slot['evening_start'] ?? null;
                $payload['evening_end'] = $slot['evening_end'] ?? null;
                $payload['full_start'] = $slot['full_start'] ?? null;
                $payload['full_end'] = $slot['full_end'] ?? null;
            }

            $payload['day_start'] = $slot['day_start'] ?? null;
            $payload['day_end'] = $slot['day_end'] ?? null;
            $payload['buffer_minutes'] = $slot['buffer_minutes'] ?? 0;

            // 'week_day' is a request-layer shorthand only — the day_of_week
            // column itself is a strict enum of the 7 real day names and
            // has no 'week_day' value at all, so this must expand into 4
            // real rows rather than being passed through as-is.
            $daysToWrite = $payload['day_of_week'] === 'week_day'
                ? self::SLOT_WEEK_DAYS
                : [$payload['day_of_week']];

            $periods = $slot['periods'] ?? null;

            foreach ($daysToWrite as $day) {
                $daySlot = $unite->slots()->updateOrCreate(
                    [
                        'unite_id' => $unite->id,
                        'day_of_week' => $day,
                    ],
                    array_merge($payload, ['day_of_week' => $day])
                );

                if ($periods !== null) {
                    $this->storePeriodsForSlot($daySlot, $periods);
                }
            }
        }
    }

    /**
     * Replaces a slot's custom availability periods entirely with the
     * submitted list — delete-then-create, matching the identical pattern
     * already established in UniteSlotRepository::storePeriodsForSlot()
     * for the standalone /unites/{id}/slots endpoint. Kept as its own
     * copy here rather than injecting UniteSlotRepository as a dependency
     * into this repository purely for one shared method.
     */
    protected function storePeriodsForSlot(UniteSlot $slot, array $periods): void
    {
        $slot->periods()->delete();

        foreach ($periods as $period) {
            if (empty($period['start_time']) || empty($period['end_time'])) {
                continue;
            }

            $slot->periods()->create([
                'start_time' => $period['start_time'],
                'end_time' => $period['end_time'],
                'status' => $period['status'] ?? 'available',
            ]);
        }
    }

    protected function storePrices(Unite $unite, array $prices, string $type): void
    {
        foreach ($prices as $price) {
            $hourlyEnabled = filter_var($price['hourly_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $hourlyData = [
                'hourly_enabled' => $hourlyEnabled,
                'day_hour_price' => $hourlyEnabled ? ($price['day_hour_price'] ?? null) : null,
                'night_hour_price' => $hourlyEnabled ? ($price['night_hour_price'] ?? null) : null,
                // day_start / day_end are NOT NULL in DB — always provide a value
                'day_start' => $price['day_start'] ?? '06:00',
                'day_end' => $price['day_end'] ?? '18:00',
                'min_booking_minutes' => $price['min_booking_minutes'] ?? 60,
            ];

            if ($type === 'stadium') {
                $unite->prices()->create(array_merge([
                    'day' => $price['day'] ?? null,
                    'price' => $price['price'] ?? null,
                ], $hourlyData));
            } else {
                $unite->prices()->create(array_merge([
                    'day' => $price['day'] ?? null,
                    'morning_price' => $price['morning_price'] ?? null,
                    'evening_price' => $price['evening_price'] ?? null,
                    'full_price' => $price['full_price'] ?? null,
                ], $hourlyData));
            }
        }
    }

    protected function storePackages(Unite $unite, array $packages): void
    {
        foreach ($packages as $package) {
            UnitePackage::create([
                'unite_id' => $unite->id,
                'name' => $package['name'] ?? '',
                'men_capacity' => $package['men_capacity'] ?? 0,
                'women_capacity' => $package['women_capacity'] ?? 0,
                'price' => $package['price'] ?? 0,
            ]);
        }
    }

    /**
     * Package booking — a genuinely different concept from storePackages()
     * above (capacity tiers). Available to every venue type; days is
     * stored as a JSON array (["any"] or specific days) rather than a
     * single day_of_week column, since a package can apply to several
     * specific days at once.
     */
    protected function storeBookingPackages(Unite $unite, array $bookingPackages): void
    {
        foreach ($bookingPackages as $pkg) {
            $bookingType = $pkg['booking_type'] ?? 'hours';

            $duration = null;
            if ($bookingType === 'days' && ! empty($pkg['day_from']) && ! empty($pkg['day_to'])) {
                $duration = \App\Models\UniteBookingPackage::computeDurationDays($pkg['day_from'], $pkg['day_to']);
            }

            \App\Models\UniteBookingPackage::create([
                'unite_id' => $unite->id,
                'name' => $pkg['name'] ?? null,
                'booking_type' => $bookingType,
                // 'hours' mode fields — null for 'days'-type packages
                'day' => $bookingType === 'hours' ? ($pkg['day'] ?? 'week_day') : null,
                'start_time' => $bookingType === 'hours' ? ($pkg['start_time'] ?? null) : null,
                'end_time' => $bookingType === 'hours' ? ($pkg['end_time'] ?? null) : null,
                // 'days' mode fields — null for 'hours'-type packages
                'day_from' => $bookingType === 'days' ? ($pkg['day_from'] ?? null) : null,
                'day_to' => $bookingType === 'days' ? ($pkg['day_to'] ?? null) : null,
                'duration_days' => $duration,
                'price' => $pkg['price'] ?? 0,
                // Free text the provider typed in — not a relation, so no
                // sync() call needed, just stored directly as a JSON array.
                'services' => array_values(array_filter(array_map(
                    'trim',
                    explode(',', $pkg['services_text'] ?? '')
                ))),
                'status' => $pkg['status'] ?? 'active',
            ]);
        }
    }

    /**
     * Viewing appointments — an optional feature letting a customer
     * schedule a visit to inspect the venue before booking it.
     * Deliberately separate from the reservation-deposit fields, which
     * govern a different concept entirely. Multiple rows per day are
     * expected and fully supported here — each row in $viewingTimes just
     * becomes its own row, with no deduplication or merging by day.
     */
    protected function storeViewingTimes(Unite $unite, array $viewingTimes): void
    {
        foreach ($viewingTimes as $vt) {
            \App\Models\UniteViewingTime::create([
                'unite_id' => $unite->id,
                'day_of_week' => $vt['day_of_week'],
                'start_time' => $vt['start_time'],
                'end_time' => $vt['end_time'],
                'status' => $vt['status'] ?? 'active',
            ]);
        }
    }

    protected function storeNewFeatures(Unite $unite, array $newFeatures): void
    {
        foreach ($newFeatures as $feature) {
            UniteNewFeature::create([
                'unite_id' => $unite->id,
                'title' => $feature['title'] ?? '',
                'description' => $feature['description'] ?? null,
            ]);
        }
    }

    public function delete(int $id): bool
    {
        $unite = Unite::findOrFail($id);

        return $unite->delete();
    }

    protected function saveDetails(Unite $unite, array $data, bool $isUpdate = false)
    {
        //
    }

    public function userFavorites(int $userId, array $filters = [])
    {
        $query = Unite::with([
            'detail',
            'images',
            'features',
            'offers',

            'prices',
            'favorites',
        ])
            ->whereHas('favorites', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->withCount(['ratings', 'views'])
            ->withAvg('ratings', 'rating');

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['filter_by_type'])) {
            $query->where('type', $filters['filter_by_type']);
        }

        $hasPriceFrom = isset($filters['price_from']) && $filters['price_from'] !== '';
        $hasPriceTo = isset($filters['price_to']) && $filters['price_to'] !== '';

        if ($hasPriceFrom || $hasPriceTo) {
            $priceFrom = $hasPriceFrom ? $filters['price_from'] : 0;
            $priceTo = $hasPriceTo ? $filters['price_to'] : 999999999;

            $query->whereHas('prices', function ($q) use ($priceFrom, $priceTo) {
                $q->where(function ($sub) use ($priceFrom, $priceTo) {
                    $sub->whereBetween('price', [$priceFrom, $priceTo])
                        ->orWhereBetween('morning_price', [$priceFrom, $priceTo])
                        ->orWhereBetween('evening_price', [$priceFrom, $priceTo])
                        ->orWhereBetween('full_price', [$priceFrom, $priceTo]);
                });
            });
        }

        if (! empty($filters['services']) && is_array($filters['services'])) {
            $services = $filters['services'];

            $query->whereHas('features', function ($q) use ($services) {
                $q->whereIn('name', $services);
            });
        }

        $unites = $query->get();

        if (! empty($filters['nearest_to_lat_long']) && ! empty($filters['lat']) && ! empty($filters['long'])) {
            $lat = (float) $filters['lat'];
            $long = (float) $filters['long'];

            $unites = $unites->sortBy(function ($unite) use ($lat, $long) {
                if (is_null($unite->latitude) || is_null($unite->longitude)) {
                    return PHP_FLOAT_MAX;
                }

                return sqrt(
                    pow($unite->latitude - $lat, 2) +
                    pow($unite->longitude - $long, 2)
                );
            })->values();
        }

        return $unites;
    }

    protected function syncServices(Unite $unite, array $serviceIds = []): void
    {
        $ids = collect($serviceIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $unite->services()->sync($ids);
    }
}
