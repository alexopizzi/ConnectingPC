<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Core\Database;
use App\Domain\Content\ContentReader;

/**
 * Letture per la gestione (area amministrativa e, dalla v0.9, area riservata): tutti gli stati, tutte le
 * lingue, tutti i recapiti. Chi chiama deve aver già verificato i permessi di lettura.
 */
final class ManagementRepository
{
    private readonly ContentReader $content;

    public function __construct(private readonly Database $database, private readonly RelatedRecords $related)
    {
        $this->content = new ContentReader($database);
    }

    // --- Organizzazioni --------------------------------------------------------------------

    /**
     * @param array{q?: string, access?: string, publication?: string, type?: int} $filters
     * @param list<int>|null $onlyIds limita alle organizzazioni indicate (area riservata)
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function organizations(array $filters, int $limit, int $offset, ?array $onlyIds = null): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(o.name LIKE ? OR o.short_name LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (($filters['access'] ?? '') !== '') {
            $where[] = 'o.access_status = ?';
            $params[] = $filters['access'];
        }
        if (($filters['publication'] ?? '') !== '') {
            $where[] = 'o.publication_status = ?';
            $params[] = $filters['publication'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'o.organization_type_id = ?';
            $params[] = (int) $filters['type'];
        }
        if ($onlyIds !== null) {
            if ($onlyIds === []) {
                return ['rows' => [], 'total' => 0];
            }
            $where[] = 'o.id IN (' . implode(',', array_fill(0, count($onlyIds), '?')) . ')';
            $params = [...$params, ...$onlyIds];
        }
        $sqlWhere = implode(' AND ', $where);

        return [
            'rows' => $this->database->fetchAll(
                "SELECT o.id, o.name, o.access_status, o.publication_status, o.listing_status, o.verification_status, o.is_community_based,
                        tt.name AS type_name,
                        (SELECT COUNT(*) FROM services s WHERE s.organization_id = o.id) AS services_count,
                        (SELECT COUNT(*) FROM sites si WHERE si.organization_id = o.id) AS sites_count
                   FROM organizations o
                   LEFT JOIN organization_type_translations tt ON tt.organization_type_id = o.organization_type_id AND tt.locale = 'it'
                  WHERE $sqlWhere ORDER BY o.name LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
                $params,
            ),
            'total' => (int) $this->database->fetchValue("SELECT COUNT(*) FROM organizations o WHERE $sqlWhere", $params),
        ];
    }

    /** @return array<string, mixed>|null */
    public function organization(int $id): ?array
    {
        $row = $this->database->fetchOne('SELECT * FROM organizations WHERE id = ?', [$id]);
        if ($row === null) {
            return null;
        }

        return [
            ...$row,
            'translations' => $this->translations('organization_translations', 'organization_id', $id),
            'languages' => array_map('strval', $this->database->fetchColumn('SELECT language_code FROM organization_languages WHERE organization_id = ? ORDER BY language_code', [$id])),
            'communities' => array_map('intval', $this->database->fetchColumn('SELECT community_id FROM organization_communities WHERE organization_id = ?', [$id])),
            'countries' => array_map('strval', $this->database->fetchColumn('SELECT country_code FROM organization_countries WHERE organization_id = ? ORDER BY country_code', [$id])),
            'contacts' => $this->related->contacts('organization', $id),
            'sites' => $this->database->fetchAll(
                'SELECT si.id, si.name, si.address_line, si.publication_status, si.is_public_place, si.lat, t.name AS town
                   FROM sites si JOIN territories t ON t.id = si.territory_id WHERE si.organization_id = ? ORDER BY si.id',
                [$id],
            ),
            'services' => $this->database->fetchAll(
                "SELECT s.id, s.publication_status, s.next_review_at, COALESCE(tr.name, ti.name, CONCAT('#', s.id)) AS name
                   FROM services s
                   LEFT JOIN service_translations tr ON tr.service_id = s.id AND tr.locale = s.source_locale
                   LEFT JOIN service_translations ti ON ti.service_id = s.id AND ti.locale = 'it'
                  WHERE s.organization_id = ? ORDER BY name",
                [$id],
            ),
            'mediators' => $this->database->fetchAll(
                'SELECT id, first_name, last_name, profile_visibility, publication_status FROM mediators WHERE organization_id = ? ORDER BY last_name, first_name',
                [$id],
            ),
        ];
    }

