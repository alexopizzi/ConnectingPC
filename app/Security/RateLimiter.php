<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Database;

/**
 * Rate limiting a finestra fissa su database (tabella rate_limits): funziona senza Redis/APCu.
 * I nomi dei bucket non contengono dati personali in chiaro (email e IP sono sottoposti a hash).
 */
final class RateLimiter
{
    public function __construct(
        private readonly Database $database,
        private readonly string $secret,
    ) {
    }

    /** Chiave di bucket con HMAC del valore (IP, email). */
    public function key(string $prefix, string ...$values): string
    {
        return $prefix . ':' . substr(hash_hmac('sha256', implode('|', $values), $this->secret), 0, 40);
    }

    public function tooManyAttempts(string $bucket, int $maxAttempts, int $windowSeconds): bool
    {
        $row = $this->database->fetchOne(
            'SELECT hits, TIMESTAMPDIFF(SECOND, window_start, UTC_TIMESTAMP()) AS age FROM rate_limits WHERE bucket = ?',
            [$bucket],
        );

        return $row !== null && (int) $row['age'] < $windowSeconds && (int) $row['hits'] >= $maxAttempts;
    }

    /** Registra un tentativo e restituisce il numero di tentativi nella finestra corrente. */
    public function hit(string $bucket, int $windowSeconds): int
    {
        $this->database->execute(
            'INSERT INTO rate_limits (bucket, hits, window_start) VALUES (?, 1, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                hits = IF(window_start < UTC_TIMESTAMP() - INTERVAL ? SECOND, 1, hits + 1),
                window_start = IF(window_start < UTC_TIMESTAMP() - INTERVAL ? SECOND, UTC_TIMESTAMP(), window_start)',
            [$bucket, $windowSeconds, $windowSeconds],
        );

        // Pulizia occasionale dei bucket scaduti (niente cron su hosting condiviso).
        if (random_int(1, 50) === 1) {
            $this->database->execute('DELETE FROM rate_limits WHERE window_start < UTC_TIMESTAMP() - INTERVAL 1 DAY');
        }

        return (int) $this->database->fetchValue('SELECT hits FROM rate_limits WHERE bucket = ?', [$bucket]);
    }

    public function clear(string $bucket): void
    {
        $this->database->execute('DELETE FROM rate_limits WHERE bucket = ?', [$bucket]);
    }
}
