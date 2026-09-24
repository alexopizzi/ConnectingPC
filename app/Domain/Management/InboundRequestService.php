<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Authorization\Gate;
use App\Core\Database;
use DomainException;

/**
 * Registro delle richieste ricevute dai gestori (RF-37, vault "52"): censimento, aggiornamenti, account,
 * abilitazione, errori nei dati. Permesso requests.manage. I dati di chi scrive si conservano fino a
 * retention_until, poi `requests:purge` li cancella (vault "71").
 */
final class InboundRequestService
{
    public const TYPES = ['census', 'update', 'account', 'enablement', 'data_error', 'other'];
    public const CHANNELS = ['email', 'phone', 'in_person', 'web_form'];
    public const STATUSES = ['new', 'in_progress', 'done', 'rejected'];

    public function __construct(
        private readonly Database $database,
        private readonly Gate $gate,
        private readonly AuditLogger $audit,
        private readonly int $retentionMonths,
    ) {
    }

    /**
     * @param array{status?: string, type?: string, q?: string} $filters
     * @return list<array<string, mixed>>
     */
    public function list(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (in_array($filters['status'] ?? '', self::STATUSES, true)) {
            $where[] = 'r.status = ?';
            $params[] = $filters['status'];
        } elseif (($filters['status'] ?? '') === 'open') {
            $where[] = "r.status IN ('new', 'in_progress')";
        }
        if (in_array($filters['type'] ?? '', self::TYPES, true)) {
            $where[] = 'r.type = ?';
            $params[] = $filters['type'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(r.requester_name LIKE ? OR r.requester_email LIKE ? OR r.message LIKE ? OR o.name LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        return $this->database->fetchAll(
            'SELECT r.*, o.name AS organization_name, u.display_name AS assigned_name FROM inbound_requests r
               LEFT JOIN organizations o ON o.id = r.organization_id LEFT JOIN users u ON u.id = r.assigned_to
              WHERE ' . implode(' AND ', $where) . ' ORDER BY FIELD(r.status, "new", "in_progress", "done", "rejected"), r.id DESC LIMIT 300',
            $params,
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->database->fetchOne(
            'SELECT r.*, o.name AS organization_name FROM inbound_requests r LEFT JOIN organizations o ON o.id = r.organization_id WHERE r.id = ?',
            [$id],
        );
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, string> $data
     */
    public function create(array $actor, array $data): int
    {
        $this->gate->authorize($actor, 'requests.manage');
        $row = $this->validated($data);
        $row['retention_until'] = (new \DateTimeImmutable('today'))->modify('+' . $this->retentionMonths . ' months')->format('Y-m-d');

        return $this->database->transaction(function () use ($actor, $row): int {
            $id = $this->database->insert('inbound_requests', [...$row, 'status' => 'new', 'created_by' => (int) $actor['id']]);
            // Nell'audit solo tipo e canale: i dati personali restano nella richiesta, con scadenza
            $this->audit->log('inbound_request.created', 'inbound_request', $id, ['type' => $row['type'], 'channel' => $row['channel']], ['organization_id' => $row['organization_id']]);

            return $id;
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, string> $data
     */
    public function update(array $actor, int $id, array $data): void
    {
        $this->gate->authorize($actor, 'requests.manage');
        $before = $this->find($id) ?? throw new DomainException('manage.error.not_found');
        $row = $this->validated($data);
        $status = (string) ($data['status'] ?? $before['status']);
        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainException('manage.error.invalid_status');
        }
        $assigned = (int) ($data['assigned_to'] ?? 0);
        if ($assigned > 0 && $this->database->fetchValue("SELECT id FROM users WHERE id = ? AND status = 'active'", [$assigned]) === null) {
            throw new DomainException('manage.error.invalid_value');
        }
        $row += [
            'status' => $status,
            'assigned_to' => $assigned ?: null,
            'resolution_note' => mb_substr(trim((string) ($data['resolution_note'] ?? '')), 0, 5000) ?: null,
        ];

        $this->database->transaction(function () use ($actor, $id, $before, $row): void {
            $this->database->update('inbound_requests', [...$row, 'updated_by' => (int) $actor['id']], ['id' => $id]);
            $changes = array_intersect_key(AuditLogger::diff($before, $row), array_flip(['type', 'channel', 'status', 'assigned_to', 'organization_id']));
            $this->audit->log('inbound_request.updated', 'inbound_request', $id, $changes, ['organization_id' => $row['organization_id']]);
        });
    }

    /** Cancella i dati personali delle richieste chiuse con conservazione scaduta. Restituisce quante. */
    public function purgeExpired(): int
    {
        return $this->database->execute(
            "UPDATE inbound_requests SET requester_name = NULL, requester_email = NULL, requester_phone = NULL, message = '[cancellato]', resolution_note = NULL
              WHERE retention_until < CURRENT_DATE AND status IN ('done', 'rejected')
                AND (requester_name IS NOT NULL OR requester_email IS NOT NULL OR requester_phone IS NOT NULL)"
        );
    }

    /**
     * @param array<string, string> $data
     * @return array<string, mixed>
     */
    private function validated(array $data): array
    {
        $type = (string) ($data['type'] ?? '');
        $channel = (string) ($data['channel'] ?? '');
        if (!in_array($type, self::TYPES, true) || !in_array($channel, self::CHANNELS, true)) {
            throw new DomainException('manage.error.invalid_value');
        }
        $email = trim((string) ($data['requester_email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('manage.error.invalid_email');
        }
        $organizationId = (int) ($data['organization_id'] ?? 0);
        if ($organizationId > 0 && $this->database->fetchValue('SELECT id FROM organizations WHERE id = ?', [$organizationId]) === null) {
            throw new DomainException('manage.error.not_found');
        }

        return [
            'type' => $type,
            'channel' => $channel,
            'organization_id' => $organizationId ?: null,
            'requester_name' => mb_substr(trim((string) ($data['requester_name'] ?? '')), 0, 150) ?: null,
            'requester_email' => $email ?: null,
            'requester_phone' => mb_substr(trim((string) ($data['requester_phone'] ?? '')), 0, 40) ?: null,
            'message' => mb_substr(trim((string) ($data['message'] ?? '')), 0, 5000) ?: null,
        ];
    }
}
