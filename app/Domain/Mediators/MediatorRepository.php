<?php

declare(strict_types=1);

namespace App\Domain\Mediators;

use App\Core\Database;
use App\Domain\Content\ContentReader;
use App\Http\Request;

/**
 * Lettura dei mediatori per il pubblico e per gli operatori (vault "63", D-013, D-033).
 *
 * Regole di visibilità, applicate qui e non nei template:
 * - pubblico, profilo individuale: `profile_visibility = public` E consenso registrato (`public_consent_at`);
 * - pubblico, forma aggregata: mediatori "solo operatori" (o "pubblici" senza consenso) collegati a
 *   un'organizzazione pubblicata → solo conteggi per lingua e ambito, con i recapiti dell'organizzazione;
 * - operatori autenticati: profili `public` e `operators`, con nome completo e recapiti `public`/`operators`;
 * - `admin`: mai fuori dall'area amministrativa. Qualifiche, note e stato di verifica non escono mai da qui.
 *
 * @phpstan-type Filters array{language: ?string, type: ?string, domain: ?string, territory: ?int, available: bool}
 */
final class MediatorRepository
{
    public const TYPES = ['linguistic', 'cultural', 'intercultural'];

    private const PUBLISHED = "m.publication_status = 'published' AND m.archived_at IS NULL";
    private const PUBLIC_PROFILE = "m.profile_visibility = 'public' AND m.public_consent_at IS NOT NULL";

    private readonly ContentReader $content;

    public function __construct(private readonly Database $database)
    {
        $this->content = new ContentReader($database);
    }

    /** @return Filters */
    public static function filtersFromRequest(Request $request): array
    {
        $language = $request->string('lingua');
        $type = $request->string('tipo');
        $domain = $request->string('ambito');
        $territory = $request->int('comune');

        return [
            'language' => preg_match('/^[a-z]{2,3}$/', $language) ? $language : null,
            'type' => in_array($type, self::TYPES, true) ? $type : null,
            'domain' => preg_match('/^[a-z_]{2,50}$/', $domain) ? $domain : null,
            'territory' => $territory > 0 ? $territory : null,
            'available' => $request->string('disponibili') === '1',
        ];
    }

    /**
     * @param Filters $filters
     * @return array<string, string>
     */
    public static function filtersToQuery(array $filters): array
    {
        return array_filter([
            'lingua' => (string) $filters['language'],
            'tipo' => (string) $filters['type'],
            'ambito' => (string) $filters['domain'],
            'comune' => $filters['territory'] === null ? '' : (string) $filters['territory'],
            'disponibili' => $filters['available'] ? '1' : '',
        ], static fn (string $v): bool => $v !== '');
    }

    /** @return list<array{code: string, name: array{text: string, lang: string, fallback: bool}}> */
    public function domains(string $locale): array
    {
        $rows = $this->database->fetchAll('SELECT id, code FROM mediation_domains ORDER BY sort_order');
        $names = $this->content->taxonomyLabels('mediation_domain_translations', 'mediation_domain_id', 'name', array_column($rows, 'id'), $locale);

        return array_map(static fn (array $r): array => ['code' => (string) $r['code'], 'name' => $names[(int) $r['id']]], $rows);
    }

    /** @return list<array{id: int, name: string, municipalities: list<array{id: int, name: string}>}> */
    public function municipalities(): array
    {
        return $this->content->districtTree();
    }

    /** @return list<string> lingue dei mediatori visibili (in qualunque forma) al pubblico */
    public function languages(): array
    {
        return array_map('strval', $this->database->fetchColumn(
            "SELECT DISTINCT ml.language_code FROM mediator_languages ml JOIN mediators m ON m.id = ml.mediator_id
              WHERE " . self::PUBLISHED . " AND m.profile_visibility IN ('public', 'operators') ORDER BY ml.language_code"
        ));
    }

    /**
     * Profili individuali pubblici (solo con consenso).
     *
     * @param Filters $filters
     * @return list<array<string, mixed>>
     */
    public function publicProfiles(array $filters, string $locale): array
    {
        return $this->profiles(self::PUBLIC_PROFILE, $filters, $locale, ['public'], false);
    }

    /**
     * Profili per gli operatori autenticati: pubblici e "solo operatori".
     *
     * @param Filters $filters
     * @return list<array<string, mixed>>
     */
    public function operatorProfiles(array $filters, string $locale): array
    {
        return $this->profiles("m.profile_visibility IN ('public', 'operators')", $filters, $locale, ['public', 'operators'], true);
    }

