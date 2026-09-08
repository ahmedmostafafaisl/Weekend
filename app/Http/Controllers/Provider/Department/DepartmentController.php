<?php

namespace App\Http\Controllers\Provider\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DepartmentRequest;
use App\Http\Resources\Department\DepartmentResource;
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

    public function update(DepartmentRequest $request, $id)
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
}
