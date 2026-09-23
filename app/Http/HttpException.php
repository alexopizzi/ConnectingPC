<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/** Errore con codice HTTP da mostrare all'utente con la pagina di errore localizzata. */
final class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($message !== '' ? $message : 'HTTP ' . $status, $status);
    }
}
