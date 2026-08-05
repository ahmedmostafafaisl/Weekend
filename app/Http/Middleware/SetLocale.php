<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if (! in_array($locale, ['en', 'ar'])) {
            $locale = 'ar';
        }

        App::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $explicit = $request->query('locale') ?? $request->header('X-Locale');
        if ($explicit) {
            return strtolower(substr($explicit, 0, 2));
        }

        // BUG FIX: this used to check Accept-Language before the session,
        // which broke the existing web language switcher (locale.switch
        // route) — browsers send Accept-Language automatically on nearly
        // every request, so a user's explicit in-app choice (which sets
        // session('locale')) would get silently overridden by their
        // browser's default header on their very next page load. An
        // explicit past choice must outrank a passive browser signal.
        //
        // hasSession() + session()->has() (not the bare session('locale',
        // $default) helper) is required here for two reasons: (1) StartSession
        // middleware only runs on the 'web' group, not 'api' — this
        // middleware runs on both, so unconditionally touching session
        // state would rely on undefined behavior for a pure API request
        // with no session ever started; hasSession() confirms one is
        // actually available first. (2) even when a session exists,
        // session('locale', $default) can't distinguish "the user never
        // set this" from "the user set it to a value that happens to match
        // the default" — has() checks genuine presence, not just a value.
        if ($request->hasSession() && $request->session()->has('locale')) {
            return $request->session()->get('locale');
        }

        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            // "ar-SA,ar;q=0.9,en;q=0.8" → "ar"
            $primary = strtolower(substr(trim(explode(',', $acceptLanguage)[0]), 0, 2));
            if (in_array($primary, ['en', 'ar'])) {
                return $primary;
            }
        }

        return config('app.locale', 'en');
    }
}
