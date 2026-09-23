<?php

declare(strict_types=1);

namespace App\Support;

/** Slug ASCII per gli URL, da testo in qualsiasi alfabeto (translitterazione ICU). */
final class Slug
{
    public static function make(string $text, int $maxLength = 120): string
    {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
        $ascii = $transliterator !== null ? ($transliterator->transliterate($text) ?: $text) : $text;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)) ?? '', '-');

        return substr($slug, 0, $maxLength) ?: 'scheda';
    }
}
