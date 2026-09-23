<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Core\Database;

/**
 * Accesso ai dati degli utenti. L'email è sempre salvata e cercata in minuscolo.
 */
final class UserRepository
{
    private const COLUMNS = 'id, email, password_hash, display_name, preferred_locale, status, email_verified_at,
                             password_changed_at, last_login_at, session_version, created_at, updated_at';

    public function __construct(private readonly Database $database)
    {
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->database->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->database->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE email = ?', [self::normalizeEmail($email)]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(string $text, ?string $status, int $limit, int $offset): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($text !== '') {
            $where[] = '(email LIKE ? OR display_name LIKE ?)';
            $like = '%' . addcslashes($text, '%_\\') . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== null) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $condition = implode(' AND ', $where);

        $total = (int) $this->database->fetchValue("SELECT COUNT(*) FROM users WHERE $condition", $params);
        $rows = $this->database->fetchAll(
            "SELECT id, email, display_name, status, last_login_at, created_at FROM users
              WHERE $condition ORDER BY display_name, id LIMIT ? OFFSET ?",
            [...$params, $limit, $offset],
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Assegnazioni attive e storiche dell'utente, con nome del ruolo e dell'organizzazione.
     *
     * @return list<array<string, mixed>>
     */
    public function assignments(int $userId): array
    {
        return $this->database->fetchAll(
            "SELECT a.id, a.scope_type, a.scope_key, a.granted_at, a.revoked_at, a.expires_at,
                    r.code AS role_code, r.name AS role_name, o.name AS organization_name, o.access_status
               FROM role_assignments a
               JOIN roles r ON r.id = a.role_id
               LEFT JOIN organizations o ON a.scope_type = 'organization' AND o.id = CAST(a.scope_key AS UNSIGNED)
              WHERE a.user_id = ?
              ORDER BY a.revoked_at IS NOT NULL, r.id, a.id",
            [$userId],
        );
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $this->database->update('users', $data, ['id' => $id]);
    }

    public function incrementSessionVersion(int $id): void
    {
        $this->database->execute('UPDATE users SET session_version = session_version + 1 WHERE id = ?', [$id]);
    }
}
