<?php

declare(strict_types=1);

namespace App\I18n;

use App\Core\Database;

/**
 * Importa i file lang/{locale}.php nel database (bin/console i18n:import).
 * - Lingua sorgente: crea o aggiorna ui_strings; se un testo sorgente cambia, le traduzioni diventano `outdated`.
 * - Altre lingue: inserisce solo le traduzioni mancanti (stato `to_review`), senza toccare quelle
 *   modificate dagli amministratori; con $force le sovrascrive.
 */
final class TranslationImporter
{
    public function __construct(
        private readonly Database $database,
        private readonly TranslationLoader $loader,
        private readonly string $sourceLocale,
    ) {
    }

    /**
     * @param list<string> $locales lingue attive (sorgente inclusa)
     * @return array<string, array{created: int, updated: int, skipped: int}>
     */
    public function import(array $locales, bool $force = false): array
    {
        $stats = [];
        $source = $this->loader->fromFile($this->sourceLocale);

        $this->database->transaction(function (Database $db) use ($source, $locales, $force, &$stats): void {
            $stats[$this->sourceLocale] = $this->importSource($db, $source);
            $ids = [];
            foreach ($db->fetchAll('SELECT id, `key`, source_text FROM ui_strings') as $row) {
                $ids[(string) $row['key']] = ['id' => (int) $row['id'], 'hash' => hash('sha256', (string) $row['source_text'])];
            }
            foreach ($locales as $locale) {
                if ($locale !== $this->sourceLocale) {
                    $stats[$locale] = $this->importLocale($db, $locale, $ids, $force);
                }
            }
        });

        $this->loader->forget();

        return $stats;
    }

    /**
     * @param array<string, string> $source
     * @return array{created: int, updated: int, skipped: int}
     */
    private function importSource(Database $db, array $source): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $existing = [];
        foreach ($db->fetchAll('SELECT id, `key`, source_text FROM ui_strings') as $row) {
            $existing[(string) $row['key']] = $row;
        }

        foreach ($source as $key => $text) {
            if (!isset($existing[$key])) {
                $db->insert('ui_strings', ['key' => $key, 'source_text' => $text]);
                $stats['created']++;
            } elseif ($existing[$key]['source_text'] !== $text) {
                $db->update('ui_strings', ['source_text' => $text], ['id' => $existing[$key]['id']]);
                $db->execute(
                    "UPDATE ui_string_translations SET status = 'outdated' WHERE ui_string_id = ? AND status <> 'draft'",
                    [$existing[$key]['id']],
                );
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * @param array<string, array{id: int, hash: string}> $ids
     * @return array{created: int, updated: int, skipped: int}
     */
    private function importLocale(Database $db, string $locale, array $ids, bool $force): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $existing = [];
        foreach ($db->fetchAll('SELECT ui_string_id FROM ui_string_translations WHERE locale = ?', [$locale]) as $row) {
            $existing[(int) $row['ui_string_id']] = true;
        }

        foreach ($this->loader->fromFile($locale) as $key => $text) {
            if (!isset($ids[$key])) {
                $stats['skipped']++;
                continue;
            }
            $id = $ids[$key]['id'];
            if (!isset($existing[$id])) {
                $db->insert('ui_string_translations', [
                    'ui_string_id' => $id,
                    'locale' => $locale,
                    'text' => $text,
                    'status' => 'to_review',
                    'source_hash' => $ids[$key]['hash'],
                ]);
                $stats['created']++;
            } elseif ($force) {
                $db->execute(
                    "UPDATE ui_string_translations SET text = ?, status = 'to_review', source_hash = ?
                      WHERE ui_string_id = ? AND locale = ?",
                    [$text, $ids[$key]['hash'], $id, $locale],
                );
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }
}
