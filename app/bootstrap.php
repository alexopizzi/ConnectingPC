<?php

declare(strict_types=1);

/*
 * Bootstrap comune a web (public/index.php) e CLI (bin/console).
 * Vedi vault: "30 - Architettura applicativa".
 */

define('APP_BASE_PATH', dirname(__DIR__));

$composerAutoload = APP_BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    // Fallback PSR-4 finché `composer install` non è stato eseguito.
    spl_autoload_register(static function (string $class): void {
        if (!str_starts_with($class, 'App\\')) {
            return;
        }
        $file = APP_BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

App\Core\Env::load(APP_BASE_PATH . '/.env');

// Logica e database in UTC; la conversione a APP_TIMEZONE avviene solo in presentazione.
date_default_timezone_set('UTC');

error_reporting(E_ALL);
ini_set('display_errors', App\Core\App::isDebug() ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(APP_BASE_PATH . '/storage/logs') && is_writable(APP_BASE_PATH . '/storage/logs')) {
    ini_set('error_log', APP_BASE_PATH . '/storage/logs/php-' . gmdate('Y-m-d') . '.log');
}
