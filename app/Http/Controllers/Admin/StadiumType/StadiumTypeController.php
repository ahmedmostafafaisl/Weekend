<?php

// app/Http/Controllers/Admin/StadiumType/StadiumTypeController.php

namespace App\Http\Controllers\Admin\StadiumType;

use App\Http\Controllers\Controller;
use App\Http\Requests\StadiumType\StoreStadiumTypeRequest;
use App\Http\Requests\StadiumType\UpdateStadiumTypeRequest;
use App\Http\Resources\StadiumType\StadiumTypeResource;
use App\Repositories\Interfaces\StadiumTypeRepositoryInterface;
use Illuminate\Http\Request;

class StadiumTypeController extends Controller
{
    protected $repo;

    public function __construct(StadiumTypeRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {

        $data = $this->repo->all();
        if ($request->wantsJson()) {

            return response()->json([
                'data' => StadiumTypeResource::collection($data),
            ]);
        }

        return view('dashboard.admin.stadium_types.index', ['stadiumTypes' => $data]);

    }

    public function store(StoreStadiumTypeRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('stadium_types', 'public');
        }

        $this->repo->create($data);

        return $request->wantsJson()
           ? new StadiumTypeResource($this->repo->create($data))
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

        return $request->wantsJson()
            ? new StadiumTypeResource($this->repo->find($id))
            : redirect()->route('admin.stadium_types.index')->with('success', __('lang.stadium_type_updated_successfully_msg'));
    }

    public function destroy(Request $request, $id)
    {
        $this->repo->delete($id);

        return $request->wantsJson()
                  ? response()->json(['message' => __('lang.stadium_type_deleted_successfully_msg')])
                  : redirect()->route('admin.stadium_types.index')->with('success', __('lang.stadium_type_deleted_successfully_msg'));

    }
}
