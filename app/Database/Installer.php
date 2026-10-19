<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Container;
use App\Core\Database;
use App\Core\FileCache;
use App\I18n\LocaleRegistry;
use App\I18n\TranslationImporter;

/**
 * Prima installazione o aggiornamento (vault "80", "81"): migrazioni → dati di base → stringhe dell'interfaccia
 * → svuotamento della cache. Usato dal comando `setup` e dalla pagina di manutenzione (hosting senza SSH).
 * Ogni passo è idempotente: rieseguirlo su un'installazione aggiornata non cambia nulla.
 */
final class Installer
{
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
     * @return list<array{version: string, applied_at: ?string, checksum_ok: ?bool}>
     */
    public function migrationStatus(): array
    {
        return (new Migrator(Database::fromEnv(forMigrations: true), APP_BASE_PATH . '/database/migrations'))->status();
    }
}
