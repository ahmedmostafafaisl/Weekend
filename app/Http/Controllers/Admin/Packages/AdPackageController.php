<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Packages\StoreAdPackageRequest;
use App\Http\Resources\Packages\AdPackageResource;
use App\Repositories\Interfaces\AdPackageInterface;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdPackageController extends Controller
{
    use HasVersionedCache;

    public function __construct(private AdPackageInterface $repository)
    {
        // ad_packages.view intentionally NOT gated behind the permission
        // middleware: index()/show() are reached via routes/api.php's
        // Route::middleware('auth:sanctum')->group(fn () => apiResource('ad-packages'...)) —
        // i.e. regular customers/providers browsing package pricing, not just
        // admins. config/permission.php sets defaults.guard='admin' project-wide,
        // so permission:ad_packages.view would check Auth::guard('admin')->user()
        // regardless of the Sanctum guard that actually authenticated the
        // request — every non-admin caller would get a hard 403. Enabling this
        // WILL break package browsing for customers/providers in production.
        $this->middleware('permission:ad_packages.create')->only(['store']);
        $this->middleware('permission:ad_packages.update')->only(['update']);
        $this->middleware('permission:ad_packages.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->get('search');

        if ($request->wantsJson()) {
            $cacheKey = $this->versionedCacheKey('ad_packages_index');

            $payload = Cache::remember($cacheKey, now()->addHours(24), function () {
                return AdPackageResource::collection($this->repository->all())->resolve();
            });

            return response()->json(['data' => $payload]);
        }

        $adPackages = $this->repository->all();

        $adPackages = collect($adPackages)
            ->filter(function ($package) use ($search) {
                if (! $search) {
                    return true;
                }

                $search = mb_strtolower($search);

                return str_contains(mb_strtolower($package->name ?? ''), $search)
                    || str_contains(mb_strtolower($package->description ?? ''), $search);
            })
            ->values();

        $perPage = 10;
        $currentPage = request()->integer('page', 1);
        $items = $adPackages->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $adPackages->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('dashboard.admin.ad-packages.index', [
            'packages' => $paginated,
            'search' => $search,
            'types' => ['count', 'duration'],
            'statuses' => ['active', 'inactive'],
        ]);
    }

    public function create()
    {
        return view('dashboard.admin.ad-packages.create');
    }

    public function store(StoreAdPackageRequest $request)
    {
        $adPackage = $this->repository->create($request->validated());

        $this->bumpCacheVersion('ad_packages_index');
        $this->bumpCacheVersion('property_packages_all');

        return $request->wantsJson()
            ? new AdPackageResource($adPackage)
            : back()->with('success', __('lang.package_created_successfully_msg'));
    }

    public function show($id, Request $request)
    {
        $adPackage = $this->repository->find($id);

        return $request->wantsJson()
            ? new AdPackageResource($adPackage)
            : view('dashboard.admin.ad-packages.show', compact('adPackage'));
    }

    public function edit($id)
    {
        $adPackage = $this->repository->find($id);

        return view('dashboard.admin.ad-packages.edit', compact('adPackage'));
    }

    public function update(StoreAdPackageRequest $request, $id)
    {
        $adPackage = $this->repository->update($id, $request->validated());

        $this->bumpCacheVersion('ad_packages_index');
        $this->bumpCacheVersion('property_packages_all');

        return $request->wantsJson()
            ? new AdPackageResource($adPackage)
            : back()->with('success', __('lang.package_updated_successfully_msg'));
    }

    public function destroy($id, Request $request)
    {
        $this->repository->delete($id);

        $this->bumpCacheVersion('ad_packages_index');
        $this->bumpCacheVersion('property_packages_all');

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.deleted_successfully')])
            : back()->with('success', __('lang.package_deleted_successfully_msg'));
    }
}
