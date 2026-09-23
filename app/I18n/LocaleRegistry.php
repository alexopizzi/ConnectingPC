<?php

declare(strict_types=1);

namespace App\I18n;

use App\Core\Database;
use App\Core\FileCache;
use Throwable;

/**
 * Lingue dell'interfaccia e dei contenuti (tabella `locales`, D-007/D-023).
 * Se il database non è ancora disponibile usa l'elenco di config/locales.php.
 *
 * @phpstan-type LocaleRow array{code: string, native_name: string, direction: string, is_public: bool, fallback_code: ?string, sort_order: int}
 */
final class LocaleRegistry
{
    private const CACHE_KEY = 'i18n.locales';

    /** @var array<string, LocaleRow>|null */
    private ?array $locales = null;

    /**
     * @param list<array{code: string, native_name: string, direction: string, is_public: bool, fallback_code?: ?string}> $defaults
     */
    public function __construct(
        private readonly ?Database $database,
        private readonly FileCache $cache,
        private readonly array $defaults,
        private readonly string $defaultLocale,
    ) {
    }

    /** @return array<string, LocaleRow> lingue attive, in ordine di visualizzazione */
    public function enabled(): array
    {
        return $this->locales ??= $this->load();
    }

    /** @return array<string, LocaleRow> lingue proposte nel selettore pubblico */
    public function public(): array
    {
        return array_filter($this->enabled(), static fn (array $l): bool => $l['is_public']);
    }

    public function isEnabled(string $code): bool
    {
        return isset($this->enabled()[$code]);
    }

    public function isPublic(string $code): bool
    {
        return isset($this->public()[$code]);
    }

    public function direction(string $code): string
    {
        return ($this->enabled()[$code]['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
    }

    public function nativeName(string $code): string
    {
        return $this->enabled()[$code]['native_name'] ?? $code;
    }

    public function default(): string
    {
        return $this->defaultLocale;
    }

    /** @param list<string> $accepted lingue del browser in ordine di preferenza */
    public function negotiate(array $accepted): string
    {
        foreach ($accepted as $code) {
            if ($this->isPublic($code)) {
                return $code;
            }
        }

        return $this->defaultLocale;
    }

    public function forget(): void
    {
        $this->locales = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    /** @return array<string, LocaleRow> */
    private function load(): array
    {
        $cached = $this->cache->get(self::CACHE_KEY);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $rows = [];
        if ($this->database !== null) {
            try {
                $rows = $this->database->fetchAll(
                    'SELECT code, native_name, direction, is_public, fallback_code, sort_order
                       FROM locales WHERE is_enabled = 1 ORDER BY sort_order, code'
                );
            } catch (Throwable) {
                $rows = [];
            }
        }

        if ($rows === []) {
            // Database non pronto: elenco predefinito, non memorizzato in cache.
            return $this->index($this->defaults);
        }

        $locales = $this->index($rows);
        $this->cache->set(self::CACHE_KEY, $locales, 3600);

        return $locales;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, LocaleRow>
     */
    private function index(array $rows): array
    {
        $locales = [];
        foreach ($rows as $position => $row) {
            $code = (string) $row['code'];
            $locales[$code] = [
                'code' => $code,
                'native_name' => (string) $row['native_name'],
                'direction' => $row['direction'] === 'rtl' ? 'rtl' : 'ltr',
                'is_public' => (bool) $row['is_public'],
                'fallback_code' => isset($row['fallback_code']) ? (string) $row['fallback_code'] : null,
                'sort_order' => (int) ($row['sort_order'] ?? $position),
            ];
        }

        return $locales;
    }
}
