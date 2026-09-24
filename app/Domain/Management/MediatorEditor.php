<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Core\Database;
use DomainException;

/**
 * Mediatori (vault "63", D-013, D-033). Regole applicate qui:
 * - visibilità `public` solo con consenso registrato; la revoca del consenso rende subito il profilo
 *   non pubblico (torna "solo operatori");
 * - dati amministrativi (qualifiche, note, verifica) solo con `mediators.manage`;
 * - un'organizzazione (`org.mediators.edit`) gestisce solo i propri mediatori.
 */
final class MediatorEditor
{
    public const TYPES = ['linguistic', 'cultural', 'intercultural'];
    public const AVAILABILITY = ['available', 'limited', 'unavailable', 'unknown'];
    public const VISIBILITY = ['public', 'operators', 'admin'];
    public const LEVELS = ['native', 'c2', 'c1', 'b2', 'b1'];
    public const VERIFICATION = ['unverified', 'pending', 'verified', 'rejected'];
    public const TEXT_FIELDS = ['bio', 'competences', 'availability_notes'];

    public function __construct(
        private readonly Database $database,
        private readonly EditorialGuard $guard,
        private readonly RelatedRecords $related,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Crea (id null) o aggiorna un mediatore.
     *
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     */
    public function save(array $actor, ?int $id, array $data): int
    {
        $before = $id === null ? [] : $this->find($id);
        $organizationId = (int) ($data['organization_id'] ?? 0) ?: null;
        $isManager = $this->guard->allows($actor, 'mediators.manage');
        if (!$isManager) {
            // Serve il permesso sia sull'organizzazione attuale sia su quella nuova.
            foreach (array_unique(array_filter([$before['organization_id'] ?? null, $organizationId])) as $org) {
                $this->guard->authorize($actor, 'org.mediators.edit', (int) $org);
            }
            if ($organizationId === null) {
                throw new DomainException('manage.error.forbidden');
            }
            $this->guard->assertCanChangePublished($actor, $organizationId, $before['publication_status'] ?? null);
        }
        if ($organizationId !== null && $this->database->fetchValue('SELECT id FROM organizations WHERE id = ?', [$organizationId]) === null) {
            throw new DomainException('manage.error.not_found');
        }

        $first = trim((string) ($data['first_name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));
        if ($first === '' || $last === '') {
            throw new DomainException('manage.error.name_required');
        }
        $types = array_values(array_intersect(self::TYPES, (array) ($data['mediation_types'] ?? [])));
        if ($types === []) {
            throw new DomainException('manage.error.type_required');
        }

        $consent = !empty($data['consent_given']);
        $visibility = $this->option((string) ($data['profile_visibility'] ?? 'admin'), self::VISIBILITY);
        if ($visibility === 'public' && !$consent) {
            throw new DomainException('manage.error.consent_required');
        }
        $after = [
            'first_name' => $first,
            'last_name' => $last,
            'public_display_name' => trim((string) ($data['public_display_name'] ?? '')) ?: null,
            'organization_id' => $organizationId,
            'mediation_types' => implode(',', $types),
            'availability' => $this->option((string) ($data['availability'] ?? 'unknown'), self::AVAILABILITY),
            'profile_visibility' => $visibility,
            'public_consent_at' => $consent ? ($before['public_consent_at'] ?? null ?: gmdate('Y-m-d H:i:s')) : null,
            'consent_reference' => $consent ? (trim((string) ($data['consent_reference'] ?? '')) ?: null) : null,
            'next_review_at' => EditorialGuard::date((string) ($data['next_review_at'] ?? '')),
        ];
        if ($isManager) {
            $after['verification_status'] = $this->option((string) ($data['verification_status'] ?? 'unverified'), self::VERIFICATION);
            $after['qualifications_admin'] = trim((string) ($data['qualifications_admin'] ?? '')) ?: null;
            $after['admin_notes'] = trim((string) ($data['admin_notes'] ?? '')) ?: null;
            if ($after['verification_status'] === 'verified' && ($before['verification_status'] ?? null) !== 'verified') {
                $after['verified_at'] = gmdate('Y-m-d H:i:s');
                $after['verified_by'] = (int) $actor['id'];
            }
        }
        $requested = (string) ($data['publication_status'] ?? 'draft');
        $canPublish = $isManager || ($organizationId !== null && $this->guard->allows($actor, 'content.publish', $organizationId));
        if (in_array($requested, ['published', 'archived'], true) && !$canPublish) {
            $requested = 'in_review';
        }
        if (!in_array($requested, EditorialGuard::PUBLICATION_STATUSES, true)) {
            throw new DomainException('manage.error.invalid_status');
        }
        $after['publication_status'] = $requested;
        if ($requested === 'published' && ($before['publication_status'] ?? null) !== 'published') {
            $after['published_at'] = gmdate('Y-m-d H:i:s');
        }
        $after['archived_at'] = $requested === 'archived' ? gmdate('Y-m-d H:i:s') : null;

        $languages = [];
        foreach ((array) ($data['languages'] ?? []) as $row) {
            $code = (string) ($row['code'] ?? '');
            $level = (string) ($row['level'] ?? '');
            if ($code !== '' && in_array($level, self::LEVELS, true)) {
                $languages[$code] = $level;
            }
        }

        return $this->database->transaction(function () use ($actor, $id, $before, $after, $languages, $data, $organizationId): int {
            if ($id === null) {
                $id = $this->database->insert('mediators', [...$after, 'created_by' => (int) $actor['id']]);
                $changes = ['created' => true];
            } else {
                $changes = AuditLogger::diff($before, $after);
                if ($changes !== []) {
                    $this->database->update('mediators', [...$after, 'updated_by' => (int) $actor['id']], ['id' => $id]);
                }
            }
            if ($after['public_consent_at'] === null) {
                // Senza consenso nessun recapito del mediatore resta pubblico.
                $this->database->execute("UPDATE contact_points SET visibility = 'operators' WHERE owner_type = 'mediator' AND owner_id = ? AND visibility = 'public'", [$id]);
                if (isset($changes['public_consent_at'])) {
                    $this->audit->log('mediator.consent_revoked', 'mediator', $id, null, ['organization_id' => $organizationId]);
                }
            }

            $valid = array_map('strval', $this->database->fetchColumn('SELECT code FROM languages WHERE is_active = 1'));
            $this->database->execute('DELETE FROM mediator_languages WHERE mediator_id = ?', [$id]);
            foreach ($languages as $code => $level) {
                if (in_array($code, $valid, true)) {
                    $this->database->insert('mediator_languages', ['mediator_id' => $id, 'language_code' => $code, 'proficiency' => $level]);
                }
            }
            $links = [
                'languages' => $languages,
                'domains' => $this->related->replaceLinks('mediator_domains', 'mediator_id', $id, 'mediation_domain_id',
                    array_map('intval', (array) ($data['domains'] ?? [])), array_map('intval', $this->database->fetchColumn('SELECT id FROM mediation_domains'))),
                'territories' => $this->related->replaceLinks('mediator_territories', 'mediator_id', $id, 'territory_id',
                    array_map('intval', (array) ($data['territories'] ?? [])), array_map('intval', $this->database->fetchColumn('SELECT id FROM territories'))),
            ];
            // Il riferimento al consenso e le note non vanno nell'audit in chiaro
            unset($changes['consent_reference'], $changes['admin_notes'], $changes['qualifications_admin']);
            $this->audit->log($before === [] ? 'mediator.created' : 'mediator.updated', 'mediator', $id, [...$changes, 'links' => $links], ['organization_id' => $organizationId]);

            return $id;
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, ?string> $texts
     */
    public function saveTexts(array $actor, int $id, string $locale, array $texts, bool $approve): void
    {
        $organizationId = $this->authorizeExisting($actor, $id);
        if (!in_array($locale, $this->guard->contentLocales(), true)) {
            throw new DomainException('manage.error.invalid_locale');
        }
        $fields = array_intersect_key($texts, array_flip(self::TEXT_FIELDS)) + array_fill_keys(self::TEXT_FIELDS, null);
        $status = $locale === 'it' || ($approve && $this->guard->allows($actor, 'translations.approve', $organizationId)) ? 'approved' : 'to_review';

        $this->database->transaction(function () use ($actor, $id, $locale, $fields, $status, $organizationId): void {
            $changes = $this->related->saveTranslation('mediator_translations', 'mediator_id', $id, $locale, 'it', $fields, $status, (int) $actor['id']);
            $this->audit->log('mediator.texts_saved', 'mediator', $id, ['locale' => $locale, 'status' => $status, 'changes' => $changes], ['organization_id' => $organizationId]);
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param list<array<string, string>> $contacts
     */
    public function saveContacts(array $actor, int $id, array $contacts): void
    {
        $organizationId = $this->authorizeExisting($actor, $id);
        $mediator = $this->find($id);
        foreach ($contacts as $contact) {
            if (($contact['visibility'] ?? '') === 'public' && trim((string) ($contact['value'] ?? '')) !== '' && $mediator['public_consent_at'] === null) {
                throw new DomainException('manage.error.consent_required');
            }
        }
        $this->database->transaction(function () use ($actor, $id, $contacts, $organizationId): void {
            $saved = $this->related->replaceContacts('mediator', $id, $contacts, (int) $actor['id']);
            $this->audit->log('mediator.contacts_saved', 'mediator', $id, ['count' => count($saved)], ['organization_id' => $organizationId]);
        });
    }

    /** @param array<string, mixed> $actor */
    private function authorizeExisting(array $actor, int $id): ?int
    {
        $mediator = $this->find($id);
        $organizationId = $mediator['organization_id'] === null ? null : (int) $mediator['organization_id'];
        if (!$this->guard->allows($actor, 'mediators.manage')) {
            if ($organizationId === null) {
                throw new DomainException('manage.error.forbidden');
            }
            $this->guard->authorize($actor, 'org.mediators.edit', $organizationId);
            $this->guard->assertCanChangePublished($actor, $organizationId, (string) $mediator['publication_status']);
        }

        return $organizationId;
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        return $this->database->fetchOne('SELECT * FROM mediators WHERE id = ?', [$id]) ?? throw new DomainException('manage.error.not_found');
    }

    /** @param list<string> $allowed */
    private function option(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : throw new DomainException('manage.error.invalid_value');
    }
}
