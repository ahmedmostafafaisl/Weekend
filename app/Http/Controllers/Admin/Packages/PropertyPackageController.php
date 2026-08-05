<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Packages\StorePropertyPackageRequest;
use App\Http\Resources\Packages\PackageResource;
use App\Http\Resources\Packages\PropertyPackageResource;
use App\Repositories\Interfaces\PropertyPackageInterface;
use Illuminate\Http\Request;

class PropertyPackageController extends Controller
{
    protected PropertyPackageInterface $repo;

    public function __construct(PropertyPackageInterface $repo)
    {
        $this->repo = $repo;

        // property_packages.view intentionally NOT gated behind the permission
        // middleware: index()/show() are reached via auth:sanctum (customers/
        // providers browsing pricing — see AdPackageController for the full
        // explanation of why defaults.guard='admin' makes this middleware
        // reject every non-admin caller). getAllPackages() specifically is even
        // more exposed than that: routes/api.php registers
        // Route::get('all-packages', [PropertyPackageController::class, 'getAllPackages'])
        // with NO auth middleware at all — it's the public pricing page callers
        // hit before ever logging in. Applying permission middleware to it
        // would 403 every anonymous visitor. Enabling this WILL break both the
        // public pricing page and authenticated package browsing.
        $this->middleware('permission:property_packages.create')->only(['store']);
        $this->middleware('permission:property_packages.update')->only(['update']);
        $this->middleware('permission:property_packages.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->get('search');

        $propertyPackages = $this->repo->all();

        if ($request->wantsJson()) {
            return PropertyPackageResource::collection($propertyPackages);
        }

        $propertyPackages = collect($propertyPackages)
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
        $items = $propertyPackages->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $propertyPackages->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('dashboard.admin.property-packages.index', [
            'packages' => $paginated,
            'search' => $search,
            'types' => ['time', 'percentage'],
            'statuses' => ['active', 'inactive'],
        ]);
    }

    public function create()
    {
        return view('dashboard.admin.property-packages.create');
    }

    public function store(StorePropertyPackageRequest $request)
    {
        $data = $request->validated();

        $package = $this->repo->create($data);

        return $request->wantsJson()
            ? new PropertyPackageResource($package)
            : back()->with('success', __('lang.package_created_successfully_msg'));
    }

    public function edit($id)
    {
        $propertyPackage = $this->repo->find($id);

        return view('dashboard.admin.property-packages.edit', compact('propertyPackage'));
    }

    public function update(StorePropertyPackageRequest $request, $id)
    {
        $data = $request->validated();

        $propertyPackage = $this->repo->update($id, $data);

        return $request->wantsJson()
            ? new PropertyPackageResource($propertyPackage)
            : back()->with('success', __('lang.package_updated_successfully_msg'));
    }

    public function destroy(Request $request, $id)
    {
        $this->repo->delete($id);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.deleted_successfully')])
            : back()->with('success', __('lang.package_deleted_successfully_msg'));
    }

    public function show(Request $request, $id)
    {
        $package = $this->repo->find($id);

        return $request->wantsJson()
            ? new PropertyPackageResource($package)
            : view('dashboard.admin.property-packages.show', compact('package'));
    }

    public function getAllPackages(Request $request)
    {
        $packages = $this->repo->getAllPackages();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => new PackageResource($packages),
            ], 200);
        }

        return view('dashboard.admin.property-packages.index', [
            'packages' => $packages,
            'search' => null,
            'types' => ['time', 'percentage'],
            'statuses' => ['active', 'inactive'],
        ]);
    }
}
