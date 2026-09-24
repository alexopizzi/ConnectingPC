<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Audit\AuditLogger;
use App\Authorization\Gate;
use App\Authorization\ResourceScope;
use App\Core\Database;
use App\I18n\TranslationLoader;
use DomainException;

/**
 * Traduzioni delle stringhe dell'interfaccia (RF-36, vault "60"). Il testo italiano (sorgente) vive nei file
 * lang/ del repository e si importa con `i18n:import`; qui si traducono e si approvano le altre lingue.
 * Permessi: translations.edit per la lingua (o locales.manage); l'approvazione richiede translations.approve.
 * L'importazione successiva non sovrascrive le traduzioni modificate qui (TranslationImporter).
 */
final class UiStringEditor
{
    public const FILTERS = ['missing', 'to_review', 'outdated', 'approved', 'all'];

    public function __construct(
        private readonly Database $database,
        private readonly Gate $gate,
        private readonly AuditLogger $audit,
        private readonly TranslationLoader $loader,
    ) {
    }

    /** @param array<string, mixed>|null $actor */
    public function canEdit(?array $actor, string $locale): bool
    {
        return $this->gate->allows($actor, 'locales.manage')
            || $this->gate->allows($actor, 'translations.edit', new ResourceScope(locale: $locale));
    }

    /**
     * @return list<array{id: int, key: string, source: string, text: ?string, status: ?string}>
     */
    public function list(string $locale, string $filter, string $query): array
    {
        $where = ['1 = 1'];
        $params = [$locale];
        $where[] = match ($filter) {
            'missing' => 't.ui_string_id IS NULL',
            'to_review', 'outdated', 'approved' => 't.status = ' . $this->database->pdo()->quote($filter),
            default => '1 = 1',
        };
        if ($query !== '') {
            $where[] = '(s.`key` LIKE ? OR s.source_text LIKE ? OR t.text LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like);
        }

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'], 'key' => (string) $r['key'], 'source' => (string) $r['source_text'],
            'text' => $r['text'] === null ? null : (string) $r['text'], 'status' => $r['status'] === null ? null : (string) $r['status'],
        ], $this->database->fetchAll(
            'SELECT s.id, s.`key`, s.source_text, t.text, t.status FROM ui_strings s
               LEFT JOIN ui_string_translations t ON t.ui_string_id = s.id AND t.locale = ?
              WHERE ' . implode(' AND ', $where) . ' ORDER BY s.`key` LIMIT 200',
            $params,
        ));
    }

    /** @return array<string, int> conteggi per stato nella lingua */
    public function summary(string $locale): array
    {
        $total = (int) $this->database->fetchValue('SELECT COUNT(*) FROM ui_strings');
        $counts = ['missing' => $total];
        foreach ($this->database->fetchAll('SELECT status, COUNT(*) AS n FROM ui_string_translations WHERE locale = ? GROUP BY status', [$locale]) as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
            $counts['missing'] -= (int) $row['n'];
        }

        return $counts + ['to_review' => 0, 'outdated' => 0, 'approved' => 0];
    }

    /** @param array<string, mixed> $actor */
    public function save(array $actor, int $stringId, string $locale, string $text, bool $approve): void
    {
        if ($locale === 'it' || $this->database->fetchValue('SELECT code FROM locales WHERE code = ? AND is_enabled = 1', [$locale]) === null) {
            throw new DomainException('manage.error.invalid_locale');
        }
        if (!$this->canEdit($actor, $locale)) {
            throw new DomainException('manage.error.forbidden');
        }
        $source = $this->database->fetchOne('SELECT `key`, source_text FROM ui_strings WHERE id = ?', [$stringId]) ?? throw new DomainException('manage.error.not_found');
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 5000) {
            throw new DomainException('manage.error.invalid_value');
        }
        // Stessi segnaposto del testo italiano ({name}, {count, plural…}): altrimenti il messaggio si rompe
        if (array_diff(self::arguments((string) $source['source_text']), self::arguments($text)) !== [] || \MessageFormatter::create($locale, $text) === null) {
            throw new DomainException('manage.error.invalid_placeholders');
        }
        $status = $approve && ($this->gate->allows($actor, 'translations.approve', new ResourceScope(locale: $locale)) || $this->gate->allows($actor, 'locales.manage'))
            ? 'approved' : 'to_review';

        $this->database->execute(
            'INSERT INTO ui_string_translations (ui_string_id, locale, text, status, source_hash, translated_by) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE text = VALUES(text), status = VALUES(status), source_hash = VALUES(source_hash), translated_by = VALUES(translated_by)',
            [$stringId, $locale, $text, $status, hash('sha256', (string) $source['source_text']), (int) $actor['id']],
        );
        $this->audit->log('ui_string.translated', 'ui_string', $stringId, ['key' => $source['key'], 'locale' => $locale, 'status' => $status]);
        $this->loader->forget();
    }

    /**
     * Nomi degli argomenti ICU al primo livello ({name}, {count, plural, …}); i testi dentro i rami
     * del plurale non sono argomenti.
     *
     * @return list<string>
     */
    public static function arguments(string $message): array
    {
        $names = [];
        $depth = 0;
        $length = strlen($message);
        for ($i = 0; $i < $length; $i++) {
            if ($message[$i] === '{') {
                if ($depth === 0 && preg_match('/\G\{\s*(\w+)/', $message, $m, 0, $i)) {
                    $names[] = $m[1];
                }
                $depth++;
            } elseif ($message[$i] === '}') {
                $depth = max(0, $depth - 1);
            }
        }

        return array_values(array_unique($names));
    }
}
