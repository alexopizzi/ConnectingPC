<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Accesso a MariaDB via PDO con prepared statement (mai emulati).
 * Ogni connessione imposta strict mode (P-003) e UTC (P-004).
 */
final class Database
{
    private const SQL_MODE = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION';

    private ?PDO $pdo = null;

    /**
     * @param array{host: string, port: string, name: string, user: string, pass: string} $settings
     */
    public function __construct(private readonly array $settings)
    {
    }

    /** Connessione con l'utente applicativo, oppure con quello delle migrazioni se richiesto e configurato. */
    public static function fromEnv(bool $forMigrations = false): self
    {
        $user = Env::get('DB_USER', '');
        $pass = Env::get('DB_PASS', '');
        if ($forMigrations && Env::get('DB_MIGRATE_USER') !== null) {
            $user = Env::get('DB_MIGRATE_USER', '');
            $pass = Env::get('DB_MIGRATE_PASS', '');
        }

        return new self([
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '3306'),
            'name' => Env::get('DB_NAME', ''),
            'user' => (string) $user,
            'pass' => (string) $pass,
        ]);
    }

    /** Scorciatoia usata dalla verifica dei requisiti. */
    public static function connect(): PDO
    {
        return self::fromEnv()->pdo();
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->settings['host'],
                $this->settings['port'],
                $this->settings['name'],
            );
            $this->pdo = new PDO($dsn, $this->settings['user'], $this->settings['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $this->pdo->exec("SET time_zone = '+00:00', sql_mode = '" . self::SQL_MODE . "'");
        }

        return $this->pdo;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $params */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return list<mixed>
     */
    public function fetchColumn(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @param array<int|string, mixed> $params */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /** @param array<string, mixed> $data */
    public function insert(string $table, array $data): int
    {
        $columns = array_map(self::identifier(...), array_keys($data));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::identifier($table),
            implode(', ', $columns),
            implode(', ', array_fill(0, count($data), '?')),
        );
        $this->run($sql, array_values($data));

        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where uguaglianze in AND
     */
    public function update(string $table, array $data, array $where): int
    {
        if ($data === [] || $where === []) {
            throw new InvalidArgumentException('update() richiede dati e condizioni.');
        }
        $set = implode(', ', array_map(static fn (string $c): string => self::identifier($c) . ' = ?', array_keys($data)));
        $cond = implode(' AND ', array_map(static fn (string $c): string => self::identifier($c) . ' = ?', array_keys($where)));

        return $this->execute(
            sprintf('UPDATE %s SET %s WHERE %s', self::identifier($table), $set, $cond),
            [...array_values($data), ...array_values($where)],
        );
    }

    /**
     * Esegue la funzione in transazione. Le chiamate annidate partecipano alla transazione esterna.
     *
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        if ($pdo->inTransaction()) {
            return $callback($this);
        }

        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Nomi di tabella/colonna: solo identificatori semplici, mai input dell'utente. */
    public static function identifier(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Identificatore SQL non valido: ' . $name);
        }

        return '`' . $name . '`';
    }

    /** @param array<int|string, mixed> $params */
    private function run(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        $positional = array_is_list($params);
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $value = (int) $value;
            }
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue($positional ? $key + 1 : ':' . ltrim((string) $key, ':'), $value, $type);
        }
        $statement->execute();

        return $statement;
    }
}