    /**
     * Presenza anonima aggregata per organizzazione: nessun dato personale, solo conteggi.
     *
     * @param Filters $filters
     * @return list<array{organization_id: int, organization_name: string, count: int, languages: array<string, int>, domains: list<array{text: string, lang: string, fallback: bool}>, contacts: list<array<string, mixed>>}>
     */
    public function aggregatedByOrganization(array $filters, string $locale): array
    {
        [$where, $params] = $this->filterConditions($filters);
        $rows = $this->database->fetchAll(
            "SELECT m.id, m.organization_id, o.name FROM mediators m JOIN organizations o ON o.id = m.organization_id
              WHERE " . self::PUBLISHED . " AND m.profile_visibility IN ('public', 'operators') AND NOT (" . self::PUBLIC_PROFILE . ")
                AND o.publication_status = 'published' AND o.listing_status = 'listed'" . ($where === [] ? '' : ' AND ' . implode(' AND ', $where)),
            $params,
        );
        if ($rows === []) {
            return [];
        }
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $languages = $this->grouped('SELECT mediator_id AS k, language_code AS v FROM mediator_languages WHERE mediator_id IN (%s)', $ids);
        $domainIds = $this->grouped('SELECT mediator_id AS k, mediation_domain_id AS v FROM mediator_domains WHERE mediator_id IN (%s)', $ids);
        $domainNames = $this->content->taxonomyLabels('mediation_domain_translations', 'mediation_domain_id', 'name', array_values(array_unique(array_merge([], ...array_values($domainIds)))), $locale);

        $groups = [];
        foreach ($rows as $row) {
            $orgId = (int) $row['organization_id'];
            $groups[$orgId] ??= ['organization_id' => $orgId, 'organization_name' => (string) $row['name'], 'count' => 0, 'languages' => [], 'domain_ids' => []];
            $groups[$orgId]['count']++;
            foreach ($languages[(int) $row['id']] ?? [] as $code) {
                if ($code !== 'it') {
                    $groups[$orgId]['languages'][$code] = ($groups[$orgId]['languages'][$code] ?? 0) + 1;
                }
            }
            $groups[$orgId]['domain_ids'] = array_unique([...$groups[$orgId]['domain_ids'], ...($domainIds[(int) $row['id']] ?? [])]);
        }
        $contacts = $this->content->contacts('organization', array_keys($groups));

        $collator = new \Collator($locale);
        uasort($groups, static fn (array $a, array $b): int => $collator->compare($a['organization_name'], $b['organization_name']));

        return array_values(array_map(static function (array $g) use ($domainNames, $contacts): array {
            arsort($g['languages']);
            $domainIds = array_map('intval', $g['domain_ids']);
            unset($g['domain_ids']);

            return [
                ...$g,
                'domains' => array_values(array_map(static fn (int $id): array => $domainNames[$id], $domainIds)),
                'contacts' => array_values(array_filter($contacts, static fn (array $c): bool => $c['owner_id'] === $g['organization_id'])),
            ];
        }, $groups));
    }

    // --- Interni ---------------------------------------------------------------------------

