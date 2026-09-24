<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Core\Database;

/**
 * Letture comuni ai repository pubblici: testi tradotti con ripiego, etichette di tassonomia, recapiti
 * filtrati per visibilità (vault "60", D-013, D-028).
 *
 * @phpstan-type Localized array{text: string, lang: string, fallback: bool}
 */
final class ContentReader
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * Campi tradotti con ripiego sulla lingua sorgente. I contenuti richiedono traduzioni approvate;
     * la lingua sorgente è sempre usabile.
     *
     * @param list<int|string> $ids
     * @param list<string> $fields
     * @param array<int|string, string> $sourceLocales
     * @return array<int, array<string, ?array{text: string, lang: string, fallback: bool}>>
     */
    public function translatedFields(string $table, string $key, array $ids, string $locale, array $fields, array $sourceLocales, bool $allowMissing): array
    {
        $result = [];
        foreach ($ids as $id) {
            $result[(int) $id] = array_fill_keys($fields, null);
        }
        if ($ids === []) {
            return $result;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $columns = implode(', ', array_map(static fn (string $f): string => Database::identifier($f), $fields));
        $rows = $this->database->fetchAll(
            'SELECT ' . Database::identifier($key) . ' AS entity_id, locale, status, ' . $columns . ' FROM ' . Database::identifier($table)
            . ' WHERE ' . Database::identifier($key) . " IN ($in)",
            array_values(array_map('intval', $ids)),
        );

        $byEntity = [];
        foreach ($rows as $row) {
            $byEntity[(int) $row['entity_id']][(string) $row['locale']] = $row;
        }
        foreach ($result as $id => $values) {
            $source = $sourceLocales[$id] ?? 'it';
            $requested = $byEntity[$id][$locale] ?? null;
            if ($requested !== null && $locale !== $source && $requested['status'] !== 'approved') {
                $requested = null;
            }
            $fallback = $byEntity[$id][$source] ?? null;
            foreach ($fields as $field) {
                $value = $requested[$field] ?? null;
                $lang = $locale;
                if (($value === null || $value === '') && $fallback !== null) {
                    $value = $fallback[$field] ?? null;
                    $lang = $source;
                }
                if ($value !== null && $value !== '') {
                    $result[$id][$field] = ['text' => (string) $value, 'lang' => $lang, 'fallback' => $lang !== $locale];
                }
            }
            if (!$allowMissing && $result[$id]['name'] === null) {
                $result[$id]['name'] = ['text' => '#' . $id, 'lang' => $locale, 'fallback' => false];
            }
        }

        return $result;
    }

    /**
     * Etichette di tassonomia: si mostrano anche se "da revisionare" (come le stringhe UI, D-028).
     *
     * @param list<int|string> $ids
     * @return array<int, array{text: string, lang: string, fallback: bool}>
     */
    public function taxonomyLabels(string $table, string $key, string $field, array $ids, string $locale): array
    {
        $result = [];
        if ($ids === []) {
            return $result;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->database->fetchAll(
            'SELECT ' . Database::identifier($key) . ' AS entity_id, locale, ' . Database::identifier($field) . " AS text
               FROM " . Database::identifier($table) . ' WHERE ' . Database::identifier($key) . " IN ($in)
                AND status <> 'draft' AND locale IN (?, 'it')",
            [...array_values(array_map('intval', $ids)), $locale],
        );
        $texts = [];
        foreach ($rows as $row) {
            $texts[(int) $row['entity_id']][(string) $row['locale']] = (string) $row['text'];
        }
        foreach ($ids as $id) {
            $id = (int) $id;
            if (isset($texts[$id][$locale])) {
                $result[$id] = ['text' => $texts[$id][$locale], 'lang' => $locale, 'fallback' => false];
            } else {
                $result[$id] = ['text' => $texts[$id]['it'] ?? '#' . $id, 'lang' => 'it', 'fallback' => $locale !== 'it'];
            }
        }

        return $result;
    }

    /**
     * Recapiti con le sole visibilità ammesse (pubblico: solo 'public', D-013).
     *
     * @param list<int> $ownerIds
     * @param list<'public'|'operators'|'admin'> $visibilities
     * @return list<array{owner_id: int, kind: string, value: string, label_key: ?string}>
     */
    public function contacts(string $ownerType, array $ownerIds, array $visibilities = ['public']): array
    {
        if ($ownerIds === [] || $visibilities === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ownerIds), '?'));
        $vis = implode(',', array_fill(0, count($visibilities), '?'));

        return array_map(static fn (array $r): array => [
            'owner_id' => (int) $r['owner_id'], 'kind' => (string) $r['kind'], 'value' => (string) $r['value'],
            'label_key' => $r['label_key'] === null ? null : (string) $r['label_key'],
        ], $this->database->fetchAll(
            "SELECT owner_id, kind, value, label_key FROM contact_points
              WHERE owner_type = ? AND owner_id IN ($in) AND visibility IN ($vis) ORDER BY sort_order, id",
            [$ownerType, ...$ownerIds, ...$visibilities],
        ));
    }

    /**
     * Territori che "coprono" o sono coperti da quello scelto: se stesso, antenati e discendenti.
     * Serve ai filtri per comune/distretto su entità collegate a territori di livello diverso.
     *
     * @return list<int>
     */
    public function territoryFamily(int $territoryId): array
    {
        $family = $this->territoryWithDescendants($territoryId);
        $current = $territoryId;
        for ($i = 0; $i < 5; $i++) {
            $parent = $this->database->fetchValue('SELECT parent_id FROM territories WHERE id = ?', [$current]);
            if ($parent === null) {
                break;
            }
            $family[] = $current = (int) $parent;
        }

        return array_values(array_unique($family));
    }

    /**
     * Distretti con i rispettivi comuni, per i filtri (facoltativamente solo i comuni indicati).
     *
     * @param list<int>|null $onlyMunicipalities
     * @return list<array{id: int, name: string, municipalities: list<array{id: int, name: string}>}>
     */
    public function districtTree(?array $onlyMunicipalities = null): array
    {
        if ($onlyMunicipalities === []) {
            return [];
        }
        $sql = "SELECT t.id, t.name, d.id AS district_id, d.name AS district_name FROM territories t
                  JOIN territories d ON d.id = t.parent_id AND d.type = 'district' WHERE t.type = 'municipality'";
        $params = [];
        if ($onlyMunicipalities !== null) {
            $sql .= ' AND t.id IN (' . implode(',', array_fill(0, count($onlyMunicipalities), '?')) . ')';
            $params = $onlyMunicipalities;
        }
        $districts = [];
        foreach ($this->database->fetchAll($sql . ' ORDER BY d.id, t.name', $params) as $row) {
            $id = (int) $row['district_id'];
            $districts[$id] ??= ['id' => $id, 'name' => (string) $row['district_name'], 'municipalities' => []];
            $districts[$id]['municipalities'][] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return array_values($districts);
    }

    /**
     * Discendenti di un territorio compreso se stesso (es. distretto → i suoi comuni).
     *
     * @return list<int>
     */
    public function territoryWithDescendants(int $territoryId): array
    {
        $all = [$territoryId];
        $level = [$territoryId];
        for ($i = 0; $i < 5 && $level !== []; $i++) {
            $in = implode(',', array_fill(0, count($level), '?'));
            $level = array_map('intval', $this->database->fetchColumn("SELECT id FROM territories WHERE parent_id IN ($in)", $level));
            $all = [...$all, ...$level];
        }

        return $all;
    }
}
