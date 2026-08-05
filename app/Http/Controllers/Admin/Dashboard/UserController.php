<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserInterface $users)
    {
        $this->middleware('permission:users.view')->only(['index']);
        $this->middleware('permission:users.create')->only(['store']);
        $this->middleware('permission:users.update')->only(['update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {

        $search = $request->get('search');
        $users = $this->users->paginate($search, 10);

        return view('dashboard.admin.users.index', [
            'users' => $users,
            'search' => $search,
            'statuses' => User::STATUSES,
            'types' => User::TYPES,
            'providerTypes' => User::PROVIDER_TYPES,
            'nations' => User::NATIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'numeric', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'], // password_confirmation
            'status' => ['required', 'in:active,inactive'],
            'type' => ['required', 'in:customer,provider'],
            'nation' => ['required', 'in:saudi,resident'],

            'provider_type' => ['nullable', 'in:individual,organization'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'ownership' => ['nullable', 'in:0,1,2'], // لو فعلاً عندك 2 => موكل
            'delegation' => ['nullable', 'string', 'max:255'],

            'commercial_register_number' => ['nullable', 'string', 'max:190'],
            'organization_name' => ['nullable', 'string', 'max:190'],
            'commercial_name' => ['nullable', 'string', 'max:190'],

            // files
            'photo' => ['nullable', 'image', 'max:4096'],
            'front_identity' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'back_identity' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'sak_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'commercial_register_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        // لو النوع customer نخلي provider_type null
        if (($data['type'] ?? 'customer') === 'customer') {
            $data['provider_type'] = null;
        }

        $this->users->create($data);

        return back()->with('success', __('lang.user_created_successfully_msg'));
    }

    public function show(int $id)
    {
        $user = $this->users->find($id);

        return view('dashboard.admin.users.show', compact('user'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', "unique:users,email,{$id}"],
            'phone' => ['nullable', 'numeric', "unique:users,phone,{$id}"],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'type' => ['required', 'in:customer,provider'],
            'nation' => ['required', 'in:saudi,resident'],

            'provider_type' => ['nullable', 'in:individual,organization'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'ownership' => ['nullable', 'in:0,1,2'],
            'delegation' => ['nullable', 'string', 'max:255'],

            'commercial_register_number' => ['nullable', 'string', 'max:190'],
            'organization_name' => ['nullable', 'string', 'max:190'],
            'commercial_name' => ['nullable', 'string', 'max:190'],

            // files
            'photo' => ['nullable', 'image', 'max:4096'],
            'front_identity' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'back_identity' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'sak_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'commercial_register_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        if (($data['type'] ?? 'customer') === 'customer') {
            $data['provider_type'] = null;
        }

        $this->users->update($id, $data);

        return back()->with('success', __('lang.user_updated_successfully_msg'));
    }

    public function toggleStatus(int $id): \Illuminate\Http\RedirectResponse
    {
        $user = \App\Models\User::findOrFail($id);
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);
        $state = $user->status === 'active' ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->name} has been {$state}.");
    }

    public function destroy(int $id)
    {
        $this->users->delete($id);

        return back()->with('success', __('lang.user_deleted_successfully_msg'));
    }
}
