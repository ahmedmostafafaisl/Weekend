<?php

namespace App\Http\Controllers\Provider\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\Department\DepartmentResource;
use App\Http\Resources\Unite\UniteResource;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Interfaces\DepartmentInterface;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentInterface $departmentRepo)
    {
        $this->middleware('permission:departments.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);

        if ($request->wantsJson()) {
            $user = auth()->user();

            $isProvider = $user && $user->type === 'provider';
            $departments = $isProvider
                ? $this->departmentRepo->getByUserId($user->id)
                : $this->departmentRepo->all();

            // The provider's current, non-exhausted property subscription's
            // remaining count -- returned once, at the top level only, as
            // a sibling to 'data' (not repeated inside every department
            // or unite entry, since they all belong to the same provider
            // and would all show the exact same number). Uses the same
            // shared method UniteController::store()'s unite-creation
            // gate itself relies on, so this always agrees with what that
            // gate would actually enforce. Only meaningful for the
            // authenticated-provider branch above, since all of that
            // provider's own departments genuinely share one subscription;
            // not meaningful when listing across multiple providers (the
            // admin/non-provider branch), since each could have a
            // different subscription -- explicitly null there rather than
            // a misleading single number.
            $maxCount = $isProvider ? $user->activePropertySubscription()?->count : null;

            return response()->json([
                'data' => DepartmentResource::collection($departments),
                'max_count' => $maxCount,
            ]);
        }

        $search = $request->get('search');

        $departments = Department::with('user')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $users = User::where('type', 'provider')->get();

        return view('dashboard.admin.departments.index', [
            'departments' => $departments,
            'users' => $users,
            'search' => $search,
            'types' => ['stadium', 'hall', 'lounge', 'camp'],
            'statuses' => ['active', 'inactive'],
        ]);
    }

    public function store(DepartmentRequest $request)
    {
        $this->authorize('create', Department::class);

        // Force user_id to the authenticated provider when called via API
        $data = $request->validated();
        if ($request->wantsJson()) {
            $data['user_id'] = auth()->id();
        }

        $department = $this->departmentRepo->create($data);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.department_created_successfully_msg'), 'data' => new DepartmentResource($department)], 201)
            : back()->with('success', __('lang.department_created_successfully_msg'));
    }

    public function show($id, Request $request)
    {
        $department = Department::with(['user', 'unites'])->findOrFail($id);

        $this->authorize('view', $department);

        $unites = $department->unites()->latest()->paginate(10)->withQueryString();

        return $request->wantsJson()
            ? new DepartmentResource($department)
            : view('dashboard.admin.departments.show', compact('department', 'unites'));
    }

    public function update(UpdateDepartmentRequest $request, $id)
    {
        $department = Department::findOrFail($id);

        $this->authorize('update', $department);

        $data = $request->validated();
        $department = $this->departmentRepo->update($id, $data);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.department_updated_successfully_msg'), 'data' => new DepartmentResource($department)])
            : back()->with('success', __('lang.department_updated_successfully_msg'));
    }

    public function destroy($id, Request $request)
    {
        $department = Department::findOrFail($id);

        $this->authorize('delete', $department);

        $this->departmentRepo->delete($id);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.deleted_successfully')])
            : back()->with('success', __('lang.deleted_successfully'));
    }

    /**
     * GET /departments/browse -- public, guest-accessible list of all
     * active departments. Deliberately lighter than DepartmentResource
     * (no nested unites[] per department, no social links/user info) --
     * a guest browsing all departments doesn't need every one of their
     * unites inlined here, since unites() below is the dedicated
     * endpoint for that; keeping this list lightweight also avoids an
     * N+1-prone eager load across every department just to list them.
     */
    public function browse(Request $request)
    {
        $type = $request->get('type');
        $location = $request->get('location');
        $search = $request->get('search');

        $departments = Department::where('status', 'active')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($location, fn ($q) => $q->where('location', 'like', "%{$location}%"))
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
            ))
            ->with('images')
            ->withCount(['unites' => fn ($q) => $q->where('status', 'active')])
            ->latest()
            ->paginate($request->integer('per_page', 20) ?: 20);

        return response()->json([
            'data' => collect($departments->items())->map(fn ($department) => [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'type' => $department->type,
                'location' => $department->location,
                'latitude' => $department->latitude,
                'longitude' => $department->longitude,
                'unites_count' => $department->unites_count,
                'images' => $department->images->map(fn ($img) => asset($img->image))->values(),
            ])->values(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
        ]);
    }

    /**
     * GET /departments/{department}/unites -- public, guest-accessible
     * list of one department's active unites. Uses UniteResource (the
     * same resource and eager-loading relationships already used by the
     * existing /unites listing endpoint -- see UniteRepository::all()),
     * so a guest browsing a specific department's venues sees the same
     * level of detail (pricing, rating, slots, etc.) as the main unite
     * listing, rather than a third, inconsistent shape for unite data.
     *
     * Query params:
     *   search    — partial match on name, description, or location_name
     *   sort_by   — price_asc | price_desc | rating (default: newest first)
     *   per_page  — 1-50 (default 20)
     */
    public function unites(Department $department, Request $request)
    {
        $search = $request->get('search');
        $sortBy = $request->get('sort_by');

        // Price subquery: MIN across all price columns for this unite.
        // Returns NULL (not a sentinel number) when a unite has no price
        // rows at all -- so ORDER BY can handle the "no price" case
        // cleanly by pushing NULLs last in both directions.
        // Matches the type-agnostic LEAST() pattern already used by
        // UniteRepository::search()'s sort_by=price.
        $priceSubquery =
            '(SELECT CASE
                WHEN COUNT(*) = 0 THEN NULL
                ELSE LEAST(
                    COALESCE(MIN(price),         999999),
                    COALESCE(MIN(morning_price), 999999),
                    COALESCE(MIN(evening_price), 999999),
                    COALESCE(MIN(full_price),    999999)
                )
            END FROM unite_prices WHERE unite_prices.unite_id = unites.id)';

        $unites = $department->unites()
            ->with([
                'detail', 'images', 'features', 'offers', 'slots', 'prices',
                'packages', 'bookingPackages', 'viewingTimes', 'newFeatures',
                'councils', 'services',
            ])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->where('status', 'active')
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location_name', 'like', "%{$search}%")
            ))
            ->when($sortBy === 'price_asc', fn ($q) => $q->orderByRaw("({$priceSubquery} IS NULL) ASC, {$priceSubquery} ASC"))
            ->when($sortBy === 'price_desc', fn ($q) => $q->orderByRaw("({$priceSubquery} IS NULL) ASC, {$priceSubquery} DESC"))
            ->when($sortBy === 'rating', fn ($q) => $q->orderByDesc('ratings_avg_rating'))
            ->when(! in_array($sortBy, ['price_asc', 'price_desc', 'rating']), fn ($q) => $q->latest())
            ->paginate($request->integer('per_page', 20) ?: 20);

        return response()->json([
            'data' => UniteResource::collection($unites)->resolve(),
            'meta' => [
                'current_page' => $unites->currentPage(),
                'last_page' => $unites->lastPage(),
                'per_page' => $unites->perPage(),
                'total' => $unites->total(),
            ],
        ]);
    }
}
