<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Core\Database;

/*
 * Lingue attive (D-023): italiano, inglese, francese, arabo.
 * Non sovrascrive le scelte fatte in admin (attivazione/pubblicazione), aggiorna solo nome e direzione.
 */
return static function (Container $c): void {
    $db = $c->get(Database::class);
    foreach ((array) $c->get(Config::class)->get('locales.defaults', []) as $locale) {
        $db->execute(
            'INSERT INTO locales (code, native_name, direction, is_enabled, is_public, fallback_code, sort_order)
             VALUES (?, ?, ?, 1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE native_name = VALUES(native_name), direction = VALUES(direction)',
            [
                $locale['code'],
                $locale['native_name'],
                $locale['direction'],
                $locale['is_public'] ? 1 : 0,
                $locale['fallback_code'] ?? null,
                $locale['sort_order'] ?? 0,
            ],
        );
    }
};
