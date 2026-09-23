<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Core\Database;

/*
 * Impostazioni predefinite (config/app.php → settings_defaults). Inserisce solo le chiavi mancanti.
 */
return static function (Container $c): void {
    $db = $c->get(Database::class);
    foreach ((array) $c->get(Config::class)->get('app.settings_defaults', []) as $key => $value) {
        $db->execute(
            'INSERT IGNORE INTO settings (`key`, value) VALUES (?, ?)',
            [$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
        );
    }
};
