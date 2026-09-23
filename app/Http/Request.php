<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\App;
use App\Core\Env;

/**
 * Richiesta HTTP immutabile nei dati di input; `attributes` raccoglie ciò che aggiungono router e middleware
 * (parametri di rotta, lingua, area, utente).
 */
final class Request
{
    /** @var array<string, mixed> */
    public array $attributes = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $server
     * @param array<string, string> $cookies
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $server = [],
        public readonly array $cookies = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            App::requestPath(),
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
        );
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** Testo ripulito (trim) e normalizzato; per le password usare raw(). */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key);
        if (!is_string($value)) {
            return $default;
        }
        $value = trim($value);
        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value;
        }

        return $value;
    }

    public function raw(string $key): string
    {
        $value = $this->input($key);

        return is_string($value) ? $value : '';
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isWrite(): bool
    {
        return !in_array($this->method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** IP del client; X-Forwarded-For è considerato solo se la richiesta arriva da un proxy fidato. */
    public function ip(): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        $trusted = array_filter(array_map('trim', explode(',', (string) Env::get('TRUSTED_PROXIES', ''))));
        if ($trusted !== [] && in_array($remote, $trusted, true)) {
            $forwarded = $this->header('X-Forwarded-For');
            if ($forwarded !== null) {
                $first = trim(explode(',', $forwarded)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }

        return $remote;
    }

    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return ($https !== '' && $https !== 'off')
            || ($this->header('X-Forwarded-Proto') === 'https' && Env::get('TRUSTED_PROXIES') !== null);
    }

    /**
     * Lingue accettate dal browser in ordine di preferenza (solo la parte principale: "fr-CA" → "fr").
     *
     * @return list<string>
     */
    public function acceptedLanguages(): array
    {
        $header = $this->header('Accept-Language') ?? '';
        $weighted = [];
        foreach (explode(',', $header) as $index => $part) {
            $pieces = explode(';', trim($part));
            $tag = strtolower(explode('-', trim($pieces[0]))[0]);
            if ($tag === '' || $tag === '*' || !preg_match('/^[a-z]{2,3}$/', $tag)) {
                continue;
            }
            $quality = 1.0;
            if (isset($pieces[1]) && preg_match('/q=([0-9.]+)/', $pieces[1], $m)) {
                $quality = (float) $m[1];
            }
            // A parità di qualità vince l'ordine di comparsa.
            $weighted[$tag] = max($weighted[$tag] ?? 0, $quality - $index / 1000);
        }
        arsort($weighted);

        return array_keys($weighted);
    }
}
