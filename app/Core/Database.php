<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Connessione PDO a MariaDB con le impostazioni di sessione obbligatorie del progetto:
 * strict mode (il server di produzione non lo ha, vedi P-003) e UTC (P-004).
 */
final class Database
{
    private const SQL_MODE = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION';

    public static function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '3306'),
            Env::get('DB_NAME', ''),
        );

        $pdo = new PDO($dsn, Env::get('DB_USER', ''), Env::get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        $pdo->exec("SET time_zone = '+00:00', sql_mode = '" . self::SQL_MODE . "'");

        return $pdo;
    }
}
