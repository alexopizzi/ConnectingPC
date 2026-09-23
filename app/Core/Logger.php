<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Log applicativo su file giornaliero (storage/logs/app-AAAA-MM-GG.log).
 * Mai registrare password, token o dati personali nel messaggio o nel contesto (vault "72 - Sicurezza").
 */
final class Logger
{
    private const LEVELS = [
        'debug' => 100, 'info' => 200, 'notice' => 250, 'warning' => 300, 'error' => 400, 'critical' => 500,
    ];

    public function __construct(
        private readonly string $directory,
        private readonly string $minimumLevel,
        private readonly string $requestId,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[$this->minimumLevel] ?? 300)) {
            return;
        }
        if (!is_dir($this->directory) || !is_writable($this->directory)) {
            error_log($message);

            return;
        }

        $line = sprintf(
            "[%s] %s [%s] %s%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            strtoupper($level),
            $this->requestId,
            str_replace(["\r", "\n"], ' ', $message),
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
        );
        file_put_contents($this->directory . '/app-' . gmdate('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function requestId(): string
    {
        return $this->requestId;
    }
}
