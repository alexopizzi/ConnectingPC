<?php

declare(strict_types=1);

namespace App\Console;

/** Argomenti della riga di comando: --opzione, --opzione=valore e argomenti posizionali. */
final class Input
{
    /**
     * @param array<string, string|true> $options
     * @param list<string> $arguments
     */
    public function __construct(
        public readonly array $options,
        public readonly array $arguments,
    ) {
    }

    /** @param list<string> $argv */
    public static function parse(array $argv): self
    {
        $options = [];
        $arguments = [];
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $options[$parts[0]] = $parts[1] ?? true;
            } else {
                $arguments[] = $arg;
            }
        }

        return new self($options, $arguments);
    }

    public function flag(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function option(string $name, ?string $default = null): ?string
    {
        $value = $this->options[$name] ?? null;

        return is_string($value) ? $value : $default;
    }
}
