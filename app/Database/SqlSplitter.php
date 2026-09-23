<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Divide un file SQL in singole istruzioni, ignorando i ";" dentro stringhe, identificatori e commenti.
 * Le migrazioni non usano DELIMITER né procedure memorizzate.
 */
final class SqlSplitter
{
    /** @return list<string> */
    public static function split(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === '\\' && $quote !== '`') {
                    $buffer .= $next;
                    $i++;
                } elseif ($char === $quote) {
                    if ($next === $quote) {
                        // Virgolette raddoppiate: carattere letterale.
                        $buffer .= $next;
                        $i++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }
            if (($char === '-' && $next === '-' && in_array($sql[$i + 2] ?? ' ', [' ', "\t", "\n", "\r"], true)) || $char === '#') {
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $length : $end;
                $buffer .= "\n";
                continue;
            }
            if ($char === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;
                $buffer .= ' ';
                continue;
            }
            if ($char === ';') {
                self::push($statements, $buffer);
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        self::push($statements, $buffer);

        return $statements;
    }

    /** @param list<string> $statements */
    private static function push(array &$statements, string $buffer): void
    {
        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }
    }
}
