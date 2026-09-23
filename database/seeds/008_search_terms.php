<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;
use App\Domain\Search\TextNormalizer;

/*
 * Dizionario iniziale dei sinonimi (vault "62"): parole ed espressioni reali delle persone → bisogni.
 * Da arricchire con operatori e mediatori; gestito in admin nelle versioni successive.
 */
return static function (Container $c): void {
    // bisogno => [lingua => [termini]]
    $terms = [
        'documents' => [
            'it' => ['permesso', 'permesso di soggiorno', 'pds', 'rinnovo', 'rinnovare', 'questura', 'kit postale', 'cittadinanza', 'ricongiungimento', 'asilo', 'documenti', 'passaporto', 'visto'],
            'en' => ['permit', 'residence permit', 'renew', 'renewal', 'citizenship', 'asylum', 'documents', 'papers', 'passport', 'visa', 'family reunification'],
            'fr' => ['titre de sejour', 'permis de sejour', 'carte de sejour', 'renouveler', 'renouvellement', 'nationalite', 'asile', 'papiers', 'passeport', 'visa', 'regroupement familial'],
            'ar' => ['تصريح الاقامه', 'الاقامه', 'اقامه', 'تجديد', 'الجنسيه', 'لجوء', 'اللجوء', 'وثائق', 'جواز السفر', 'تاشيره', 'لم شمل'],
        ],
        'work' => [
            'it' => ['lavoro', 'lavorare', 'impiego', 'curriculum', 'cv', 'disoccupato', 'offerte di lavoro', 'centro per l impiego', 'tirocinio', 'contratto'],
            'en' => ['job', 'work', 'employment', 'cv', 'resume', 'unemployed', 'internship', 'contract'],
            'fr' => ['travail', 'emploi', 'boulot', 'cv', 'chomage', 'stage', 'contrat'],
            'ar' => ['عمل', 'شغل', 'وظيفه', 'سيره ذاتيه', 'بطاله', 'عقد عمل', 'تدريب'],
        ],
        'housing' => [
            'it' => ['casa', 'affitto', 'alloggio', 'appartamento', 'dormire', 'dormitorio', 'sfratto', 'senza casa', 'casa popolare'],
            'en' => ['house', 'home', 'housing', 'rent', 'flat', 'apartment', 'sleep', 'shelter', 'eviction', 'homeless'],
            'fr' => ['maison', 'logement', 'loyer', 'appartement', 'dormir', 'hebergement', 'expulsion', 'sans abri'],
            'ar' => ['بيت', 'سكن', 'منزل', 'ايجار', 'شقه', 'مبيت', 'ماوى', 'طرد', 'بلا ماوى'],
        ],
        'health' => [
            'it' => ['medico', 'dottore', 'salute', 'ospedale', 'tessera sanitaria', 'malattia', 'medicine', 'stp', 'ambulatorio', 'vaccino'],
            'en' => ['doctor', 'health', 'hospital', 'health card', 'sick', 'medicine', 'clinic', 'vaccine', 'gp'],
            'fr' => ['medecin', 'docteur', 'sante', 'hopital', 'carte sanitaire', 'malade', 'medicament', 'dispensaire', 'vaccin'],
            'ar' => ['طبيب', 'دكتور', 'صحه', 'مستشفى', 'بطاقه صحيه', 'مرض', 'دواء', 'عياده', 'لقاح'],
        ],
        'italian_language' => [
            'it' => ['italiano', 'corso di italiano', 'imparare italiano', 'lingua italiana', 'cpia', 'scuola di italiano', 'certificazione', 'a2'],
            'en' => ['italian', 'italian course', 'learn italian', 'language course'],
            'fr' => ['italien', 'cours d italien', 'apprendre l italien', 'cours de langue'],
            'ar' => ['الايطاليه', 'اللغه الايطاليه', 'تعلم الايطاليه', 'دوره لغه'],
        ],
        'school_children' => [
            'it' => ['scuola', 'iscrizione scuola', 'figlio', 'figli', 'bambino', 'bambini', 'asilo nido', 'nido', 'doposcuola', 'mensa'],
            'en' => ['school', 'enrol', 'child', 'children', 'kids', 'nursery', 'kindergarten', 'after school'],
            'fr' => ['ecole', 'inscription', 'enfant', 'enfants', 'creche', 'maternelle', 'devoirs'],
            'ar' => ['مدرسه', 'تسجيل', 'طفل', 'اطفال', 'ابني', 'حضانه', 'روضه'],
        ],
        'admin_procedures' => [
            'it' => ['residenza', 'anagrafe', 'codice fiscale', 'isee', 'patronato', 'pratica', 'certificato', 'bonus', 'assegno unico'],
            'en' => ['residence registration', 'registry office', 'tax code', 'certificate', 'benefits', 'paperwork'],
            'fr' => ['residence', 'etat civil', 'code fiscal', 'certificat', 'allocation', 'demarches'],
            'ar' => ['تسجيل الاقامه', 'السجل المدني', 'الرمز الضريبي', 'شهاده', 'اعانه', 'معامله'],
        ],
        'legal' => [
            'it' => ['avvocato', 'legale', 'consulenza legale', 'diritti', 'discriminazione', 'ricorso', 'denuncia'],
            'en' => ['lawyer', 'legal', 'legal advice', 'rights', 'discrimination', 'appeal'],
            'fr' => ['avocat', 'juridique', 'conseil juridique', 'droits', 'discrimination', 'recours'],
            'ar' => ['محامي', 'قانوني', 'استشاره قانونيه', 'حقوق', 'تمييز', 'طعن'],
        ],
        'social_support' => [
            'it' => ['cibo', 'mangiare', 'vestiti', 'aiuto', 'soldi', 'sussidio', 'assistente sociale', 'servizi sociali', 'povero'],
            'en' => ['food', 'eat', 'clothes', 'help', 'money', 'social worker', 'social services'],
            'fr' => ['nourriture', 'manger', 'vetements', 'aide', 'argent', 'assistante sociale', 'services sociaux'],
            'ar' => ['طعام', 'اكل', 'ملابس', 'مساعده', 'مال', 'مساعد اجتماعي', 'خدمات اجتماعيه'],
        ],
        'mediation' => [
            'it' => ['mediatore', 'mediatrice', 'mediazione', 'interprete', 'traduttore', 'traduzione'],
            'en' => ['mediator', 'interpreter', 'translator', 'translation'],
            'fr' => ['mediateur', 'mediatrice', 'interprete', 'traducteur', 'traduction'],
            'ar' => ['وسيط', 'وسيط ثقافي', 'مترجم', 'ترجمه'],
        ],
        'family' => [
            'it' => ['violenza', 'donna', 'donne', 'gravidanza', 'incinta', 'maternita', 'consultorio', 'famiglia'],
            'en' => ['violence', 'woman', 'women', 'pregnancy', 'pregnant', 'maternity', 'family'],
            'fr' => ['violence', 'femme', 'femmes', 'grossesse', 'enceinte', 'maternite', 'famille'],
            'ar' => ['عنف', 'امراه', 'نساء', 'حمل', 'حامل', 'امومه', 'اسره', 'عائله'],
        ],
        'community' => [
            'it' => ['associazione', 'associazioni', 'comunita', 'connazionali', 'centro culturale'],
            'en' => ['association', 'community', 'fellow countrymen', 'cultural centre'],
            'fr' => ['association', 'communaute', 'compatriotes', 'centre culturel'],
            'ar' => ['جمعيه', 'جاليه', 'ابناء البلد', 'مركز ثقافي'],
        ],
    ];

    $db = $c->get(Database::class);
    $db->transaction(static function (Database $db) use ($terms): void {
        foreach ($terms as $needCode => $byLocale) {
            $needId = (int) $db->fetchValue('SELECT id FROM needs WHERE code = ?', [$needCode]);
            if ($needId === 0) {
                continue;
            }
            foreach ($byLocale as $locale => $list) {
                foreach ($list as $term) {
                    $db->execute(
                        "INSERT IGNORE INTO search_terms (locale, term, target_type, target_id, weight) VALUES (?, ?, 'need', ?, 8)",
                        [$locale, TextNormalizer::normalize($term), $needId],
                    );
                }
            }
        }
    });
};
