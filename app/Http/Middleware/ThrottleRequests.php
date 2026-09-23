<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Container;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Security\RateLimiter;

/** "throttle:massimo,secondi" — limite di richieste per IP (API pubbliche, proxy delle tile). */
final class ThrottleRequests implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        $max = (int) ($arguments[0] ?? 120);
        $window = (int) ($arguments[1] ?? 60);
        $limiter = $this->container->get(RateLimiter::class);
        $route = $request->attribute('route');
        $bucket = $limiter->key('throttle.' . ($route?->name ?? 'route'), $request->ip());

        if ($limiter->hit($bucket, $window) > $max) {
            throw new HttpException(429, '', ['Retry-After' => (string) $window]);
        }

        return $next($request);
    }
}
