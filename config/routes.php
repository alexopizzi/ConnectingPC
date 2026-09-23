<?php

declare(strict_types=1);

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\System\HealthController;
use App\Http\Router;

/*
 * Tabella delle rotte (vault "30 - Architettura applicativa", schema degli URL).
 * I segmenti del percorso restano in italiano in tutte le lingue.
 */
return static function (Router $r): void {
    $r->get('/health', [HealthController::class, 'show'])->name('health');

    // "/" → lingua preferita del browser tra quelle pubbliche
    $r->get('/', [HomeController::class, 'root'])->name('root');

    // Area pubblica: nessuna sessione, nessun cookie
    $r->group(['prefix' => '/{locale}', 'middleware' => ['locale'], 'area' => 'public'], static function (Router $r): void {
        $r->get('', [HomeController::class, 'index'])->name('public.home');
        $r->get('/cerca', [PageController::class, 'search'])->name('public.search');
        $r->get('/{section:' . implode('|', array_keys(PageController::SECTIONS)) . '}', [PageController::class, 'section'])
            ->name('public.section');
    });
};
