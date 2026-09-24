<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Authorization\DatabaseAuthorizationData;
use App\Core\Database;
use App\Support\Slug;
use DomainException;

/**
 * Modifica delle organizzazioni (vault "52", "53"): profilo, assi di stato, testi tradotti, lingue,
 * comunità, paesi e recapiti. Ogni operazione verifica il permesso nell'ambito dell'organizzazione e
 * scrive nell'audit log. Usato dall'area amministrativa e (v0.9) dall'area riservata.
 */
final class OrganizationEditor
{
    public const TEXT_FIELDS = ['description', 'activities', 'participation_info'];
    public const STATUS_AXES = [
        'census_status' => ['to_census', 'censused'],
        'listing_status' => ['hidden', 'listed'],
        'verification_status' => ['unverified', 'pending', 'verified', 'rejected'],
        'access_status' => ['not_enabled', 'to_enable', 'enabled', 'suspended', 'disabled'],
    ];

    public function __construct(
        private readonly Database $database,
        private readonly EditorialGuard $guard,
        private readonly RelatedRecords $related,
        private readonly AuditLogger $audit,
        private readonly DatabaseAuthorizationData $authorization,
    ) {
    }

    /**
     * @param array<string, mixed> $actor
     * @param array{name: string, organization_type_id: int, is_community_based?: bool, source_locale?: string} $data
     */
    public function create(array $actor, array $data): int
    {
        if (!$this->guard->allows($actor, 'organizations.manage')) {
            throw new DomainException('manage.error.forbidden');
        }
        $this->assertType((int) $data['organization_type_id']);

        return $this->database->transaction(function () use ($actor, $data): int {
            $id = $this->database->insert('organizations', [
                'name' => trim($data['name']),
                'organization_type_id' => (int) $data['organization_type_id'],
                'is_community_based' => empty($data['is_community_based']) ? 0 : 1,
                'source_locale' => $this->sourceLocale($data['source_locale'] ?? 'it'),
                'created_by' => (int) $actor['id'],
            ]);
            $this->database->insert('organization_translations', [
                'organization_id' => $id, 'locale' => $this->sourceLocale($data['source_locale'] ?? 'it'),
                'slug' => Slug::make($data['name']), 'status' => 'approved',
            ]);
            $this->audit->log('organization.created', 'organization', $id, ['name' => $data['name']], ['organization_id' => $id]);

            return $id;
        });
    }

    /**
     * Dati di profilo: nome, sito, tipo, "creata da cittadini stranieri", lingua sorgente.
     * Il tipo di organizzazione lo cambia solo chi gestisce tutte le organizzazioni.
     *
     * @param array<string, mixed> $actor
     * @param array{name: string, short_name?: ?string, website?: ?string, organization_type_id?: int, is_community_based?: bool, source_locale?: string} $data
     */
    public function updateProfile(array $actor, int $id, array $data): void
    {
        $this->guard->authorize($actor, 'org.profile.edit', $id);
        $before = $this->find($id);
        $this->guard->assertCanChangePublished($actor, $id, (string) $before['publication_status']);
        $website = trim((string) ($data['website'] ?? ''));
        if ($website !== '' && !preg_match('#^https?://#i', $website)) {
            throw new DomainException('manage.error.invalid_url');
        }
        $after = [
            'name' => trim($data['name']),
            'short_name' => trim((string) ($data['short_name'] ?? '')) ?: null,
            'website' => $website ?: null,
            'is_community_based' => empty($data['is_community_based']) ? 0 : 1,
            'source_locale' => $this->sourceLocale((string) ($data['source_locale'] ?? $before['source_locale'])),
        ];
        if (isset($data['organization_type_id']) && $this->guard->allows($actor, 'organizations.manage')) {
            $this->assertType((int) $data['organization_type_id']);
            $after['organization_type_id'] = (int) $data['organization_type_id'];
        }

        $this->save($actor, $id, $before, $after, 'organization.updated');
    }

