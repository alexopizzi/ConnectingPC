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

        // Catalogo: lettura con organizations.view_all; le modifiche sono verificate dai servizi di dominio
        $r->group(['middleware' => ['can:organizations.view_all']], static function (Router $r): void {
            $r->get('/organizzazioni', [Admin\OrganizationController::class, 'index'])->name('admin.organizations.index');
            $r->get('/organizzazioni/nuova', [Admin\OrganizationController::class, 'create'])->name('admin.organizations.create');
            $r->post('/organizzazioni', [Admin\OrganizationController::class, 'store'])->name('admin.organizations.store');
            $r->get('/organizzazioni/{id}', [Admin\OrganizationController::class, 'show'])->name('admin.organizations.show');
            $r->post('/organizzazioni/{id}', [Admin\OrganizationController::class, 'update'])->name('admin.organizations.update');
            $r->post('/organizzazioni/{id}/stato', [Admin\OrganizationController::class, 'updateStatus'])->name('admin.organizations.status');
            $r->post('/organizzazioni/{id}/testi', [Admin\OrganizationController::class, 'saveTexts'])->name('admin.organizations.texts');
            $r->post('/organizzazioni/{id}/collegamenti', [Admin\OrganizationController::class, 'saveLinks'])->name('admin.organizations.links');
            $r->post('/organizzazioni/{id}/recapiti', [Admin\OrganizationController::class, 'saveContacts'])->name('admin.organizations.contacts');

            $r->get('/organizzazioni/{id}/sedi/nuova', [Admin\SiteController::class, 'create'])->name('admin.sites.create');
            $r->post('/organizzazioni/{id}/sedi', [Admin\SiteController::class, 'store'])->name('admin.sites.store');
            $r->post('/organizzazioni/{id}/geocodifica', [Admin\SiteController::class, 'geocode'])->name('admin.sites.geocode')->middleware('throttle:30,60');
            $r->get('/sedi/{id}', [Admin\SiteController::class, 'show'])->name('admin.sites.show');
            $r->post('/sedi/{id}', [Admin\SiteController::class, 'update'])->name('admin.sites.update');
            $r->post('/sedi/{id}/orari', [Admin\SiteController::class, 'saveHours'])->name('admin.sites.hours');
            $r->post('/sedi/{id}/recapiti', [Admin\SiteController::class, 'saveContacts'])->name('admin.sites.contacts');
            $r->post('/sedi/{id}/testi', [Admin\SiteController::class, 'saveTexts'])->name('admin.sites.texts');

            $r->get('/servizi', [Admin\ServiceController::class, 'index'])->name('admin.services.index');
            $r->get('/organizzazioni/{id}/servizi/nuovo', [Admin\ServiceController::class, 'create'])->name('admin.services.create');
            $r->post('/organizzazioni/{id}/servizi', [Admin\ServiceController::class, 'store'])->name('admin.services.store');
            $r->get('/servizi/{id}', [Admin\ServiceController::class, 'show'])->name('admin.services.show');
            $r->post('/servizi/{id}', [Admin\ServiceController::class, 'update'])->name('admin.services.update');
            $r->post('/servizi/{id}/testi', [Admin\ServiceController::class, 'saveTexts'])->name('admin.services.texts');
        });

        $r->group(['prefix' => '/mediatori', 'middleware' => ['can:mediators.manage']], static function (Router $r): void {
            $r->get('', [Admin\MediatorController::class, 'index'])->name('admin.mediators.index');
            $r->get('/nuovo', [Admin\MediatorController::class, 'create'])->name('admin.mediators.create');
            $r->post('', [Admin\MediatorController::class, 'store'])->name('admin.mediators.store');
            $r->get('/{id}', [Admin\MediatorController::class, 'show'])->name('admin.mediators.show');
            $r->post('/{id}', [Admin\MediatorController::class, 'update'])->name('admin.mediators.update');
            $r->post('/{id}/testi', [Admin\MediatorController::class, 'saveTexts'])->name('admin.mediators.texts');
            $r->post('/{id}/recapiti', [Admin\MediatorController::class, 'saveContacts'])->name('admin.mediators.contacts');
            $r->post('/{id}/consenso/revoca', [Admin\MediatorController::class, 'revokeConsent'])->name('admin.mediators.revoke');
        });

        $r->group(['middleware' => ['can:taxonomy.manage']], static function (Router $r): void {
            $r->get('/sinonimi', [Admin\CatalogToolsController::class, 'synonyms'])->name('admin.synonyms.index');
            $r->get('/tassonomie', [Admin\TaxonomyController::class, 'index'])->name('admin.taxonomy.index');
            $r->get('/tassonomie/{type:needs|categories|organization_types|communities|mediation_domains}', [Admin\TaxonomyController::class, 'list'])->name('admin.taxonomy.list');
            $r->get('/tassonomie/{type:needs|categories|organization_types|communities|mediation_domains}/nuova', [Admin\TaxonomyController::class, 'create'])->name('admin.taxonomy.create');
            $r->post('/tassonomie/{type:needs|categories|organization_types|communities|mediation_domains}', [Admin\TaxonomyController::class, 'store'])->name('admin.taxonomy.store');
            $r->get('/tassonomie/{type:needs|categories|organization_types|communities|mediation_domains}/{id}', [Admin\TaxonomyController::class, 'edit'])->name('admin.taxonomy.edit');
            $r->post('/tassonomie/{type:needs|categories|organization_types|communities|mediation_domains}/{id}', [Admin\TaxonomyController::class, 'update'])->name('admin.taxonomy.update');
            $r->post('/sinonimi', [Admin\CatalogToolsController::class, 'addSynonym'])->name('admin.synonyms.store');
            $r->post('/sinonimi/{id}/elimina', [Admin\CatalogToolsController::class, 'deleteSynonym'])->name('admin.synonyms.delete');
        });
        $r->group(['middleware' => ['can:content.review']], static function (Router $r): void {
            $r->get('/revisione', [Admin\ReviewController::class, 'index'])->name('admin.review.index');
            $r->post('/revisione/{type:organization|site|service|mediator}/{id}', [Admin\ReviewController::class, 'decide'])->name('admin.review.decide');
            $r->post('/revisione/{type:organization|site|service|mediator}/{id}/traduzione', [Admin\ReviewController::class, 'approveTranslation'])->name('admin.review.translation');
        });
        $r->group(['prefix' => '/richieste', 'middleware' => ['can:requests.manage']], static function (Router $r): void {
            $r->get('', [Admin\InboundRequestController::class, 'index'])->name('admin.requests.index');
            $r->get('/nuova', [Admin\InboundRequestController::class, 'create'])->name('admin.requests.create');
            $r->post('', [Admin\InboundRequestController::class, 'store'])->name('admin.requests.store');
            $r->get('/{id}', [Admin\InboundRequestController::class, 'show'])->name('admin.requests.show');
            $r->post('/{id}', [Admin\InboundRequestController::class, 'update'])->name('admin.requests.update');
        });
        // Stringhe dell'interfaccia: i permessi per lingua li verifica UiStringEditor
        $r->get('/stringhe', [Admin\UiStringController::class, 'index'])->name('admin.strings.index');
        $r->post('/stringhe/{id}', [Admin\UiStringController::class, 'save'])->name('admin.strings.save');
        $r->get('/esporta/servizi.csv', [Admin\ExportController::class, 'services'])->name('admin.export.services')->middleware('can:organizations.view_all');
        $r->get('/esporta/organizzazioni.csv', [Admin\ExportController::class, 'organizations'])->name('admin.export.organizations')->middleware('can:organizations.view_all');
        $r->get('/modifiche-recenti', [Admin\CatalogToolsController::class, 'recentChanges'])->name('admin.recent.index')->middleware('can:content.review');
        $r->get('/qualita', [Admin\CatalogToolsController::class, 'quality'])->name('admin.quality.index')->middleware('can:quality.view');
        $r->get('/impostazioni', [Admin\CatalogToolsController::class, 'settings'])->name('admin.settings.index')->middleware('can:settings.manage');
        $r->post('/impostazioni', [Admin\CatalogToolsController::class, 'saveSettings'])->name('admin.settings.update')->middleware('can:settings.manage');
        $r->post('/impostazioni/piattaforma', [Admin\CatalogToolsController::class, 'saveOptions'])->name('admin.settings.options')->middleware('can:settings.manage');
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

            // Gestione dei contenuti della propria organizzazione: stessi servizi di dominio dell'admin (D-034),
            // lettura limitata alle proprie organizzazioni, scrittura verificata dal Gate
            $r->get('/organizzazioni/{id}', [Portal\OrganizationController::class, 'show'])->name('portal.organizations.show');
            $r->post('/organizzazioni/{id}', [Portal\OrganizationController::class, 'update'])->name('portal.organizations.update');
            $r->post('/organizzazioni/{id}/testi', [Portal\OrganizationController::class, 'saveTexts'])->name('portal.organizations.texts');
            $r->post('/organizzazioni/{id}/collegamenti', [Portal\OrganizationController::class, 'saveLinks'])->name('portal.organizations.links');
            $r->post('/organizzazioni/{id}/recapiti', [Portal\OrganizationController::class, 'saveContacts'])->name('portal.organizations.contacts');

            $r->get('/organizzazioni/{id}/sedi/nuova', [Portal\SiteController::class, 'create'])->name('portal.sites.create');
            $r->post('/organizzazioni/{id}/sedi', [Portal\SiteController::class, 'store'])->name('portal.sites.store');
            $r->post('/organizzazioni/{id}/geocodifica', [Portal\SiteController::class, 'geocode'])->name('portal.sites.geocode')->middleware('throttle:30,60');
            $r->get('/sedi/{id}', [Portal\SiteController::class, 'show'])->name('portal.sites.show');
            $r->post('/sedi/{id}', [Portal\SiteController::class, 'update'])->name('portal.sites.update');
            $r->post('/sedi/{id}/orari', [Portal\SiteController::class, 'saveHours'])->name('portal.sites.hours');
            $r->post('/sedi/{id}/recapiti', [Portal\SiteController::class, 'saveContacts'])->name('portal.sites.contacts');
            $r->post('/sedi/{id}/testi', [Portal\SiteController::class, 'saveTexts'])->name('portal.sites.texts');

            $r->get('/organizzazioni/{id}/servizi/nuovo', [Portal\ServiceController::class, 'create'])->name('portal.services.create');
            $r->post('/organizzazioni/{id}/servizi', [Portal\ServiceController::class, 'store'])->name('portal.services.store');
            $r->get('/servizi/{id}', [Portal\ServiceController::class, 'show'])->name('portal.services.show');
            $r->post('/servizi/{id}', [Portal\ServiceController::class, 'update'])->name('portal.services.update');
            $r->post('/servizi/{id}/testi', [Portal\ServiceController::class, 'saveTexts'])->name('portal.services.texts');

            $r->get('/gestione-mediatori/nuovo', [Portal\ManagedMediatorController::class, 'create'])->name('portal.mediators.create');
            $r->post('/gestione-mediatori', [Portal\ManagedMediatorController::class, 'store'])->name('portal.mediators.store');
            $r->get('/gestione-mediatori/{id}', [Portal\ManagedMediatorController::class, 'show'])->name('portal.mediators.show');
            $r->post('/gestione-mediatori/{id}', [Portal\ManagedMediatorController::class, 'update'])->name('portal.mediators.update');
            $r->post('/gestione-mediatori/{id}/testi', [Portal\ManagedMediatorController::class, 'saveTexts'])->name('portal.mediators.texts');
            $r->post('/gestione-mediatori/{id}/recapiti', [Portal\ManagedMediatorController::class, 'saveContacts'])->name('portal.mediators.contacts');
            $r->post('/gestione-mediatori/{id}/consenso/revoca', [Portal\ManagedMediatorController::class, 'revokeConsent'])->name('portal.mediators.revoke');
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
