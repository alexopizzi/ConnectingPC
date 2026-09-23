<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Core\Database;

/** Dati di autorizzazione dal database, memorizzati per la durata della richiesta. */
final class DatabaseAuthorizationData implements AuthorizationData
{
    /** @var array<int, list<array{assignment_id: int, role: string, scope_type: string, scope_key: string, permissions: list<string>}>> */
    private array $assignments = [];

    /** @var array<int, array{access_status: string, portal_edit_enabled: bool, publication_policy: ?string}|null> */
    private array $organizations = [];

    public function __construct(private readonly Database $database)
    {
    }

    public function assignments(int $userId): array
    {
        if (isset($this->assignments[$userId])) {
            return $this->assignments[$userId];
        }

        $rows = $this->database->fetchAll(
            'SELECT a.id, r.code AS role, a.scope_type, a.scope_key, p.code AS permission
               FROM role_assignments a
               JOIN roles r ON r.id = a.role_id
               LEFT JOIN role_permissions rp ON rp.role_id = r.id
               LEFT JOIN permissions p ON p.id = rp.permission_id
              WHERE a.user_id = ?
                AND a.revoked_at IS NULL
                AND (a.expires_at IS NULL OR a.expires_at > UTC_TIMESTAMP())
              ORDER BY a.id',
            [$userId],
        );

        $grouped = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $grouped[$id] ??= [
                'assignment_id' => $id,
                'role' => (string) $row['role'],
                'scope_type' => (string) $row['scope_type'],
                'scope_key' => (string) $row['scope_key'],
                'permissions' => [],
            ];
            if ($row['permission'] !== null) {
                $grouped[$id]['permissions'][] = (string) $row['permission'];
            }
        }

        return $this->assignments[$userId] = array_values($grouped);
    }

    public function organization(int $organizationId): ?array
    {
        if (!array_key_exists($organizationId, $this->organizations)) {
            $row = $this->database->fetchOne(
                'SELECT access_status, portal_edit_enabled, publication_policy FROM organizations WHERE id = ?',
                [$organizationId],
            );
            $this->organizations[$organizationId] = $row === null ? null : [
                'access_status' => (string) $row['access_status'],
                'portal_edit_enabled' => (bool) $row['portal_edit_enabled'],
                'publication_policy' => $row['publication_policy'] === null ? null : (string) $row['publication_policy'],
            ];
        }

        return $this->organizations[$organizationId];
    }

    /** Da chiamare dopo modifiche a ruoli o organizzazioni nella stessa richiesta. */
    public function forget(): void
    {
        $this->assignments = [];
        $this->organizations = [];
    }
}
