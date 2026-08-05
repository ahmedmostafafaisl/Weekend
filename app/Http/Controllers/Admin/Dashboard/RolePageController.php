<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $roles = Role::query()
            ->where('guard_name', 'admin')
            ->when($search, fn ($q) => $q->where('name', 'like', "%$search%"))
            ->with('permissions')
            ->latest('id')
            ->paginate(10);

        $permissions = Permission::where('guard_name', 'admin')->orderBy('name')->get();

        return view('dashboard.admin.roles.index', compact('roles', 'permissions', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->where('guard_name', 'admin')],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);

        $permissions = Permission::where('guard_name', 'admin')
            ->whereIn('id', $data['permission_ids'] ?? [])
            ->get();

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('lang.role_created_successfully_msg'));
    }

    public function update(Request $request, Role $role)
    {
        // ✅ safety: only admin guard roles
        abort_if($role->guard_name !== 'admin', 404);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('roles', 'name')->where('guard_name', 'admin')->ignore($role->id),
            ],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'name' => $data['name'],
        ]);

        $permissions = Permission::where('guard_name', 'admin')
            ->whereIn('id', $data['permission_ids'] ?? [])
            ->get();

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('lang.role_updated_successfully_msg'));
    }

    public function destroy(Role $role)
    {
        abort_if($role->guard_name !== 'admin', 404);

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('lang.role_deleted'));
    }
}
