<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Verifica dei requisiti dell'ambiente (Docker locale, staging, produzione Aruba).
 * Usata da `bin/console env:check` e da `/health?detail=1`.
 * Requisiti documentati nel vault: "32 - Ambiente di produzione e compatibilità Aruba".
 */
final class EnvironmentCheck
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    private const MIN_PHP = '8.3.0';
    private const MIN_MARIADB = '11.4.0';
    private const REQUIRED_EXTENSIONS = [
        'pdo_mysql', 'intl', 'mbstring', 'sodium', 'openssl', 'fileinfo', 'json', 'curl', 'zip',
    ];
    private const WRITABLE_DIRS = [
        'storage/logs', 'storage/cache', 'storage/sessions', 'storage/uploads', 'storage/maintenance',
    ];
    private const REQUIRED_COLLATION = 'utf8mb4_uca1400_ai_ci';

    /** @var list<array{check: string, status: string, detail: string}> */
    private array $results = [];

    /**
     * @return list<array{check: string, status: string, detail: string}>
     */
    public function run(bool $network = false): array
    {
        $this->results = [];

        $this->checkPhp();
        $this->checkConfiguration();
        $this->checkStorage();
        $this->checkDatabase();
        if (PHP_SAPI !== 'cli') {
            $this->checkWebServer();
        }
        if ($network) {
            $this->checkOutboundHttp();
        }

        return $this->results;
    }

    /**
     * @param list<array{check: string, status: string, detail: string}> $results
     */
    public static function hasFailures(array $results): bool
    {
        foreach ($results as $result) {
            if ($result['status'] === self::FAIL) {
                return true;
            }
        }

        return false;
    }

    private function checkPhp(): void
    {
        $this->add(
            'PHP >= ' . self::MIN_PHP,
            version_compare(PHP_VERSION, self::MIN_PHP, '>=') ? self::OK : self::FAIL,
            PHP_VERSION . ' (' . PHP_SAPI . ')',
        );

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $this->add("Estensione $extension", $loaded ? self::OK : self::FAIL, $loaded ? 'presente' : 'mancante');
        }

        $images = array_values(array_filter(['gd', 'imagick'], 'extension_loaded'));
        $this->add(
            'Elaborazione immagini',
            $images !== [] ? self::OK : self::FAIL,
            $images !== [] ? implode(', ', $images) : 'né gd né imagick disponibili',
        );

        $opcache = extension_loaded('Zend OPcache');
        $this->add('OPcache', $opcache ? self::OK : self::WARN, $opcache ? 'presente' : 'assente: prestazioni ridotte');

        $argon = defined('PASSWORD_ARGON2ID');
        $this->add(
            'Hash password Argon2id',
            $argon ? self::OK : self::WARN,
            $argon ? 'disponibile' : 'non disponibile: verrà usato bcrypt',
        );
    }

    private function checkConfiguration(): void
    {
        $env = App::environment();
        $this->add('APP_ENV', self::OK, $env);

        $debugInProduction = $env === 'production' && Env::bool('APP_DEBUG');
        $this->add(
            'Debug disattivato in produzione',
            $debugInProduction ? self::FAIL : self::OK,
            $debugInProduction ? 'APP_DEBUG=true in produzione' : 'ok',
        );

        $key = Env::get('APP_KEY');
        $this->add(
            'APP_KEY',
            $key !== null ? self::OK : ($env === 'production' ? self::FAIL : self::WARN),
            $key !== null ? 'impostata' : 'mancante: generarla con `php bin/console key:generate`',
        );
    }

    private function checkStorage(): void
    {
        foreach (self::WRITABLE_DIRS as $dir) {
            $path = APP_BASE_PATH . '/' . $dir;
            $ok = is_dir($path) && is_writable($path);
            $this->add("Scrittura $dir", $ok ? self::OK : self::FAIL, $ok ? 'scrivibile' : 'assente o non scrivibile');
        }
    }

    private function checkDatabase(): void
    {
        try {
            $pdo = Database::connect();
        } catch (Throwable $e) {
            // Nessun dettaglio della connessione (host, utente) nell'output: solo la classe dell'errore.
            $this->add('Connessione al database', self::FAIL, 'connessione non riuscita (' . $e::class . ')');

            return;
        }
        $this->add('Connessione al database', self::OK, 'riuscita');

        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $isMariaDb = stripos($version, 'mariadb') !== false;
        $numeric = preg_match('/^\d+\.\d+\.\d+/', $version, $m) ? $m[0] : '0.0.0';
        $this->add(
            'MariaDB >= ' . self::MIN_MARIADB,
            $isMariaDb && version_compare($numeric, self::MIN_MARIADB, '>=') ? self::OK : self::FAIL,
            $version,
        );

        // MariaDB >= 10.10 elenca le collation UCA 14 senza charset (uca1400_ai_ci) in SHOW COLLATION:
        // il nome completo si trova solo in COLLATION_CHARACTER_SET_APPLICABILITY.
        $collation = $pdo->prepare(
            'SELECT 1 FROM information_schema.COLLATION_CHARACTER_SET_APPLICABILITY WHERE FULL_COLLATION_NAME = ?'
        );
        $collation->execute([self::REQUIRED_COLLATION]);
        $available = $collation->fetch() !== false;
        $this->add(
            'Collation ' . self::REQUIRED_COLLATION,
            $available ? self::OK : self::FAIL,
            $available ? 'disponibile' : 'non disponibile',
        );

        $session = $pdo->query('SELECT @@session.time_zone AS tz, @@session.sql_mode AS mode')->fetch();
        $strict = str_contains((string) $session['mode'], 'STRICT_ALL_TABLES');
        $this->add(
            'Sessione DB in UTC e strict mode',
            $session['tz'] === '+00:00' && $strict ? self::OK : self::FAIL,
            'time_zone=' . $session['tz'] . ', strict=' . ($strict ? 'sì' : 'no'),
        );

        $ftMin = $pdo->query('SELECT @@innodb_ft_min_token_size')->fetchColumn();
        $this->add('FULLTEXT InnoDB (lunghezza minima dei termini)', self::OK, (string) $ftMin);
    }

    private function checkWebServer(): void
    {
        $rewrite = isset($_SERVER['CPC_REWRITE']) || isset($_SERVER['REDIRECT_CPC_REWRITE']);
        $this->add(
            'Apache mod_rewrite / .htaccess',
            $rewrite ? self::OK : self::FAIL,
            $rewrite ? 'attivo' : 'le regole di public/.htaccess non vengono applicate',
        );

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $this->add(
            'HTTPS',
            $https ? self::OK : (App::isLocal() ? self::WARN : self::FAIL),
            $https ? 'attivo' : 'richiesta in HTTP',
        );
    }

    private function checkOutboundHttp(): void
    {
        $targets = [
            'Tile OpenStreetMap' => 'https://tile.openstreetmap.org/0/0/0.png',
            'Geocoder (Nominatim)' => rtrim(Env::get('GEOCODER_URL', 'https://nominatim.openstreetmap.org'), '/') . '/status',
        ];

        foreach ($targets as $label => $url) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_USERAGENT => Env::get('GEOCODER_USER_AGENT', 'ConnectingPC/' . App::version()),
            ]);
            curl_exec($handle);
            $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);

            $this->add(
                "HTTP in uscita: $label",
                $code >= 200 && $code < 400 ? self::OK : self::WARN,
                $code > 0 ? "HTTP $code" : 'nessuna risposta',
            );
        }
    }

    private function add(string $check, string $status, string $detail): void
    {
        $this->results[] = ['check' => $check, 'status' => $status, 'detail' => $detail];
    }
}
