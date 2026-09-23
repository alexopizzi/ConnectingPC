<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Container;
use Throwable;

/**
 * Console minimale (bin/console). I comandi sono registrati in app/Console/commands.php.
 */
final class Application
{
    /** @var array<string, array{description: string, handler: callable(Input, Output, Container): int}> */
    private array $commands = [];

    public function __construct(private readonly Container $container)
    {
    }

    /** @param callable(Input, Output, Container): int $handler */
    public function register(string $name, string $description, callable $handler): void
    {
        $this->commands[$name] = ['description' => $description, 'handler' => $handler];
    }

    /** @param list<string> $argv */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'help';
        $input = Input::parse(array_slice($argv, 2));
        $output = new Output();

        if ($name === 'help' || $name === '--help' || !isset($this->commands[$name])) {
            $this->help($output);

            return in_array($name, ['help', '--help'], true) ? 0 : 1;
        }

        try {
            return ($this->commands[$name]['handler'])($input, $output, $this->container);
        } catch (Throwable $e) {
            $output->error($e->getMessage());

            return 1;
        }
    }

    private function help(Output $output): void
    {
        $output->line('Uso: php bin/console <comando> [opzioni]');
        $output->line('');
        $output->line('Comandi:');
        ksort($this->commands);
        foreach ($this->commands as $name => $command) {
            $output->line(sprintf('  %-22s %s', $name, $command['description']));
        }
    }
}
