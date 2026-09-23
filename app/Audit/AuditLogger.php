<?php

declare(strict_types=1);

namespace App\Audit;

use App\Core\Database;

/**
 * Registro delle operazioni significative (D-011, vault "54 - Audit log e qualità dei dati").
 * Va chiamato dentro la stessa transazione dell'operazione registrata. Append-only.
 */
final class AuditLogger
{
    /** Campi mai registrati nelle differenze. */
    private const SECRET_FIELDS = [
        'password', 'password_hash', 'token', 'token_hash', 'mfa_secret_enc', 'session_version', 'remember_token',
    ];

    private ?int $userId = null;
    private ?int $organizationId = null;
    private ?string $ip = null;

    public function __construct(
        private readonly Database $database,
        private readonly string $appKey,
        private readonly ?string $requestId,
    ) {
    }

    /** Chi agisce e per conto di quale organizzazione (impostato dall'autenticazione). */
    public function setActor(?int $userId, ?int $organizationId = null): void
    {
        $this->userId = $userId;
        $this->organizationId = $organizationId;
    }

    public function setIp(?string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}>|array<string, mixed>|null $changes
     * @param array{approved_by?: int, change_request_id?: int, organization_id?: int, user_id?: int} $extra
     */
    public function log(string $action, ?string $entityType = null, ?int $entityId = null, ?array $changes = null, array $extra = []): void
    {
        $this->database->insert('audit_log', [
            'request_id' => $this->requestId,
            'user_id' => $extra['user_id'] ?? $this->userId,
            'organization_id' => $extra['organization_id'] ?? $this->organizationId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changes' => $changes === null ? null : json_encode(self::redact($changes), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'approved_by' => $extra['approved_by'] ?? null,
            'change_request_id' => $extra['change_request_id'] ?? null,
            // IP pseudonimizzato: HMAC con la chiave dell'applicazione (vault "71 - Privacy").
            'ip_hash' => $this->ip === null ? null : hash_hmac('sha256', $this->ip, $this->appKey),
        ]);
    }

    /**
     * Differenze campo per campo fra due versioni di un record.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $new) {
            $old = $before[$field] ?? null;
            if ((string) json_encode($old) !== (string) json_encode($new) && $old != $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return self::redact($changes);
    }

    /**
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private static function redact(array $changes): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (array_key_exists($field, $changes)) {
                $changes[$field] = '[redatto]';
            }
        }

        return $changes;
    }
}
