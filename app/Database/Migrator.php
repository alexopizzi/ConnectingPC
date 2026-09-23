<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use RuntimeException;
use Throwable;

/**
 * Migrazioni SQL versionate (database/migrations/NNNN_descrizione.sql).
 * Regole (vault "81"): una migrazione rilasciata non si modifica mai; schema expand/contract.
 * In MariaDB le istruzioni DDL causano commit implicito: una migrazione fallita a metà va corretta
 * con una nuova migrazione o ripristinando il backup; per questo si usano CREATE ... IF NOT EXISTS.
 */
final class Migrator
{
    public function __construct(
        private readonly Database $database,
        private readonly string $directory,
    ) {
    }

    /** @return list<array{version: string, applied_at: ?string, checksum_ok: ?bool}> */
    public function status(): array
    {
        $this->ensureTable();
        $applied = [];
        foreach ($this->database->fetchAll('SELECT version, checksum, applied_at FROM schema_migrations') as $row) {
            $applied[(string) $row['version']] = $row;
        }

        $status = [];
        foreach ($this->files() as $version => $file) {
            $row = $applied[$version] ?? null;
            $status[] = [
                'version' => $version,
                'applied_at' => $row === null ? null : (string) $row['applied_at'],
                'checksum_ok' => $row === null ? null : hash_equals((string) $row['checksum'], $this->checksum($file)),
            ];
        }

        return $status;
    }

    /**
     * @param callable(string): void|null $output
     * @return list<string> versioni applicate
     */
    public function migrate(?callable $output = null): array
    {
        $this->ensureTable();
        $appliedVersions = array_map('strval', $this->database->fetchColumn('SELECT version FROM schema_migrations'));
        $done = [];

        foreach ($this->files() as $version => $file) {
            if (in_array($version, $appliedVersions, true)) {
                continue;
            }
            $output && $output("Applico $version");
            $statements = SqlSplitter::split((string) file_get_contents($file));
            foreach ($statements as $index => $statement) {
                try {
                    $this->database->pdo()->exec($statement);
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        sprintf('Migrazione %s fallita all\'istruzione %d: %s', $version, $index + 1, $e->getMessage()),
                        0,
                        $e,
                    );
                }
            }
            $this->database->insert('schema_migrations', ['version' => $version, 'checksum' => $this->checksum($file)]);
            $done[] = $version;
        }

        return $done;
    }

    public function ensureTable(): void
    {
        $this->database->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version    VARCHAR(100) NOT NULL PRIMARY KEY,
                checksum   CHAR(64) NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci'
        );
    }

    /** @return array<string, string> versione => percorso, in ordine */
    private function files(): array
    {
        $files = [];
        foreach (glob($this->directory . '/*.sql') ?: [] as $file) {
            $version = basename($file, '.sql');
            if (preg_match('/^\d{4}_[a-z0-9_]+$/', $version)) {
                $files[$version] = $file;
            }
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    private function checksum(string $file): string
    {
        // Normalizza i fine riga: lo stesso file su Windows e Linux ha la stessa impronta.
        return hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($file)));
    }
}
