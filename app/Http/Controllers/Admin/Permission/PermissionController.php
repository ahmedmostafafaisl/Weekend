<?php

namespace App\Http\Controllers\Admin\Permission;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginatedCollection;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Repositories\Interfaces\PermissionRepositoryInterface;

class PermissionController extends Controller
{
    public function __construct(private PermissionRepositoryInterface $permissions)
    {
        // middleware permissions optional
    }

    public function index(Request $request)
    {
        $perPage = (int)($request->get('per_page', 15));
        $search = $request->get('search');
        $paginator = $this->permissions->paginate($perPage, $search);

         return new PaginatedCollection($paginator);
    }

    public function show(int $id)
    {
        return response()->json(['data' => $this->permissions->findOrFail($id)]);
    }

    public function store(StorePermissionRequest $request)
    {
        $permission = $this->permissions->create($request->validated());
        return response()->json(['message' => 'Permission created', 'data' => $permission], 201);
    }

    public function update(UpdatePermissionRequest $request, int $id)
    {
        $permission = $this->permissions->update($id, $request->validated());
        return response()->json(['message' => 'Permission updated', 'data' => $permission]);
    }

    public function destroy(int $id)
    {
        $this->permissions->delete($id);
        return response()->json(['message' => 'Permission deleted']);
    }
}
