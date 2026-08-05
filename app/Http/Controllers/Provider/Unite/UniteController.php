<?php

namespace App\Http\Controllers\Provider\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\RateUniteRequest;
use App\Http\Requests\Unite\RateVendorRequest;
use App\Http\Requests\Unite\StoreUniteRequest;
use App\Http\Resources\Unite\FavoriteUniteResource;
use App\Http\Resources\Unite\SingleUniteResource;
use App\Http\Resources\Unite\UniteResource;
use App\Http\Resources\Unite\UniteResource2;
use App\Models\Ad;
use App\Models\Department;
use App\Models\FavoriteUnite;
use App\Models\Unite;
use App\Models\UniteRating;
use App\Models\UniteView;
use App\Models\User;
use App\Models\VendorRating;
use App\Repositories\Interfaces\UniteRepositoryInterface;
use Illuminate\Http\Request;

class UniteController extends Controller
{
    public function __construct(protected UniteRepositoryInterface $repo) {}

    // -------------------------------------------------------------------------
    // Public browsing — no auth required
    // -------------------------------------------------------------------------

    public function index(Request $req)
    {
        $filters = $req->only(['search', 'type', 'status']);
        $unites = $this->repo->all($filters);

        return $req->wantsJson()
            ? UniteResource::collection($unites)
            : view('dashboard.web.unites.index', compact('unites'));
    }

