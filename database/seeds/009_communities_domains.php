<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Comunità di riferimento e ambiti di mediazione (vault "63", "64"). BOZZA da validare con la rete.
 * Le comunità non coincidono con una nazionalità: possono essere linguistiche, culturali, interculturali (brief §14).
 * Traduzioni en/fr/ar: bozze da revisionare (stato to_review).
 */
return static function (Container $c): void {
    // codice => [tipo, paesi ISO, [it, en, fr, ar]]
    $communities = [
        'senegalese' => ['national', ['SN'], ['Comunità senegalese', 'Senegalese community', 'Communauté sénégalaise', 'الجالية السنغالية']],
        'moroccan' => ['national', ['MA'], ['Comunità marocchina', 'Moroccan community', 'Communauté marocaine', 'الجالية المغربية']],
        'tunisian' => ['national', ['TN'], ['Comunità tunisina', 'Tunisian community', 'Communauté tunisienne', 'الجالية التونسية']],
        'egyptian' => ['national', ['EG'], ['Comunità egiziana', 'Egyptian community', 'Communauté égyptienne', 'الجالية المصرية']],
        'albanian' => ['national', ['AL', 'XK'], ['Comunità albanese', 'Albanian community', 'Communauté albanaise', 'الجالية الألبانية']],
        'romanian' => ['national', ['RO', 'MD'], ['Comunità romena e moldava', 'Romanian and Moldovan community', 'Communauté roumaine et moldave', 'الجالية الرومانية والمولدوفية']],
        'ukrainian' => ['national', ['UA'], ['Comunità ucraina', 'Ukrainian community', 'Communauté ukrainienne', 'الجالية الأوكرانية']],
        'north_macedonian' => ['national', ['MK'], ['Comunità macedone', 'North Macedonian community', 'Communauté macédonienne', 'جالية مقدونيا الشمالية']],
        'indian_punjabi' => ['national', ['IN'], ['Comunità indiana e punjabi', 'Indian and Punjabi community', 'Communauté indienne et pendjabie', 'الجالية الهندية والبنجابية']],
        'pakistani' => ['national', ['PK'], ['Comunità pakistana', 'Pakistani community', 'Communauté pakistanaise', 'الجالية الباكستانية']],
        'chinese' => ['national', ['CN'], ['Comunità cinese', 'Chinese community', 'Communauté chinoise', 'الجالية الصينية']],
        'nigerian' => ['national', ['NG'], ['Comunità nigeriana', 'Nigerian community', 'Communauté nigériane', 'الجالية النيجيرية']],
        'ecuadorian' => ['national', ['EC'], ['Comunità ecuadoriana', 'Ecuadorian community', 'Communauté équatorienne', 'الجالية الإكوادورية']],
        'arabic_speaking' => ['linguistic', ['MA', 'TN', 'EG', 'DZ', 'SY'], ['Comunità di lingua araba', 'Arabic-speaking community', 'Communauté arabophone', 'الجالية الناطقة بالعربية']],
        'francophone_africa' => ['linguistic', ['SN', 'CI', 'ML', 'BF', 'CM'], ['Comunità africane francofone', 'French-speaking African communities', 'Communautés africaines francophones', 'الجاليات الأفريقية الناطقة بالفرنسية']],
        'latin_american' => ['cultural', ['EC', 'PE', 'DO', 'BR'], ['Comunità latinoamericane', 'Latin American communities', 'Communautés latino-américaines', 'جاليات أمريكا اللاتينية']],
        'intercultural' => ['intercultural', [], ['Realtà interculturali', 'Intercultural groups', 'Groupes interculturels', 'مجموعات متعددة الثقافات']],
    ];

    $domains = [
        'health' => ['Sanità', 'Health', 'Santé', 'الصحة'],
        'school' => ['Scuola', 'School', 'École', 'المدرسة'],
        'social_services' => ['Servizi sociali', 'Social services', 'Services sociaux', 'الخدمات الاجتماعية'],
        'justice' => ['Giustizia e tribunali', 'Justice and courts', 'Justice et tribunaux', 'القضاء والمحاكم'],
        'public_administration' => ['Uffici pubblici e immigrazione', 'Public offices and immigration', 'Administrations et immigration', 'المكاتب العامة والهجرة'],
        'employment' => ['Lavoro', 'Employment', 'Emploi', 'العمل'],
        'reception' => ['Accoglienza e protezione', 'Reception and protection', 'Accueil et protection', 'الاستقبال والحماية'],
        'housing' => ['Casa', 'Housing', 'Logement', 'السكن'],
    ];
    $locales = ['it', 'en', 'fr', 'ar'];

    $db = $c->get(Database::class);
    $db->transaction(static function (Database $db) use ($communities, $domains, $locales): void {
        $order = 0;
        foreach ($communities as $code => [$kind, $countries, $names]) {
            $order += 10;
            $db->execute(
                'INSERT INTO communities (code, kind, sort_order) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE kind = VALUES(kind), sort_order = VALUES(sort_order)',
                [$code, $kind, $order],
            );
            $id = (int) $db->fetchValue('SELECT id FROM communities WHERE code = ?', [$code]);
            foreach ($locales as $i => $locale) {
                $db->execute(
                    'INSERT IGNORE INTO community_translations (community_id, locale, name, status, source_hash) VALUES (?, ?, ?, ?, ?)',
                    [$id, $locale, $names[$i], $locale === 'it' ? 'approved' : 'to_review', hash('sha256', $names[0])],
                );
            }
            foreach ($countries as $country) {
                $db->execute('INSERT IGNORE INTO community_countries (community_id, country_code) VALUES (?, ?)', [$id, $country]);
            }
        }

        $order = 0;
        foreach ($domains as $code => $names) {
            $order += 10;
            $db->execute('INSERT INTO mediation_domains (code, sort_order) VALUES (?, ?) ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)', [$code, $order]);
            $id = (int) $db->fetchValue('SELECT id FROM mediation_domains WHERE code = ?', [$code]);
            foreach ($locales as $i => $locale) {
                $db->execute(
                    'INSERT IGNORE INTO mediation_domain_translations (mediation_domain_id, locale, name, status, source_hash) VALUES (?, ?, ?, ?, ?)',
                    [$id, $locale, $names[$i], $locale === 'it' ? 'approved' : 'to_review', hash('sha256', $names[0])],
                );
            }
        }
    });
};
