<?php

declare(strict_types=1);

namespace App\Domain\Communities;

use App\Core\Database;
use App\Domain\Content\ContentReader;
use App\Domain\Search\TextNormalizer;
use App\Http\Request;

/**
 * Directory pubblica "Associazioni e comunità" (vault "64"): solo organizzazioni pubblicate ed elencate
 * che sono associazioni o realtà di comunità. Recapiti solo pubblici.
 *
 * @phpstan-type Filters array{q: string, language: ?string, community: ?string, country: ?string, territory: ?int, type: ?string, community_based: bool}
 */
final class CommunityRepository
{
    /** Tipi di organizzazione che compaiono nella directory anche senza comunità collegate. */
    public const ASSOCIATION_TYPES = [
        'association', 'cultural_association', 'volunteer_association', 'community_association',
        'cultural_centre', 'religious_organization', 'informal_network',
    ];

    /** Parole generiche (già normalizzate) ignorate nella ricerca se accompagnate da altre. */
    private const GENERIC_WORDS = [
        'associazione', 'associazioni', 'comunita', 'gruppo', 'gruppi', 'centro', 'realta', 'che', 'parlano', 'parla',
        'association', 'associations', 'community', 'communities', 'group', 'groups', 'speak', 'speaking', 'that',
        'communaute', 'communautes', 'groupe', 'groupes', 'qui', 'parlent', 'parle',
        'جمعيه', 'جمعيات', 'جاليه', 'الجاليه', 'جاليات', 'مجموعه', 'تتحدث', 'يتحدث',
    ];

    private const PUBLIC_ORGANIZATION = "o.publication_status = 'published' AND o.listing_status = 'listed'";

    private readonly ContentReader $content;

    public function __construct(private readonly Database $database)
    {
        $this->content = new ContentReader($database);
    }

    /** @return Filters */
    public static function filtersFromRequest(Request $request): array
    {
        $language = $request->string('lingua');
        $community = $request->string('comunita');
        $country = strtoupper($request->string('paese'));
        $type = $request->string('tipo');
        $territory = $request->int('comune');

        return [
            'q' => mb_substr($request->string('q'), 0, 200),
            'language' => preg_match('/^[a-z]{2,3}$/', $language) ? $language : null,
            'community' => preg_match('/^[a-z_]{2,60}$/', $community) ? $community : null,
            'country' => preg_match('/^[A-Z]{2}$/', $country) ? $country : null,
            'territory' => $territory > 0 ? $territory : null,
            'type' => preg_match('/^[a-z_]{2,50}$/', $type) ? $type : null,
            'community_based' => $request->string('stranieri') === '1',
        ];
    }

    /**
     * @param Filters $filters
     * @return array<string, string>
     */
    public static function filtersToQuery(array $filters): array
    {
        return array_filter([
            'q' => $filters['q'],
            'lingua' => (string) $filters['language'],
            'comunita' => (string) $filters['community'],
            'paese' => (string) $filters['country'],
            'comune' => $filters['territory'] === null ? '' : (string) $filters['territory'],
            'tipo' => (string) $filters['type'],
            'stranieri' => $filters['community_based'] ? '1' : '',
        ], static fn (string $v): bool => $v !== '');
    }

    // --- Opzioni dei filtri ----------------------------------------------------------------

