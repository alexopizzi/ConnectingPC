<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Regole per le password (NIST 800-63B, vault "50"): almeno 12 caratteri, nessuna regola di composizione,
 * esclusione delle password comuni e di quelle che contengono l'email.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;
    public const MAX_LENGTH = 200;

    /** @var array<string, true>|null */
    private ?array $common = null;

    public function __construct(private readonly string $commonPasswordsFile)
    {
    }

    /**
     * @return list<string> chiavi di traduzione degli errori (vuoto se la password è valida)
     */
    public function validate(string $password, string $confirmation, string $email = ''): array
    {
        $errors = [];
        $length = mb_strlen($password);

        if ($length < self::MIN_LENGTH) {
            $errors[] = 'auth.password.too_short';
        } elseif ($length > self::MAX_LENGTH) {
            $errors[] = 'auth.password.too_long';
        }
        if ($password !== $confirmation) {
            $errors[] = 'auth.password.mismatch';
        }

        $normalized = mb_strtolower($password);
        if ($this->isCommon($normalized)) {
            $errors[] = 'auth.password.common';
        }
        $local = mb_strtolower(explode('@', $email)[0]);
        if (mb_strlen($local) >= 4 && str_contains($normalized, $local)) {
            $errors[] = 'auth.password.contains_email';
        }

        return $errors;
    }

    private function isCommon(string $password): bool
    {
        if ($this->common === null) {
            $this->common = [];
            if (is_file($this->commonPasswordsFile)) {
                foreach (file($this->commonPasswordsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $line = trim($line);
                    if ($line !== '' && !str_starts_with($line, '#')) {
                        $this->common[mb_strtolower($line)] = true;
                    }
                }
            }
        }

        return isset($this->common[$password]);
    }
}
