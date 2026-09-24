<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Authorization\Gate;
use App\Authorization\ResourceScope;
use App\Core\Database;
use App\Core\FileCache;
use DomainException;

/**
 * Tassonomie (RF-31, vault "65"): bisogni, categorie e sottocategorie, tipi di organizzazione, comunità,
 * ambiti di mediazione. Permesso taxonomy.manage. Il codice non cambia dopo la creazione (è usato negli URL,
 * nei seed e nei filtri); le voci non si cancellano, si disattivano dove previsto.
 * Etichette: italiano sempre approvato; altre lingue approvate solo con translations.approve (D-028).
 */
final class TaxonomyEditor
{
    /** tipo => [tabella, tabella traduzioni, chiave, campo etichetta, campi propri] */
    public const TYPES = [
        'needs' => ['needs', 'need_translations', 'need_id', 'label', ['icon', 'is_featured', 'is_active']],
        'categories' => ['categories', 'category_translations', 'category_id', 'name', ['icon', 'parent_id', 'is_active']],
        'organization_types' => ['organization_types', 'organization_type_translations', 'organization_type_id', 'name', ['is_public_body']],
        'communities' => ['communities', 'community_translations', 'community_id', 'name', ['kind', 'is_active']],
        'mediation_domains' => ['mediation_domains', 'mediation_domain_translations', 'mediation_domain_id', 'name', []],
    ];
    public const COMMUNITY_KINDS = ['national', 'linguistic', 'cultural', 'religious', 'regional', 'intercultural', 'other'];
    private const FLAGS = ['is_featured', 'is_active', 'is_public_body'];

    public function __construct(
        private readonly Database $database,
        private readonly Gate $gate,
        private readonly AuditLogger $audit,
        private readonly FileCache $cache,
    ) {
    }

    /**
     * Voci del tipo con etichette in tutte le lingue.
     *
     * @return list<array<string, mixed>>
     */
    public function list(string $type): array
    {
        [$table, $trTable, $key, $labelField] = $this->definition($type);
        $order = $type === 'categories' ? 'COALESCE(p.sort_order, t.sort_order), t.parent_id IS NOT NULL, t.sort_order' : 't.sort_order';
        $join = $type === 'categories' ? 'LEFT JOIN categories p ON p.id = t.parent_id' : '';
        $rows = $this->database->fetchAll('SELECT t.* FROM ' . Database::identifier($table) . " t $join ORDER BY $order");
        $labels = [];
        foreach ($this->database->fetchAll('SELECT ' . Database::identifier($key) . ' AS id, locale, ' . Database::identifier($labelField) . ' AS label, status FROM ' . Database::identifier($trTable)) as $l) {
            $labels[(int) $l['id']][(string) $l['locale']] = ['text' => (string) $l['label'], 'status' => (string) $l['status']];
        }

        return array_map(static fn (array $r): array => [...$r, 'id' => (int) $r['id'], 'labels' => $labels[(int) $r['id']] ?? []], $rows);
    }

    /** @return array<string, mixed>|null */
    public function find(string $type, int $id): ?array
    {
        foreach ($this->list($type) as $row) {
            if ($row['id'] === $id) {
                if ($type === 'needs') {
                    $row['categories'] = array_map('intval', $this->database->fetchColumn('SELECT category_id FROM need_category WHERE need_id = ?', [$id]));
                }
                if ($type === 'communities') {
                    $row['countries'] = array_map('strval', $this->database->fetchColumn('SELECT country_code FROM community_countries WHERE community_id = ? ORDER BY country_code', [$id]));
                }

                return $row;
            }
        }

        return null;
    }

