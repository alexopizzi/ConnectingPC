<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cache su file PHP (letti tramite OPcache). APCu e Redis non sono disponibili in produzione (vault "32").
 * Valori ammessi: scalari e array (serializzati con var_export).
 */
final class FileCache
{
    public function __construct(private readonly string $directory)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);
        if (!is_file($file)) {
            return $default;
        }
        $entry = @include $file;
        if (!is_array($entry) || !array_key_exists('value', $entry)) {
            return $default;
        }
        if ($entry['expires'] !== 0 && $entry['expires'] < time()) {
            $this->forget($key);

            return $default;
        }

        return $entry['value'];
    }

    /** @param int $ttl secondi; 0 = senza scadenza */
    public function set(string $key, mixed $value, int $ttl = 0): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            return;
        }
        $file = $this->path($key);
        $payload = '<?php return ' . var_export(['expires' => $ttl > 0 ? time() + $ttl : 0, 'value' => $value], true) . ';' . PHP_EOL;
        $temp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($temp, $payload, LOCK_EX) === false) {
            return;
        }
        rename($temp, $file);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $marker = new \stdClass();
        $value = $this->get($key, $marker);
        if ($value !== $marker) {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function forget(string $key): void
    {
        $file = $this->path($key);
        if (is_file($file)) {
            @unlink($file);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($file, true);
            }
        }
    }

    /** Cancella tutte le voci il cui nome inizia con il prefisso indicato. */
    public function clear(string $prefix = ''): void
    {
        foreach (glob($this->directory . '/' . $this->sanitize($prefix) . '*.php') ?: [] as $file) {
            @unlink($file);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($file, true);
            }
        }
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . $this->sanitize($key) . '.php';
    }

    private function sanitize(string $key): string
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) ?? '';
    }
}
