<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Core\App;
use App\Core\EnvironmentCheck;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * /health: stato minimo pubblico; con ?detail=1 (solo in locale o con HEALTH_TOKEN) la verifica completa
 * dei requisiti dell'ambiente (vault "32").
 */
final class HealthController extends Controller
{
    public function show(Request $request): Response
    {
        $payload = ['status' => 'ok', 'version' => App::version()];
        $status = 200;

        $token = (string) ($request->header('X-Health-Token') ?? $request->query['token'] ?? '');
        if (isset($request->query['detail']) && (App::isLocal() || App::healthTokenMatches($token))) {
            $checks = (new EnvironmentCheck())->run();
            $payload['environment'] = App::environment();
            $payload['checks'] = $checks;
            if (EnvironmentCheck::hasFailures($checks)) {
                $payload['status'] = 'fail';
                $status = 503;
            }
        }

        return Response::json($payload, $status)->withHeader('Cache-Control', 'no-store');
    }
}
