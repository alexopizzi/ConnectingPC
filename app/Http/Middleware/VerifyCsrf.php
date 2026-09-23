<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Container;
use App\Core\Logger;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Security\Csrf;

/** Verifica del token CSRF su ogni richiesta che modifica dati. */
final class VerifyCsrf implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        if ($request->isWrite()) {
            $token = $request->body[Csrf::FIELD] ?? $request->header('X-CSRF-Token');
            if (!$this->container->get(Csrf::class)->validate($token)) {
                $this->container->get(Logger::class)->warning('Token CSRF non valido', ['path' => $request->path]);
                throw new HttpException(419);
            }
        }

        return $next($request);
    }
}
