<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionPageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $permissions = Permission::query()
            ->where('guard_name', 'admin')
            ->when($search, fn ($q) => $q->where('name', 'like', "%$search%"))
            ->latest('id')
            ->paginate(10);

        return view('dashboard.admin.permissions.index', compact('permissions', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('permissions', 'name')->where('guard_name', 'admin')],
        ]);

        Permission::create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('lang.permission_created'));
    }

    public function update(Request $request, Permission $permission)
    {
        abort_if($permission->guard_name !== 'admin', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150',
                Rule::unique('permissions', 'name')->where('guard_name', 'admin')->ignore($permission->id),
            ],
        ]);

        $permission->update(['name' => $data['name']]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('lang.permission_updated'));
    }

    public function destroy(Permission $permission)
    {
        abort_if($permission->guard_name !== 'admin', 404);

        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('lang.permission_deleted'));
    }
}
