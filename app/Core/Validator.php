<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validazione lato server dei moduli. Le regole restituiscono chiavi di traduzione (validation.*),
 * tradotte dal controller nella lingua dell'utente.
 *
 * Regole: required, email, max:N, min:N, in:a|b|c, integer.
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, list<string>> $rules
     * @return array<string, list<array{key: string, params: array<string, string|int>}>>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $string = is_scalar($value) ? trim((string) $value) : '';
            $empty = $string === '';

            foreach ($fieldRules as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                $error = match ($name) {
                    'required' => $empty ? ['key' => 'validation.required', 'params' => []] : null,
                    'email' => !$empty && filter_var($string, FILTER_VALIDATE_EMAIL) === false
                        ? ['key' => 'validation.email', 'params' => []] : null,
                    'max' => !$empty && mb_strlen($string) > (int) $argument
                        ? ['key' => 'validation.max', 'params' => ['max' => (int) $argument]] : null,
                    'min' => !$empty && mb_strlen($string) < (int) $argument
                        ? ['key' => 'validation.min', 'params' => ['min' => (int) $argument]] : null,
                    'in' => !$empty && !in_array($string, explode('|', (string) $argument), true)
                        ? ['key' => 'validation.in', 'params' => []] : null,
                    'integer' => !$empty && !ctype_digit($string)
                        ? ['key' => 'validation.integer', 'params' => []] : null,
                    default => throw new \InvalidArgumentException('Regola sconosciuta: ' . $name),
                };
                if ($error !== null) {
                    $errors[$field][] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
