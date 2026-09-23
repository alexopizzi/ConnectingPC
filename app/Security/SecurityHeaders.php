<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\App;
use App\Core\Env;
use App\Http\Request;
use App\Http\Response;

/**
 * Header di sicurezza applicati a ogni risposta dinamica (vault "72 - Sicurezza").
 * Le risposte possono sovrascriverli impostandoli prima (es. Referrer-Policy: no-referrer sulle pagine con token).
 */
final class SecurityHeaders
{
    public static function apply(Request $request, Response $response): Response
    {
        $defaults = [
            'Content-Security-Policy' => self::contentSecurityPolicy(),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(self), camera=(), microphone=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];
        if ($request->isSecure() && App::environment() === 'production') {
            $defaults['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($defaults as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    private static function contentSecurityPolicy(): string
    {
        $tileHost = parse_url((string) Env::get('MAP_TILE_URL', ''), PHP_URL_HOST);
        $images = "'self' data:" . (is_string($tileHost) && $tileHost !== '' ? ' https://' . $tileHost : '');

        return implode('; ', [
            "default-src 'self'",
            'img-src ' . $images,
            "style-src 'self'",
            "script-src 'self'",
            "connect-src 'self'",
            "font-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
