<?php

declare(strict_types=1);

use App\Console\Application;
use App\Console\Input;
use App\Console\Output;
use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Core\EnvironmentCheck;
use App\Core\FileCache;
use App\Database\Migrator;
use App\Database\Seeder;
use App\I18n\LocaleRegistry;
use App\I18n\TranslationImporter;

/*
 * Comandi della console (vault "33 - Struttura delle directory", "80 - Installazione").
 */
return static function (Application $console): void {
    $console->register('version', 'Mostra la versione dell\'applicazione', static function (Input $in, Output $out): int {
        $out->line(App::version());

        return 0;
    });

    $console->register('env:check', 'Verifica i requisiti dell\'ambiente (--network: anche HTTP in uscita)', static function (Input $in, Output $out): int {
        $results = (new EnvironmentCheck())->run($in->flag('network'));
        $symbols = [EnvironmentCheck::OK => '[ OK ]', EnvironmentCheck::WARN => '[WARN]', EnvironmentCheck::FAIL => '[FAIL]'];
        $out->line('ConnectingPC ' . App::version() . ' — ambiente: ' . App::environment());
        $out->line();
        foreach ($results as $row) {
            $out->line(sprintf('%s %-48s %s', $symbols[$row['status']], $row['check'], $row['detail']));
        }
        $failed = EnvironmentCheck::hasFailures($results);
        $out->line();
        $out->line($failed ? 'Esito: requisiti NON soddisfatti.' : 'Esito: requisiti soddisfatti.');

        return $failed ? 1 : 0;
    });

    $console->register('key:generate', 'Genera un valore per APP_KEY (da copiare a mano in .env)', static function (Input $in, Output $out): int {
        $out->line('APP_KEY=base64:' . base64_encode(random_bytes(32)));

        return 0;
    });

    $console->register('migrate', 'Applica le migrazioni del database in sospeso', static function (Input $in, Output $out, Container $c): int {
        $migrator = new Migrator(Database::fromEnv(forMigrations: true), APP_BASE_PATH . '/database/migrations');
        $done = $migrator->migrate(static fn (string $line) => $out->line($line));
        $out->line($done === [] ? 'Nessuna migrazione da applicare.' : count($done) . ' migrazioni applicate.');
        $c->get(FileCache::class)->clear();

        return 0;
    });

    $console->register('migrate:status', 'Mostra lo stato delle migrazioni', static function (Input $in, Output $out): int {
        $migrator = new Migrator(Database::fromEnv(forMigrations: true), APP_BASE_PATH . '/database/migrations');
        $problems = 0;
        foreach ($migrator->status() as $row) {
            $state = $row['applied_at'] === null ? 'da applicare' : 'applicata il ' . $row['applied_at'] . ' UTC';
            if ($row['checksum_ok'] === false) {
                $state .= ' — ATTENZIONE: file modificato dopo l\'applicazione';
                $problems++;
            }
            $out->line(sprintf('%-40s %s', $row['version'], $state));
        }

        return $problems > 0 ? 1 : 0;
    });

    $console->register('seed', 'Carica i dati di base (--demo: anche dati dimostrativi fittizi)', static function (Input $in, Output $out, Container $c): int {
        $seeder = new Seeder($c, APP_BASE_PATH . '/database/seeds');
        $seeder->run($in->flag('demo'), static fn (string $line) => $out->line($line));
        $c->get(FileCache::class)->clear();

        return 0;
    });

    $console->register('i18n:import', 'Importa le stringhe dell\'interfaccia da lang/ (--force: sovrascrive le traduzioni)', static function (Input $in, Output $out, Container $c): int {
        $c->get(LocaleRegistry::class)->forget();
        $locales = array_keys($c->get(LocaleRegistry::class)->enabled());
        $stats = $c->get(TranslationImporter::class)->import($locales, $in->flag('force'));
        foreach ($stats as $locale => $row) {
            $out->line(sprintf('%-4s create %d · aggiornate %d · invariate %d', $locale, $row['created'], $row['updated'], $row['skipped']));
        }

        return 0;
    });

    $console->register('cache:clear', 'Svuota la cache applicativa (storage/cache)', static function (Input $in, Output $out, Container $c): int {
        $c->get(FileCache::class)->clear();
        $out->line('Cache svuotata.');

        return 0;
    });

    $console->register('setup', 'Prima installazione o aggiornamento: migrate + seed + i18n:import', static function (Input $in, Output $out, Container $c) use ($console): int {
        foreach (['migrate', 'seed', 'i18n:import'] as $command) {
            $out->line("== $command");
            $argv = ['console', $command, ...($command === 'seed' && $in->flag('demo') ? ['--demo'] : [])];
            if ($console->run($argv) !== 0) {
                return 1;
            }
        }

        return 0;
    });
};
