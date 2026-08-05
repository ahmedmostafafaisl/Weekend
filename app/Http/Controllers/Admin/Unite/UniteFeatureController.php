<?php

namespace App\Http\Controllers\Admin\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\StoreUniteFeatureRequest;
use App\Http\Resources\Unite\UniteFeatureResource;
use App\Models\Unite;
use App\Repositories\Interfaces\UniteFeatureInterface;
use Illuminate\Http\Request;

class UniteFeatureController extends Controller
{
    public function __construct(
        protected UniteFeatureInterface $repo
    ) {}

    public function index(Request $request, Unite $unite)
    {
        $features = $this->repo->allByUnite($unite);

        if ($request->wantsJson()) {
            return UniteFeatureResource::collection($features);
        }

        return view('dashboard.admin.unite-features.index', compact('unite', 'features'));
    }

    public function show(Request $request, Unite $unite, int $feature)
    {
        $feature = $this->repo->findByUnite($unite, $feature);

        abort_unless($feature, 404);

        if ($request->wantsJson()) {
            return new UniteFeatureResource($feature);
        }

        return view('dashboard.admin.unite-features.show', compact('unite', 'feature'));
    }

    public function create(Unite $unite)
    {
        return view('dashboard.admin.unite-features.create', compact('unite'));
    }

    public function store(StoreUniteFeatureRequest $request, Unite $unite)
    {
        $feature = $this->repo->createForUnite($unite, $request->validated());

        return $request->wantsJson()
            ? new UniteFeatureResource($feature)
            : redirect()->route('admin.unite-features.index', $unite->id)
                ->with('success', __('lang.feature_created_successfully_msg'));
    }

    public function edit(Unite $unite, int $feature)
    {
        $feature = $this->repo->findByUnite($unite, $feature);

        abort_unless($feature, 404);

        return view('dashboard.admin.unite-features.edit', compact('unite', 'feature'));
    }

    public function update(StoreUniteFeatureRequest $request, Unite $unite, int $feature)
    {
        $feature = $this->repo->updateForUnite($unite, $feature, $request->validated());

        return $request->wantsJson()
            ? new UniteFeatureResource($feature)
            : redirect()->route('admin.unite-features.show', [$unite->id, $feature->id])
                ->with('success', __('lang.feature_updated_successfully_msg'));
    }

    public function destroy(Request $request, Unite $unite, int $feature)
    {
        $this->repo->deleteForUnite($unite, $feature);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.feature_deleted_successfully_msg')])
            : redirect()->route('admin.unite-features.index', $unite->id)
                ->with('success', __('lang.feature_deleted_successfully_msg'));
    }
}