    /**
     * Crea (id null) o aggiorna una voce.
     *
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data code, sort_order, campi propri, labels[locale], approve, categories, countries
     */
    public function save(array $actor, string $type, ?int $id, array $data): int
    {
        $this->gate->authorize($actor, 'taxonomy.manage');
        [$table, $trTable, $key, $labelField, $fields] = $this->definition($type);
        $before = $id === null ? [] : ($this->database->fetchOne('SELECT * FROM ' . Database::identifier($table) . ' WHERE id = ?', [$id]) ?? throw new DomainException('manage.error.not_found'));

        $labels = array_map(static fn ($v): string => trim((string) $v), (array) ($data['labels'] ?? []));
        if (($labels['it'] ?? '') === '') {
            throw new DomainException('manage.error.name_required');
        }
        $row = ['sort_order' => max(-999, min(9999, (int) ($data['sort_order'] ?? 0)))];
        foreach ($fields as $field) {
            $row[$field] = match (true) {
                in_array($field, self::FLAGS, true) => empty($data[$field]) ? 0 : 1,
                $field === 'icon' => $this->icon((string) ($data['icon'] ?? ''), $type === 'needs'),
                $field === 'parent_id' => $this->parent($id, (int) ($data['parent_id'] ?? 0)),
                $field === 'kind' => in_array($data['kind'] ?? '', self::COMMUNITY_KINDS, true) ? (string) $data['kind'] : throw new DomainException('manage.error.invalid_value'),
            };
        }
        if ($id === null) {
            $code = (string) ($data['code'] ?? '');
            if (!preg_match('/^[a-z][a-z0-9_]{1,49}$/', $code)) {
                throw new DomainException('manage.error.invalid_code');
            }
            if ($this->database->fetchValue('SELECT id FROM ' . Database::identifier($table) . ' WHERE code = ?', [$code]) !== null) {
                throw new DomainException('manage.error.code_taken');
            }
            $row['code'] = $code;
        }
        $approve = !empty($data['approve']);

        $id = $this->database->transaction(function () use ($actor, $type, $id, $before, $row, $labels, $approve, $data, $table, $trTable, $key, $labelField): int {
            if ($id === null) {
                $id = $this->database->insert($table, $row);
                $changes = ['created' => $row['code']];
            } else {
                $changes = AuditLogger::diff($before, $row);
                if ($changes !== []) {
                    $this->database->update($table, $row, ['id' => $id]);
                }
            }
            foreach ($labels as $locale => $text) {
                if (!preg_match('/^[a-z]{2}$/', (string) $locale) || $this->database->fetchValue('SELECT code FROM locales WHERE code = ?', [$locale]) === null) {
                    continue;
                }
                if ($text === '') {
                    $this->database->execute('DELETE FROM ' . Database::identifier($trTable) . ' WHERE ' . Database::identifier($key) . ' = ? AND locale = ?', [$id, $locale]);
                    continue;
                }
                $status = $locale === 'it' || ($approve && $this->gate->allows($actor, 'translations.approve', new ResourceScope(locale: (string) $locale))) ? 'approved' : 'to_review';
                $current = $this->database->fetchOne('SELECT ' . Database::identifier($labelField) . ' AS label, status FROM ' . Database::identifier($trTable) . ' WHERE ' . Database::identifier($key) . ' = ? AND locale = ?', [$id, $locale]);
                if ($current !== null && $current['label'] === $text && ($current['status'] === 'approved' || $status !== 'approved')) {
                    continue;
                }
                $this->database->execute(
                    'INSERT INTO ' . Database::identifier($trTable) . ' (' . Database::identifier($key) . ', locale, ' . Database::identifier($labelField) . ', status, source_hash)
                     VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE ' . Database::identifier($labelField) . ' = VALUES(' . Database::identifier($labelField) . '), status = VALUES(status), source_hash = VALUES(source_hash)',
                    [$id, $locale, mb_substr($text, 0, 120), $status, hash('sha256', $labels['it'])],
                );
                $changes['labels'][$locale] = $text;
            }
            if ($type === 'needs' && array_key_exists('categories', $data)) {
                $valid = array_map('intval', $this->database->fetchColumn('SELECT id FROM categories'));
                $this->database->execute('DELETE FROM need_category WHERE need_id = ?', [$id]);
                foreach (array_unique(array_map('intval', (array) $data['categories'])) as $categoryId) {
                    if (in_array($categoryId, $valid, true)) {
                        $this->database->insert('need_category', ['need_id' => $id, 'category_id' => $categoryId]);
                    }
                }
            }
            if ($type === 'communities' && array_key_exists('countries', $data)) {
                $this->database->execute('DELETE FROM community_countries WHERE community_id = ?', [$id]);
                foreach (array_unique((array) $data['countries']) as $country) {
                    if (preg_match('/^[A-Z]{2}$/', (string) $country)) {
                        $this->database->insert('community_countries', ['community_id' => $id, 'country_code' => $country]);
                    }
                }
            }
            $this->audit->log('taxonomy.saved', $type, $id, $changes);

            return $id;
        });
        $this->cache->clear();

        return $id;
    }

    /** @return array{0: string, 1: string, 2: string, 3: string, 4: list<string>} */
    private function definition(string $type): array
    {
        return self::TYPES[$type] ?? throw new DomainException('manage.error.not_found');
    }

    private function icon(string $icon, bool $required): ?string
    {
        $icon = trim($icon);
        if ($icon === '') {
            return $required ? throw new DomainException('manage.error.invalid_value') : null;
        }

        return preg_match('/^[a-z_-]{2,50}$/', $icon) ? $icon : throw new DomainException('manage.error.invalid_value');
    }

    /** Solo due livelli: area → sottocategoria; un'area non può diventare figlia di sé stessa. */
    private function parent(?int $id, int $parentId): ?int
    {
        if ($parentId === 0) {
            return null;
        }
        $parent = $this->database->fetchOne('SELECT id, parent_id FROM categories WHERE id = ?', [$parentId]);
        if ($parent === null || $parent['parent_id'] !== null || $parentId === $id
            || ($id !== null && $this->database->fetchValue('SELECT id FROM categories WHERE parent_id = ? LIMIT 1', [$id]) !== null)) {
            throw new DomainException('manage.error.invalid_parent');
        }

        return $parentId;
    }
}
