<?php

namespace App\Http\Controllers\AdminAuth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // An already-authenticated admin landing here (e.g. via the root "/"
        // redirect, or a bookmarked login link) should go straight to their
        // real dashboard rather than seeing the login form again. This check
        // replaces the guest:admin middleware that used to sit on this route —
        // that middleware redirected to RouteServiceProvider::HOME ('/dashboard'),
        // a constant shared with the provider/customer login flow, which is a
        // different, generic Breeze scaffold page rather than the actual admin
        // dashboard at admin.dashboard.
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard.admin.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AdminLoginRequest $request)
    {
        $request->authenticate('admin');

        $request->session()->regenerate();

        return \to_route('admin.index');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return \to_route('admin.login');
    }
}
