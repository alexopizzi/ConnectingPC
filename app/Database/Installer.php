<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Container;
use App\Core\Database;
use App\Core\Env;
use App\Core\FileCache;
use App\I18n\LocaleRegistry;
use App\I18n\TranslationImporter;
use DomainException;

/**
 * Prima installazione o aggiornamento (vault "80", "81"): migrazioni → dati di base → stringhe dell'interfaccia
 * → svuotamento della cache. Usato dal comando `setup` e dalla pagina di manutenzione (hosting senza SSH).
 * Ogni passo è idempotente: rieseguirlo su un'installazione aggiornata non cambia nulla.
 */
final class Installer
{
    /** Seed dimostrativi esclusi dal caricamento in produzione. */
    private const NEVER_IN_PRODUCTION = ['910_demo_users'];

    public function __construct(private readonly Container $container)
    {
    }

    /** @param callable(string): void $log */
    public function run(callable $log, bool $demo = false): void
    {
        $log('== migrate');
        $migrator = new Migrator(Database::fromEnv(forMigrations: true), APP_BASE_PATH . '/database/migrations');
        $done = $migrator->migrate($log);
        $log($done === [] ? 'Nessuna migrazione da applicare.' : count($done) . ' migrazioni applicate.');

        $log('== seed');
        (new Seeder($this->container, APP_BASE_PATH . '/database/seeds'))->run($demo, $log);

        $log('== i18n:import');
        $registry = $this->container->get(LocaleRegistry::class);
        $registry->forget();
        $stats = $this->container->get(TranslationImporter::class)->import(array_keys($registry->enabled()), false);
        foreach ($stats as $locale => $row) {
            $log(sprintf('%-4s create %d · aggiornate %d · invariate %d', $locale, $row['created'], $row['updated'], $row['skipped']));
        }

        $this->container->get(FileCache::class)->clear();
        $log('Cache svuotata.');
    }

    /**
     * Dati dimostrativi FITTIZI su un'installazione di produzione, su richiesta esplicita del committente (D-042).
     * Serve DEMO_DATA_ALLOWED=true nel .env. I seed dimostrativi rifiutano APP_ENV=production: l'ambiente
     * viene cambiato solo per la durata del caricamento e poi ripristinato. Gli utenti dimostrativi non si
     * caricano mai in produzione (la loro password è pubblica nel repository).
     *
     * @param callable(string): void $log
     */
    public function loadDemoData(callable $log): void
    {
        if (!self::demoDataAllowed()) {
            throw new DomainException('Dati dimostrativi non abilitati: impostare DEMO_DATA_ALLOWED=true nel file .env.');
        }
        $previous = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'demo';
        try {
            (new Seeder($this->container, APP_BASE_PATH . '/database/seeds'))->run(true, $log, self::NEVER_IN_PRODUCTION);
        } finally {
            if ($previous === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previous;
            }
        }
        $log('Saltati: ' . implode(', ', self::NEVER_IN_PRODUCTION) . ' (utenti dimostrativi con password pubblica).');
        $this->container->get(FileCache::class)->clear();
        $log('Cache svuotata.');
    }

    public static function demoDataAllowed(): bool
    {
        return Env::bool('DEMO_DATA_ALLOWED');
    }

    /**
     * @return list<array{version: string, applied_at: ?string, checksum_ok: ?bool}>
     */
    public function migrationStatus(): array
    {
        return (new Migrator(Database::fromEnv(forMigrations: true), APP_BASE_PATH . '/database/migrations'))->status();
    }
}
