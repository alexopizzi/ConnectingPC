<?php

declare(strict_types=1);

namespace App\I18n;

use App\Core\Database;
use App\Core\FileCache;
use Throwable;

/**
 * Stringhe dell'interfaccia per lingua.
 * Fonte viva: database (ui_strings + ui_string_translations), compilata in cache su file.
 * Seed e ripiego se il database non è pronto: lang/{locale}.php (D-007).
 */
final class TranslationLoader
{
    private const CACHE_PREFIX = 'i18n.messages.';

    public function __construct(
        private readonly ?Database $database,
        private readonly FileCache $cache,
        private readonly string $langDirectory,
        private readonly string $sourceLocale,
    ) {
    }

    /** @return array<string, string> */
    public function messages(string $locale): array
    {
        $cached = $this->cache->get(self::CACHE_PREFIX . $locale);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $messages = $this->fromDatabase($locale);
        } catch (Throwable) {
            return $this->fromFile($locale);
        }
        if ($messages === []) {
            // Stringhe non ancora importate (bin/console i18n:import).
            return $this->fromFile($locale);
        }

        $this->cache->set(self::CACHE_PREFIX . $locale, $messages);

        return $messages;
    }

    /** @return array<string, string> */
    public function fromFile(string $locale): array
    {
        if (!preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})?$/', $locale)) {
            return [];
        }
        $file = $this->langDirectory . '/' . $locale . '.php';
        if (!is_file($file)) {
            return [];
        }
        $messages = require $file;

        return is_array($messages) ? array_map('strval', $messages) : [];
    }

    public function forget(): void
    {
        $this->cache->clear(self::CACHE_PREFIX);
    }

    /** @return array<string, string> */
    private function fromDatabase(string $locale): array
    {
        if ($this->database === null) {
            return [];
        }
        if ($locale === $this->sourceLocale) {
            $rows = $this->database->fetchAll('SELECT `key`, source_text AS text FROM ui_strings');
        } else {
            // Le bozze non si mostrano; le altre (anche da revisionare) sì, per non lasciare l'interfaccia in italiano.
            $rows = $this->database->fetchAll(
                "SELECT s.`key`, t.text
                   FROM ui_string_translations t
                   JOIN ui_strings s ON s.id = t.ui_string_id
                  WHERE t.locale = ? AND t.status <> 'draft'",
                [$locale],
            );
        }

        $messages = [];
        foreach ($rows as $row) {
            $messages[(string) $row['key']] = (string) $row['text'];
        }

        return $messages;
    }
}
