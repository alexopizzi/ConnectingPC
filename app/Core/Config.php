<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Configurazione applicativa versionata (config/*.php), letta con chiavi puntate: "app.name".
 * I valori dipendenti dall'ambiente arrivano da Env dentro i file di config.
 */
final class Config
{
    /** @var array<string, array<mixed>> */
    private array $files = [];

    public function __construct(private readonly string $directory)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!array_key_exists($file, $this->files)) {
            $path = $this->directory . '/' . $file . '.php';
            $loaded = is_file($path) ? require $path : [];
            $this->files[$file] = is_array($loaded) ? $loaded : [];
        }

        $value = $this->files[$file];
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
