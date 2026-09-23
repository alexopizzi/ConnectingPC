<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Authorization\Gate;
use App\Core\Container;
use App\Http\Request;
use App\Http\Response;

/** "can:permesso" — verifica un permesso senza risorsa specifica (dopo "auth"). */
final class Authorize implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        $this->container->get(Gate::class)->authorize($request->attribute('user'), $arguments[0] ?? '');

        return $next($request);
    }
}
