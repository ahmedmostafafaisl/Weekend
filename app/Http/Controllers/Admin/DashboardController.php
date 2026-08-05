<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
   public function index()
    {
        return view('dashboard.admin.index', [
            'total_admins' => Admin::count(),
            'total_roles' => Role::where('guard_name', 'admin')->count(),
            'total_permissions' => Permission::where('guard_name', 'admin')->count(),
        ]);
    }
}
