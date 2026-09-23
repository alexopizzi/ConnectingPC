<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Tipi di organizzazione (vault "41" §3, brief §9 e §14). Nomi in it/en/fr/ar:
 * le traduzioni sono bozze (stato to_review) da far revisionare.
 */
return static function (Container $c): void {
    $types = [
        // code => [pubblico?, it, en, fr, ar]
        'prefecture' => [1, 'Prefettura', 'Prefecture', 'Préfecture', 'المحافظة (البريفتورا)'],
        'municipality' => [1, 'Comune', 'Municipality', 'Commune', 'البلدية'],
        'local_authority' => [1, 'Altro ente locale', 'Other local authority', 'Autre collectivité locale', 'هيئة محلية أخرى'],
        'health_authority' => [1, 'Azienda sanitaria (ASL)', 'Local health authority', 'Agence sanitaire locale', 'الهيئة الصحية المحلية'],
        'school' => [1, 'Scuola', 'School', 'École', 'مدرسة'],
        'public_service' => [1, 'Servizio pubblico', 'Public service', 'Service public', 'خدمة عامة'],
        'association' => [0, 'Associazione', 'Association', 'Association', 'جمعية'],
        'cooperative' => [0, 'Cooperativa', 'Cooperative', 'Coopérative', 'تعاونية'],
        'foundation' => [0, 'Fondazione', 'Foundation', 'Fondation', 'مؤسسة'],
        'third_sector' => [0, 'Ente del Terzo Settore', 'Third-sector organisation', 'Organisme du tiers secteur', 'منظمة من القطاع الثالث'],
        'patronage' => [0, 'Patronato', 'Welfare advice centre (patronato)', 'Bureau d’assistance sociale (patronato)', 'مكتب المساعدة الاجتماعية (باتروناتو)'],
        'trade_union' => [0, 'Sindacato', 'Trade union', 'Syndicat', 'نقابة'],
        'cultural_association' => [0, 'Associazione culturale', 'Cultural association', 'Association culturelle', 'جمعية ثقافية'],
        'volunteer_association' => [0, 'Associazione di volontariato', 'Volunteer association', 'Association de bénévoles', 'جمعية تطوعية'],
        'community_association' => [0, 'Associazione di comunità', 'Community association', 'Association communautaire', 'جمعية جالية'],
        'cultural_centre' => [0, 'Centro culturale', 'Cultural centre', 'Centre culturel', 'مركز ثقافي'],
        'religious_organization' => [0, 'Realtà religiosa con attività sociali', 'Faith organisation with social activities', 'Organisation religieuse avec activités sociales', 'منظمة دينية ذات أنشطة اجتماعية'],
        'informal_network' => [0, 'Rete informale', 'Informal network', 'Réseau informel', 'شبكة غير رسمية'],
        'other' => [0, 'Altro', 'Other', 'Autre', 'أخرى'],
    ];

    $db = $c->get(Database::class);
    $db->transaction(static function (Database $db) use ($types): void {
        $order = 0;
        foreach ($types as $code => [$public, $it, $en, $fr, $ar]) {
            $order += 10;
            $db->execute(
                'INSERT INTO organization_types (code, is_public_body, sort_order) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE is_public_body = VALUES(is_public_body)',
                [$code, $public, $order],
            );
            $id = (int) $db->fetchValue('SELECT id FROM organization_types WHERE code = ?', [$code]);
            $hash = hash('sha256', $it);
            foreach (['it' => [$it, 'approved'], 'en' => [$en, 'to_review'], 'fr' => [$fr, 'to_review'], 'ar' => [$ar, 'to_review']] as $locale => [$name, $status]) {
                // Non sovrascrive i nomi già modificati in admin
                $db->execute(
                    'INSERT IGNORE INTO organization_type_translations (organization_type_id, locale, name, status, source_hash)
                     VALUES (?, ?, ?, ?, ?)',
                    [$id, $locale, $name, $status, $hash],
                );
            }
        }
    });
};
