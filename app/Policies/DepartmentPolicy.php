<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    // -------------------------------------------------------------------------
    // Admins bypass all checks — defined in AuthServiceProvider Gate::before
    // -------------------------------------------------------------------------

    /**
     * Any authenticated user can view the list (providers see only their own
     * departments via DepartmentController::index scoping).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can view a single department.
     */
    public function view(User $user, Department $department): bool
    {
        return true;
    }

    /**
     * Only providers can create departments, and the department will belong to them.
     */
    public function create(User $user): bool
    {
        return $user->type === 'provider';
    }

    /**
     * Only the provider who owns this department can update it.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->type === 'provider'
            && $department->user_id === $user->id;
    }

    /**
     * Only the owning provider can delete their department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->type === 'provider'
            && $department->user_id === $user->id;
    }
}
