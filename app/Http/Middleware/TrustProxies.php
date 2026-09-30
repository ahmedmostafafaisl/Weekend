<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Proxies are configured per server via TRUSTED_PROXIES:
     *   unset          — trust none (default; right when PHP faces clients directly)
     *   10.0.0.1,…     — the proxy / load-balancer addresses in front of the app
     *   *              — trust whatever connects (only if the app is reachable
     *                    solely through a proxy, e.g. Cloudflare → origin firewall)
     *
     * This matters for every rate limiter and the login lockout, which key on
     * $request->ip(): behind an untrusted proxy all visitors share one IP (one
     * busy minute locks everyone out); trusting '*' without a proxy lets
     * attackers spoof X-Forwarded-For to dodge the limits.
     */
    protected function proxies()
    {
        $value = trim((string) config('app.trusted_proxies', ''));

        if ($value === '') {
            return null;
        }

        return $value === '*' ? '*' : array_map('trim', explode(',', $value));
    }
}
