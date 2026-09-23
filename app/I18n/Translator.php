<?php

declare(strict_types=1);

namespace App\I18n;

use MessageFormatter;

/**
 * Traduzione delle stringhe dell'interfaccia.
 * Parametri e plurali con la sintassi ICU MessageFormat: "{count, plural, one {# risultato} other {# risultati}}".
 * Convenzione: nelle stringhe con parametri usare l'apostrofo tipografico ’ (l'apostrofo ' è un carattere
 * di escape in ICU).
 */
final class Translator
{
    private string $locale;

    /** @var array<string, array<string, string>> */
    private array $catalogues = [];

    /** @var array<string, true> */
    private array $missing = [];

    public function __construct(
        private readonly TranslationLoader $loader,
        private readonly string $sourceLocale,
        ?string $locale = null,
    ) {
        $this->locale = $locale ?? $sourceLocale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /** @param array<string, string|int|float> $params */
    public function get(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->locale;
        $message = $this->catalogue($locale)[$key] ?? null;
        $usedLocale = $locale;

        if ($message === null && $locale !== $this->sourceLocale) {
            $message = $this->catalogue($this->sourceLocale)[$key] ?? null;
            $usedLocale = $this->sourceLocale;
        }
        if ($message === null) {
            $this->missing[$key] = true;

            return $key;
        }

        return $params === [] ? $message : $this->format($message, $params, $usedLocale);
    }

    public function has(string $key, ?string $locale = null): bool
    {
        return isset($this->catalogue($locale ?? $this->locale)[$key]);
    }

    /** @return list<string> chiavi richieste ma assenti anche nella lingua sorgente (per il log in sviluppo) */
    public function missingKeys(): array
    {
        return array_keys($this->missing);
    }

    /** @param array<string, string|int|float> $params */
    private function format(string $message, array $params, string $locale): string
    {
        $formatted = MessageFormatter::formatMessage($locale, $message, $params);
        if ($formatted !== false) {
            return $formatted;
        }

        // Messaggio non valido per ICU: sostituzione semplice dei segnaposto.
        $replacements = [];
        foreach ($params as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($message, $replacements);
    }

    /** @return array<string, string> */
    private function catalogue(string $locale): array
    {
        return $this->catalogues[$locale] ??= $this->loader->messages($locale);
    }
}
