<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Audit\AuditLogger;
use App\Authorization\DatabaseAuthorizationData;
use App\Authorization\Gate;
use App\Core\Database;
use DomainException;

/**
 * Gestione amministrativa degli utenti: creazione su invito, stato, ruoli con ambito (vault "50", "51").
 * Tutte le operazioni verificano i permessi dell'attore e scrivono nell'audit log.
 */
final class UserService
{
    private const STATUSES = ['active', 'suspended', 'disabled'];

    /** @param list<string> $protectedRoles ruoli assegnabili solo dal super amministratore */
    public function __construct(
        private readonly Database $database,
        private readonly UserRepository $users,
        private readonly Gate $gate,
        private readonly DatabaseAuthorizationData $authorization,
        private readonly AuditLogger $audit,
        private readonly array $protectedRoles,
    ) {
    }

    /**
     * Crea un utente in stato "invitato" con il primo ruolo. L'invito si invia a parte (AccountService).
     *
     * @param array<string, mixed> $actor
     */
    public function create(array $actor, string $email, string $name, ?string $locale, string $roleCode, string $scopeType, string $scopeKey): int
    {
        $this->gate->authorize($actor, 'users.manage');
        $email = UserRepository::normalizeEmail($email);
        if ($this->users->findByEmail($email) !== null) {
            throw new DomainException('admin.users.error.email_taken');
        }

        return $this->database->transaction(function () use ($actor, $email, $name, $locale, $roleCode, $scopeType, $scopeKey): int {
            $id = $this->database->insert('users', [
                'email' => $email,
                'display_name' => $name,
                'preferred_locale' => $locale,
                'status' => 'invited',
                'created_by' => (int) $actor['id'],
            ]);
            $this->audit->log('user.created', 'user', $id, ['email' => $email, 'display_name' => $name, 'preferred_locale' => $locale]);
            $this->assignRole($actor, $id, $roleCode, $scopeType, $scopeKey);

            return $id;
        });
    }

    /** @param array<string, mixed> $actor */
    public function setStatus(array $actor, int $userId, string $status): void
    {
        $this->gate->authorize($actor, 'users.manage');
        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainException('admin.users.error.invalid_status');
        }
        if ($userId === (int) $actor['id']) {
            throw new DomainException('admin.users.error.self_status');
        }
        $user = $this->users->find($userId) ?? throw new DomainException('admin.users.error.not_found');
        if ($user['status'] === 'invited' && $status === 'active') {
            throw new DomainException('admin.users.error.invited_activation');
        }
        $this->guardProtectedTarget($actor, $userId);

