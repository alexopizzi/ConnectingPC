<?php

declare(strict_types=1);

use App\Core\Env;

/*
 * Configurazione applicativa (vault "34 - Configurazione"). I valori d'ambiente arrivano da .env.
 */
return [
    'name' => 'ConnectingPC',
    'url' => Env::get('APP_URL', 'http://localhost'),
    'default_locale' => Env::get('APP_DEFAULT_LOCALE', 'it'),
    'timezone' => Env::get('APP_TIMEZONE', 'Europe/Rome'),
    'log_level' => Env::get('LOG_LEVEL', 'warning'),

    'session' => [
        'name' => Env::get('SESSION_NAME', 'cpc_sid'),
        'idle_minutes' => (int) Env::get('SESSION_IDLE_MINUTES', '60'),
        'admin_idle_minutes' => (int) Env::get('SESSION_ADMIN_IDLE_MINUTES', '30'),
        'absolute_hours' => 12,
    ],

    // Alias dei middleware usati in config/routes.php
    'middleware' => [
        'locale' => App\Http\Middleware\SetLocale::class,
        'session' => App\Http\Middleware\StartSession::class,
        'csrf' => App\Http\Middleware\VerifyCsrf::class,
        'auth' => App\Http\Middleware\Authenticate::class,
        'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
        'can' => App\Http\Middleware\Authorize::class,
        'throttle' => App\Http\Middleware\ThrottleRequests::class,
    ],

    // Valori predefiniti delle impostazioni modificabili in admin (tabella settings)
    'settings_defaults' => [
        'publication.default_policy' => 'direct',   // D-022
        'quality.review_interval_days' => 180,
        'quality.warning_days' => 30,
        // Q-02: recapiti ufficiali dei gestori — PROVVISORI (riempitivo), modificabili in admin → Impostazioni
        'contacts.managers' => [
            'name' => 'Gestori della piattaforma ConnectingPC',
            'email' => 'gestori@example.org',
            'phone' => '+39 0523 000 000',
            'hours' => 'Lunedì–venerdì 9:00–13:00',
            'address' => 'Indirizzo da definire, Piacenza',
        ],
        'search.log_zero_results' => false,         // Q-15
    ],
];
