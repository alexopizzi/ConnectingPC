<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Authorization\Gate;
use App\Authorization\ResourceScope;
use App\Core\Database;
use DomainException;

/**
 * Coda di revisione (RF-35, vault "53"): contenuti "in revisione" e traduzioni "da revisionare".
 * Approvazione e rifiuto con nota (RF-25), registrati in review_decisions e nell'audit log.
 * Permessi: content.review (contenuti) e translations.approve (traduzioni), nell'ambito dell'organizzazione.
 */
final class ReviewService
{
    public const ENTITIES = ['organization', 'site', 'service', 'mediator'];
    private const TABLES = ['organization' => 'organizations', 'site' => 'sites', 'service' => 'services', 'mediator' => 'mediators'];
    private const TRANSLATIONS = [
        'organization' => ['organization_translations', 'organization_id'],
        'site' => ['site_translations', 'site_id'],
        'service' => ['service_translations', 'service_id'],
        'mediator' => ['mediator_translations', 'mediator_id'],
    ];

    public function __construct(private readonly Database $database, private readonly Gate $gate, private readonly AuditLogger $audit)
    {
    }

    /**
     * Contenuti in revisione, dal più vecchio.
     *
     * @param list<int>|null $onlyOrganizations
     * @return list<array{entity_type: string, entity_id: int, organization_id: ?int, organization_name: ?string, label: string, updated_at: ?string}>
     */
    public function pendingContent(?array $onlyOrganizations = null): array
    {
        $rows = $this->database->fetchAll(
            "SELECT 'organization' AS entity_type, o.id AS entity_id, o.id AS organization_id, o.name AS organization_name, o.name AS label, o.updated_at
               FROM organizations o WHERE o.publication_status = 'in_review'
             UNION ALL
             SELECT 'site', si.id, o.id, o.name, CONCAT(COALESCE(CONCAT(si.name, ' – '), ''), si.address_line), si.updated_at
               FROM sites si JOIN organizations o ON o.id = si.organization_id WHERE si.publication_status = 'in_review'
             UNION ALL
             SELECT 'service', s.id, o.id, o.name, COALESCE(t.name, CONCAT('#', s.id)), s.updated_at
               FROM services s JOIN organizations o ON o.id = s.organization_id
               LEFT JOIN service_translations t ON t.service_id = s.id AND t.locale = s.source_locale
              WHERE s.publication_status = 'in_review'
             UNION ALL
             SELECT 'mediator', m.id, o.id, o.name, CONCAT(m.first_name, ' ', m.last_name), m.updated_at
               FROM mediators m LEFT JOIN organizations o ON o.id = m.organization_id WHERE m.publication_status = 'in_review'
             ORDER BY updated_at IS NULL, updated_at"
        );

        return array_values(array_filter(array_map(static fn (array $r): array => [
            'entity_type' => (string) $r['entity_type'], 'entity_id' => (int) $r['entity_id'],
            'organization_id' => $r['organization_id'] === null ? null : (int) $r['organization_id'],
            'organization_name' => $r['organization_name'] === null ? null : (string) $r['organization_name'],
            'label' => (string) $r['label'], 'updated_at' => $r['updated_at'] === null ? null : (string) $r['updated_at'],
        ], $rows), static fn (array $r): bool => $onlyOrganizations === null || in_array($r['organization_id'], $onlyOrganizations, true)));
    }

    /**
     * Traduzioni "da revisionare" dei contenuti.
     *
     * @return list<array{entity_type: string, entity_id: int, locale: string, organization_id: ?int, organization_name: ?string, label: string}>
     */
    public function pendingTranslations(): array
    {
        return array_map(static fn (array $r): array => [
            'entity_type' => (string) $r['entity_type'], 'entity_id' => (int) $r['entity_id'], 'locale' => (string) $r['locale'],
            'organization_id' => $r['organization_id'] === null ? null : (int) $r['organization_id'],
            'organization_name' => $r['organization_name'] === null ? null : (string) $r['organization_name'], 'label' => (string) $r['label'],
        ], $this->database->fetchAll(
            "SELECT 'organization' AS entity_type, o.id AS entity_id, t.locale, o.id AS organization_id, o.name AS organization_name, o.name AS label
               FROM organization_translations t JOIN organizations o ON o.id = t.organization_id WHERE t.status = 'to_review'
             UNION ALL
             SELECT 'site', si.id, t.locale, o.id, o.name, si.address_line
               FROM site_translations t JOIN sites si ON si.id = t.site_id JOIN organizations o ON o.id = si.organization_id WHERE t.status = 'to_review'
             UNION ALL
             SELECT 'service', s.id, t.locale, o.id, o.name, COALESCE(src.name, t.name)
               FROM service_translations t JOIN services s ON s.id = t.service_id JOIN organizations o ON o.id = s.organization_id
               LEFT JOIN service_translations src ON src.service_id = s.id AND src.locale = s.source_locale
              WHERE t.status = 'to_review'
             UNION ALL
             SELECT 'mediator', m.id, t.locale, o.id, o.name, CONCAT(m.first_name, ' ', m.last_name)
               FROM mediator_translations t JOIN mediators m ON m.id = t.mediator_id LEFT JOIN organizations o ON o.id = m.organization_id
              WHERE t.status = 'to_review'
             ORDER BY 5, 6 LIMIT 300"
        ));
    }