    /**
     * Assi di stato (D-009): censimento, elenco pubblico e verifica (organizations.verify); abilitazione,
     * modifica dall'area riservata e politica di pubblicazione (organizations.enable); pubblicazione della
     * scheda (content.publish). I campi per cui manca il permesso restano invariati.
     *
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     */
    public function updateStatus(array $actor, int $id, array $data): void
    {
        $before = $this->find($id);
        $after = [];
        if ($this->guard->allows($actor, 'organizations.verify')) {
            foreach (['census_status', 'listing_status', 'verification_status'] as $axis) {
                if (isset($data[$axis])) {
                    $after[$axis] = $this->axis($axis, (string) $data[$axis]);
                }
            }
            if (($after['verification_status'] ?? null) === 'verified' && $before['verification_status'] !== 'verified') {
                $after['verified_at'] = gmdate('Y-m-d H:i:s');
                $after['verified_by'] = (int) $actor['id'];
            }
            if (array_key_exists('next_review_at', $data)) {
                $after['next_review_at'] = EditorialGuard::date((string) $data['next_review_at']);
            }
            if (array_key_exists('status_note', $data)) {
                $after['status_note'] = mb_substr(trim((string) $data['status_note']), 0, 500) ?: null;
            }
        }
        if ($this->guard->allows($actor, 'organizations.enable')) {
            if (isset($data['access_status'])) {
                $after['access_status'] = $this->axis('access_status', (string) $data['access_status']);
            }
            if (array_key_exists('portal_edit_enabled', $data)) {
                $after['portal_edit_enabled'] = empty($data['portal_edit_enabled']) ? 0 : 1;
            }
            if (array_key_exists('publication_policy', $data)) {
                $policy = (string) $data['publication_policy'];
                $after['publication_policy'] = in_array($policy, ['review', 'direct'], true) ? $policy : null;
            }
        }
        if (isset($data['publication_status']) && $this->guard->allows($actor, 'content.publish', $id)) {
            $publication = $this->guard->publication($actor, $id, (string) $data['publication_status'], (string) $before['publication_status']);
            $after['publication_status'] = $publication['status'];
            $after += array_diff_key($publication, ['status' => true]);
        }
        if ($after === []) {
            throw new DomainException('manage.error.forbidden');
        }

        $this->save($actor, $id, $before, $after, 'organization.status_changed');
        $this->authorization->forget();
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, ?string> $texts description, activities, participation_info
     */
    public function saveTexts(array $actor, int $id, string $locale, array $texts, bool $approve): void
    {
        $organization = $this->find($id);
        $this->guard->authorize($actor, $locale === $organization['source_locale'] ? 'org.profile.edit' : 'translations.edit', $id);
        if ($locale === $organization['source_locale']) {
            $this->guard->assertCanChangePublished($actor, $id, (string) $organization['publication_status']);
        }
        if (!in_array($locale, $this->guard->contentLocales(), true)) {
            throw new DomainException('manage.error.invalid_locale');
        }
        $fields = array_intersect_key($texts, array_flip(self::TEXT_FIELDS)) + array_fill_keys(self::TEXT_FIELDS, null);
        $fields['slug'] = Slug::make((string) $organization['name']);
        $status = $this->guard->translationStatus($actor, $id, $locale, (string) $organization['source_locale'], $approve);

        $this->database->transaction(function () use ($actor, $id, $locale, $organization, $fields, $status): void {
            $changes = $this->related->saveTranslation('organization_translations', 'organization_id', $id, $locale, (string) $organization['source_locale'], $fields, $status, (int) $actor['id']);
            $this->audit->log('organization.texts_saved', 'organization', $id, ['locale' => $locale, 'status' => $status, 'changes' => $changes], ['organization_id' => $id]);
        });
    }

    /**
     * Lingue parlate, comunità e paesi collegati.
     *
     * @param array<string, mixed> $actor
     * @param list<string> $languages
     * @param list<int> $communities
     * @param list<string> $countries codici ISO 3166-1 alpha-2
     */
    public function saveLinks(array $actor, int $id, array $languages, array $communities, array $countries): void
    {
        $this->guard->authorize($actor, 'org.profile.edit', $id);
        $this->guard->assertCanChangePublished($actor, $id, (string) $this->find($id)['publication_status']);
        $countries = array_values(array_filter(array_map('strtoupper', $countries), static fn (string $c): bool => (bool) preg_match('/^[A-Z]{2}$/', $c)));

        $this->database->transaction(function () use ($id, $languages, $communities, $countries): void {
            $saved = [
                'languages' => $this->related->replaceLinks('organization_languages', 'organization_id', $id, 'language_code', $languages,
                    $this->database->fetchColumn('SELECT code FROM languages WHERE is_active = 1')),
                'communities' => $this->related->replaceLinks('organization_communities', 'organization_id', $id, 'community_id', array_map('intval', $communities),
                    array_map('intval', $this->database->fetchColumn('SELECT id FROM communities WHERE is_active = 1'))),
                'countries' => $this->related->replaceLinks('organization_countries', 'organization_id', $id, 'country_code', $countries, $countries),
            ];
            $this->audit->log('organization.links_saved', 'organization', $id, $saved, ['organization_id' => $id]);
        });
    }

    /**
     * @param array<string, mixed> $actor
     * @param list<array<string, string>> $contacts
     */
    public function saveContacts(array $actor, int $id, array $contacts): void
    {
        $this->guard->authorize($actor, 'org.profile.edit', $id);
        $this->guard->assertCanChangePublished($actor, $id, (string) $this->find($id)['publication_status']);
        $this->database->transaction(function () use ($actor, $id, $contacts): void {
            $saved = $this->related->replaceContacts('organization', $id, $contacts, (int) $actor['id']);
            $this->audit->log('organization.contacts_saved', 'organization', $id, ['count' => count($saved)], ['organization_id' => $id]);
        });
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        return $this->database->fetchOne('SELECT * FROM organizations WHERE id = ?', [$id]) ?? throw new DomainException('manage.error.not_found');
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function save(array $actor, int $id, array $before, array $after, string $action): void
    {
        $changes = AuditLogger::diff($before, $after);
        if ($changes === []) {
            return;
        }
        $this->database->transaction(function () use ($actor, $id, $after, $changes, $action): void {
            $this->database->update('organizations', [...$after, 'updated_by' => (int) $actor['id']], ['id' => $id]);
            $this->audit->log($action, 'organization', $id, $changes, ['organization_id' => $id]);
        });
    }

    private function axis(string $axis, string $value): string
    {
        return in_array($value, self::STATUS_AXES[$axis], true) ? $value : throw new DomainException('manage.error.invalid_status');
    }

    private function assertType(int $typeId): void
    {
        if ($this->database->fetchValue('SELECT id FROM organization_types WHERE id = ?', [$typeId]) === null) {
            throw new DomainException('manage.error.invalid_type');
        }
    }

    private function sourceLocale(string $locale): string
    {
        return in_array($locale, $this->guard->contentLocales(), true) ? $locale : 'it';
    }
}
