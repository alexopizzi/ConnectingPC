<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Audit\AuditLogger;
use App\Core\App;
use App\Core\Env;
use App\Core\EnvironmentCheck;
use App\Core\FileCache;
use App\Core\Session;
use App\Database\Installer;
use App\Domain\Users\SuperAdminCreator;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;
use Throwable;

/**
 * Pagina di manutenzione per hosting senza SSH (vault "80", "81", D-041): verifica dei requisiti, installazione
 * e aggiornamento del database, primo super amministratore, cache, OPcache, modalità manutenzione.
 * - Esiste solo se MAINTENANCE_TOKEN è impostato (almeno 32 caratteri); altrimenti risponde 404.
 * - Il token si scrive in un modulo (mai nell'URL); l'accesso dura 30 minuti nella sessione;
 *   dopo 10 tentativi errati in 15 minuti lo stesso IP riceve 429 (contatore su file, non sul database).
 * - Raggiungibile anche con il sito in manutenzione; non usa il database per mostrarsi (prima installazione).
 */
final class MaintenanceController extends Controller
{
    private const SESSION_KEY = 'maintenance_until';
    private const TTL = 1800;
    private const MIN_TOKEN_LENGTH = 32;
    private const MAX_FAILED_ATTEMPTS = 10;
    private const ATTEMPT_WINDOW = 900;

    public function show(Request $request): Response
    {
        $this->ensureEnabled();
        $session = $this->container->get(Session::class);
        if (!$this->authorized()) {
            return $this->page('system/maintenance-login', ['pageTitle' => $this->t('admin.maintenance.title')]);
        }

        $migrations = null;
        $dbError = null;
        $superAdmin = null;
        try {
            $migrations = $this->container->get(Installer::class)->migrationStatus();
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }
        if ($dbError === null) {
            try {
                $superAdmin = $this->container->get(SuperAdminCreator::class)->exists();
            } catch (Throwable) {
                $superAdmin = null; // tabelle non ancora create: prima si installa
            }
        }

        return $this->page('system/maintenance', [
            'pageTitle' => $this->t('admin.maintenance.title'),
            'version' => App::version(),
            'environment' => App::environment(),
            'checks' => (new EnvironmentCheck())->run(false),
            'migrations' => $migrations,
            'dbError' => $dbError,
            'superAdmin' => $superAdmin,
            'down' => is_file($this->downFile()),
            'demoAllowed' => Installer::demoDataAllowed(),
            'log' => $session->getFlash('maintenance_log', []),
            'activationLink' => $session->getFlash('maintenance_link'),
            'expiresAt' => (int) $session->get(self::SESSION_KEY),
        ]);
    }

    public function login(Request $request): Response
    {
        $this->ensureEnabled();
        $session = $this->container->get(Session::class);
        $cache = $this->container->get(FileCache::class);
        $attemptsKey = 'maintenance.attempts.' . hash('sha256', $request->ip());
        $failed = (int) $cache->get($attemptsKey, 0);
        if ($failed >= self::MAX_FAILED_ATTEMPTS) {
            throw new HttpException(429, '', ['Retry-After' => (string) self::ATTEMPT_WINDOW]);
        }
        if (hash_equals($this->configuredToken(), $request->raw('token'))) {
            $cache->forget($attemptsKey);
            $session->regenerate();
            $session->set(self::SESSION_KEY, time() + self::TTL);
            $this->audit('maintenance.login', $request);
        } else {
            $cache->set($attemptsKey, $failed + 1, self::ATTEMPT_WINDOW);
            $this->audit('maintenance.login_failed', $request);
            $this->flash('error', $this->t('admin.maintenance.wrong_token'));
        }

        return $this->redirectTo('system.maintenance');
    }

    public function action(Request $request): Response
    {
        $this->ensureEnabled();
        if (!$this->authorized()) {
            throw new HttpException(403);
        }
        $session = $this->container->get(Session::class);
        $action = $request->string('action');
        $log = [];

        try {
            switch ($action) {
                case 'install':
                    $this->container->get(Installer::class)->run(static function (string $line) use (&$log): void {
                        $log[] = $line;
                    });
                    $this->resetOpcache();
                    $this->flash('success', $this->t('admin.maintenance.install_done'));
                    break;
                case 'demo':
                    $this->container->get(Installer::class)->loadDemoData(static function (string $line) use (&$log): void {
                        $log[] = $line;
                    });
                    $this->resetOpcache();
                    $this->flash('success', $this->t('admin.maintenance.demo_done'));
                    break;
                case 'create_admin':
                    $creator = $this->container->get(SuperAdminCreator::class);
                    if ($creator->exists()) {
                        throw new DomainException($this->t('admin.maintenance.admin_exists'));
                    }
                    $session->flash('maintenance_link', $creator->create($request->string('email'), $request->string('name'), 'it', 'maintenance'));
                    $this->flash('success', $this->t('admin.maintenance.admin_created'));
                    break;
                case 'cache':
                    $this->container->get(FileCache::class)->clear();
                    $this->resetOpcache();
                    $this->flash('success', $this->t('admin.maintenance.cache_done'));
                    break;
                case 'down':
                    @mkdir(dirname($this->downFile()), 0750, true);
                    file_put_contents($this->downFile(), gmdate('c'));
                    $this->flash('success', $this->t('admin.maintenance.down_done'));
                    break;
                case 'up':
                    if (is_file($this->downFile())) {
                        unlink($this->downFile());
                    }
                    $this->flash('success', $this->t('admin.maintenance.up_done'));
                    break;
                case 'logout':
                    $session->forget(self::SESSION_KEY);
                    $session->regenerate();
                    $this->audit('maintenance.logout', $request);

                    return $this->redirectTo('system.maintenance');
                default:
                    throw new HttpException(400);
            }
            $this->audit('maintenance.' . $action, $request);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            $log[] = $e->getMessage();
            $this->flash('error', $this->t('admin.maintenance.failed'));
        }
        $session->flash('maintenance_log', $log);

        return $this->redirectTo('system.maintenance');
    }

    // --- Interni --------------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    private function page(string $template, array $data): Response
    {
        return $this->render($template, $data, 'layouts/maintenance')
            ->withHeader('X-Robots-Tag', 'noindex, nofollow')
            ->withHeader('Referrer-Policy', 'no-referrer');
    }

    private function ensureEnabled(): void
    {
        if (strlen($this->configuredToken()) < self::MIN_TOKEN_LENGTH) {
            throw new HttpException(404);
        }
    }

    private function configuredToken(): string
    {
        return (string) Env::get('MAINTENANCE_TOKEN', '');
    }

    private function authorized(): bool
    {
        return (int) $this->container->get(Session::class)->get(self::SESSION_KEY, 0) > time();
    }

    private function downFile(): string
    {
        return APP_BASE_PATH . '/storage/maintenance/down';
    }

    private function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    /** Audit "best effort": prima dell'installazione la tabella non esiste ancora. */
    private function audit(string $action, Request $request): void
    {
        try {
            $audit = $this->container->get(AuditLogger::class);
            $audit->setIp($request->ip());
            $audit->log($action, 'system', null, null);
        } catch (Throwable) {
            // nessun audit possibile: database non ancora pronto
        }
    }
}
