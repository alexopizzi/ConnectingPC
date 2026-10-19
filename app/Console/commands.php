<?php

declare(strict_types=1);

use App\Console\Application;
use App\Console\Input;
use App\Console\Output;
use App\Console\ReleaseBuilder;
use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Core\EnvironmentCheck;
use App\Core\FileCache;
use App\Database\Migrator;
use App\Database\Installer;
use App\Database\Seeder;
use App\Domain\Management\InboundRequestService;
use App\Domain\Users\SuperAdminCreator;
use App\Domain\Users\UserRepository;
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

    // Da pianificare con un cron di Plesk (es. una volta al giorno): vault "71", "83"
    $console->register('requests:purge', 'Cancella i dati personali delle richieste in ingresso chiuse e scadute', static function (Input $in, Output $out, Container $c): int {
        $count = $c->get(InboundRequestService::class)->purgeExpired();
        $out->line($count . ' richieste ripulite.');

        return 0;
    });

    $console->register('user:create-admin', 'Crea un super amministratore e stampa il link di attivazione (--email= --name= [--locale=it])', static function (Input $in, Output $out, Container $c): int {
        $email = UserRepository::normalizeEmail((string) $in->option('email', ''));
        $name = trim((string) $in->option('name', ''));
        $locale = (string) $in->option('locale', 'it');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $name === '') {
            $out->error('Uso: php bin/console user:create-admin --email=nome@dominio.it --name="Nome Cognome"');

            return 1;
        }
        try {
            $link = $c->get(SuperAdminCreator::class)->create($email, $name, $locale, 'console');
        } catch (DomainException | RuntimeException $e) {
            $out->error($e->getMessage());

            return 1;
        }

        $out->line('Super amministratore creato. Link di attivazione (valido 72 ore, uso singolo):');
        $out->line($link);
        $out->line('Il link è un segreto: trasmetterlo solo alla persona interessata.');

        return 0;
    });

    $console->register('release:build', 'Crea il pacchetto di produzione dist/connectingpc-vX.Y.Z.zip (vault "81")', static function (Input $in, Output $out): int {
        $result = (new ReleaseBuilder(APP_BASE_PATH))->build(App::version(), static fn (string $line) => $out->line($line));
        $out->line('Pacchetto: dist/' . basename($result['zip']));
        $out->line('Impronta:  dist/' . basename($result['sums']));
        $out->line('Modello .env con segreti generati: dist/' . basename($result['env']) . ' (non condividerlo; completare database e SMTP)');

        return 0;
    });

    $console->register('setup', 'Prima installazione o aggiornamento: migrate + seed + i18n:import', static function (Input $in, Output $out, Container $c): int {
        $c->get(Installer::class)->run(static fn (string $line) => $out->line($line), $in->flag('demo'));

        return 0;
    });
};
