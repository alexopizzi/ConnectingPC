<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Core\Database;
use DomainException;

/**
 * Sedi delle organizzazioni (vault "61"): indirizzo, comune, coordinate, accessibilità, orari, recapiti,
 * testi tradotti (indicazioni, note). Permesso org.sites.edit nell'ambito dell'organizzazione.
 */
final class SiteEditor
{
    public const TEXT_FIELDS = ['directions', 'accessibility_notes', 'hours_notes'];
    /** Riquadro utile della provincia (D-024): fuori si avvisa, non si blocca. */
    public const BOUNDS = ['lat' => [44.55, 45.20], 'lng' => [9.15, 10.10]];

    public function __construct(
        private readonly Database $database,
        private readonly EditorialGuard $guard,
        private readonly RelatedRecords $related,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Crea o aggiorna una sede. Restituisce l'id e, se presente, un avviso (coordinate fuori provincia).
     *
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     * @return array{id: int, warning: ?string}
     */
    public function save(array $actor, int $organizationId, ?int $siteId, array $data): array
    {
        $this->guard->authorize($actor, 'org.sites.edit', $organizationId);
        $before = [];
        if ($siteId !== null) {
            $before = $this->find($siteId);
            if ((int) $before['organization_id'] !== $organizationId) {
                throw new DomainException('manage.error.forbidden');
            }
        }

        $territoryId = (int) ($data['territory_id'] ?? 0);
        if ($this->database->fetchValue("SELECT id FROM territories WHERE id = ? AND type = 'municipality'", [$territoryId]) === null) {
            throw new DomainException('manage.error.invalid_territory');
        }
        [$lat, $lng] = [$this->coordinate($data['lat'] ?? ''), $this->coordinate($data['lng'] ?? '')];
        if (($lat === null) !== ($lng === null)) {
            throw new DomainException('manage.error.invalid_coordinates');
        }
        $warning = null;
        if ($lat !== null && ($lat < self::BOUNDS['lat'][0] || $lat > self::BOUNDS['lat'][1] || $lng < self::BOUNDS['lng'][0] || $lng > self::BOUNDS['lng'][1])) {
            $warning = 'manage.warning.outside_province';
        }

        $after = [
            'name' => trim((string) ($data['name'] ?? '')) ?: null,
            'address_line' => trim((string) ($data['address_line'] ?? '')),
            'postal_code' => trim((string) ($data['postal_code'] ?? '')) ?: null,
            'territory_id' => $territoryId,
            'lat' => $lat,
            'lng' => $lng,
            'geo_source' => $lat === null ? null : 'manual',
            'geo_checked' => $lat === null ? 0 : 1,
            'is_public_place' => empty($data['is_public_place']) ? 0 : 1,
            'step_free_access' => $this->option((string) ($data['step_free_access'] ?? 'unknown'), ['yes', 'partial', 'no', 'unknown']),
            'accessible_toilet' => $this->option((string) ($data['accessible_toilet'] ?? 'unknown'), ['yes', 'no', 'unknown']),
        ];
        if ($after['address_line'] === '') {
            throw new DomainException('manage.error.address_required');
        }
        $publication = $this->guard->publication($actor, $organizationId, (string) ($data['publication_status'] ?? 'draft'), $before['publication_status'] ?? null);
        $after['publication_status'] = $publication['status'];
        $after += array_diff_key($publication, ['status' => true]);

        $id = $this->database->transaction(function () use ($actor, $organizationId, $siteId, $before, $after): int {
            if ($siteId === null) {
                $id = $this->database->insert('sites', [...$after, 'organization_id' => $organizationId, 'created_by' => (int) $actor['id']]);
                $this->audit->log('site.created', 'site', $id, $after, ['organization_id' => $organizationId]);

                return $id;
            }
            $changes = AuditLogger::diff($before, $after);
            if ($changes !== []) {
                $this->database->update('sites', [...$after, 'updated_by' => (int) $actor['id']], ['id' => $siteId]);
                $this->audit->log('site.updated', 'site', $siteId, $changes, ['organization_id' => $organizationId]);
            }

            return $siteId;
        });

        return ['id' => $id, 'warning' => $warning];
    }

    /**
     * @param array<string, mixed> $actor
     * @param list<array<string, mixed>> $hours
     */
    public function saveHours(array $actor, int $siteId, array $hours): void
    {
        $organizationId = $this->ownerOf($actor, $siteId);
        $this->database->transaction(function () use ($siteId, $hours, $organizationId): void {
            $saved = $this->related->replaceHours('site', $siteId, $hours);
            $this->audit->log('site.hours_saved', 'site', $siteId, ['hours' => $saved], ['organization_id' => $organizationId]);
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param list<array<string, string>> $contacts
     */
    public function saveContacts(array $actor, int $siteId, array $contacts): void
    {
        $organizationId = $this->ownerOf($actor, $siteId);
        $this->database->transaction(function () use ($actor, $siteId, $contacts, $organizationId): void {
            $saved = $this->related->replaceContacts('site', $siteId, $contacts, (int) $actor['id']);
            $this->audit->log('site.contacts_saved', 'site', $siteId, ['count' => count($saved)], ['organization_id' => $organizationId]);
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, ?string> $texts
     */
    public function saveTexts(array $actor, int $siteId, string $locale, array $texts, bool $approve): void
    {
        $organizationId = $this->ownerOf($actor, $siteId, false);
        if (!in_array($locale, $this->guard->contentLocales(), true)) {
            throw new DomainException('manage.error.invalid_locale');
        }
        // Le sedi non hanno una lingua sorgente propria: si usa quella dell'organizzazione.
        $source = (string) $this->database->fetchValue('SELECT source_locale FROM organizations WHERE id = ?', [$organizationId]);
        if ($locale === $source) {
            $this->guard->assertCanChangePublished($actor, $organizationId, (string) $this->find($siteId)['publication_status']);
        }
        $fields = array_intersect_key($texts, array_flip(self::TEXT_FIELDS)) + array_fill_keys(self::TEXT_FIELDS, null);
        $status = $this->guard->translationStatus($actor, $organizationId, $locale, $source, $approve);

        $this->database->transaction(function () use ($actor, $siteId, $locale, $source, $fields, $status, $organizationId): void {
            $changes = $this->related->saveTranslation('site_translations', 'site_id', $siteId, $locale, $source, $fields, $status, (int) $actor['id']);
            $this->audit->log('site.texts_saved', 'site', $siteId, ['locale' => $locale, 'status' => $status, 'changes' => $changes], ['organization_id' => $organizationId]);
        });
    }

    /**
     * Organizzazione proprietaria, dopo aver verificato il permesso; con $content = true verifica anche
     * che chi scrive possa modificare una sede già pubblicata.
     *
     * @param array<string, mixed> $actor
     */
    private function ownerOf(array $actor, int $siteId, bool $content = true): int
    {
        $site = $this->find($siteId);
        $organizationId = (int) $site['organization_id'];
        $this->guard->authorize($actor, 'org.sites.edit', $organizationId);
        if ($content) {
            $this->guard->assertCanChangePublished($actor, $organizationId, (string) $site['publication_status']);
        }

        return $organizationId;
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        return $this->database->fetchOne('SELECT * FROM sites WHERE id = ?', [$id]) ?? throw new DomainException('manage.error.not_found');
    }

    private function coordinate(mixed $value): ?float
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value) || abs((float) $value) > 180) {
            throw new DomainException('manage.error.invalid_coordinates');
        }

        return round((float) $value, 6);
    }

    /** @param list<string> $allowed */
    private function option(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : throw new DomainException('manage.error.invalid_value');
    }
}