        $this->database->transaction(function () use ($actor, $user, $userId, $status): void {
            $this->users->update($userId, ['status' => $status, 'updated_by' => (int) $actor['id']]);
            if ($status !== 'active') {
                // Chiude subito le sessioni dell'utente sospeso o disabilitato.
                $this->users->incrementSessionVersion($userId);
            }
            $this->audit->log('user.status_changed', 'user', $userId, ['status' => ['old' => $user['status'], 'new' => $status]]);
        });
    }

    /** @param array<string, mixed> $actor */
    public function assignRole(array $actor, int $userId, string $roleCode, string $scopeType, string $scopeKey): void
    {
        $this->gate->authorize($actor, 'users.manage');
        $role = $this->database->fetchOne('SELECT id, code, allowed_scopes FROM roles WHERE code = ?', [$roleCode])
            ?? throw new DomainException('admin.users.error.invalid_role');
        if (in_array($roleCode, $this->protectedRoles, true) && !$this->gate->allows($actor, 'roles.manage')) {
            throw new DomainException('admin.users.error.protected_role');
        }
        if (!in_array($scopeType, explode(',', (string) $role['allowed_scopes']), true)) {
            throw new DomainException('admin.users.error.invalid_scope');
        }
        $scopeKey = $this->validateScopeKey($scopeType, $scopeKey);

        $this->database->transaction(function () use ($actor, $userId, $role, $scopeType, $scopeKey): void {
            $this->database->execute(
                'INSERT INTO role_assignments (user_id, role_id, scope_type, scope_key, granted_by)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE revoked_at = NULL, revoked_by = NULL, granted_by = VALUES(granted_by),
                                         granted_at = UTC_TIMESTAMP()',
                [$userId, (int) $role['id'], $scopeType, $scopeKey, (int) $actor['id']],
            );
            $this->audit->log('role.granted', 'user', $userId, [
                'role' => $role['code'], 'scope_type' => $scopeType, 'scope_key' => $scopeKey,
            ], $scopeType === 'organization' ? ['organization_id' => (int) $scopeKey] : []);
        });
        $this->authorization->forget();
    }

    /** @param array<string, mixed> $actor */
    public function revokeAssignment(array $actor, int $userId, int $assignmentId): void
    {
        $this->gate->authorize($actor, 'users.manage');
        $assignment = $this->database->fetchOne(
            'SELECT a.id, a.scope_type, a.scope_key, r.code AS role FROM role_assignments a JOIN roles r ON r.id = a.role_id
              WHERE a.id = ? AND a.user_id = ? AND a.revoked_at IS NULL',
            [$assignmentId, $userId],
        ) ?? throw new DomainException('admin.users.error.not_found');

        if (in_array($assignment['role'], $this->protectedRoles, true) && !$this->gate->allows($actor, 'roles.manage')) {
            throw new DomainException('admin.users.error.protected_role');
        }
        if ($assignment['role'] === 'super_admin' && $this->activeSuperAdmins() <= 1) {
            throw new DomainException('admin.users.error.last_super_admin');
        }

        $this->database->transaction(function () use ($actor, $userId, $assignment): void {
            $this->database->execute(
                'UPDATE role_assignments SET revoked_at = UTC_TIMESTAMP(), revoked_by = ? WHERE id = ?',
                [(int) $actor['id'], (int) $assignment['id']],
            );
            $this->audit->log('role.revoked', 'user', $userId, [
                'role' => $assignment['role'], 'scope_type' => $assignment['scope_type'], 'scope_key' => $assignment['scope_key'],
            ]);
        });
        $this->authorization->forget();
    }

    private function validateScopeKey(string $scopeType, string $scopeKey): string
    {
        return match ($scopeType) {
            'global' => '',
            'organization' => $this->database->fetchValue('SELECT id FROM organizations WHERE id = ?', [(int) $scopeKey]) !== null
                ? (string) (int) $scopeKey
                : throw new DomainException('admin.users.error.invalid_organization'),
            'locale' => $this->database->fetchValue('SELECT code FROM locales WHERE code = ?', [$scopeKey]) !== null
                ? $scopeKey
                : throw new DomainException('admin.users.error.invalid_scope'),
            // Categorie e territori: tabelle introdotte con v0.4.0.
            default => ctype_digit($scopeKey) ? $scopeKey : throw new DomainException('admin.users.error.invalid_scope'),
        };
    }

    /** Solo il super amministratore può modificare account con ruoli protetti. */
    private function guardProtectedTarget(array $actor, int $userId): void
    {
        if ($this->gate->allows($actor, 'roles.manage')) {
            return;
        }
        foreach ($this->authorization->assignments($userId) as $assignment) {
            if (in_array($assignment['role'], $this->protectedRoles, true)) {
                throw new DomainException('admin.users.error.protected_role');
            }
        }
    }

    private function activeSuperAdmins(): int
    {
        return (int) $this->database->fetchValue(
            "SELECT COUNT(DISTINCT a.user_id) FROM role_assignments a
               JOIN roles r ON r.id = a.role_id JOIN users u ON u.id = a.user_id
              WHERE r.code = 'super_admin' AND a.revoked_at IS NULL AND u.status = 'active'"
        );
    }
}
