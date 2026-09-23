<?php

declare(strict_types=1);

namespace App\Console;

final class Output
{
    public function line(string $text = ''): void
    {
        fwrite(STDOUT, $text . PHP_EOL);
    }

    public function error(string $text): void
    {
        fwrite(STDERR, 'ERRORE: ' . $text . PHP_EOL);
    }
}
