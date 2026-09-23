<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Session;

/**
 * Token CSRF sincronizzato per sessione (vault "72 - Sicurezza").
 */
final class Csrf
{
    public const FIELD = '_token';
    private const KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function validate(mixed $given): bool
    {
        $expected = $this->session->get(self::KEY);

        return is_string($expected) && is_string($given) && $given !== '' && hash_equals($expected, $given);
    }

    /** Nuovo token dopo login/logout. */
    public function rotate(): void
    {
        $this->session->forget(self::KEY);
    }
}
