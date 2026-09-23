<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Hash delle password: Argon2id se disponibile (verificato in Docker e da verificare sul nuovo hosting),
 * altrimenti bcrypt con costo 12. Rehash automatico al login (vault "50 - Autenticazione").
 */
final class PasswordHasher
{
    /** Hash valido usato quando l'utente non esiste, per non rivelarlo con i tempi di risposta. */
    private ?string $dummyHash = null;

    public function hash(string $password): string
    {
        return password_hash($password, $this->algorithm(), $this->options());
    }

    public function verify(string $password, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            password_verify($password, $this->dummyHash ??= $this->hash('dummy-password-for-timing'));

            return false;
        }

        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm(), $this->options());
    }

    private function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /** @return array<string, int> */
    private function options(): array
    {
        return defined('PASSWORD_ARGON2ID') ? [] : ['cost' => 12];
    }
}
