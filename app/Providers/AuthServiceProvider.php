<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Department;
use App\Models\Unite;
use App\Models\UniteReservation;
use App\Policies\DepartmentPolicy;
use App\Policies\UnitePolicy;
use App\Policies\UniteReservationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Department::class => DepartmentPolicy::class,
        Unite::class => UnitePolicy::class,
        UniteReservation::class => UniteReservationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Admins use a completely separate guard and model (App\Models\Admin).
        // Returning true here short-circuits all policy checks so an Admin
        // instance never reaches the policy type-hinted for App\Models\User.
        Gate::before(function ($user, $ability) {
            if ($user instanceof Admin) {
                return true;
            }
        });
    }
}
