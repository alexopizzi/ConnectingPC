<?php

declare(strict_types=1);

/*
 * Front controller PROVVISORIO (v0.1.0): espone solo /health e una pagina tecnica.
 * Router, middleware e aree applicative arrivano in v0.2.0
 * (vault: "30 - Architettura applicativa", "11 - Stato analisi e promemoria").
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\EnvironmentCheck;

$path = App::requestPath();

if ($path === '/health') {
    $payload = ['status' => 'ok', 'version' => App::version()];
    $status = 200;

    $token = (string) ($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? $_GET['token'] ?? '');
    if (isset($_GET['detail']) && (App::isLocal() || App::healthTokenMatches($token))) {
        $checks = (new EnvironmentCheck())->run();
        $payload['environment'] = App::environment();
        $payload['checks'] = $checks;
        if (EnvironmentCheck::hasFailures($checks)) {
            $payload['status'] = 'fail';
            $status = 503;
        }
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($path !== '/') {
    http_response_code(404);
}

// Il dettaglio dei requisiti è mostrato solo in ambiente locale.
$checks = App::isLocal() ? (new EnvironmentCheck())->run() : [];

header('Content-Type: text/html; charset=utf-8');
require APP_BASE_PATH . '/templates/system/skeleton.php';