    /**
     * Approva (pubblica) o respinge con nota un contenuto in revisione.
     *
     * @param array<string, mixed> $actor
     */
    public function decide(array $actor, string $entityType, int $id, string $decision, string $note): void
    {
        [$row, $organizationId] = $this->load($entityType, $id);
        $this->authorize($actor, 'content.review', $organizationId);
        if ($row['publication_status'] !== 'in_review') {
            throw new DomainException('manage.error.not_in_review');
        }
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new DomainException('manage.error.invalid_value');
        }
        $note = mb_substr(trim($note), 0, 1000);
        if ($decision === 'rejected' && $note === '') {
            throw new DomainException('manage.error.note_required');
        }

        $this->database->transaction(function () use ($actor, $entityType, $id, $decision, $note, $organizationId): void {
            $update = $decision === 'approved'
                ? ['publication_status' => 'published', 'published_at' => gmdate('Y-m-d H:i:s')]
                : ['publication_status' => 'rejected'];
            $this->database->update(self::TABLES[$entityType], [...$update, 'updated_by' => (int) $actor['id']], ['id' => $id]);
            $this->database->insert('review_decisions', [
                'entity_type' => $entityType, 'entity_id' => $id, 'organization_id' => $organizationId,
                'decision' => $decision, 'note' => $note ?: null, 'decided_by' => (int) $actor['id'],
            ]);
            $this->audit->log($entityType . '.review_' . $decision, $entityType, $id, ['note' => $note], ['organization_id' => $organizationId, 'approved_by' => (int) $actor['id']]);
        });
    }

    /**
     * Approva una traduzione "da revisionare".
     *
     * @param array<string, mixed> $actor
     */
    public function approveTranslation(array $actor, string $entityType, int $id, string $locale): void
    {
        [, $organizationId] = $this->load($entityType, $id);
        if (!$this->gate->allows($actor, 'translations.approve', new ResourceScope(locale: $locale))) {
            $this->authorize($actor, 'translations.approve', $organizationId);
        }
        [$table, $key] = self::TRANSLATIONS[$entityType];
        $updated = $this->database->execute(
            'UPDATE ' . Database::identifier($table) . " SET status = 'approved', reviewed_by = ? WHERE " . Database::identifier($key) . " = ? AND locale = ? AND status = 'to_review'",
            [(int) $actor['id'], $id, $locale],
        );
        if ($updated === 0) {
            throw new DomainException('manage.error.not_in_review');
        }
        $this->audit->log($entityType . '.translation_approved', $entityType, $id, ['locale' => $locale], ['organization_id' => $organizationId]);
    }

    /**
     * Ultime decisioni sui contenuti delle organizzazioni indicate (per l'area riservata).
     *
     * @param list<int> $organizationIds
     * @return list<array<string, mixed>>
     */
    public function decisionsFor(array $organizationIds, int $limit = 20): array
    {
        if ($organizationIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($organizationIds), '?'));

        return $this->database->fetchAll(
            "SELECT d.entity_type, d.entity_id, d.organization_id, d.decision, d.note, d.decided_at
               FROM review_decisions d WHERE d.organization_id IN ($in) ORDER BY d.id DESC LIMIT " . max(1, $limit),
            $organizationIds,
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?int} riga e organizzazione proprietaria
     */
    private function load(string $entityType, int $id): array
    {
        if (!isset(self::TABLES[$entityType])) {
            throw new DomainException('manage.error.invalid_value');
        }
        $row = $this->database->fetchOne('SELECT * FROM ' . Database::identifier(self::TABLES[$entityType]) . ' WHERE id = ?', [$id])
            ?? throw new DomainException('manage.error.not_found');
        $organizationId = $entityType === 'organization' ? $id : ($row['organization_id'] === null ? null : (int) $row['organization_id']);

        return [$row, $organizationId];
    }

    /** @param array<string, mixed> $actor */
    private function authorize(array $actor, string $permission, ?int $organizationId): void
    {
        $scope = $organizationId === null ? null : ResourceScope::organization($organizationId);
        if (!$this->gate->allows($actor, $permission, $scope)) {
            throw new DomainException('manage.error.forbidden');
        }
    }
}
