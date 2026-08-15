<?php

// app/Http/Controllers/Admin/StadiumType/StadiumTypeController.php

namespace App\Http\Controllers\Admin\StadiumType;

use App\Http\Controllers\Controller;
use App\Http\Requests\StadiumType\StoreStadiumTypeRequest;
use App\Http\Requests\StadiumType\UpdateStadiumTypeRequest;
use App\Http\Resources\StadiumType\StadiumTypeResource;
use App\Repositories\Interfaces\StadiumTypeRepositoryInterface;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StadiumTypeController extends Controller
{
    use HasVersionedCache;

    protected $repo;

    public function __construct(StadiumTypeRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $cacheKey = $this->versionedCacheKey('stadium_types_index');

            $payload = Cache::remember($cacheKey, now()->addHours(24), function () {
                return StadiumTypeResource::collection($this->repo->all())->resolve();
            });

            return response()->json(['data' => $payload]);
        }

        $data = $this->repo->all();

        return view('dashboard.admin.stadium_types.index', ['stadiumTypes' => $data]);
    }

    public function store(StoreStadiumTypeRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('stadium_types', 'public');
        }

        $stadiumType = $this->repo->create($data);

        $this->bumpCacheVersion('stadium_types_index');

        return $request->wantsJson()
           ? new StadiumTypeResource($stadiumType)
           : redirect()->route('admin.stadium_types.index')->with('success', __('lang.stadium_type_created_successfully_msg'));

    }

    public function show($id)
    {
        if (request()->wantsJson()) {
            return new StadiumTypeResource($this->repo->find($id));
        }

        return view('dashboard.admin.stadium_types.show', ['stadiumType' => $this->repo->find($id)]);
    }

    public function update(UpdateStadiumTypeRequest $request, $id)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('stadium_types', 'public');
        }
        $this->repo->update($id, $data);

        $this->bumpCacheVersion('stadium_types_index');

        return $request->wantsJson()
            ? new StadiumTypeResource($this->repo->find($id))
            : redirect()->route('admin.stadium_types.index')->with('success', __('lang.stadium_type_updated_successfully_msg'));
    }

    public function destroy(Request $request, $id)
    {
        $this->repo->delete($id);

        $this->bumpCacheVersion('stadium_types_index');

        return $request->wantsJson()
                  ? response()->json(['message' => __('lang.stadium_type_deleted_successfully_msg')])
                  : redirect()->route('admin.stadium_types.index')->with('success', __('lang.stadium_type_deleted_successfully_msg'));

    }
}
