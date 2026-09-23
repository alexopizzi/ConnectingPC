<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\App;
use App\Core\Config;
use App\Core\Container;
use App\Core\Logger;
use App\Core\View;
use App\Http\Middleware\Middleware;
use App\Security\SecurityHeaders;
use LogicException;
use Throwable;

/**
 * Ciclo della richiesta: manutenzione → rotta → middleware della rotta → controller;
 * errori convertiti in pagine localizzate; header di sicurezza su ogni risposta.
 */
final class Kernel
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->dispatch($request);
        } catch (HttpException $e) {
            $response = $this->container->get(ErrorRenderer::class)->render($request, $e->status);
            foreach ($e->headers as $name => $value) {
                $response->withHeader($name, $value);
            }
        } catch (Throwable $e) {
            $this->container->get(Logger::class)->error('Eccezione non gestita', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => str_replace(APP_BASE_PATH, '', $e->getFile()) . ':' . $e->getLine(),
            ]);
            $response = $this->container->get(ErrorRenderer::class)->render($request, 500, App::isDebug() ? $e : null);
        }

        if ($request->attribute('session_started') === true) {
            $response->withHeader('Cache-Control', 'no-store, private');
        }
        $response->withHeader('X-Request-Id', $this->container->get(Logger::class)->requestId());

        return SecurityHeaders::apply($request, $response);
    }

    private function dispatch(Request $request): Response
    {
        if ($this->inMaintenance() && $request->path !== '/health') {
            throw new HttpException(503, '', ['Retry-After' => '600']);
        }

        $match = $this->container->get(Router::class)->match($request->method, $request->path)
            ?? throw new HttpException(404);
        $route = $match['route'];
        $request->attributes = [...$request->attributes, ...$match['params']];
        $request->attributes['route'] = $route;
        $request->attributes['area'] = $route->area;

        $view = $this->container->get(View::class);
        $view->share('route_name', $route->name);
        $view->share('route_params', $match['params']);
        $view->share('query_params', array_filter($request->query, 'is_scalar'));

        $pipeline = fn (Request $r): Response => $this->callHandler($route, $r);
        foreach (array_reverse($route->middleware) as $alias) {
            $next = $pipeline;
            [$name, $arguments] = $this->parseAlias($alias);
            $middleware = $this->resolveMiddleware($name);
            $pipeline = static fn (Request $r): Response => $middleware->handle($r, $next, $arguments);
        }

        return $pipeline($request);
    }

    private function callHandler(Route $route, Request $request): Response
    {
        $handler = $route->handler;
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class($this->container);

            return $controller->$method($request);
        }

        return $handler($request, $this->container);
    }

    /** @return array{0: string, 1: list<string>} */
    private function parseAlias(string $alias): array
    {
        $parts = explode(':', $alias, 2);

        return [$parts[0], isset($parts[1]) ? explode(',', $parts[1]) : []];
    }

    private function resolveMiddleware(string $name): Middleware
    {
        $class = $this->container->get(Config::class)->get('app.middleware.' . $name)
            ?? throw new LogicException('Middleware sconosciuto: ' . $name);
        $middleware = new $class($this->container);
        if (!$middleware instanceof Middleware) {
            throw new LogicException('Classe middleware non valida: ' . $class);
        }

        return $middleware;
    }

    private function inMaintenance(): bool
    {
        return is_file(APP_BASE_PATH . '/storage/maintenance/down');
    }
}
