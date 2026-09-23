<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Container;

/**
 * Dati di base (database/seeds/NNN_nome.php). Ogni seed è idempotente: si può rieseguire senza duplicare.
 * I seed con "demo" nel nome contengono solo dati fittizi e girano solo con --demo.
 */
final class Seeder
{
    public function __construct(
        private readonly Container $container,
        private readonly string $directory,
    ) {
    }

    /**
     * @param callable(string): void|null $output
     * @return list<string>
     */
    public function run(bool $demo = false, ?callable $output = null): array
    {
        $files = glob($this->directory . '/*.php') ?: [];
        sort($files, SORT_STRING);
        $done = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (str_contains($name, 'demo') && !$demo) {
                continue;
            }
            $seed = require $file;
            if (!is_callable($seed)) {
                continue;
            }
            $output && $output("Seed $name");
            $seed($this->container);
            $done[] = $name;
        }

        return $done;
    }
}
