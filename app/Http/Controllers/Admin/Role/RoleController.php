<?php

namespace App\Http\Controllers\Admin\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\PaginatedCollection;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['store']);
        $this->middleware('permission:roles.update')->only(['update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search');

        $paginator = $this->roles->paginate($perPage, $search);

        return new PaginatedCollection($paginator);
    }

    public function show(int $id)
    {
        $role = $this->roles->findOrFail($id)->load('permissions');

        return response()->json(['data' => $role]);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = $this->roles->create($request->validated());

        return response()->json(['message' => 'Role created', 'data' => $role], 201);
    }

    public function update(UpdateRoleRequest $request, int $id)
    {
        $role = $this->roles->update($id, $request->validated());

        return response()->json(['message' => 'Role updated', 'data' => $role]);
    }

    public function destroy(int $id)
    {
        $this->roles->delete($id);

        return response()->json(['message' => 'Role deleted']);
    }
}
