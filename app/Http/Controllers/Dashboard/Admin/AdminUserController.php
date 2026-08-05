<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function __construct()
    {
        // ✅ extra safety (even if routes already protected)
        $this->middleware('permission:admins.view')->only(['index']);
        $this->middleware('permission:admins.create')->only(['store']);
        $this->middleware('permission:admins.update')->only(['update']);
        $this->middleware('permission:admins.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->get('search');

        $admins = Admin::query()
            ->with(['roles.permissions']) // ✅ role + permissions only
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::query()
            ->where('guard_name', 'admin')
            ->orderBy('name')
            ->get();

        return view('dashboard.admin.admins.index', compact('admins', 'roles', 'search'));
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'admin'),
            ],
        ]);

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // ✅ single role
        $admin->syncRoles([$data['role']]);

        return back()->with('success', __('lang.admin_created_successfully_msg'));
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'admin'),
            ],
        ]);

        $admin->name = $data['name'];
        $admin->email = $data['email'];

        if (! empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        // ✅ single role
        $admin->syncRoles([$data['role']]);

        return back()->with('success', __('lang.admin_updated_successfully_msg'));
    }

    public function destroy(Admin $admin)
    {
        // ✅ prevent deleting yourself
        if (auth('admin')->id() === $admin->id) {
            return back()->with('error', __('lang.cannot_delete_own_account'));
        }

        $admin->delete();

        return back()->with('success', __('lang.admin_deleted_successfully_msg'));
    }
}
