<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Informazioni generali sull'applicazione e sulla richiesta corrente.
 */
final class App
{
    private static ?string $version = null;

    /** Versione SemVer letta dal file VERSION (vault: "13 - Versioni e rilasci"). */
    public static function version(): string
    {
        if (self::$version === null) {
            $raw = @file_get_contents(APP_BASE_PATH . '/VERSION');
            self::$version = ($raw === false || trim($raw) === '') ? '0.0.0' : trim($raw);
        }

        return self::$version;
    }

    public static function environment(): string
    {
        return Env::get('APP_ENV', 'production');
    }

    public static function isLocal(): bool
    {
        return self::environment() === 'local';
    }

    /** In produzione il debug è sempre spento, qualunque sia il valore di APP_DEBUG. */
    public static function isDebug(): bool
    {
        return self::environment() !== 'production' && Env::bool('APP_DEBUG');
    }

    /** Percorso della richiesta relativo alla cartella di installazione, senza slash finale. */
    public static function requestPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';

        $base = self::baseUrl();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return '/' . trim($path, '/');
    }

    /** Prefisso URL dell'installazione ('' se l'app è nella radice del dominio). */
    public static function baseUrl(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

        return rtrim($dir, '/.');
    }

    public static function healthTokenMatches(string $given): bool
    {
        $expected = Env::get('HEALTH_TOKEN');

        return $expected !== null && $given !== '' && hash_equals($expected, $given);
    }
}
