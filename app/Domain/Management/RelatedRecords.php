<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Core\Database;
use DomainException;

/**
 * Recapiti, orari e collegamenti multipli: sostituzione completa delle righe di un proprietario,
 * con validazione dei valori ammessi. Le autorizzazioni le verifica chi chiama.
 */
final class RelatedRecords
{
    public const CONTACT_KINDS = ['phone', 'mobile', 'whatsapp', 'email', 'pec', 'website', 'social', 'other'];
    public const VISIBILITIES = ['public', 'operators', 'admin'];

    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param list<array{kind?: string, value?: string, visibility?: string}> $rows righe vuote ignorate
     * @return list<array{kind: string, value: string, visibility: string}> righe salvate
     */
    public function replaceContacts(string $ownerType, int $ownerId, array $rows, ?int $actorId, string $defaultVisibility = 'admin'): array
    {
        $clean = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $kind = (string) ($row['kind'] ?? '');
            $visibility = (string) ($row['visibility'] ?? $defaultVisibility);
            if (!in_array($kind, self::CONTACT_KINDS, true) || !in_array($visibility, self::VISIBILITIES, true) || mb_strlen($value) > 500) {
                throw new DomainException('manage.error.invalid_contact');
            }
            if (in_array($kind, ['email', 'pec'], true) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                throw new DomainException('manage.error.invalid_email');
            }
            if (in_array($kind, ['website', 'social'], true) && !preg_match('#^https?://#i', $value)) {
                throw new DomainException('manage.error.invalid_url');
            }
            $clean[] = ['kind' => $kind, 'value' => $value, 'visibility' => $visibility];
        }

        $this->database->execute('DELETE FROM contact_points WHERE owner_type = ? AND owner_id = ?', [$ownerType, $ownerId]);
        foreach ($clean as $i => $row) {
            $this->database->insert('contact_points', [
                'owner_type' => $ownerType, 'owner_id' => $ownerId, ...$row, 'sort_order' => $i + 1, 'created_by' => $actorId,
            ]);
        }

