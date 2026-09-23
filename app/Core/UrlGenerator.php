<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Router;

/**
 * Costruzione degli URL: rotte con nome, asset con versione per il cache busting, URL assoluti per le email.
 */
final class UrlGenerator
{
    public function __construct(
        private readonly Router $router,
        private readonly string $basePath,
        private readonly string $appUrl,
        private readonly string $publicDirectory,
    ) {
    }

    /** @param array<string, string|int> $params */
    public function route(string $name, array $params = []): string
    {
        return $this->basePath . $this->router->path($name, $params);
    }

    /**
     * URL assoluto (email, link esterni): usa APP_URL, mai l'header Host della richiesta.
     *
     * @param array<string, string|int> $params
     */
    public function absoluteRoute(string $name, array $params = []): string
    {
        return rtrim($this->appUrl, '/') . $this->router->path($name, $params);
    }

    public function to(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }

    public function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = $this->publicDirectory . '/assets/' . $path;
        $version = is_file($file) ? (string) filemtime($file) : App::version();

        return $this->basePath . '/assets/' . $path . '?v=' . $version;
    }

    public function router(): Router
    {
        return $this->router;
    }
}
