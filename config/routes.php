<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api\CatalogApiController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\CommunityController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MediatorController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\System\HealthController;
use App\Http\Controllers\System\TileController;
use App\Http\Router;

/*
 * Tabella delle rotte (vault "30 - Architettura applicativa", schema degli URL).
 * I segmenti del percorso restano in italiano in tutte le lingue.
 * Middleware: locale · session · csrf · guest · auth:<area> · can:<permesso>
 */
return static function (Router $r): void {
    $r->get('/health', [HealthController::class, 'show'])->name('health');

    // Proxy delle tile della mappa (vault "61"): attivo se MAP_TILE_URL=/tiles/{z}/{x}/{y}.png
    $r->get('/tiles/{z:[0-9]{1,2}}/{x:[0-9]{1,6}}/{y:[0-9]{1,6}}.png', [TileController::class, 'show'])->name('tiles')
        ->middleware('throttle:900,60');

    // API pubbliche in sola lettura (vault "36")
    $r->group(['prefix' => '/api/v1', 'middleware' => ['throttle:240,60'], 'area' => 'api'], static function (Router $r): void {
        $r->get('/map/points', [CatalogApiController::class, 'mapPoints'])->name('api.map.points');
        $r->get('/search/suggest', [CatalogApiController::class, 'suggest'])->name('api.search.suggest');
        $r->get('/needs', [CatalogApiController::class, 'needs'])->name('api.needs');
        $r->get('/services', [CatalogApiController::class, 'services'])->name('api.services');
    });

    // "/" → lingua preferita del browser tra quelle pubbliche
    $r->get('/', [HomeController::class, 'root'])->name('root');

    // Area amministrativa: interfaccia in italiano, sessione breve, permesso admin.access
    $r->group(['prefix' => '/admin', 'middleware' => ['locale:it', 'session', 'csrf', 'auth:admin', 'can:admin.access'], 'area' => 'admin'], static function (Router $r): void {
        $r->get('', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

        $r->group(['middleware' => ['can:users.manage']], static function (Router $r): void {
            $r->get('/utenti', [Admin\UserController::class, 'index'])->name('admin.users.index');
            $r->get('/utenti/nuovo', [Admin\UserController::class, 'create'])->name('admin.users.create');
            $r->post('/utenti', [Admin\UserController::class, 'store'])->name('admin.users.store');
            $r->get('/utenti/{id}', [Admin\UserController::class, 'show'])->name('admin.users.show');
            $r->post('/utenti/{id}/stato', [Admin\UserController::class, 'updateStatus'])->name('admin.users.status');
            $r->post('/utenti/{id}/invito', [Admin\UserController::class, 'resendInvitation'])->name('admin.users.invite');
            $r->post('/utenti/{id}/ruoli', [Admin\UserController::class, 'assignRole'])->name('admin.users.roles.assign');
            $r->post('/utenti/{id}/ruoli/{assignment:[1-9][0-9]{0,9}}/revoca', [Admin\UserController::class, 'revokeRole'])->name('admin.users.roles.revoke');
            $r->get('/ruoli', [Admin\RoleController::class, 'index'])->name('admin.roles.index');
        });

        $r->get('/audit', [Admin\AuditController::class, 'index'])->name('admin.audit.index')->middleware('can:audit.view');
    });

    $r->group(['prefix' => '/{locale}', 'middleware' => ['locale']], static function (Router $r): void {
        // Autenticazione (sessione avviata solo qui e nelle aree riservate)
        $r->group(['middleware' => ['session', 'csrf'], 'area' => 'auth'], static function (Router $r): void {
            $r->get('/accedi', [LoginController::class, 'show'])->name('auth.login')->middleware('guest');
            $r->post('/accedi', [LoginController::class, 'login'])->name('auth.login.submit')->middleware('guest');
            $r->post('/esci', [LoginController::class, 'logout'])->name('auth.logout');
            $r->get('/password/recupero', [PasswordController::class, 'forgot'])->name('auth.forgot')->middleware('guest');
            $r->post('/password/recupero', [PasswordController::class, 'sendLink'])->name('auth.forgot.submit')->middleware('guest');
            $r->get('/password/nuova', [PasswordController::class, 'resetForm'])->name('auth.reset');
            $r->post('/password/nuova', [PasswordController::class, 'reset'])->name('auth.reset.submit');
            $r->get('/invito', [InvitationController::class, 'show'])->name('auth.invitation');
            $r->post('/invito', [InvitationController::class, 'accept'])->name('auth.invitation.submit');
        });

        // Area riservata delle organizzazioni
        $r->group(['prefix' => '/area-riservata', 'middleware' => ['session', 'csrf', 'auth:portal'], 'area' => 'portal'], static function (Router $r): void {
            $r->get('', [Portal\DashboardController::class, 'index'])->name('portal.dashboard');
            $r->get('/mediatori', [Portal\MediatorController::class, 'index'])->name('portal.mediators');
        });

        // Area pubblica: nessuna sessione, nessun cookie
        $r->group(['area' => 'public'], static function (Router $r): void {
            $r->get('', [HomeController::class, 'index'])->name('public.home');
            $r->get('/cerca', [CatalogController::class, 'search'])->name('public.search');
            $r->get('/bisogni/{code:[a-z_]{2,50}}', [CatalogController::class, 'need'])->name('public.need');
            $r->get('/servizi', [CatalogController::class, 'services'])->name('public.services');
            $r->get('/mappa', [CatalogController::class, 'map'])->name('public.map');
            $r->get('/servizi/{id}', [CatalogController::class, 'service'])->name('public.service');
            $r->get('/organizzazioni/{id}', [CatalogController::class, 'organization'])->name('public.organization');
            $r->get('/associazioni-comunita', [CommunityController::class, 'index'])->name('public.communities');
            $r->get('/mediatori', [MediatorController::class, 'index'])->name('public.mediators');
            $r->get('/{section:' . implode('|', array_keys(PageController::SECTIONS)) . '}', [PageController::class, 'section'])
                ->name('public.section');
        });
    });
};