    /**
     * @param Filters $filters
     * @param list<'public'|'operators'> $contactVisibilities
     * @return list<array<string, mixed>>
     */
    private function profiles(string $visibility, array $filters, string $locale, array $contactVisibilities, bool $fullName): array
    {
        [$where, $params] = $this->filterConditions($filters);
        $rows = $this->database->fetchAll(
            'SELECT m.id, m.first_name, m.last_name, m.public_display_name, m.mediation_types, m.availability, m.organization_id,
                    o.name AS organization_name
               FROM mediators m
               LEFT JOIN organizations o ON o.id = m.organization_id AND o.publication_status = \'published\' AND o.listing_status = \'listed\'
              WHERE ' . self::PUBLISHED . ' AND ' . $visibility . ($where === [] ? '' : ' AND ' . implode(' AND ', $where)),
            $params,
        );
        if ($rows === []) {
            return [];
        }
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $in = implode(',', array_fill(0, count($ids), '?'));

        $languages = [];
        foreach ($this->database->fetchAll(
            "SELECT mediator_id, language_code, proficiency FROM mediator_languages WHERE mediator_id IN ($in)
              ORDER BY FIELD(proficiency, 'native', 'c2', 'c1', 'b2', 'b1'), language_code",
            $ids,
        ) as $row) {
            $languages[(int) $row['mediator_id']][] = ['code' => (string) $row['language_code'], 'level' => (string) $row['proficiency']];
        }
        $domainIds = $this->grouped('SELECT md.mediator_id AS k, md.mediation_domain_id AS v FROM mediator_domains md JOIN mediation_domains d ON d.id = md.mediation_domain_id WHERE md.mediator_id IN (%s) ORDER BY d.sort_order', $ids);
        $domainNames = $this->content->taxonomyLabels('mediation_domain_translations', 'mediation_domain_id', 'name', array_values(array_unique(array_merge([], ...array_values($domainIds)))), $locale);
        $territories = $this->grouped('SELECT mt.mediator_id AS k, t.name AS v FROM mediator_territories mt JOIN territories t ON t.id = mt.territory_id WHERE mt.mediator_id IN (%s) ORDER BY t.name', $ids);
        $texts = $this->content->translatedFields('mediator_translations', 'mediator_id', $ids, $locale, ['bio', 'competences', 'availability_notes'], [], true);
        $contacts = $this->content->contacts('mediator', $ids, $contactVisibilities);

        $profiles = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $display = trim((string) $row['public_display_name']);
            if ($display === '') {
                $display = $row['first_name'] . ' ' . mb_substr((string) $row['last_name'], 0, 1) . '.';
            }
            $profiles[] = [
                'id' => $id,
                'display_name' => $display,
                'full_name' => $fullName ? $row['first_name'] . ' ' . $row['last_name'] : null,
                'types' => array_values(array_filter(explode(',', (string) $row['mediation_types']))),
                'availability' => (string) $row['availability'],
                'organization' => $row['organization_name'] === null ? null : ['id' => (int) $row['organization_id'], 'name' => (string) $row['organization_name']],
                'languages' => $languages[$id] ?? [],
                'domains' => array_map(static fn (string $d): array => $domainNames[(int) $d], $domainIds[$id] ?? []),
                'territories' => $territories[$id] ?? [],
                'texts' => $texts[$id],
                'contacts' => array_values(array_filter($contacts, static fn (array $c): bool => $c['owner_id'] === $id)),
            ];
        }

        // Prima i disponibili, poi per nome
        $rank = ['available' => 0, 'limited' => 1, 'unknown' => 2, 'unavailable' => 3];
        $collator = new \Collator($locale);
        usort($profiles, static fn (array $a, array $b): int => ($rank[$a['availability']] <=> $rank[$b['availability']])
            ?: $collator->compare($a['display_name'], $b['display_name']));

        return $profiles;
    }

    /**
     * @param Filters $filters
     * @return array{0: list<string>, 1: list<int|string>}
     */
    private function filterConditions(array $filters): array
    {
        $where = [];
        $params = [];
        if ($filters['language'] !== null) {
            $where[] = 'm.id IN (SELECT mediator_id FROM mediator_languages WHERE language_code = ?)';
            $params[] = $filters['language'];
        }
        if ($filters['type'] !== null) {
            $where[] = 'FIND_IN_SET(?, m.mediation_types) > 0';
            $params[] = $filters['type'];
        }
        if ($filters['domain'] !== null) {
            $where[] = 'm.id IN (SELECT md.mediator_id FROM mediator_domains md JOIN mediation_domains d ON d.id = md.mediation_domain_id WHERE d.code = ?)';
            $params[] = $filters['domain'];
        }
        if ($filters['territory'] !== null) {
            // Chi opera in tutta la provincia o nel distretto copre anche il comune scelto, e viceversa.
            $family = $this->content->territoryFamily($filters['territory']);
            $where[] = 'm.id IN (SELECT mediator_id FROM mediator_territories WHERE territory_id IN (' . implode(',', array_fill(0, count($family), '?')) . '))';
            $params = [...$params, ...$family];
        }
        if ($filters['available']) {
            $where[] = "m.availability IN ('available', 'limited')";
        }

        return [$where, $params];
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<string>>
     */
    private function grouped(string $sql, array $ids): array
    {
        $grouped = [];
        foreach ($this->database->fetchAll(sprintf($sql, implode(',', array_fill(0, count($ids), '?'))), $ids) as $row) {
            $grouped[(int) $row['k']][] = (string) $row['v'];
        }

        return $grouped;
    }
}
