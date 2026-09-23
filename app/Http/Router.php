<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;

/**
 * Tabella delle rotte (config/routes.php) e generazione degli URL per nome.
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $named = [];

    /** @var list<array{prefix: string, middleware: list<string>, area: ?string}> */
    private array $groups = [];

    /** @param array{0: class-string, 1: string}|callable $handler */
    public function get(string $path, array|callable $handler): Route
    {
        return $this->add(['GET', 'HEAD'], $path, $handler);
    }

    /** @param array{0: class-string, 1: string}|callable $handler */
    public function post(string $path, array|callable $handler): Route
    {
        return $this->add(['POST'], $path, $handler);
    }

    /**
     * @param array{prefix?: string, middleware?: list<string>, area?: string} $attributes
     * @param callable(self): void $routes
     */
    public function group(array $attributes, callable $routes): void
    {
        $this->groups[] = [
            'prefix' => $attributes['prefix'] ?? '',
            'middleware' => $attributes['middleware'] ?? [],
            'area' => $attributes['area'] ?? null,
        ];
        $routes($this);
        array_pop($this->groups);
    }

    /**
     * @return array{route: Route, params: array<string, string>}|null
     * @throws HttpException 405 se il percorso esiste ma con un altro metodo
     */
    public function match(string $method, string $path): ?array
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            $params = $route->match($path);
            if ($params === null) {
                continue;
            }
            if (in_array($method, $route->methods, true)) {
                return ['route' => $route, 'params' => $params];
            }
            $allowed = [...$allowed, ...$route->methods];
        }
        if ($allowed !== []) {
            throw new HttpException(405, '', ['Allow' => implode(', ', array_unique($allowed))]);
        }

        return null;
    }

    /**
     * Percorso della rotta con nome (senza prefisso di installazione).
     *
     * @param array<string, string|int> $params i parametri non usati nel percorso diventano query string
     */
    public function path(string $name, array $params = []): string
    {
        $this->named = $this->named ?: $this->indexNames();
        $route = $this->named[$name] ?? throw new InvalidArgumentException('Rotta sconosciuta: ' . $name);

        $path = $route->path;
        foreach ($route->parameters as $parameter) {
            if (!array_key_exists($parameter, $params)) {
                throw new InvalidArgumentException("Parametro mancante '$parameter' per la rotta $name");
            }
            $path = preg_replace('/\{' . $parameter . '(?::(?:[^{}]|\{[^{}]*\})+)?\}/', rawurlencode((string) $params[$parameter]), $path) ?? $path;
            unset($params[$parameter]);
        }

        return $path . ($params === [] ? '' : '?' . http_build_query($params));
    }

    /** @return list<string> nomi dei parametri del percorso della rotta */
    public function parameters(string $name): array
    {
        $this->named = $this->named ?: $this->indexNames();

        return isset($this->named[$name]) ? $this->named[$name]->parameters : [];
    }

    public function has(string $name): bool
    {
        $this->named = $this->named ?: $this->indexNames();

        return isset($this->named[$name]);
    }

    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string}|callable $handler
     */
    private function add(array $methods, string $path, array|callable $handler): Route
    {
        $prefix = '';
        $middleware = [];
        $area = null;
        foreach ($this->groups as $group) {
            $prefix .= $group['prefix'];
            $middleware = [...$middleware, ...$group['middleware']];
            $area = $group['area'] ?? $area;
        }
        $full = '/' . trim($prefix . $path, '/');

        $route = new Route($methods, $full, $handler);
        $route->middleware(...$middleware);
        $route->area = $area;
        $this->routes[] = $route;
        $this->named = [];

        return $route;
    }

    /** @return array<string, Route> */
    private function indexNames(): array
    {
        $named = [];
        foreach ($this->routes as $route) {
            if ($route->name !== null) {
                $named[$route->name] = $route;
            }
        }

        return $named;
    }
}
