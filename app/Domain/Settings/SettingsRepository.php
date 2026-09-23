<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use App\Core\Database;
use App\Core\FileCache;
use Throwable;

/**
 * Impostazioni modificabili dagli amministratori (tabella `settings`, valori JSON),
 * con i valori predefiniti di config/app.php (vault "34 - Configurazione").
 */
final class SettingsRepository
{
    private const CACHE_KEY = 'settings.all';

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    /** @param array<string, mixed> $defaults */
    public function __construct(
        private readonly Database $database,
        private readonly FileCache $cache,
        private readonly array $defaults,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : ($this->defaults[$key] ?? $default);
    }

    public function set(string $key, mixed $value, ?int $userId = null): void
    {
        $this->database->execute(
            'INSERT INTO settings (`key`, value, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by)',
            [$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $userId],
        );
        $this->values = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }
        $cached = $this->cache->get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $this->values = $cached;
        }
        try {
            $values = [];
            foreach ($this->database->fetchAll('SELECT `key`, value FROM settings') as $row) {
                $values[(string) $row['key']] = json_decode((string) $row['value'], true);
            }
        } catch (Throwable) {
            return $this->values = [];
        }
        $this->cache->set(self::CACHE_KEY, $values, 3600);

        return $this->values = $values;
    }
}
