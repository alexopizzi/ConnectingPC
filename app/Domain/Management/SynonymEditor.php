<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Authorization\Gate;
use App\Core\Database;
use App\Domain\Search\TextNormalizer;
use DomainException;

/**
 * Dizionario dei sinonimi della ricerca (vault "62", D-029): espressioni dell'utente → bisogni.
 * I termini si salvano già normalizzati, come li confronta la ricerca. Permesso taxonomy.manage.
 */
final class SynonymEditor
{
    public function __construct(private readonly Database $database, private readonly Gate $gate, private readonly AuditLogger $audit)
    {
    }

    /** @return list<array<string, mixed>> */
    public function list(string $locale, int $needId): array
    {
        $where = ["st.target_type = 'need'"];
        $params = [];
        if ($locale !== '') {
            $where[] = 'st.locale = ?';
            $params[] = $locale;
        }
        if ($needId > 0) {
            $where[] = 'st.target_id = ?';
            $params[] = $needId;
        }

        return $this->database->fetchAll(
            "SELECT st.id, st.locale, st.term, st.weight, st.target_id, nt.label AS need_label FROM search_terms st
               LEFT JOIN need_translations nt ON nt.need_id = st.target_id AND nt.locale = 'it'
              WHERE " . implode(' AND ', $where) . ' ORDER BY st.locale, nt.label, st.term LIMIT 500',
            $params,
        );
    }

    /** @param array<string, mixed> $actor */
    public function add(array $actor, string $locale, string $term, int $needId, int $weight): void
    {
        $this->gate->authorize($actor, 'taxonomy.manage');
        $normalized = TextNormalizer::normalize($term);
        if ($normalized === '' || mb_strlen($normalized) > 190) {
            throw new DomainException('manage.error.invalid_term');
        }
        if ($this->database->fetchValue('SELECT code FROM locales WHERE code = ?', [$locale]) === null
            || $this->database->fetchValue('SELECT id FROM needs WHERE id = ?', [$needId]) === null) {
            throw new DomainException('manage.error.invalid_value');
        }
        $weight = max(1, min(10, $weight));
        $this->database->execute(
            "INSERT INTO search_terms (locale, term, target_type, target_id, weight, created_by) VALUES (?, ?, 'need', ?, ?, ?)
             ON DUPLICATE KEY UPDATE weight = VALUES(weight)",
            [$locale, $normalized, $needId, $weight, (int) $actor['id']],
        );
        $this->audit->log('search_term.saved', 'need', $needId, ['locale' => $locale, 'term' => $normalized, 'weight' => $weight]);
    }

    /** @param array<string, mixed> $actor */
    public function delete(array $actor, int $id): void
    {
        $this->gate->authorize($actor, 'taxonomy.manage');
        $row = $this->database->fetchOne('SELECT locale, term, target_id FROM search_terms WHERE id = ?', [$id]) ?? throw new DomainException('manage.error.not_found');
        $this->database->execute('DELETE FROM search_terms WHERE id = ?', [$id]);
        $this->audit->log('search_term.deleted', 'need', (int) $row['target_id'], ['locale' => $row['locale'], 'term' => $row['term']]);
    }
}
