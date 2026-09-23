<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;

/**
 * Token monouso per inviti e recupero password (vault "50", "72"):
 * 256 bit casuali, nel database solo l'hash SHA-256, scadenza, uso singolo.
 */
final class TokenService
{
    public const INVITE_TTL = 72 * 3600;
    public const PASSWORD_RESET_TTL = 3600;

    public function __construct(private readonly Database $database)
    {
    }

    /** Emette un nuovo token e invalida quelli non usati dello stesso tipo per lo stesso utente. */
    public function issue(int $userId, string $purpose, int $ttlSeconds): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->database->execute(
            'UPDATE auth_tokens SET used_at = UTC_TIMESTAMP() WHERE user_id = ? AND purpose = ? AND used_at IS NULL',
            [$userId, $purpose],
        );
        $this->database->execute(
            'INSERT INTO auth_tokens (user_id, purpose, token_hash, expires_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP() + INTERVAL ? SECOND)',
            [$userId, $purpose, hash('sha256', $token), $ttlSeconds],
        );

        return $token;
    }

    /**
     * Token valido (non usato, non scaduto) del tipo indicato.
     *
     * @return array{id: int, user_id: int}|null
     */
    public function find(string $token, string $purpose): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{16,128}$/', $token)) {
            return null;
        }
        $row = $this->database->fetchOne(
            'SELECT id, user_id FROM auth_tokens
              WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()',
            [hash('sha256', $token), $purpose],
        );

        return $row === null ? null : ['id' => (int) $row['id'], 'user_id' => (int) $row['user_id']];
    }

    public function markUsed(int $tokenId): void
    {
        $this->database->execute('UPDATE auth_tokens SET used_at = UTC_TIMESTAMP() WHERE id = ? AND used_at IS NULL', [$tokenId]);
    }
}
