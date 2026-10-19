<?php

declare(strict_types=1);

namespace App\Console;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Pacchetto di rilascio per l'hosting condiviso (vault "81", D-041): `dist/connectingpc-vX.Y.Z.zip`.
 * Contiene codice, asset e `vendor/` di produzione (composer --no-dev); non contiene `.env`, dati di
 * `storage/`, test, Docker né file di sviluppo. Le voci stanno alla radice dello zip: si estrae
 * direttamente nella cartella del sito. Accanto: `SHA256SUMS` e un modello di `.env` con i segreti già generati.
 */
final class ReleaseBuilder
{
    /** Cartelle e file copiati dalla radice del progetto. */
    private const INCLUDE = [
        'app', 'bin', 'config', 'database', 'lang', 'public', 'templates',
        '.htaccess', 'composer.json', 'composer.lock', 'VERSION', 'CHANGELOG.md', '.env.example', '.env.production.example',
    ];

    /** Percorsi esclusi (relativi alla radice, con / come separatore). */
    private const EXCLUDE = ['database/seeds/data/tools', 'public/assets/tmp-axe'];

    /** Cartelle di storage create vuote (con .gitkeep) e protette da storage/.htaccess. */
    private const STORAGE = ['cache', 'logs', 'sessions', 'uploads', 'tiles', 'maintenance'];

    public function __construct(private readonly string $root)
    {
    }

    /**
     * @param callable(string): void $log
     * @return array{zip: string, sums: string, env: string}
     */
    public function build(string $version, callable $log): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Estensione zip non disponibile.');
        }
        $dist = $this->root . '/dist';
        $name = 'connectingpc-v' . $version;
        $stage = $dist . '/build/' . $name;
        $this->remove($dist . '/build');
        mkdir($stage, 0755, true);

        $log('Copia dei file…');
        foreach (self::INCLUDE as $entry) {
            $this->copy($this->root . '/' . $entry, $stage . '/' . $entry, $entry);
        }
        foreach (self::STORAGE as $dir) {
            mkdir($stage . '/storage/' . $dir, 0755, true);
            touch($stage . '/storage/' . $dir . '/.gitkeep');
        }
        copy($this->root . '/storage/.htaccess', $stage . '/storage/.htaccess') ?: throw new RuntimeException('storage/.htaccess mancante');
        copy($this->root . '/deploy/INSTALLAZIONE.md', $stage . '/INSTALLAZIONE.md') ?: throw new RuntimeException('deploy/INSTALLAZIONE.md mancante');

        $log('Dipendenze di produzione (composer install --no-dev)…');
        $command = 'cd ' . escapeshellarg($stage) . ' && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-progress --quiet 2>&1';
        exec($command, $output, $code);
        if ($code !== 0) {
            throw new RuntimeException("composer non riuscito:\n" . implode("\n", $output));
        }

        $zipPath = $dist . '/' . $name . '.zip';
        @unlink($zipPath);
        $log('Creazione di ' . basename($zipPath) . '…');
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossibile creare ' . $zipPath);
        }
        $files = 0;
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($items as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($stage) + 1));
            if ($item->isDir()) {
                $zip->addEmptyDir($relative);
                $zip->setExternalAttributesName($relative . '/', ZipArchive::OPSYS_UNIX, (040755 & 0xffff) << 16);
                continue;
            }
            $zip->addFile($item->getPathname(), $relative);
            $mode = str_starts_with($relative, 'bin/') ? 0100755 : 0100644;
            $zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, ($mode & 0xffff) << 16);
            $files++;
        }
        $zip->close();
        $this->remove($dist . '/build');

        $sumsPath = $dist . '/SHA256SUMS';
        file_put_contents($sumsPath, hash_file('sha256', $zipPath) . '  ' . basename($zipPath) . "\n");

        $envPath = $dist . '/' . $name . '.env.produzione';
        file_put_contents($envPath, $this->productionEnv($version));
        @chmod($envPath, 0600);

        $log(sprintf('%d file, %.1f MB.', $files, filesize($zipPath) / 1048576));

        return ['zip' => $zipPath, 'sums' => $sumsPath, 'env' => $envPath];
    }

    /** Modello di .env con APP_KEY e token generati: restano da inserire database e SMTP. */
    private function productionEnv(string $version): string
    {
        $template = (string) file_get_contents($this->root . '/.env.production.example');
        $secrets = [
            'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
            'HEALTH_TOKEN' => bin2hex(random_bytes(24)),
            'MAINTENANCE_TOKEN' => bin2hex(random_bytes(24)),
        ];
        foreach ($secrets as $key => $value) {
            $template = (string) preg_replace('/^' . $key . '=.*$/m', $key . '=' . $value, $template);
        }

        return (string) preg_replace('/ConnectingPC\/[0-9.]+/', 'ConnectingPC/' . $version, $template);
    }

    private function copy(string $from, string $to, string $relative): void
    {
        foreach (self::EXCLUDE as $excluded) {
            if ($relative === $excluded || str_starts_with($relative, $excluded . '/')) {
                return;
            }
        }
        if (is_dir($from)) {
            @mkdir($to, 0755, true);
            foreach (new FilesystemIterator($from, FilesystemIterator::SKIP_DOTS) as $child) {
                $base = $child->getFilename();
                if (in_array($base, ['.DS_Store', 'Thumbs.db', 'desktop.ini'], true)) {
                    continue;
                }
                $this->copy($child->getPathname(), $to . '/' . $base, $relative . '/' . $base);
            }

            return;
        }
        if (!is_file($from)) {
            throw new RuntimeException('File mancante: ' . $relative);
        }
        copy($from, $to) ?: throw new RuntimeException('Copia non riuscita: ' . $relative);
    }

    private function remove(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            unlink($path);

            return;
        }
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
