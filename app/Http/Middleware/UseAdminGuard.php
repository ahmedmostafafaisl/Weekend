<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UseAdminGuard
{
    public function handle($request, Closure $next)
    {
        Auth::shouldUse('admin');
        return $next($request);
    }
}
