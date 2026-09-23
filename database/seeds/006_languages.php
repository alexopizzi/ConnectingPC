<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Lingue parlate selezionabili nei filtri ("qualcuno che parla la mia lingua").
 * Indipendenti dalle lingue dell'interfaccia; i nomi arrivano da ICU in ogni lingua.
 */
return static function (Container $c): void {
    $codes = ['it', 'en', 'fr', 'ar', 'es', 'pt', 'sq', 'ro', 'uk', 'ru', 'pl', 'tr', 'zh', 'hi', 'pa', 'ur', 'bn', 'wo', 'ff', 'ti', 'am', 'so', 'ha', 'yo', 'tw', 'ln', 'fa', 'ps', 'ku', 'ta', 'si'];
    $db = $c->get(Database::class);
    foreach ($codes as $order => $code) {
        $db->execute('INSERT IGNORE INTO languages (code, is_active, sort_order) VALUES (?, 1, ?)', [$code, $order + 1]);
    }
};