        return $clean;
    }

    /**
     * @param list<array{weekday?: string|int, opens?: string, closes?: string, appointment?: string|bool}> $rows
     * @return list<array{weekday: int, opens: string, closes: string, appointment: int}>
     */
    public function replaceHours(string $ownerType, int $ownerId, array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            $opens = trim((string) ($row['opens'] ?? ''));
            $closes = trim((string) ($row['closes'] ?? ''));
            if ($opens === '' && $closes === '') {
                continue;
            }
            $weekday = (int) ($row['weekday'] ?? 0);
            if ($weekday < 1 || $weekday > 7 || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $opens)
                || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $closes) || $closes <= $opens) {
                throw new DomainException('manage.error.invalid_hours');
            }
            $clean[] = ['weekday' => $weekday, 'opens' => $opens, 'closes' => $closes, 'appointment' => empty($row['appointment']) ? 0 : 1];
        }
        usort($clean, static fn (array $a, array $b): int => [$a['weekday'], $a['opens']] <=> [$b['weekday'], $b['opens']]);

        $this->database->execute('DELETE FROM opening_hours WHERE owner_type = ? AND owner_id = ?', [$ownerType, $ownerId]);
        foreach ($clean as $row) {
            $this->database->insert('opening_hours', [
                'owner_type' => $ownerType, 'owner_id' => $ownerId, 'weekday' => $row['weekday'],
                'opens_at' => $row['opens'], 'closes_at' => $row['closes'], 'by_appointment' => $row['appointment'],
            ]);
        }

        return $clean;
    }

    /**
     * Sostituisce una tabella di collegamento (chiave del proprietario + un valore), accettando solo valori
     * presenti nell'elenco consentito.
     *
     * @param list<int|string> $values
     * @param list<int|string> $allowed
     * @return list<int|string>
     */
    public function replaceLinks(string $table, string $ownerColumn, int $ownerId, string $valueColumn, array $values, array $allowed): array
    {
        $values = array_values(array_unique(array_filter($values, static fn ($v): bool => in_array($v, $allowed, false))));
        $this->database->execute('DELETE FROM ' . Database::identifier($table) . ' WHERE ' . Database::identifier($ownerColumn) . ' = ?', [$ownerId]);
        foreach ($values as $value) {
            $this->database->insert($table, [$ownerColumn => $ownerId, $valueColumn => $value]);
        }

        return $values;
    }

    /**
     * Salva la traduzione di un contenuto. Se cambia il testo nella lingua sorgente, le traduzioni
     * approvate delle altre lingue diventano "da aggiornare" (vault "60").
     *
     * @param array<string, ?string> $fields
     * @return array<string, array{old: mixed, new: mixed}> differenze per l'audit
     */
    public function saveTranslation(string $table, string $key, int $id, string $locale, string $sourceLocale, array $fields, string $status, ?int $actorId): array
    {
        $fields = array_map(static fn (?string $v): ?string => $v === null || trim($v) === '' ? null : trim($v), $fields);
        $before = $this->database->fetchOne(
            'SELECT * FROM ' . Database::identifier($table) . ' WHERE ' . Database::identifier($key) . ' = ? AND locale = ?',
            [$id, $locale],
        ) ?? [];
        $hash = hash('sha256', (string) json_encode($fields, JSON_UNESCAPED_UNICODE));
        $row = [...$fields, 'status' => $status, ($locale === $sourceLocale ? 'reviewed_by' : 'translated_by') => $actorId];
        if ($locale === $sourceLocale) {
            $row['source_hash'] = $hash;
        } else {
            $row['source_hash'] = $this->database->fetchValue(
                'SELECT source_hash FROM ' . Database::identifier($table) . ' WHERE ' . Database::identifier($key) . ' = ? AND locale = ?',
                [$id, $sourceLocale],
            );
        }

        $columns = [$key => $id, 'locale' => $locale, ...$row];
        $updates = implode(', ', array_map(static fn (string $c): string => Database::identifier($c) . ' = VALUES(' . Database::identifier($c) . ')', array_keys($row)));
        $this->database->execute(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                Database::identifier($table),
                implode(', ', array_map(Database::identifier(...), array_keys($columns))),
                implode(', ', array_fill(0, count($columns), '?')),
                $updates,
            ),
            array_values($columns),
        );

        $changes = AuditLogger::diff(array_intersect_key($before, $fields), $fields);
        if ($locale === $sourceLocale && $before !== [] && $changes !== []) {
            $this->database->execute(
                'UPDATE ' . Database::identifier($table) . " SET status = 'outdated' WHERE " . Database::identifier($key) . " = ? AND locale <> ? AND status IN ('approved', 'to_review', 'machine')",
                [$id, $sourceLocale],
            );
        }

        return $changes;
    }

    /** @return list<array{kind: string, value: string, visibility: string}> recapiti (tutte le visibilità) */
    public function contacts(string $ownerType, int $ownerId): array
    {
        return $this->database->fetchAll(
            'SELECT kind, value, visibility FROM contact_points WHERE owner_type = ? AND owner_id = ? ORDER BY sort_order, id',
            [$ownerType, $ownerId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function hours(string $ownerType, int $ownerId): array
    {
        return array_map(static fn (array $h): array => [
            'weekday' => (int) $h['weekday'], 'opens' => substr((string) $h['opens_at'], 0, 5),
            'closes' => substr((string) $h['closes_at'], 0, 5), 'appointment' => (bool) $h['by_appointment'],
        ], $this->database->fetchAll(
            'SELECT weekday, opens_at, closes_at, by_appointment FROM opening_hours WHERE owner_type = ? AND owner_id = ? ORDER BY weekday, opens_at',
            [$ownerType, $ownerId],
        ));
    }
}
