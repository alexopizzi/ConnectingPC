<?php

declare(strict_types=1);

/*
 * Lingue predefinite (D-023): usate per il seed della tabella `locales` e come ripiego
 * quando il database non è disponibile. La fonte viva è la tabella `locales`.
 */
return [
    'defaults' => [
        ['code' => 'it', 'native_name' => 'Italiano', 'direction' => 'ltr', 'is_public' => true, 'fallback_code' => null, 'sort_order' => 1],
        ['code' => 'en', 'native_name' => 'English', 'direction' => 'ltr', 'is_public' => true, 'fallback_code' => 'it', 'sort_order' => 2],
        ['code' => 'fr', 'native_name' => 'Français', 'direction' => 'ltr', 'is_public' => true, 'fallback_code' => 'it', 'sort_order' => 3],
        ['code' => 'ar', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_public' => true, 'fallback_code' => 'it', 'sort_order' => 4],
    ],
];
