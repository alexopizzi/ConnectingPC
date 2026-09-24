<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Core\Database;
use App\Support\Slug;
use DomainException;

/**
 * Servizi (vault "62", "65"): dati di accesso, categorie, bisogni, lingue, sedi, pubblicazione e testi
 * tradotti. Permesso org.services.edit nell'ambito dell'organizzazione proprietaria.
 */
final class ServiceEditor
{
    public const TEXT_FIELDS = [
        'name', 'summary', 'description', 'target_audience', 'requirements', 'documents',
        'access_info', 'booking_info', 'cost_info', 'notes', 'keywords',
    ];
    public const ACCESS_MODES = ['in_person', 'phone', 'online', 'email', 'home_visit'];
    public const BOOKING = ['not_needed', 'recommended', 'required'];
    public const COST = ['free', 'paid', 'partly_free', 'unknown'];
    public const MEDIATION = ['available', 'on_request', 'not_available', 'unknown'];
    public const LANGUAGE_MODES = ['staff', 'mediator', 'written_material', 'on_request'];

    public function __construct(
        private readonly Database $database,
        private readonly EditorialGuard $guard,
        private readonly RelatedRecords $related,
        private readonly AuditLogger $audit,
        private readonly int $reviewIntervalDays = 180,
    ) {
    }

    /**
     * Crea un servizio con il nome nella lingua sorgente, poi applica i dati.
     *
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     */
    public function create(array $actor, int $organizationId, string $name, array $data): int
    {
        $this->guard->authorize($actor, 'org.services.edit', $organizationId);
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 255) {
            throw new DomainException('manage.error.name_required');
        }
        $source = (string) $this->database->fetchValue('SELECT source_locale FROM organizations WHERE id = ?', [$organizationId]);
        if ($source === '') {
            throw new DomainException('manage.error.not_found');
        }

