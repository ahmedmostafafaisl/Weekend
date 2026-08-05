<?php

namespace App\Http\Controllers\Admin\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\StoreUnitePackageRequest;
use App\Http\Resources\Unite\UnitePackageResource;
use App\Models\Unite;
use App\Repositories\Interfaces\UnitePackageInterface;
use Illuminate\Http\Request;

class UnitePackageController extends Controller
{
    public function __construct(
        protected UnitePackageInterface $repo
    ) {}

    public function index(Request $request, Unite $unite)
    {
        $packages = $this->repo->allByUnite($unite);

        if ($request->wantsJson()) {
            return UnitePackageResource::collection($packages);
        }

        return view('dashboard.admin.unite-packages.index', compact('unite', 'packages'));
    }

    public function show(Request $request, Unite $unite, int $package)
    {
        $package = $this->repo->findByUnite($unite, $package);

        abort_unless($package, 404);

        if ($request->wantsJson()) {
            return new UnitePackageResource($package);
        }

        return view('dashboard.admin.unite-packages.show', compact('unite', 'package'));
    }

    public function create(Unite $unite)
    {
        return view('dashboard.admin.unite-packages.create', compact('unite'));
    }

    public function store(StoreUnitePackageRequest $request, Unite $unite)
    {
        $package = $this->repo->createForUnite($unite, $request->validated());

        return $request->wantsJson()
            ? new UnitePackageResource($package)
            : redirect()->route('admin.unite-packages.index', $unite->id)
                ->with('success', __('lang.package_created_successfully_msg'));
    }

    public function edit(Unite $unite, int $package)
    {
        $package = $this->repo->findByUnite($unite, $package);

        abort_unless($package, 404);

        return view('dashboard.admin.unite-packages.edit', compact('unite', 'package'));
    }

    public function update(StoreUnitePackageRequest $request, Unite $unite, int $package)
    {
        $package = $this->repo->updateForUnite($unite, $package, $request->validated());

        return $request->wantsJson()
            ? new UnitePackageResource($package)
            : redirect()->route('admin.unite-packages.show', [$unite->id, $package->id])
                ->with('success', __('lang.package_updated_successfully_msg'));
    }

    public function destroy(Request $request, Unite $unite, int $package)
    {
        $this->repo->deleteForUnite($unite, $package);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.package_deleted_successfully_msg')])
            : redirect()->route('admin.unite-packages.index', $unite->id)
                ->with('success', __('lang.package_deleted_successfully_msg'));
    }
}
