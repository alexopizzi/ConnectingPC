<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Authorization\Gate;
use App\Core\Container;
use App\Core\View;
use App\Http\Request;
use App\Http\Response;

/** "guest" — le pagine di login e recupero password non servono a chi è già autenticato. */
final class RedirectIfAuthenticated implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        $user = $this->container->get(Auth::class)->user();
        if ($user !== null) {
            $view = $this->container->get(View::class);
            $target = $this->container->get(Gate::class)->allows($user, 'admin.access')
                ? $view->route('admin.dashboard')
                : $view->route('portal.dashboard');

            return Response::redirect($target);
        }

        return $next($request);
    }
}
