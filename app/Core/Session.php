<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sessione PHP nativa su file privati (storage/sessions), avviata solo dove serve
 * (login, area riservata, admin): i visitatori anonimi non ricevono cookie (vault "50", "71").
 */
final class Session
{
    private bool $started = false;

    public function __construct(
        private readonly string $savePath,
        private readonly string $name,
        private readonly bool $secureCookie,
        private readonly string $cookiePath,
    ) {
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            // CLI e test: sessione in memoria.
            $_SESSION ??= [];
            $this->started = true;
            $this->ageFlash();

            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', '43200');
        // Su hosting condiviso non c'è cron: la pulizia dei file avviene con probabilità 1/100 per richiesta.
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        if (is_dir($this->savePath) && is_writable($this->savePath)) {
            session_save_path($this->savePath);
        }
        session_name($this->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $this->cookiePath !== '' ? $this->cookiePath : '/',
            'secure' => $this->secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        $this->started = true;
        $this->ageFlash();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();

        return array_key_exists($key, $_SESSION);
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    /** Valore disponibile solo alla richiesta successiva (messaggi, errori di validazione, vecchi input). */
    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['_flash_next'][$key] = $value;
    }

    /** Aggiunge un elemento a una lista flash (es. più messaggi nella stessa richiesta). */
    public function flashPush(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['_flash_next'][$key][] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION['_flash_now'][$key] ?? $default;
    }

    /** Nuovo identificativo di sessione (al login e a ogni cambio di privilegi). */
    public function regenerate(): void
    {
        $this->start();
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie($this->name, '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_destroy();
        }
        $this->started = false;
    }

    private function ageFlash(): void
    {
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        unset($_SESSION['_flash_next']);
    }
}
