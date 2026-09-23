<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\System\HealthController;
use App\Http\Router;

/*
 * Tabella delle rotte (vault "30 - Architettura applicativa", schema degli URL).
 * I segmenti del percorso restano in italiano in tutte le lingue.
 * Middleware: locale · session · csrf · guest · auth:<area> · can:<permesso>
 */
return static function (Router $r): void {
    $r->get('/health', [HealthController::class, 'show'])->name('health');

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
        });

        // Area pubblica: nessuna sessione, nessun cookie
        $r->group(['area' => 'public'], static function (Router $r): void {
            $r->get('', [HomeController::class, 'index'])->name('public.home');
            $r->get('/cerca', [PageController::class, 'search'])->name('public.search');
            $r->get('/{section:' . implode('|', array_keys(PageController::SECTIONS)) . '}', [PageController::class, 'section'])
                ->name('public.section');
        });
    });
};