        return $this->database->transaction(function () use ($actor, $organizationId, $name, $data, $source): int {
            $id = $this->database->insert('services', [
                'organization_id' => $organizationId,
                'primary_category_id' => $this->category((int) ($data['primary_category_id'] ?? 0)),
                'source_locale' => $source,
                'created_by' => (int) $actor['id'],
            ]);
            $this->database->insert('service_translations', [
                'service_id' => $id, 'locale' => $source, 'slug' => Slug::make($name), 'name' => $name, 'status' => 'approved',
                'reviewed_by' => (int) $actor['id'],
            ]);
            $this->audit->log('service.created', 'service', $id, ['name' => $name], ['organization_id' => $organizationId]);
            $this->update($actor, $id, $data);

            return $id;
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     */
    public function update(array $actor, int $id, array $data): void
    {
        $before = $this->find($id);
        $organizationId = (int) $before['organization_id'];
        $this->guard->authorize($actor, 'org.services.edit', $organizationId);

        $modes = array_values(array_intersect(self::ACCESS_MODES, (array) ($data['access_modes'] ?? [])));
        $after = [
            'primary_category_id' => $this->category((int) ($data['primary_category_id'] ?? 0)),
            'access_modes' => implode(',', $modes === [] ? ['in_person'] : $modes),
            'booking' => $this->option((string) ($data['booking'] ?? 'not_needed'), self::BOOKING),
            'booking_url' => $this->url((string) ($data['booking_url'] ?? '')),
            'cost_type' => $this->option((string) ($data['cost_type'] ?? 'unknown'), self::COST),
            'mediation' => $this->option((string) ($data['mediation'] ?? 'unknown'), self::MEDIATION),
            'online_url' => $this->url((string) ($data['online_url'] ?? '')),
            'valid_from' => EditorialGuard::date((string) ($data['valid_from'] ?? '')),
            'valid_to' => EditorialGuard::date((string) ($data['valid_to'] ?? '')),
            'next_review_at' => EditorialGuard::date((string) ($data['next_review_at'] ?? '')),
        ];
        if ($after['valid_from'] !== null && $after['valid_to'] !== null && $after['valid_to'] < $after['valid_from']) {
            throw new DomainException('manage.error.invalid_period');
        }
        $publication = $this->guard->publication($actor, $organizationId, (string) ($data['publication_status'] ?? 'draft'), (string) $before['publication_status']);
        $after['publication_status'] = $publication['status'];
        $after += array_diff_key($publication, ['status' => true]);
        if (!empty($data['mark_verified'])) {
            $after['verified_at'] = gmdate('Y-m-d H:i:s');
            $after['verified_by'] = (int) $actor['id'];
            // Verifica rapida (RF-26): se non è indicata, la prossima revisione segue l'intervallo di piattaforma
            if ($after['next_review_at'] === null || $after['next_review_at'] <= gmdate('Y-m-d')) {
                $after['next_review_at'] = (new \DateTimeImmutable('today'))->modify('+' . $this->reviewIntervalDays . ' days')->format('Y-m-d');
            }
        }

        $categories = array_map('intval', (array) ($data['categories'] ?? []));
        $needs = array_map('intval', (array) ($data['needs'] ?? []));
        $sites = array_map('intval', (array) ($data['sites'] ?? []));
        $mainSite = (int) ($data['main_site'] ?? 0);
        $languages = [];
        foreach ((array) ($data['languages'] ?? []) as $row) {
            $code = (string) ($row['code'] ?? '');
            $mode = (string) ($row['mode'] ?? '');
            if ($code !== '' && in_array($mode, self::LANGUAGE_MODES, true)) {
                $languages[$code . '|' . $mode] = [$code, $mode];
            }
        }

        $this->database->transaction(function () use ($actor, $id, $organizationId, $before, $after, $categories, $needs, $sites, $mainSite, $languages): void {
            $changes = AuditLogger::diff($before, $after);
            if ($changes !== []) {
                $this->database->update('services', [...$after, 'updated_by' => (int) $actor['id']], ['id' => $id]);
            }

            $validCategories = array_map('intval', $this->database->fetchColumn('SELECT id FROM categories WHERE is_active = 1'));
            $links['categories'] = $this->related->replaceLinks('service_categories', 'service_id', $id, 'category_id',
                array_values(array_unique([$after['primary_category_id'], ...$categories])), $validCategories);
            $links['needs'] = $this->related->replaceLinks('service_needs', 'service_id', $id, 'need_id', $needs,
                array_map('intval', $this->database->fetchColumn('SELECT id FROM needs WHERE is_active = 1')));

            // Solo sedi della stessa organizzazione
            $ownSites = array_map('intval', $this->database->fetchColumn('SELECT id FROM sites WHERE organization_id = ?', [$organizationId]));
            $sites = array_values(array_intersect($sites, $ownSites));
            if ($mainSite === 0 || !in_array($mainSite, $sites, true)) {
                $mainSite = $sites[0] ?? 0;
            }
            $this->database->execute('DELETE FROM service_sites WHERE service_id = ?', [$id]);
            foreach ($sites as $siteId) {
                $this->database->insert('service_sites', ['service_id' => $id, 'site_id' => $siteId, 'is_main' => $siteId === $mainSite ? 1 : 0]);
            }
            $links['sites'] = $sites;

            $validLanguages = array_map('strval', $this->database->fetchColumn('SELECT code FROM languages WHERE is_active = 1'));
            $this->database->execute('DELETE FROM service_languages WHERE service_id = ?', [$id]);
            foreach ($languages as [$code, $mode]) {
                if (in_array($code, $validLanguages, true)) {
                    $this->database->insert('service_languages', ['service_id' => $id, 'language_code' => $code, 'mode' => $mode]);
                }
            }
            $links['languages'] = array_keys($languages);

            $this->audit->log('service.updated', 'service', $id, [...$changes, 'links' => $links], ['organization_id' => $organizationId]);
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, ?string> $texts
     */
    public function saveTexts(array $actor, int $id, string $locale, array $texts, bool $approve): void
    {
        $service = $this->find($id);
        $organizationId = (int) $service['organization_id'];
        $source = (string) $service['source_locale'];
        $this->guard->authorize($actor, $locale === $source ? 'org.services.edit' : 'translations.edit', $organizationId);
        if ($locale === $source) {
            $this->guard->assertCanChangePublished($actor, $organizationId, (string) $service['publication_status']);
        }
        if (!in_array($locale, $this->guard->contentLocales(), true)) {
            throw new DomainException('manage.error.invalid_locale');
        }
        $fields = array_intersect_key($texts, array_flip(self::TEXT_FIELDS)) + array_fill_keys(self::TEXT_FIELDS, null);
        $name = trim((string) $fields['name']);
        if ($name === '') {
            throw new DomainException('manage.error.name_required');
        }
        if (mb_strlen((string) $fields['summary']) > 500) {
            throw new DomainException('manage.error.summary_too_long');
        }
        $fields['slug'] = Slug::make($name);
        $status = $this->guard->translationStatus($actor, $organizationId, $locale, $source, $approve);

        $this->database->transaction(function () use ($actor, $id, $locale, $source, $fields, $status, $organizationId): void {
            $changes = $this->related->saveTranslation('service_translations', 'service_id', $id, $locale, $source, $fields, $status, (int) $actor['id']);
            $this->audit->log('service.texts_saved', 'service', $id, ['locale' => $locale, 'status' => $status, 'changes' => $changes], ['organization_id' => $organizationId]);
        });
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        return $this->database->fetchOne('SELECT * FROM services WHERE id = ?', [$id]) ?? throw new DomainException('manage.error.not_found');
    }

    private function category(int $id): int
    {
        return $this->database->fetchValue('SELECT id FROM categories WHERE id = ? AND is_active = 1', [$id]) !== null
            ? $id : throw new DomainException('manage.error.invalid_category');
    }

    /** @param list<string> $allowed */
    private function option(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : throw new DomainException('manage.error.invalid_value');
    }

    private function url(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return preg_match('#^https?://#i', $value) && mb_strlen($value) <= 500 ? $value : throw new DomainException('manage.error.invalid_url');
    }
}
