<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Unauthenticated auth endpoints. Kept moderate (20/min) because Saudi
        // mobile carriers put many subscribers behind one CGNAT address; the
        // per-email login lockout in LoginRequest handles targeted guessing.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(20)->by('auth|'.$request->ip());
        });

        // Each call can send an email: cap per IP (stops one host spraying many
        // addresses — mail-reputation risk) and per target email address.
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(5)->by('pwd-ip|'.$request->ip()),
                Limit::perHour(5)->by('pwd-email|'.strtolower((string) $request->input('email'))),
            ];
        });

        // Promo codes are guessable secrets: the generic 60/min allowed ~86k
        // guesses per IP per day.
        RateLimiter::for('promo', function (Request $request) {
            return Limit::perMinute(10)->by('promo|'.($request->user()?->id ?: $request->ip()));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
