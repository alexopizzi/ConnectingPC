<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Core\Database;
use App\Domain\Content\ContentReader;
use App\Domain\Search\TextNormalizer;

/**
 * Lettura pubblica del catalogo (solo contenuti pubblicati, solo recapiti pubblici).
 * Testi: lingua richiesta se la traduzione è approvata, altrimenti lingua sorgente, con indicazione
 * del ripiego per marcare il blocco con lang/dir corretti (vault "60").
 *
 * @phpstan-type Localized array{text: string, lang: string, fallback: bool}
 */
final class CatalogRepository
{
    private const SERVICE_TEXT_FIELDS = [
        'name', 'summary', 'description', 'target_audience', 'requirements', 'documents',
        'access_info', 'booking_info', 'cost_info', 'notes',
    ];

    /** Condizione di visibilità pubblica di un servizio (alias s = services, o = organizations). */
    private const PUBLIC_SERVICE = "s.publication_status = 'published' AND s.archived_at IS NULL
        AND (s.valid_to IS NULL OR s.valid_to >= CURRENT_DATE)
        AND o.publication_status = 'published' AND o.listing_status = 'listed'";

    private readonly ContentReader $content;

    public function __construct(private readonly Database $database)
    {
        $this->content = new ContentReader($database);
    }

    // --- Tassonomie ------------------------------------------------------------------------

