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
        // The 3 policies below are type-hinted for App\Models\User, so an
        // Admin instance can never satisfy them structurally — this bypass
        // exists ONLY so $this->authorize(...)/@can(...) calls against
        // Department/Unite/UniteReservation don't fail outright for an
        // Admin. It must NOT apply to anything else.
        //
        // ROOT CAUSE FIX: this previously returned true unconditionally for
        // ANY $user instanceof Admin, regardless of $ability — but
        // Gate::before() intercepts every single ability check application-
        // wide, not just these 3 policies. That includes every Spatie
        // permission-name check ($me->can('admins.create'), ->can('users.
        // delete'), etc.), since Spatie's can() resolves through this same
        // Gate facade. The unconditional version silently made every
        // permission check across the whole admin dashboard return true
        // for every Admin, no matter their actual role or permissions —
        // which is exactly why a reviewer with only reservations.view could
        // see and use the entire dashboard: every @if($me->can(...)) check
        // anywhere in the app was being short-circuited to true right here,
        // before Spatie's actual role/permission lookup ever ran.
        //
        // Restricting the bypass to only fire when the resource being
        // authorized is actually one of these 3 policy-backed models (or
        // their class name) means every other ability check — including
        // every real permission string — now correctly falls through to
        // Spatie's normal resolution instead of being pre-empted here.
        $policyBackedClasses = [Department::class, Unite::class, UniteReservation::class];

        Gate::before(function ($user, $ability, $arguments = []) use ($policyBackedClasses) {
            if (! $user instanceof Admin) {
                return null;
            }

            $target = $arguments[0] ?? null;
            $targetClass = is_object($target) ? get_class($target) : $target;

            if (in_array($targetClass, $policyBackedClasses, true)) {
                return true;
            }

            // Not one of the 3 policy-backed models — let Spatie's actual
            // role/permission check decide, exactly as it would for any
            // other ability. Returning null (not false) here is
            // deliberate: null tells Gate::before() to fall through to the
            // next check rather than denying outright, which matters if
            // any other before()/after() hook is ever added later.
            return null;
        });
    }
}