    /**
     * GET /api/unites/search — filtered venue discovery for mobile app
     *
     * Query params:
     *   type                   — stadium | hall | lounge | camp
     *   city                   — partial match on location_name
     *   date                   — Y-m-d (filters out booked/unavailable venues)
     *   period_type            — morning | evening | full_day (used with date)
     *   max_price              — numeric SAR
     *   min_capacity           — integer
     *   families_and_singles   — families | singles | both
     *   per_page               — 1-50 (default 20)
     *
     * Each result includes date_availability if date was requested.
     */
    public function search(Request $req)
    {
        $req->validate([
            'type' => ['nullable', 'in:stadium,hall,lounge,camp'],
            'city' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'period_type' => ['nullable', 'in:morning,evening,full_day'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_capacity' => ['nullable', 'integer', 'min:1'],
            'families_and_singles' => ['nullable', 'in:families,singles,both'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort_by' => ['nullable', 'in:price,rating,distance'],
            'lat' => ['nullable', 'numeric', 'between:-90,90',
                'required_if:sort_by,distance'],
            'lng' => ['nullable', 'numeric', 'between:-180,180',
                'required_if:sort_by,distance'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $filters = $req->only([
            'type', 'city', 'date', 'period_type',
            'min_price', 'max_price', 'min_capacity', 'families_and_singles',
            'min_rating', 'sort_by', 'lat', 'lng',
        ]);

        $perPage = min((int) ($req->per_page ?? 20), 50);
        $results = $this->repo->search($filters, $perPage);

        // Map to resource — handle both paginator and plain collection
        if ($results instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return response()->json([
                'success' => true,
                'data' => UniteResource::collection($results->items()),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                ],
                'filters_applied' => array_filter($filters),
            ]);
        }

        // Date filter returns plain collection (filtered post-pagination)
        return response()->json([
            'success' => true,
            'data' => UniteResource::collection($results),
            'meta' => ['total' => $results->count()],
            'filters_applied' => array_filter($filters),
        ]);
    }

    /**
     * GET /api/unites2
     *
     * filter_by (sorting — new): price_asc | price_desc | rating_asc | rating_desc
     *   - price_*: sorts by each venue's own cheapest price across all its
     *     price rows/columns (the same "starting from" figure a venue card
     *     would display).
     *   - rating_*: sorts by average customer rating.
     */
    public function index2(Request $req)
    {
        $filters = $req->only([
            'search', 'nearest_to_lat_long', 'lat', 'long',
            'rating', 'services', 'new_place',
            'price_from', 'price_to', 'filter_by_type', 'filter_by',
        ]);

        $userId = auth('sanctum')->id();
        $result = $this->repo->all2($filters, $userId);
        $unites = $result['unites'];

        return $req->wantsJson()
            ? UniteResource2::collection($unites)->additional([
                'meta' => [
                    'min_price' => $result['min_price'],
                    'max_price' => $result['max_price'],
                ],
            ])
            : view('dashboard.web.unites.index', compact('unites'));
    }

    public function show(Request $req, $id)
    {
        // Guard: if the segment isn't numeric it was meant for another route
        if (! is_numeric($id)) {
            abort(404);
        }

        $unite = $this->repo->find((int) $id);

        abort_unless($unite, 404);

        // Track views — use try/catch to silently handle duplicate IP entries
        // (unique index on unite_id + ip_address can race on concurrent requests)
        try {
            if ($req->user()) {
                UniteView::firstOrCreate(
                    ['unite_id' => $unite->id, 'user_id' => $req->user()->id],
                    ['ip_address' => $req->ip()]
                );
            } else {
                UniteView::firstOrCreate(
                    ['unite_id' => $unite->id, 'ip_address' => $req->ip()],
                    ['user_id' => null]
                );
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Already recorded this view — safe to ignore
        }

        // Re-fetch with full eager-loading after view tracking
        $unite = $this->repo->find((int) $id);

        // Return resource for API requests (prefixed /api) OR explicit JSON accept
        if ($req->wantsJson() || $req->is('api/*')) {
            return new SingleUniteResource($unite);
        }

        return view('dashboard.web.unites.show', compact('unite'));
    }

    // -------------------------------------------------------------------------
    // Provider mutations — guarded by UnitePolicy
    // -------------------------------------------------------------------------

    public function create()
    {
        $this->authorize('create', Unite::class);

        $departments = Department::all();
        $insurancePolicies = \App\Models\InsurancePolicy::orderBy('name')->get();

        return view('dashboard.web.unites.create', compact('departments', 'insurancePolicies'));
    }

    public function store(StoreUniteRequest $request)
    {
        $this->authorize('create', Unite::class);

        // Verify the chosen department belongs to this provider
        $department = Department::findOrFail($request->validated()['department_id']);
        if ($request->wantsJson() && $department->user_id !== auth()->id()) {
            abort(403, __('lang.venue_not_owned'));
        }

        $data = $request->validated();
        $unite = $this->repo->create($data);

        if ($request->boolean('add_to_story')) {
            $firstImage = $unite->images()->first();
            Ad::create([
                'user_id' => auth()->id(),
                'property_id' => $unite->id,
                'type' => 'property',
                'title' => $unite->name,
                'description' => $unite->description,
                'thumbnail' => $firstImage?->image,
                'media' => $firstImage?->image,
                'is_active' => true,
                'activated_at' => now(),
                'expires_at' => now()->addDay(),
            ]);
        }

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.unite_created_successfully'), 'data' => $unite], 201)
            : redirect()->route('unites.index')->with('success', __('lang.unite_created_successfully'));
    }

    public function edit($id)
    {
        $unite = $this->repo->find($id);
        abort_unless($unite, 404);

        $this->authorize('update', $unite);

        $departments = Department::all();
        $insurancePolicies = \App\Models\InsurancePolicy::orderBy('name')->get();
        // dd($unite->offers);

        return view('dashboard.web.unites.edit', compact('unite', 'departments', 'insurancePolicies'));
    }

    public function update(StoreUniteRequest $request, Unite $unite)
    {
        $this->authorize('update', $unite);

        $result = $this->repo->update($unite, $request->validated());

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.unite_updated_successfully'), 'data' => $result])
            : redirect()->route('unites.index')->with('success', __('lang.unite_updated_successfully'));
    }

    /**
     * PATCH /unites/{unite}/toggle-approval
     * Flip the requires_approval boolean. Super-admin and admin only.
     */
    public function toggleApproval(Unite $unite)
    {
        $unite->update(['requires_approval' => ! $unite->requires_approval]);

        $state = $unite->requires_approval ? 'enabled' : 'disabled';

        return back()->with('success', "Approval mode {$state} for {$unite->name}.");
    }

    public function destroy(Request $req, $id)
    {
        $unite = Unite::findOrFail($id);

        $this->authorize('delete', $unite);

        $this->repo->delete($id);

        return $req->wantsJson()
            ? response()->json(null, 204)
            : redirect()->route('unites.index')->with('success', 'Deleted.');
    }

    // -------------------------------------------------------------------------
    // Customer interactions — authenticated users only
    // -------------------------------------------------------------------------

    public function toggleFavorite($id, Request $request)
    {
        $user = $request->user();
        $unite = Unite::findOrFail($id);

        $favorite = FavoriteUnite::where('user_id', $user->id)
            ->where('unite_id', $unite->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json(['message' => __('lang.removed_from_favorites'), 'is_favorite' => false]);
        }

        FavoriteUnite::create(['user_id' => $user->id, 'unite_id' => $unite->id]);

        return response()->json(['message' => __('lang.added_to_favorites'), 'is_favorite' => true]);
    }

    public function rate($id, RateUniteRequest $request)
    {
        $unite = Unite::findOrFail($id);
        $user = $request->user();

        $rating = UniteRating::updateOrCreate(
            ['unite_id' => $unite->id, 'user_id' => $user->id],
            ['rating' => $request->rating, 'review' => $request->review]
        );

        return response()->json(['message' => __('lang.unite_rated_successfully'), 'data' => $rating]);
    }

    public function rateVendor($id, RateVendorRequest $request)
    {
        $vendor = User::findOrFail($id);
        $user = $request->user();

        if ($vendor->id === $user->id) {
            return response()->json(['message' => __('lang.cannot_rate_yourself')], 422);
        }

        $rating = VendorRating::updateOrCreate(
            ['vendor_user_id' => $vendor->id, 'user_id' => $user->id],
            ['rating' => $request->rating, 'review' => $request->review]
        );

        return response()->json(['message' => __('lang.vendor_rated_successfully'), 'data' => $rating]);
    }

    public function userFavorites(Request $request)
    {
        $filters = $request->only([
            'search', 'nearest_to_lat_long', 'lat', 'long',
            'rating', 'services', 'new_place',
            'price_from', 'price_to', 'filter_by_type', 'filter_by',
        ]);

        $unites = $this->repo->userFavorites($request->user()->id, $filters);

        return response()->json([
            'unites' => FavoriteUniteResource::collection($unites)->resolve(),
        ]);
    }
}