    // --- Sedi ------------------------------------------------------------------------------

    /** @return array<string, mixed>|null */
    public function site(int $id): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT si.*, o.name AS organization_name, o.source_locale FROM sites si JOIN organizations o ON o.id = si.organization_id WHERE si.id = ?',
            [$id],
        );

        return $row === null ? null : [
            ...$row,
            'translations' => $this->translations('site_translations', 'site_id', $id),
            'hours' => $this->related->hours('site', $id),
            'contacts' => $this->related->contacts('site', $id),
        ];
    }

    // --- Servizi ---------------------------------------------------------------------------

    /**
     * @param array{q?: string, publication?: string, category?: int, organization?: int, review_due?: bool} $filters
     * @param list<int>|null $onlyOrganizations
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function services(array $filters, int $limit, int $offset, ?array $onlyOrganizations = null): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($filters['q'] ?? '') !== '') {
            $where[] = 's.id IN (SELECT service_id FROM service_translations WHERE name LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (($filters['publication'] ?? '') !== '') {
            $where[] = 's.publication_status = ?';
            $params[] = $filters['publication'];
        }
        if (!empty($filters['category'])) {
            $where[] = 's.id IN (SELECT sc.service_id FROM service_categories sc JOIN categories c ON c.id = sc.category_id WHERE c.id = ? OR c.parent_id = ?)';
            $params[] = (int) $filters['category'];
            $params[] = (int) $filters['category'];
        }
        if (!empty($filters['organization'])) {
            $where[] = 's.organization_id = ?';
            $params[] = (int) $filters['organization'];
        }
        if (!empty($filters['review_due'])) {
            $where[] = 's.next_review_at IS NOT NULL AND s.next_review_at <= CURRENT_DATE';
        }
        if ($onlyOrganizations !== null) {
            if ($onlyOrganizations === []) {
                return ['rows' => [], 'total' => 0];
            }
            $where[] = 's.organization_id IN (' . implode(',', array_fill(0, count($onlyOrganizations), '?')) . ')';
            $params = [...$params, ...$onlyOrganizations];
        }
        $sqlWhere = implode(' AND ', $where);

        return [
            'rows' => $this->database->fetchAll(
                "SELECT s.id, s.publication_status, s.next_review_at, s.updated_at, o.id AS organization_id, o.name AS organization_name,
                        COALESCE(tr.name, CONCAT('#', s.id)) AS name, ct.name AS category_name,
                        (SELECT GROUP_CONCAT(CONCAT(locale, ':', status) ORDER BY locale) FROM service_translations x WHERE x.service_id = s.id) AS translation_states
                   FROM services s
                   JOIN organizations o ON o.id = s.organization_id
                   LEFT JOIN service_translations tr ON tr.service_id = s.id AND tr.locale = s.source_locale
                   LEFT JOIN category_translations ct ON ct.category_id = s.primary_category_id AND ct.locale = 'it'
                  WHERE $sqlWhere ORDER BY name LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
                $params,
            ),
            'total' => (int) $this->database->fetchValue("SELECT COUNT(*) FROM services s WHERE $sqlWhere", $params),
        ];
    }

    /** @return array<string, mixed>|null */
    public function service(int $id): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT s.*, o.name AS organization_name FROM services s JOIN organizations o ON o.id = s.organization_id WHERE s.id = ?',
            [$id],
        );
        if ($row === null) {
            return null;
        }
        $languages = [];
        foreach ($this->database->fetchAll('SELECT language_code, mode FROM service_languages WHERE service_id = ? ORDER BY language_code', [$id]) as $l) {
            $languages[] = ['code' => (string) $l['language_code'], 'mode' => (string) $l['mode']];
        }

        return [
            ...$row,
            'translations' => $this->translations('service_translations', 'service_id', $id),
            'categories' => array_map('intval', $this->database->fetchColumn('SELECT category_id FROM service_categories WHERE service_id = ?', [$id])),
            'needs' => array_map('intval', $this->database->fetchColumn('SELECT need_id FROM service_needs WHERE service_id = ?', [$id])),
            'languages' => $languages,
            'sites' => array_map('intval', $this->database->fetchColumn('SELECT site_id FROM service_sites WHERE service_id = ?', [$id])),
            'main_site' => (int) $this->database->fetchValue('SELECT site_id FROM service_sites WHERE service_id = ? AND is_main = 1', [$id]),
            'organization_sites' => $this->organizationSites((int) $row['organization_id']),
        ];
    }

    /** @return list<array{id: int, label: string}> */
    public function organizationSites(int $organizationId): array
    {
        return array_map(static fn (array $s): array => [
            'id' => (int) $s['id'],
            'label' => trim(($s['name'] ? $s['name'] . ' – ' : '') . $s['address_line'] . ', ' . $s['town']),
        ], $this->database->fetchAll(
            'SELECT si.id, si.name, si.address_line, t.name AS town FROM sites si JOIN territories t ON t.id = si.territory_id WHERE si.organization_id = ? ORDER BY si.id',
            [$organizationId],
        ));
    }

    // --- Mediatori -------------------------------------------------------------------------

    /**
     * @param array{q?: string, visibility?: string, organization?: int} $filters
     * @param list<int>|null $onlyOrganizations
     * @return list<array<string, mixed>>
     */
    public function mediators(array $filters, ?array $onlyOrganizations = null): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($filters['q'] ?? '') !== '') {
            $where[] = "(CONCAT(m.first_name, ' ', m.last_name) LIKE ? OR m.public_display_name LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (($filters['visibility'] ?? '') !== '') {
            $where[] = 'm.profile_visibility = ?';
            $params[] = $filters['visibility'];
        }
        if (!empty($filters['organization'])) {
            $where[] = 'm.organization_id = ?';
            $params[] = (int) $filters['organization'];
        }
        if ($onlyOrganizations !== null) {
            if ($onlyOrganizations === []) {
                return [];
            }
            $where[] = 'm.organization_id IN (' . implode(',', array_fill(0, count($onlyOrganizations), '?')) . ')';
            $params = [...$params, ...$onlyOrganizations];
        }

        return $this->database->fetchAll(
            'SELECT m.id, m.first_name, m.last_name, m.public_display_name, m.profile_visibility, m.public_consent_at, m.availability,
                    m.verification_status, m.publication_status, o.name AS organization_name,
                    (SELECT GROUP_CONCAT(language_code ORDER BY language_code) FROM mediator_languages WHERE mediator_id = m.id) AS languages
               FROM mediators m LEFT JOIN organizations o ON o.id = m.organization_id
              WHERE ' . implode(' AND ', $where) . ' ORDER BY m.last_name, m.first_name',
            $params,
        );
    }

    /** @return array<string, mixed>|null */
    public function mediator(int $id): ?array
    {
        $row = $this->database->fetchOne('SELECT * FROM mediators WHERE id = ?', [$id]);
        if ($row === null) {
            return null;
        }
        $languages = [];
        foreach ($this->database->fetchAll('SELECT language_code, proficiency FROM mediator_languages WHERE mediator_id = ? ORDER BY language_code', [$id]) as $l) {
            $languages[] = ['code' => (string) $l['language_code'], 'level' => (string) $l['proficiency']];
        }

        return [
            ...$row,
            'translations' => $this->translations('mediator_translations', 'mediator_id', $id),
            'languages' => $languages,
            'domains' => array_map('intval', $this->database->fetchColumn('SELECT mediation_domain_id FROM mediator_domains WHERE mediator_id = ?', [$id])),
            'territories' => array_map('intval', $this->database->fetchColumn('SELECT territory_id FROM mediator_territories WHERE mediator_id = ?', [$id])),
            'contacts' => $this->related->contacts('mediator', $id),
        ];
    }

    // --- Opzioni per i moduli --------------------------------------------------------------

    /** @return list<array{id: int, name: string}> */
    public function organizationTypes(string $locale = 'it'): array
    {
        return $this->labelled(
            "SELECT t.id, COALESCE(l.name, i.name) AS name FROM organization_types t
               JOIN organization_type_translations i ON i.organization_type_id = t.id AND i.locale = 'it'
               LEFT JOIN organization_type_translations l ON l.organization_type_id = t.id AND l.locale = ? AND l.status <> 'draft'
              ORDER BY t.sort_order",
            [$locale],
        );
    }

    /** @return list<array{id: int, name: string, parent: ?string}> categorie con l'area di appartenenza */
    public function categories(string $locale = 'it'): array
    {
        return array_map(static fn (array $r): array => ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'parent' => $r['parent'] === null ? null : (string) $r['parent']],
            $this->database->fetchAll(
                "SELECT c.id, COALESCE(cl.name, ci.name) AS name, COALESCE(pl.name, pi.name) AS parent FROM categories c
                   JOIN category_translations ci ON ci.category_id = c.id AND ci.locale = 'it'
                   LEFT JOIN category_translations cl ON cl.category_id = c.id AND cl.locale = ? AND cl.status <> 'draft'
                   LEFT JOIN categories p ON p.id = c.parent_id
                   LEFT JOIN category_translations pi ON pi.category_id = p.id AND pi.locale = 'it'
                   LEFT JOIN category_translations pl ON pl.category_id = p.id AND pl.locale = ? AND pl.status <> 'draft'
                  WHERE c.is_active = 1 ORDER BY COALESCE(p.sort_order, c.sort_order), c.parent_id IS NOT NULL, c.sort_order",
                [$locale, $locale],
            ));
    }

    /** @return list<array{id: int, name: string}> */
    public function needs(string $locale = 'it'): array
    {
        return $this->labelled(
            "SELECT n.id, COALESCE(l.label, i.label) AS name FROM needs n
               JOIN need_translations i ON i.need_id = n.id AND i.locale = 'it'
               LEFT JOIN need_translations l ON l.need_id = n.id AND l.locale = ? AND l.status <> 'draft'
              WHERE n.is_active = 1 ORDER BY n.sort_order",
            [$locale],
        );
    }

    /** @return list<array{id: int, name: string}> */
    public function communities(string $locale = 'it'): array
    {
        return $this->labelled(
            "SELECT c.id, COALESCE(l.name, i.name) AS name FROM communities c
               JOIN community_translations i ON i.community_id = c.id AND i.locale = 'it'
               LEFT JOIN community_translations l ON l.community_id = c.id AND l.locale = ? AND l.status <> 'draft'
              WHERE c.is_active = 1 ORDER BY c.sort_order",
            [$locale],
        );
    }

    /** @return list<array{id: int, name: string}> */
    public function mediationDomains(string $locale = 'it'): array
    {
        return $this->labelled(
            "SELECT d.id, COALESCE(l.name, i.name) AS name FROM mediation_domains d
               JOIN mediation_domain_translations i ON i.mediation_domain_id = d.id AND i.locale = 'it'
               LEFT JOIN mediation_domain_translations l ON l.mediation_domain_id = d.id AND l.locale = ? AND l.status <> 'draft'
              ORDER BY d.sort_order",
            [$locale],
        );
    }

    /** @return list<string> */
    public function languages(): array
    {
        return array_map('strval', $this->database->fetchColumn('SELECT code FROM languages WHERE is_active = 1 ORDER BY sort_order'));
    }

    /** @return list<array{id: int, name: string, municipalities: list<array{id: int, name: string}>}> */
    public function districtTree(): array
    {
        return $this->content->districtTree();
    }

    /** @return list<array{id: int, name: string, type: string}> provincia e distretti (zone ampie per i mediatori) */
    public function wideTerritories(): array
    {
        return array_map(static fn (array $r): array => ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'type' => (string) $r['type']],
            $this->database->fetchAll("SELECT id, name, type FROM territories WHERE type IN ('province', 'district') ORDER BY type DESC, id"));
    }

    /**
     * @param list<int>|null $onlyIds
     * @return list<array{id: int, name: string}>
     */
    public function organizationOptions(?array $onlyIds = null): array
    {
        if ($onlyIds === null) {
            return $this->labelled('SELECT id, name FROM organizations ORDER BY name');
        }
        if ($onlyIds === []) {
            return [];
        }

        return $this->labelled('SELECT id, name FROM organizations WHERE id IN (' . implode(',', array_fill(0, count($onlyIds), '?')) . ') ORDER BY name', $onlyIds);
    }

    // --- Cruscotto dell'area riservata (RF-21) ---------------------------------------------

    /**
     * Contenuti delle organizzazioni indicate che richiedono attenzione: bozze, in revisione, respinti,
     * servizi con revisione scaduta. Per i respinti, l'ultima nota del revisore.
     *
     * @param list<int> $organizationIds
     * @return list<array{entity_type: string, entity_id: int, organization_id: int, label: string, status: string, note: ?string}>
     */
    public function pendingWork(array $organizationIds): array
    {
        if ($organizationIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($organizationIds), '?'));
        $rows = $this->database->fetchAll(
            "SELECT 'organization' AS entity_type, o.id AS entity_id, o.id AS organization_id, o.name AS label, o.publication_status AS status
               FROM organizations o WHERE o.id IN ($in) AND o.publication_status IN ('draft', 'in_review', 'rejected')
             UNION ALL
             SELECT 'site', si.id, si.organization_id, si.address_line, si.publication_status
               FROM sites si WHERE si.organization_id IN ($in) AND si.publication_status IN ('draft', 'in_review', 'rejected')
             UNION ALL
             SELECT 'service', s.id, s.organization_id, COALESCE(t.name, CONCAT('#', s.id)),
                    CASE WHEN s.publication_status = 'published' THEN 'review_due' ELSE s.publication_status END
               FROM services s LEFT JOIN service_translations t ON t.service_id = s.id AND t.locale = s.source_locale
              WHERE s.organization_id IN ($in)
                AND (s.publication_status IN ('draft', 'in_review', 'rejected')
                     OR (s.publication_status = 'published' AND s.next_review_at IS NOT NULL AND s.next_review_at <= CURRENT_DATE))
             UNION ALL
             SELECT 'mediator', m.id, m.organization_id, CONCAT(m.first_name, ' ', m.last_name), m.publication_status
               FROM mediators m WHERE m.organization_id IN ($in) AND m.publication_status IN ('draft', 'in_review', 'rejected')
             ORDER BY 5, 4",
            [...$organizationIds, ...$organizationIds, ...$organizationIds, ...$organizationIds],
        );
        $notes = [];
        foreach ($this->database->fetchAll(
            "SELECT entity_type, entity_id, note FROM review_decisions WHERE organization_id IN ($in) AND decision = 'rejected' ORDER BY id",
            $organizationIds,
        ) as $d) {
            $notes[$d['entity_type'] . ':' . $d['entity_id']] = $d['note'] === null ? null : (string) $d['note'];
        }

        return array_map(static fn (array $r): array => [
            'entity_type' => (string) $r['entity_type'], 'entity_id' => (int) $r['entity_id'], 'organization_id' => (int) $r['organization_id'],
            'label' => (string) $r['label'], 'status' => (string) $r['status'],
            'note' => $r['status'] === 'rejected' ? ($notes[$r['entity_type'] . ':' . $r['entity_id']] ?? null) : null,
        ], $rows);
    }

    // --- Modifiche recenti delle organizzazioni (vault "53", controllo a posteriori) ------------

    /**
     * Modifiche ai contenuti fatte dagli utenti delle organizzazioni (non dallo staff con ruolo globale).
     *
     * @return list<array<string, mixed>>
     */
    public function recentChanges(int $organizationId, int $days): array
    {
        $params = [max(1, min(365, $days))];
        $organization = '';
        if ($organizationId > 0) {
            $organization = ' AND l.organization_id = ?';
            $params[] = $organizationId;
        }

        return $this->database->fetchAll(
            "SELECT l.id, l.occurred_at, l.action, l.entity_type, l.entity_id, l.changes, u.display_name AS user_name,
                    o.id AS organization_id, o.name AS organization_name
               FROM audit_log l
               JOIN users u ON u.id = l.user_id
               JOIN organizations o ON o.id = l.organization_id
              WHERE l.occurred_at >= UTC_TIMESTAMP() - INTERVAL ? DAY
                AND l.entity_type IN ('organization', 'site', 'service', 'mediator')
                AND NOT EXISTS (SELECT 1 FROM role_assignments a WHERE a.user_id = l.user_id AND a.scope_type = 'global' AND a.revoked_at IS NULL)
                $organization
              ORDER BY l.id DESC LIMIT 300",
            $params,
        );
    }

    // --- Qualità dei dati (vault "72") -----------------------------------------------------

    /** @return array<string, list<array<string, mixed>>> */
    public function qualityReport(): array
    {
        $locales = array_map('strval', $this->database->fetchColumn('SELECT code FROM locales WHERE is_enabled = 1 ORDER BY sort_order'));
        $missing = [];
        foreach ($locales as $locale) {
            $missing[] = [
                'locale' => $locale,
                'count' => (int) $this->database->fetchValue(
                    "SELECT COUNT(*) FROM services s WHERE s.publication_status = 'published'
                        AND NOT EXISTS (SELECT 1 FROM service_translations t WHERE t.service_id = s.id AND t.locale = ? AND t.status = 'approved')",
                    [$locale],
                ),
            ];
        }

        return [
            'review_due' => $this->database->fetchAll(
                "SELECT s.id, COALESCE(t.name, CONCAT('#', s.id)) AS name, s.next_review_at, o.name AS organization_name
                   FROM services s JOIN organizations o ON o.id = s.organization_id
                   LEFT JOIN service_translations t ON t.service_id = s.id AND t.locale = s.source_locale
                  WHERE s.publication_status = 'published' AND s.next_review_at IS NOT NULL AND s.next_review_at <= CURRENT_DATE
                  ORDER BY s.next_review_at LIMIT 100"
            ),
            'never_verified' => $this->database->fetchAll(
                "SELECT s.id, COALESCE(t.name, CONCAT('#', s.id)) AS name, o.name AS organization_name
                   FROM services s JOIN organizations o ON o.id = s.organization_id
                   LEFT JOIN service_translations t ON t.service_id = s.id AND t.locale = s.source_locale
                  WHERE s.publication_status = 'published' AND s.verified_at IS NULL ORDER BY name LIMIT 100"
            ),
            'sites_without_coordinates' => $this->database->fetchAll(
                "SELECT si.id, si.address_line, t.name AS town, o.name AS organization_name
                   FROM sites si JOIN organizations o ON o.id = si.organization_id JOIN territories t ON t.id = si.territory_id
                  WHERE si.publication_status = 'published' AND si.is_public_place = 1 AND (si.lat IS NULL OR si.geo_checked = 0) LIMIT 100"
            ),
            'services_without_sites' => $this->database->fetchAll(
                "SELECT s.id, COALESCE(t.name, CONCAT('#', s.id)) AS name, o.name AS organization_name
                   FROM services s JOIN organizations o ON o.id = s.organization_id
                   LEFT JOIN service_translations t ON t.service_id = s.id AND t.locale = s.source_locale
                  WHERE s.publication_status = 'published' AND s.online_url IS NULL
                    AND NOT EXISTS (SELECT 1 FROM service_sites ss WHERE ss.service_id = s.id) LIMIT 100"
            ),
            'outdated_translations' => $this->database->fetchAll(
                "SELECT s.id, t.locale, COALESCE(src.name, CONCAT('#', s.id)) AS name
                   FROM service_translations t JOIN services s ON s.id = t.service_id
                   LEFT JOIN service_translations src ON src.service_id = s.id AND src.locale = s.source_locale
                  WHERE t.status = 'outdated' ORDER BY s.id LIMIT 100"
            ),
            'organizations_to_census' => $this->database->fetchAll(
                "SELECT id, name FROM organizations WHERE census_status = 'to_census' OR verification_status IN ('unverified', 'pending') ORDER BY name LIMIT 100"
            ),
            'missing_translations' => $missing,
        ];
    }

    // --- Interni ---------------------------------------------------------------------------

    /** @return array<string, array<string, mixed>> traduzioni per lingua */
    private function translations(string $table, string $key, int $id): array
    {
        $result = [];
        foreach ($this->database->fetchAll('SELECT * FROM ' . Database::identifier($table) . ' WHERE ' . Database::identifier($key) . ' = ?', [$id]) as $row) {
            $result[(string) $row['locale']] = $row;
        }

        return $result;
    }

    /**
     * @param list<int|string> $params
     * @return list<array{id: int, name: string}>
     */
    private function labelled(string $sql, array $params = []): array
    {
        return array_map(static fn (array $r): array => ['id' => (int) $r['id'], 'name' => (string) $r['name']], $this->database->fetchAll($sql, $params));
    }
}