    /** @return list<array{id: int, code: string, kind: string, name: array{text: string, lang: string, fallback: bool}, countries: list<string>}> */
    public function communities(string $locale): array
    {
        $rows = $this->database->fetchAll('SELECT id, code, kind FROM communities WHERE is_active = 1 ORDER BY sort_order');
        $names = $this->content->taxonomyLabels('community_translations', 'community_id', 'name', array_column($rows, 'id'), $locale);
        $countries = [];
        foreach ($this->database->fetchAll('SELECT community_id, country_code FROM community_countries ORDER BY country_code') as $row) {
            $countries[(int) $row['community_id']][] = (string) $row['country_code'];
        }

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'], 'code' => (string) $r['code'], 'kind' => (string) $r['kind'],
            'name' => $names[(int) $r['id']], 'countries' => $countries[(int) $r['id']] ?? [],
        ], $rows);
    }

    /** @return list<string> paesi collegati alle organizzazioni della directory (direttamente o tramite comunità) */
    public function countries(): array
    {
        return array_map('strval', $this->database->fetchColumn(
            'SELECT country_code FROM organization_countries oc JOIN organizations o ON o.id = oc.organization_id WHERE ' . self::PUBLIC_ORGANIZATION . '
             UNION
             SELECT cc.country_code FROM community_countries cc JOIN organization_communities ocm ON ocm.community_id = cc.community_id
               JOIN organizations o ON o.id = ocm.organization_id WHERE ' . self::PUBLIC_ORGANIZATION . ' ORDER BY 1'
        ));
    }

    /** @return list<string> lingue parlate nelle organizzazioni della directory */
    public function languages(): array
    {
        return array_map('strval', $this->database->fetchColumn(
            'SELECT DISTINCT ol.language_code FROM organization_languages ol JOIN organizations o ON o.id = ol.organization_id
               JOIN organization_types t ON t.id = o.organization_type_id
              WHERE ' . self::PUBLIC_ORGANIZATION . ' AND ' . $this->directoryCondition() . ' ORDER BY ol.language_code',
            self::ASSOCIATION_TYPES,
        ));
    }

    /** @return list<array{id: int, name: string, municipalities: list<array{id: int, name: string}>}> comuni con sedi della directory */
    public function municipalities(): array
    {
        return $this->content->districtTree(array_map('intval', $this->database->fetchColumn(
            "SELECT DISTINCT si.territory_id FROM sites si JOIN organizations o ON o.id = si.organization_id
               JOIN organization_types t ON t.id = o.organization_type_id
              WHERE si.publication_status = 'published' AND " . self::PUBLIC_ORGANIZATION . ' AND ' . $this->directoryCondition(),
            self::ASSOCIATION_TYPES,
        )));
    }

    /** @return list<array{code: string, name: array{text: string, lang: string, fallback: bool}}> */
    public function types(string $locale): array
    {
        $in = implode(',', array_fill(0, count(self::ASSOCIATION_TYPES), '?'));
        $rows = $this->database->fetchAll("SELECT id, code FROM organization_types WHERE code IN ($in) ORDER BY sort_order", self::ASSOCIATION_TYPES);
        $names = $this->content->taxonomyLabels('organization_type_translations', 'organization_type_id', 'name', array_column($rows, 'id'), $locale);

        return array_map(static fn (array $r): array => ['code' => (string) $r['code'], 'name' => $names[(int) $r['id']]], $rows);
    }

    // --- Ricerca ---------------------------------------------------------------------------

    /**
     * @param Filters $filters
     * @return list<int> id ordinati per pertinenza (con testo) o per nome
     */
    public function searchOrganizationIds(array $filters, string $locale): array
    {
        $where = [self::PUBLIC_ORGANIZATION, $this->directoryCondition()];
        $params = self::ASSOCIATION_TYPES;

        if ($filters['language'] !== null) {
            $where[] = 'o.id IN (SELECT organization_id FROM organization_languages WHERE language_code = ?)';
            $params[] = $filters['language'];
        }
        if ($filters['community'] !== null) {
            $where[] = 'o.id IN (SELECT oc.organization_id FROM organization_communities oc JOIN communities c ON c.id = oc.community_id WHERE c.code = ?)';
            $params[] = $filters['community'];
        }
        if ($filters['country'] !== null) {
            $where[] = '(o.id IN (SELECT organization_id FROM organization_countries WHERE country_code = ?)
                         OR o.id IN (SELECT oc.organization_id FROM organization_communities oc
                                       JOIN community_countries cc ON cc.community_id = oc.community_id WHERE cc.country_code = ?))';
            $params[] = $filters['country'];
            $params[] = $filters['country'];
        }
        if ($filters['territory'] !== null) {
            $territories = $this->content->territoryWithDescendants($filters['territory']);
            $where[] = "o.id IN (SELECT si.organization_id FROM sites si WHERE si.publication_status = 'published'
                                   AND si.territory_id IN (" . implode(',', array_fill(0, count($territories), '?')) . '))';
            $params = [...$params, ...$territories];
        }
        if ($filters['type'] !== null) {
            $where[] = 't.code = ?';
            $params[] = $filters['type'];
        }
        if ($filters['community_based']) {
            $where[] = 'o.is_community_based = 1';
        }

        $rows = $this->database->fetchAll(
            'SELECT o.id, o.name FROM organizations o JOIN organization_types t ON t.id = o.organization_type_id WHERE ' . implode(' AND ', $where),
            $params,
        );
        $names = array_column($rows, 'name', 'id');
        $collator = new \Collator($locale);
        uksort($names, static fn (int|string $a, int|string $b): int => $collator->compare((string) $names[$a], (string) $names[$b]));
        $ids = array_map('intval', array_keys($names));

        $query = trim($filters['q']);

        return $query === '' ? $ids : $this->rankByText($ids, $query, $locale);
    }

    /**
     * Schede sintetiche per l'elenco.
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function summaries(array $ids, string $locale): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = [];
        foreach ($this->database->fetchAll(
            "SELECT id, name, is_community_based, organization_type_id, source_locale FROM organizations WHERE id IN ($in)",
            $ids,
        ) as $row) {
            $rows[(int) $row['id']] = $row;
        }
        $texts = $this->content->translatedFields('organization_translations', 'organization_id', $ids, $locale, ['description', 'activities'], array_column($rows, 'source_locale', 'id'), true);
        $types = $this->content->taxonomyLabels('organization_type_translations', 'organization_type_id', 'name', array_values(array_unique(array_map('intval', array_column($rows, 'organization_type_id')))), $locale);
        $communities = $this->communitiesOf($ids, $locale);
        $languages = $this->grouped("SELECT organization_id AS k, language_code AS v FROM organization_languages WHERE organization_id IN ($in) ORDER BY language_code", $ids);
        $towns = $this->grouped(
            "SELECT DISTINCT si.organization_id AS k, t.name AS v FROM sites si JOIN territories t ON t.id = si.territory_id
              WHERE si.organization_id IN ($in) AND si.publication_status = 'published' ORDER BY t.name",
            $ids,
        );

        $result = [];
        foreach ($ids as $id) {
            if (!isset($rows[$id])) {
                continue;
            }
            $result[] = [
                'id' => $id,
                'name' => (string) $rows[$id]['name'],
                'is_community_based' => (bool) $rows[$id]['is_community_based'],
                'type' => $types[(int) $rows[$id]['organization_type_id']],
                'description' => $texts[$id]['description'],
                'activities' => $texts[$id]['activities'],
                'communities' => $communities[$id] ?? [],
                'languages' => $languages[$id] ?? [],
                'towns' => $towns[$id] ?? [],
            ];
        }

        return $result;
    }

    /**
     * Comunità e paesi di un'organizzazione (scheda pubblica).
     *
     * @return array{communities: list<array{code: string, name: array{text: string, lang: string, fallback: bool}}>, countries: list<string>}
     */
    public function profileOf(int $organizationId, string $locale): array
    {
        return [
            'communities' => $this->communitiesOf([$organizationId], $locale)[$organizationId] ?? [],
            'countries' => array_map('strval', $this->database->fetchColumn(
                'SELECT country_code FROM organization_countries WHERE organization_id = ? ORDER BY country_code',
                [$organizationId],
            )),
        ];
    }

    // --- Interni ---------------------------------------------------------------------------

    /** Associazioni per tipo, realtà di comunità o organizzazioni collegate a una comunità (alias o, t). */
    private function directoryCondition(): string
    {
        $in = implode(',', array_fill(0, count(self::ASSOCIATION_TYPES), '?'));

        return "(t.code IN ($in) OR o.is_community_based = 1 OR o.id IN (SELECT organization_id FROM organization_communities))";
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<array{code: string, name: array{text: string, lang: string, fallback: bool}}>>
     */
    private function communitiesOf(array $ids, string $locale): array
    {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->database->fetchAll(
            "SELECT oc.organization_id, c.id, c.code FROM organization_communities oc JOIN communities c ON c.id = oc.community_id
              WHERE oc.organization_id IN ($in) AND c.is_active = 1 ORDER BY c.sort_order",
            $ids,
        );
        $names = $this->content->taxonomyLabels('community_translations', 'community_id', 'name', array_values(array_unique(array_map('intval', array_column($rows, 'id')))), $locale);
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['organization_id']][] = ['code' => (string) $row['code'], 'name' => $names[(int) $row['id']]];
        }

        return $result;
    }

    /**
     * Ricerca testuale: nome (30), comunità, paesi e lingue collegati (20), descrizione e attività (10).
     *
     * @param list<int> $ids
     * @return list<int>
     */
    private function rankByText(array $ids, string $query, string $locale): array
    {
        $tokens = TextNormalizer::tokens($query);
        // "associazioni ucraine": le parole generiche contano solo se sono le uniche della ricerca
        $specific = array_values(array_diff($tokens, self::GENERIC_WORDS));
        if ($specific !== []) {
            $tokens = $specific;
        }
        if ($ids === [] || $tokens === []) {
            return [];
        }
        $matches = static function (string $haystack, string $token): bool {
            $prefix = mb_strlen($token) >= 5 ? mb_substr($token, 0, -1) : $token; // "ucraine" ≈ "ucraina"
            $haystack = ' ' . $haystack . ' ';

            return str_contains($haystack, ' ' . $token) || str_contains($haystack, ' ' . $prefix);
        };
        $in = implode(',', array_fill(0, count($ids), '?'));

        // Parole associate a ogni organizzazione: nomi delle comunità (tutte le lingue), dei paesi e delle lingue.
        $tags = [];
        foreach ($this->database->fetchAll(
            "SELECT oc.organization_id AS k, ct.name AS v FROM organization_communities oc JOIN community_translations ct ON ct.community_id = oc.community_id
              WHERE oc.organization_id IN ($in)",
            $ids,
        ) as $row) {
            $tags[(int) $row['k']][] = (string) $row['v'];
        }
        foreach ($this->database->fetchAll(
            "SELECT organization_id AS k, country_code AS v FROM organization_countries WHERE organization_id IN ($in)
             UNION SELECT oc.organization_id, cc.country_code FROM organization_communities oc JOIN community_countries cc ON cc.community_id = oc.community_id
              WHERE oc.organization_id IN ($in)",
            [...$ids, ...$ids],
        ) as $row) {
            foreach (array_unique([$locale, 'it', 'en']) as $l) {
                $tags[(int) $row['k']][] = \Locale::getDisplayRegion('und-' . $row['v'], $l);
            }
        }
        foreach ($this->database->fetchAll("SELECT organization_id AS k, language_code AS v FROM organization_languages WHERE organization_id IN ($in)", $ids) as $row) {
            foreach (array_unique([$locale, 'it', 'en']) as $l) {
                $tags[(int) $row['k']][] = \Locale::getDisplayLanguage((string) $row['v'], $l);
            }
        }

        $scores = [];
        foreach ($this->database->fetchAll(
            "SELECT o.id, o.name, tr.description, tr.activities FROM organizations o
               LEFT JOIN organization_translations tr ON tr.organization_id = o.id AND tr.locale IN (?, 'it')
              WHERE o.id IN ($in)",
            [$locale, ...$ids],
        ) as $row) {
            $id = (int) $row['id'];
            $name = TextNormalizer::normalize((string) $row['name']);
            $body = TextNormalizer::normalize($row['description'] . ' ' . $row['activities']);
            $tagText = TextNormalizer::normalize(implode(' ', $tags[$id] ?? []));
            $score = 0;
            foreach ($tokens as $token) {
                $score += match (true) {
                    $matches($name, $token) => 30,
                    $matches($tagText, $token) => 20,
                    $matches($body, $token) => 10,
                    default => 0,
                };
            }
            $scores[$id] = max($scores[$id] ?? 0, $score);
        }

        $ranked = array_filter($scores, static fn (int $s): bool => $s > 0);
        // Ordinamento stabile: a parità di punteggio resta l'ordine alfabetico ricevuto
        $position = array_flip($ids);
        uksort($ranked, static fn (int $a, int $b): int => [$ranked[$b], $position[$a]] <=> [$ranked[$a], $position[$b]]);

        return array_keys($ranked);
    }

    /**
     * @param list<int> $params
     * @return array<int, list<string>>
     */
    private function grouped(string $sql, array $params): array
    {
        $grouped = [];
        foreach ($this->database->fetchAll($sql, $params) as $row) {
            $grouped[(int) $row['k']][] = (string) $row['v'];
        }

        return $grouped;
    }
}
