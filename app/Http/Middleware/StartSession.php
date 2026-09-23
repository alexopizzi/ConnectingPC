<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Container;
use App\Core\Session;
use App\Http\Request;
use App\Http\Response;

/** Avvia la sessione solo sulle rotte che ne hanno bisogno (login, aree riservate). */
final class StartSession implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        $this->container->get(Session::class)->start();
        $request->attributes['session_started'] = true;

        return $next($request);
    }
}