    /** @return list<array{id: int, code: string, icon: string, label: Localized}> */
    public function needs(string $locale, bool $featuredOnly = false): array
    {
        $rows = $this->database->fetchAll(
            'SELECT n.id, n.code, n.icon FROM needs n WHERE n.is_active = 1' . ($featuredOnly ? ' AND n.is_featured = 1' : '') . ' ORDER BY n.sort_order',
        );
        $labels = $this->taxonomyLabels('need_translations', 'need_id', 'label', array_column($rows, 'id'), $locale);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'], 'code' => (string) $r['code'], 'icon' => (string) $r['icon'], 'label' => $labels[(int) $r['id']],
        ], $rows);
    }

    /** @return array{id: int, code: string, icon: string, label: Localized}|null */
    public function need(string $code, string $locale): ?array
    {
        foreach ($this->needs($locale) as $need) {
            if ($need['code'] === $code) {
                return $need;
            }
        }

        return null;
    }

    /** @return list<array{id: int, code: string, parent_id: ?int, name: Localized}> */
    public function categories(string $locale): array
    {
        $rows = $this->database->fetchAll('SELECT id, code, parent_id FROM categories WHERE is_active = 1 ORDER BY sort_order');
        $names = $this->taxonomyLabels('category_translations', 'category_id', 'name', array_column($rows, 'id'), $locale);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'], 'code' => (string) $r['code'],
            'parent_id' => $r['parent_id'] === null ? null : (int) $r['parent_id'], 'name' => $names[(int) $r['id']],
        ], $rows);
    }

    /**
     * Distretti con i rispettivi comuni che hanno almeno una sede pubblicata.
     *
     * @return list<array{id: int, name: string, municipalities: list<array{id: int, name: string}>}>
     */
    public function municipalitiesWithServices(): array
    {
        $rows = $this->database->fetchAll(
            "SELECT DISTINCT t.id, t.name, d.id AS district_id, d.name AS district_name FROM territories t
               JOIN territories d ON d.id = t.parent_id AND d.type = 'district'
               JOIN sites si ON si.territory_id = t.id AND si.publication_status = 'published'
               JOIN service_sites ss ON ss.site_id = si.id
               JOIN services s ON s.id = ss.service_id
               JOIN organizations o ON o.id = s.organization_id
              WHERE " . self::PUBLIC_SERVICE . ' ORDER BY d.id, t.name'
        );
        $districts = [];
        foreach ($rows as $row) {
            $id = (int) $row['district_id'];
            $districts[$id] ??= ['id' => $id, 'name' => (string) $row['district_name'], 'municipalities' => []];
            $districts[$id]['municipalities'][] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return array_values($districts);
    }

    /** @return list<array{id: int, name: string, lat: ?float, lng: ?float}> comuni della provincia con centroide */
    public function municipalities(): array
    {
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'], 'name' => (string) $r['name'],
            'lat' => $r['centroid_lat'] === null ? null : (float) $r['centroid_lat'],
            'lng' => $r['centroid_lng'] === null ? null : (float) $r['centroid_lng'],
        ], $this->database->fetchAll("SELECT id, name, centroid_lat, centroid_lng FROM territories WHERE type = 'municipality' ORDER BY name"));
    }

    /** @return list<array{code: string, name: array{text: string, lang: string, fallback: bool}}> tipi di ente con servizi pubblicati */
    public function organizationTypesWithServices(string $locale): array
    {
        $rows = $this->database->fetchAll(
            'SELECT DISTINCT t.id, t.code, t.sort_order FROM organization_types t JOIN organizations o ON o.organization_type_id = t.id
               JOIN services s ON s.organization_id = o.id WHERE ' . self::PUBLIC_SERVICE . ' ORDER BY t.sort_order'
        );
        $names = $this->taxonomyLabels('organization_type_translations', 'organization_type_id', 'name', array_column($rows, 'id'), $locale);

        return array_map(static fn (array $r): array => ['code' => (string) $r['code'], 'name' => $names[(int) $r['id']]], $rows);
    }

    /** @return list<string> codici delle lingue parlate nei servizi pubblicati */
    public function spokenLanguages(): array
    {
        return array_map('strval', $this->database->fetchColumn(
            'SELECT DISTINCT l.language_code FROM service_languages l
               JOIN services s ON s.id = l.service_id JOIN organizations o ON o.id = s.organization_id
              WHERE ' . self::PUBLIC_SERVICE . ' ORDER BY l.language_code'
        ));
    }

    // --- Servizi ---------------------------------------------------------------------------

    /**
     * Ricerca con filtri. Restituisce gli id ordinati per pertinenza o per nome.
     *
     * @param array{need?: ?string, category?: ?string, territory?: ?int, language?: ?string, mediation?: bool, free?: bool, accessible?: bool, org_type?: ?string, access_mode?: ?string, q?: string} $filters
     * @return list<int>
     */
    public function searchServiceIds(array $filters, string $locale): array
    {
        $where = [self::PUBLIC_SERVICE];
        $params = [];

        if (!empty($filters['need'])) {
            $where[] = '(s.id IN (SELECT sn.service_id FROM service_needs sn JOIN needs n ON n.id = sn.need_id WHERE n.code = ?)
                      OR s.id IN (SELECT sc.service_id FROM service_categories sc
                                    JOIN need_category nc ON nc.category_id = sc.category_id
                                    JOIN needs n2 ON n2.id = nc.need_id WHERE n2.code = ?))';
            $params[] = $filters['need'];
            $params[] = $filters['need'];
        }
        if (!empty($filters['category'])) {
            // Categoria principale o sua sottocategoria
            $where[] = 's.id IN (SELECT sc.service_id FROM service_categories sc JOIN categories c ON c.id = sc.category_id
                                   LEFT JOIN categories p ON p.id = c.parent_id WHERE c.code = ? OR p.code = ?)';
            $params[] = $filters['category'];
            $params[] = $filters['category'];
        }
        if (!empty($filters['territory'])) {
            // Comune oppure distretto (i comuni del distretto sono figli nella gerarchia dei territori)
            $where[] = 's.id IN (SELECT ss.service_id FROM service_sites ss JOIN sites si ON si.id = ss.site_id
                                   JOIN territories t ON t.id = si.territory_id WHERE t.id = ? OR t.parent_id = ?)';
            $params[] = (int) $filters['territory'];
            $params[] = (int) $filters['territory'];
        }
        if (!empty($filters['language'])) {
            $where[] = '(s.id IN (SELECT service_id FROM service_languages WHERE language_code = ?)
                         OR s.organization_id IN (SELECT organization_id FROM organization_languages WHERE language_code = ?))';
            $params[] = $filters['language'];
            $params[] = $filters['language'];
        }
        if (!empty($filters['mediation'])) {
            $where[] = "s.mediation IN ('available', 'on_request')";
        }
        if (!empty($filters['free'])) {
            $where[] = "s.cost_type = 'free'";
        }
        if (!empty($filters['accessible'])) {
            // Almeno una sede pubblicata e aperta al pubblico con accesso senza gradini
            $where[] = "s.id IN (SELECT ss.service_id FROM service_sites ss JOIN sites si ON si.id = ss.site_id
                                  WHERE si.publication_status = 'published' AND si.is_public_place = 1 AND si.step_free_access = 'yes')";
        }
        if (!empty($filters['org_type'])) {
            $where[] = 'o.organization_type_id IN (SELECT id FROM organization_types WHERE code = ?)';
            $params[] = $filters['org_type'];
        }
        if (!empty($filters['access_mode'])) {
            $where[] = 'FIND_IN_SET(?, s.access_modes) > 0';
            $params[] = $filters['access_mode'];
        }

        $rows = $this->database->fetchAll(
            'SELECT s.id FROM services s JOIN organizations o ON o.id = s.organization_id WHERE ' . implode(' AND ', $where),
            $params,
        );
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);

        $query = trim((string) ($filters['q'] ?? ''));

        return $query === '' ? $this->sortByName($ids, $locale) : $this->rankByText($ids, $query, $locale);
    }

    /**
     * Schede sintetiche per gli elenchi.
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function serviceSummaries(array $ids, string $locale): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->database->fetchAll(
            "SELECT s.id, s.source_locale, s.cost_type, s.mediation, s.access_modes, s.organization_id,
                    o.name AS organization_name, s.primary_category_id
               FROM services s JOIN organizations o ON o.id = s.organization_id WHERE s.id IN ($in)",
            $ids,
        );
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }
        $texts = $this->serviceTexts(array_keys($byId), $locale, ['name', 'summary'], array_column($rows, 'source_locale', 'id'));
        $towns = $this->groupedColumn(
            "SELECT DISTINCT ss.service_id AS k, t.name AS v FROM service_sites ss
               JOIN sites si ON si.id = ss.site_id JOIN territories t ON t.id = si.territory_id WHERE ss.service_id IN ($in) ORDER BY t.name",
            $ids,
        );
        $languages = $this->groupedColumn("SELECT DISTINCT service_id AS k, language_code AS v FROM service_languages WHERE service_id IN ($in)", $ids);
        // Coordinate della sede principale (per l'ordinamento per distanza nel browser, D-006)
        $coordinates = [];
        foreach ($this->database->fetchAll(
            "SELECT ss.service_id, si.lat, si.lng FROM service_sites ss JOIN sites si ON si.id = ss.site_id
              WHERE ss.service_id IN ($in) AND si.is_public_place = 1 AND si.lat IS NOT NULL ORDER BY ss.is_main DESC",
            $ids,
        ) as $row) {
            $coordinates[(int) $row['service_id']] ??= [(float) $row['lat'], (float) $row['lng']];
        }
        $categories = $this->categories($locale);
        $categoryNames = array_column($categories, 'name', 'id');

        $result = [];
        foreach ($ids as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $row = $byId[$id];
            $result[] = [
                'id' => $id,
                'name' => $texts[$id]['name'],
                'summary' => $texts[$id]['summary'],
                'organization_id' => (int) $row['organization_id'],
                'organization_name' => (string) $row['organization_name'],
                'category' => $categoryNames[(int) $row['primary_category_id']] ?? null,
                'cost_type' => (string) $row['cost_type'],
                'mediation' => (string) $row['mediation'],
                'towns' => $towns[$id] ?? [],
                'languages' => $languages[$id] ?? [],
                'lat' => $coordinates[$id][0] ?? null,
                'lng' => $coordinates[$id][1] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Punti della mappa: sedi pubbliche con coordinate dei servizi selezionati, ciascuna con i propri servizi.
     *
     * @param list<int> $serviceIds
     * @return list<array<string, mixed>>
     */
    public function mapPoints(array $serviceIds, string $locale): array
    {
        if ($serviceIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($serviceIds), '?'));
        $rows = $this->database->fetchAll(
            "SELECT ss.service_id, si.id AS site_id, si.name AS site_name, si.address_line, si.lat, si.lng, si.step_free_access,
                    t.name AS town, o.id AS organization_id, o.name AS organization_name, s.mediation, s.primary_category_id,
                    COALESCE(p.icon, c.icon) AS icon
               FROM service_sites ss
               JOIN sites si ON si.id = ss.site_id AND si.publication_status = 'published' AND si.is_public_place = 1
                              AND si.lat IS NOT NULL AND si.lng IS NOT NULL
               JOIN territories t ON t.id = si.territory_id
               JOIN services s ON s.id = ss.service_id
               JOIN organizations o ON o.id = s.organization_id
               JOIN categories c ON c.id = s.primary_category_id
               LEFT JOIN categories p ON p.id = c.parent_id
              WHERE ss.service_id IN ($in)",
            $serviceIds,
        );
        $names = $this->serviceTexts(array_values(array_unique(array_map(static fn (array $r): int => (int) $r['service_id'], $rows))), $locale, ['name'], []);

        $points = [];
        foreach ($rows as $row) {
            $siteId = (int) $row['site_id'];
            $points[$siteId] ??= [
                'id' => $siteId,
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lng'],
                'name' => (string) ($row['site_name'] ?? $row['organization_name']),
                'organization' => (string) $row['organization_name'],
                'organization_id' => (int) $row['organization_id'],
                'address' => trim($row['address_line'] . ', ' . $row['town'], ', '),
                'icon' => (string) ($row['icon'] ?? 'default'),
                'mediation' => false,
                'step_free' => $row['step_free_access'] === 'yes',
                'services' => [],
            ];
            $points[$siteId]['mediation'] = $points[$siteId]['mediation'] || in_array($row['mediation'], ['available', 'on_request'], true);
            $serviceId = (int) $row['service_id'];
            $points[$siteId]['services'][] = ['id' => $serviceId, 'name' => $names[$serviceId]['name']['text'] ?? '#' . $serviceId];
        }

        return array_values($points);
    }

    /**
     * Suggerimenti durante la digitazione: bisogni e servizi (massimo $limit).
     *
     * @return list<array{type: string, label: string, code?: string, id?: int}>
     */
    public function suggestions(string $query, string $locale, int $limit = 8): array
    {
        $normalized = TextNormalizer::normalize($query);
        if (mb_strlen($normalized) < 2) {
            return [];
        }
        $suggestions = [];
        foreach ($this->needs($locale) as $need) {
            if (str_contains(TextNormalizer::normalize($need['label']['text']), $normalized)) {
                $suggestions[] = ['type' => 'need', 'label' => $need['label']['text'], 'code' => $need['code']];
            }
        }
        $needCodes = $this->database->fetchColumn(
            "SELECT DISTINCT n.code FROM search_terms st JOIN needs n ON n.id = st.target_id
              WHERE st.target_type = 'need' AND st.locale IN (?, 'it') AND st.term LIKE ?",
            [$locale, addcslashes($normalized, '%_\\') . '%'],
        );
        foreach ($this->needs($locale) as $need) {
            if (in_array($need['code'], $needCodes, true) && !in_array($need['code'], array_column($suggestions, 'code'), true)) {
                $suggestions[] = ['type' => 'need', 'label' => $need['label']['text'], 'code' => $need['code']];
            }
        }
        foreach ($this->serviceSummaries(array_slice($this->searchServiceIds(['q' => $query], $locale), 0, $limit), $locale) as $service) {
            $suggestions[] = ['type' => 'service', 'label' => $service['name']['text'], 'id' => $service['id']];
        }

        return array_slice($suggestions, 0, $limit);
    }

    /** @return array<string, mixed>|null scheda completa di un servizio pubblicato */
    public function service(int $id, string $locale): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT s.*, o.name AS organization_name, o.id AS organization_id
               FROM services s JOIN organizations o ON o.id = s.organization_id
              WHERE s.id = ? AND ' . self::PUBLIC_SERVICE,
            [$id],
        );
        if ($row === null) {
            return null;
        }
        $texts = $this->serviceTexts([$id], $locale, self::SERVICE_TEXT_FIELDS, [$id => $row['source_locale']])[$id];
        $categories = $this->categories($locale);
        $categoryIds = array_map('intval', $this->database->fetchColumn('SELECT category_id FROM service_categories WHERE service_id = ?', [$id]));
        $needIds = array_map('intval', $this->database->fetchColumn('SELECT need_id FROM service_needs WHERE service_id = ? ORDER BY relevance DESC', [$id]));
        $needs = array_values(array_filter($this->needs($locale), static fn (array $n): bool => in_array($n['id'], $needIds, true)));

        return [
            ...$row,
            'id' => (int) $row['id'],
            'texts' => $texts,
            'categories' => array_values(array_filter($categories, static fn (array $c): bool => in_array($c['id'], $categoryIds, true))),
            'needs' => $needs,
            'access_modes' => array_filter(explode(',', (string) $row['access_modes'])),
            'languages' => $this->database->fetchAll('SELECT language_code, mode FROM service_languages WHERE service_id = ? ORDER BY language_code', [$id]),
            'sites' => $this->sitesOfService($id, $locale),
            'contacts' => $this->publicContacts('service', [$id]),
            'territories' => array_map('strval', $this->database->fetchColumn(
                'SELECT t.name FROM service_territories st JOIN territories t ON t.id = st.territory_id WHERE st.service_id = ? ORDER BY t.name',
                [$id],
            )),
        ];
    }

    // --- Organizzazioni --------------------------------------------------------------------

    /** @return array<string, mixed>|null */
    public function organization(int $id, string $locale): ?array
    {
        $row = $this->database->fetchOne(
            "SELECT o.id, o.name, o.website, o.is_community_based, o.source_locale, o.organization_type_id, o.verified_at
               FROM organizations o WHERE o.id = ? AND o.publication_status = 'published' AND o.listing_status = 'listed'",
            [$id],
        );
        if ($row === null) {
            return null;
        }
        $texts = $this->translatedFields('organization_translations', 'organization_id', [$id], $locale, ['description', 'activities', 'participation_info'], [$id => $row['source_locale']], true)[$id];
        $type = $this->taxonomyLabels('organization_type_translations', 'organization_type_id', 'name', [(int) $row['organization_type_id']], $locale);
        $sites = $this->database->fetchAll(
            "SELECT si.id FROM sites si WHERE si.organization_id = ? AND si.publication_status = 'published' ORDER BY si.id",
            [$id],
        );

        return [
            ...$row,
            'id' => (int) $row['id'],
            'texts' => $texts,
            'type' => $type[(int) $row['organization_type_id']],
            'languages' => array_map('strval', $this->database->fetchColumn('SELECT language_code FROM organization_languages WHERE organization_id = ? ORDER BY language_code', [$id])),
            'sites' => $this->sitesByIds(array_map(static fn (array $s): int => (int) $s['id'], $sites), $locale),
            'contacts' => $this->publicContacts('organization', [$id]),
            'service_ids' => $this->sortByName(array_map('intval', $this->database->fetchColumn(
                'SELECT s.id FROM services s JOIN organizations o ON o.id = s.organization_id WHERE s.organization_id = ? AND ' . self::PUBLIC_SERVICE,
                [$id],
            )), $locale),
        ];
    }

    // --- Interni ---------------------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    private function sitesOfService(int $serviceId, string $locale): array
    {
        $ids = array_map('intval', $this->database->fetchColumn(
            "SELECT ss.site_id FROM service_sites ss JOIN sites si ON si.id = ss.site_id
              WHERE ss.service_id = ? AND si.publication_status = 'published' ORDER BY ss.is_main DESC, si.id",
            [$serviceId],
        ));

        return $this->sitesByIds($ids, $locale);
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    private function sitesByIds(array $ids, string $locale): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->database->fetchAll(
            "SELECT si.id, si.name, si.address_line, si.postal_code, si.lat, si.lng, si.is_public_place, si.step_free_access,
                    si.accessible_toilet, t.name AS town
               FROM sites si JOIN territories t ON t.id = si.territory_id WHERE si.id IN ($in)",
            $ids,
        );
        $texts = $this->translatedFields('site_translations', 'site_id', $ids, $locale, ['directions', 'accessibility_notes', 'hours_notes'], array_fill_keys($ids, 'it'), true);
        $hours = [];
        foreach ($this->database->fetchAll(
            "SELECT owner_id, weekday, opens_at, closes_at, by_appointment FROM opening_hours
              WHERE owner_type = 'site' AND owner_id IN ($in) AND (valid_from IS NULL OR valid_from <= CURRENT_DATE)
                AND (valid_to IS NULL OR valid_to >= CURRENT_DATE)
              ORDER BY weekday, opens_at",
            $ids,
        ) as $h) {
            $hours[(int) $h['owner_id']][] = [
                'weekday' => (int) $h['weekday'],
                'opens' => substr((string) $h['opens_at'], 0, 5),
                'closes' => substr((string) $h['closes_at'], 0, 5),
                'appointment' => (bool) $h['by_appointment'],
            ];
        }
        $contacts = $this->publicContacts('site', $ids);

        $byId = [];
        foreach ($rows as $row) {
            $siteId = (int) $row['id'];
            $public = (bool) $row['is_public_place'];
            $byId[$siteId] = [
                ...$row,
                'id' => $siteId,
                // Sedi non pubblicamente accessibili: nessun indirizzo né coordinate (vault "61", "71").
                'address_line' => $public ? $row['address_line'] : null,
                'lat' => $public && $row['lat'] !== null ? (float) $row['lat'] : null,
                'lng' => $public && $row['lng'] !== null ? (float) $row['lng'] : null,
                'texts' => $texts[$siteId],
                'hours' => $hours[$siteId] ?? [],
                'contacts' => array_values(array_filter($contacts, static fn (array $c): bool => $c['owner_id'] === $siteId)),
            ];
        }

        return array_values(array_filter(array_map(static fn (int $id): ?array => $byId[$id] ?? null, $ids)));
    }

    /**
     * Solo recapiti con visibilità pubblica (D-013).
     *
     * @param list<int> $ownerIds
     * @return list<array{owner_id: int, kind: string, value: string, label_key: ?string}>
     */
    private function publicContacts(string $ownerType, array $ownerIds): array
    {
        return $this->content->contacts($ownerType, $ownerIds);
    }

    /**
     * @param list<int> $ids
     * @param list<string> $fields
     * @param array<int|string, string> $sourceLocales
     * @return array<int, array<string, ?array{text: string, lang: string, fallback: bool}>>
     */
    private function serviceTexts(array $ids, string $locale, array $fields, array $sourceLocales): array
    {
        return $this->translatedFields('service_translations', 'service_id', $ids, $locale, $fields, $sourceLocales, false);
    }

    /**
     * @param list<int|string> $ids
     * @param list<string> $fields
     * @param array<int|string, string> $sourceLocales
     * @return array<int, array<string, ?array{text: string, lang: string, fallback: bool}>>
     */
    private function translatedFields(string $table, string $key, array $ids, string $locale, array $fields, array $sourceLocales, bool $allowMissing): array
    {
        return $this->content->translatedFields($table, $key, $ids, $locale, $fields, $sourceLocales, $allowMissing);
    }

    /**
     * @param list<int|string> $ids
     * @return array<int, array{text: string, lang: string, fallback: bool}>
     */
    private function taxonomyLabels(string $table, string $key, string $field, array $ids, string $locale): array
    {
        return $this->content->taxonomyLabels($table, $key, $field, $ids, $locale);
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function sortByName(array $ids, string $locale): array
    {
        if ($ids === []) {
            return [];
        }
        $names = $this->serviceTexts($ids, $locale, ['name'], []);
        $collator = new \Collator($locale);
        usort($ids, static fn (int $a, int $b): int => $collator->compare($names[$a]['name']['text'] ?? '', $names[$b]['name']['text'] ?? ''));

        return $ids;
    }

    /**
     * Ricerca testuale di base (v0.4.0): dizionario dei sinonimi → bisogni, più parole nei testi del servizio.
     * La ricerca FULLTEXT completa arriverà con v0.6.0 (vault "62").
     *
     * @param list<int> $ids
     * @return list<int>
     */
    private function rankByText(array $ids, string $query, string $locale): array
    {
        if ($ids === []) {
            return [];
        }
        $normalized = TextNormalizer::normalize($query);
        $tokens = TextNormalizer::tokens($query);
        if ($normalized === '' || $tokens === []) {
            return [];
        }

        // 1. Dizionario: termini contenuti nella frase (anche di più parole) → bisogni.
        $needWeights = [];
        foreach ($this->database->fetchAll(
            "SELECT target_id, MAX(weight) AS weight FROM search_terms
              WHERE target_type = 'need' AND locale IN (?, 'it', 'en')
                AND (CONCAT(' ', ?, ' ') LIKE CONCAT('% ', term, ' %'))
              GROUP BY target_id",
            [$locale, $normalized],
        ) as $row) {
            $needWeights[(int) $row['target_id']] = (int) $row['weight'];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $serviceNeeds = [];
        if ($needWeights !== []) {
            foreach ($this->database->fetchAll("SELECT service_id, need_id, relevance FROM service_needs WHERE service_id IN ($in)", $ids) as $row) {
                if (isset($needWeights[(int) $row['need_id']])) {
                    $score = $needWeights[(int) $row['need_id']] * (int) $row['relevance'];
                    $serviceNeeds[(int) $row['service_id']] = max($serviceNeeds[(int) $row['service_id']] ?? 0, $score);
                }
            }
        }

        // 2. Parole nei testi (lingua corrente e sorgente), confronto su testo normalizzato.
        $scores = [];
        foreach ($this->database->fetchAll(
            "SELECT service_id, locale, name, summary, keywords, description FROM service_translations
              WHERE service_id IN ($in) AND (locale = ? OR locale = 'it')",
            [...$ids, $locale],
        ) as $row) {
            $id = (int) $row['service_id'];
            $name = ' ' . TextNormalizer::normalize((string) $row['name']) . ' ';
            $body = ' ' . TextNormalizer::normalize($row['summary'] . ' ' . $row['keywords'] . ' ' . $row['description']) . ' ';
            $score = 0;
            foreach ($tokens as $token) {
                $prefix = mb_strlen($token) >= 5 ? mb_substr($token, 0, -1) : $token; // "rinnovare" ≈ "rinnovo"
                if (str_contains($name, ' ' . $token) || str_contains($name, ' ' . $prefix)) {
                    $score += 30;
                } elseif (str_contains($body, ' ' . $token) || str_contains($body, ' ' . $prefix)) {
                    $score += 10;
                }
            }
            $scores[$id] = max($scores[$id] ?? 0, $score);
        }

        $ranked = [];
        foreach ($ids as $id) {
            $total = ($scores[$id] ?? 0) + ($serviceNeeds[$id] ?? 0);
            if ($total > 0) {
                $ranked[$id] = $total;
            }
        }
        arsort($ranked);

        return array_keys($ranked);
    }

    /**
     * @param list<int> $params
     * @return array<int, list<string>>
     */
    private function groupedColumn(string $sql, array $params): array
    {
        $grouped = [];
        foreach ($this->database->fetchAll($sql, $params) as $row) {
            $grouped[(int) $row['k']][] = (string) $row['v'];
        }

        return $grouped;
    }
}
