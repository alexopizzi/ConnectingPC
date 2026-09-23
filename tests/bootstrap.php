<?php

declare(strict_types=1);

/*
 * Bootstrap dei test: stesso bootstrap dell'applicazione, con le variabili di phpunit.xml.dist
 * (database connectingpc_test, APP_ENV=testing).
 */

$GLOBALS['container'] = require dirname(__DIR__) . '/app/bootstrap.php';
