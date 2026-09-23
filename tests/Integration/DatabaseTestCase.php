<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Container;
use App\Core\Database;
use App\Database\Migrator;
use App\Database\Seeder;
use PHPUnit\Framework\TestCase;

/**
 * Base dei test di integrazione: database `connectingpc_test` ricreato una volta per esecuzione,
 * con migrazioni e seed di base applicati. Ogni test gira in una transazione annullata alla fine.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static bool $prepared = false;

    protected Container $container;
    protected Database $db;

    protected function setUp(): void
    {
        $this->container = $GLOBALS['container'];
        $this->db = $this->container->get(Database::class);

        if (!self::$prepared) {
            $this->resetSchema();
            (new Migrator($this->db, APP_BASE_PATH . '/database/migrations'))->migrate();
            (new Seeder($this->container, APP_BASE_PATH . '/database/seeds'))->run();
            self::$prepared = true;
        }

        $this->db->pdo()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->pdo()->inTransaction()) {
            $this->db->pdo()->rollBack();
        }
    }

    private function resetSchema(): void
    {
        $name = (string) $this->db->fetchValue('SELECT DATABASE()');
        if ($name !== 'connectingpc_test') {
            self::fail('I test di integrazione devono usare il database connectingpc_test, non ' . $name);
        }
        $pdo = $this->db->pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->db->fetchColumn('SHOW TABLES') as $table) {
            $pdo->exec('DROP TABLE `' . str_replace('`', '', (string) $table) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
