<?php

declare(strict_types=1);

use App\Audit\AuditLogger;
use App\Auth\AccountMailer;
use App\Auth\AccountService;
use App\Auth\Auth;
use App\Auth\LoginService;
use App\Auth\TokenService;
use App\Authorization\DatabaseAuthorizationData;
use App\Authorization\Gate;
use App\Core\App;
use App\Core\Mailer;
use App\Domain\Users\UserRepository;
use App\Domain\Users\UserService;
use App\Security\PasswordHasher;
use App\Security\PasswordPolicy;
use App\Security\RateLimiter;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\Env;
use App\Core\FileCache;
use App\Core\Logger;
use App\Core\Session;
use App\Core\UrlGenerator;
use App\Core\View;
use App\Domain\Settings\SettingsRepository;
use App\Http\ErrorRenderer;
use App\Http\Router;
use App\I18n\LocaleRegistry;
use App\I18n\TranslationImporter;
use App\I18n\TranslationLoader;
use App\I18n\Translator;
use App\Security\Csrf;

/*
 * Registrazione dei servizi. Ogni servizio è creato alla prima richiesta e riusato.
 */
return static function (Container $c): void {
    $c->set(Config::class, static fn () => new Config(APP_BASE_PATH . '/config'));

    $c->set(Logger::class, static fn (Container $c) => new Logger(
        APP_BASE_PATH . '/storage/logs',
        (string) $c->get(Config::class)->get('app.log_level', 'warning'),
        bin2hex(random_bytes(8)),
    ));

    $c->set(Database::class, static fn () => Database::fromEnv());

    // I test usano una cache separata per non mescolare dati con l'ambiente di sviluppo.
    $c->set(FileCache::class, static fn () => new FileCache(
        APP_BASE_PATH . '/storage/cache' . (App::environment() === 'testing' ? '/testing' : ''),
    ));

    $c->set(Session::class, static fn (Container $c) => new Session(
        APP_BASE_PATH . '/storage/sessions',
        (string) $c->get(Config::class)->get('app.session.name', 'cpc_sid'),
        str_starts_with((string) Env::get('APP_URL', ''), 'https://'),
        PHP_SAPI === 'cli' ? '/' : (App::baseUrl() !== '' ? App::baseUrl() : '/'),
    ));

    $c->set(Csrf::class, static fn (Container $c) => new Csrf($c->get(Session::class)));

    $c->set(Router::class, static function () {
        $router = new Router();
        (require APP_BASE_PATH . '/config/routes.php')($router);

        return $router;
    });

    $c->set(UrlGenerator::class, static fn (Container $c) => new UrlGenerator(
        $c->get(Router::class),
        PHP_SAPI === 'cli' ? '' : App::baseUrl(),
        (string) $c->get(Config::class)->get('app.url'),
        APP_BASE_PATH . '/public',
    ));

    $c->set(LocaleRegistry::class, static fn (Container $c) => new LocaleRegistry(
        $c->get(Database::class),
        $c->get(FileCache::class),
        (array) $c->get(Config::class)->get('locales.defaults', []),
        (string) $c->get(Config::class)->get('app.default_locale', 'it'),
    ));

    $c->set(TranslationLoader::class, static fn (Container $c) => new TranslationLoader(
        $c->get(Database::class),
        $c->get(FileCache::class),
        APP_BASE_PATH . '/lang',
        (string) $c->get(Config::class)->get('app.default_locale', 'it'),
    ));

    $c->set(Translator::class, static fn (Container $c) => new Translator(
        $c->get(TranslationLoader::class),
        (string) $c->get(Config::class)->get('app.default_locale', 'it'),
    ));

    $c->set(TranslationImporter::class, static fn (Container $c) => new TranslationImporter(
        $c->get(Database::class),
        $c->get(TranslationLoader::class),
        (string) $c->get(Config::class)->get('app.default_locale', 'it'),
    ));

    $c->set(View::class, static fn (Container $c) => new View(
        APP_BASE_PATH . '/templates',
        $c->get(Translator::class),
        $c->get(UrlGenerator::class),
        $c->get(LocaleRegistry::class),
        $c->get(Session::class),
        $c->get(Csrf::class),
    ));

    $c->set(AuditLogger::class, static fn (Container $c) => new AuditLogger(
        $c->get(Database::class),
        (string) Env::get('APP_KEY', ''),
        $c->get(Logger::class)->requestId(),
    ));

    $c->set(SettingsRepository::class, static fn (Container $c) => new SettingsRepository(
        $c->get(Database::class),
        $c->get(FileCache::class),
        (array) $c->get(Config::class)->get('app.settings_defaults', []),
    ));

    $c->set(ErrorRenderer::class, static fn (Container $c) => new ErrorRenderer($c));

    // --- Sicurezza, autenticazione, autorizzazione (v0.3.0) ---

    $c->set(PasswordHasher::class, static fn () => new PasswordHasher());

    $c->set(PasswordPolicy::class, static fn () => new PasswordPolicy(APP_BASE_PATH . '/config/common-passwords.txt'));

    $c->set(RateLimiter::class, static fn (Container $c) => new RateLimiter(
        $c->get(Database::class),
        (string) Env::get('APP_KEY', 'connectingpc'),
    ));

    $c->set(TokenService::class, static fn (Container $c) => new TokenService($c->get(Database::class)));

    $c->set(Mailer::class, static fn (Container $c) => new Mailer($c->get(Logger::class)));

    $c->set(UserRepository::class, static fn (Container $c) => new UserRepository($c->get(Database::class)));

    $c->set(DatabaseAuthorizationData::class, static fn (Container $c) => new DatabaseAuthorizationData($c->get(Database::class)));

    $c->set(Gate::class, static fn (Container $c) => new Gate(
        $c->get(DatabaseAuthorizationData::class),
        (string) $c->get(SettingsRepository::class)->get('publication.default_policy', 'direct'),
        (array) $c->get(Config::class)->get('permissions.organization_edit_permissions', []),
        (array) $c->get(Config::class)->get('permissions.organization_publish_permissions', []),
    ));

    $c->set(Auth::class, static fn (Container $c) => new Auth(
        $c->get(Session::class),
        $c->get(UserRepository::class),
        $c->get(Csrf::class),
        $c->get(AuditLogger::class),
        (int) $c->get(Config::class)->get('app.session.absolute_hours', 12),
    ));

    $c->set(LoginService::class, static fn (Container $c) => new LoginService(
        $c->get(UserRepository::class),
        $c->get(PasswordHasher::class),
        $c->get(RateLimiter::class),
        $c->get(Gate::class),
        $c->get(DatabaseAuthorizationData::class),
        $c->get(Auth::class),
        $c->get(AuditLogger::class),
    ));

    $c->set(AccountMailer::class, static fn (Container $c) => new AccountMailer(
        $c->get(Mailer::class),
        $c->get(Translator::class),
        $c->get(UrlGenerator::class),
        $c->get(LocaleRegistry::class),
    ));

    $c->set(AccountService::class, static fn (Container $c) => new AccountService(
        $c->get(Database::class),
        $c->get(UserRepository::class),
        $c->get(TokenService::class),
        $c->get(PasswordHasher::class),
        $c->get(PasswordPolicy::class),
        $c->get(RateLimiter::class),
        $c->get(AccountMailer::class),
        $c->get(AuditLogger::class),
    ));

    $c->set(UserService::class, static fn (Container $c) => new UserService(
        $c->get(Database::class),
        $c->get(UserRepository::class),
        $c->get(Gate::class),
        $c->get(DatabaseAuthorizationData::class),
        $c->get(AuditLogger::class),
        (array) $c->get(Config::class)->get('permissions.protected_roles', []),
    ));
};
