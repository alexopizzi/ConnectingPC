<?php

declare(strict_types=1);

namespace App\Domain\Search;

/**
 * Normalizzazione del testo per ricerca e dizionario dei sinonimi (vault "62"):
 * minuscole, rimozione dei segni diacritici (anche i segni vocalici arabi), varianti arabe unificate,
 * punteggiatura trasformata in spazi.
 */
final class TextNormalizer
{
    private static ?\Transliterator $stripMarks = null;

    public static function normalize(string $text): string
    {
        $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        $text = mb_strtolower($text, 'UTF-8');

        self::$stripMarks ??= \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
        if (self::$stripMarks !== null) {
            $text = self::$stripMarks->transliterate($text) ?: $text;
        }

        // Arabo: tatwil, varianti di alif, tāʾ marbūṭa, alif maqṣūra.
        $text = strtr($text, [
            "\u{0640}" => '',
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
        ]);

        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Parole significative (almeno 2 caratteri), senza le parole vuote più comuni.
     *
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        static $stopwords = [
            'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'una', 'di', 'da', 'del', 'della', 'dei', 'per', 'con', 'in', 'a', 'e', 'o',
            'mi', 'ti', 'si', 'ho', 'devo', 'voglio', 'cerco', 'mio', 'mia',
            'the', 'a', 'an', 'of', 'for', 'to', 'and', 'or', 'my', 'i', 'need', 'want',
            'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'pour', 'et', 'ou', 'mon', 'ma', 'je',
            'في', 'من', 'على', 'الى', 'عن', 'و',
        ];
        $tokens = [];
        foreach (explode(' ', self::normalize($text)) as $token) {
            if (mb_strlen($token) >= 2 && !in_array($token, $stopwords, true)) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }
}
