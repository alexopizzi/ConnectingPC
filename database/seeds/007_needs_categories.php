<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Tassonomia iniziale di bisogni e categorie (vault "65"). BOZZA da validare con Area IV e operatori.
 * Traduzioni en/fr/ar: bozze prodotte dall'IA da revisionare (stato to_review). Come le stringhe UI,
 * le etichette delle tassonomie sono mostrate se non sono bozze (D-028).
 * Non sovrascrive testi già modificati in admin (INSERT IGNORE).
 */
return static function (Container $c): void {
    // codice => [icona, in evidenza, [it, en, fr, ar], categorie collegate]
    $needs = [
        'documents' => ['id-card', 1, ['Documenti e permesso di soggiorno', 'Documents and residence permit', 'Papiers et titre de séjour', 'الوثائق وتصريح الإقامة'], ['documents']],
        'work' => ['briefcase', 1, ['Cerco lavoro', 'I am looking for a job', 'Je cherche un travail', 'أبحث عن عمل'], ['work']],
        'housing' => ['home', 1, ['Ho bisogno di una casa', 'I need a home', 'J’ai besoin d’un logement', 'أحتاج إلى سكن'], ['housing']],
        'health' => ['health', 1, ['Salute e medico', 'Health and doctor', 'Santé et médecin', 'الصحة والطبيب'], ['health']],
        'italian_language' => ['language', 1, ['Imparare l’italiano', 'Learn Italian', 'Apprendre l’italien', 'تعلّم اللغة الإيطالية'], ['italian_language']],
        'school_children' => ['school', 1, ['Scuola e figli', 'School and children', 'École et enfants', 'المدرسة والأطفال'], ['education']],
        'admin_procedures' => ['office', 1, ['Pratiche e uffici', 'Paperwork and offices', 'Démarches et bureaux', 'المعاملات والمكاتب'], ['admin_offices']],
        'legal' => ['legal', 1, ['Aiuto legale', 'Legal help', 'Aide juridique', 'المساعدة القانونية'], ['legal']],
        'social_support' => ['support', 1, ['Aiuto sociale', 'Social support', 'Aide sociale', 'المساعدة الاجتماعية'], ['social_support']],
        'mediation' => ['mediation', 1, ['Ho bisogno di un mediatore', 'I need a mediator', 'J’ai besoin d’un médiateur', 'أحتاج إلى وسيط ثقافي'], ['mediation']],
        'family' => ['family', 1, ['Famiglia e donne', 'Family and women', 'Famille et femmes', 'الأسرة والنساء'], ['family_women']],
        'community' => ['community', 1, ['Associazioni e comunità', 'Associations and communities', 'Associations et communautés', 'الجمعيات والجاليات'], ['community']],
        'training' => ['training', 0, ['Studio e formazione', 'Study and training', 'Études et formation', 'الدراسة والتكوين'], ['vocational_training', 'italian_language']],
    ];

    // codice => [genitore|null, icona, [it, en, fr, ar]]
    $categories = [
        'documents' => [null, 'id-card', ['Documenti e soggiorno', 'Documents and residence', 'Papiers et séjour', 'الوثائق والإقامة']],
        'residence_permit' => ['documents', null, ['Permesso di soggiorno', 'Residence permit', 'Titre de séjour', 'تصريح الإقامة']],
        'citizenship' => ['documents', null, ['Cittadinanza italiana', 'Italian citizenship', 'Nationalité italienne', 'الجنسية الإيطالية']],
        'family_reunification' => ['documents', null, ['Ricongiungimento familiare', 'Family reunification', 'Regroupement familial', 'لمّ شمل الأسرة']],
        'international_protection' => ['documents', null, ['Protezione internazionale e asilo', 'International protection and asylum', 'Protection internationale et asile', 'الحماية الدولية واللجوء']],
        'admin_offices' => [null, 'office', ['Uffici e pratiche', 'Offices and paperwork', 'Bureaux et démarches', 'المكاتب والمعاملات']],
        'registry_residence' => ['admin_offices', null, ['Residenza e anagrafe', 'Registered residence', 'Résidence et état civil', 'الإقامة والسجل المدني']],
        'tax_code_health_card' => ['admin_offices', null, ['Codice fiscale e tessera sanitaria', 'Tax code and health card', 'Code fiscal et carte sanitaire', 'الرمز الضريبي والبطاقة الصحية']],
        'welfare_paperwork' => ['admin_offices', null, ['ISEE, bonus e pratiche di welfare', 'ISEE, benefits and welfare paperwork', 'ISEE, aides et démarches sociales', 'ISEE والإعانات ومعاملات الرعاية']],
        'work' => [null, 'briefcase', ['Lavoro', 'Work', 'Travail', 'العمل']],
        'job_centres' => ['work', null, ['Centri per l’impiego', 'Job centres', 'Centres pour l’emploi', 'مراكز التوظيف']],
        'job_orientation' => ['work', null, ['Orientamento e curriculum', 'Career guidance and CV', 'Orientation et CV', 'التوجيه والسيرة الذاتية']],
        'vocational_training' => ['work', null, ['Formazione professionale', 'Vocational training', 'Formation professionnelle', 'التكوين المهني']],
        'workers_rights' => ['work', null, ['Diritti dei lavoratori', 'Workers’ rights', 'Droits des travailleurs', 'حقوق العمال']],
        'housing' => [null, 'home', ['Casa', 'Housing', 'Logement', 'السكن']],
        'housing_help_desk' => ['housing', null, ['Sportelli casa e affitto', 'Housing and rent help desks', 'Guichets logement et loyer', 'مكاتب السكن والإيجار']],
        'housing_emergency' => ['housing', null, ['Emergenza abitativa e accoglienza', 'Emergency housing and shelter', 'Urgence logement et accueil', 'السكن الطارئ والإيواء']],
        'health' => [null, 'health', ['Salute', 'Health', 'Santé', 'الصحة']],
        'primary_care' => ['health', null, ['Medico di base e ambulatori', 'GP and clinics', 'Médecin traitant et dispensaires', 'طبيب الأسرة والعيادات']],
        'family_counselling' => ['health', null, ['Consultori', 'Family health centres', 'Centres de planning familial', 'مراكز الاستشارة الأسرية']],
        'emergency_care' => ['health', null, ['Urgenze e pronto soccorso', 'Emergency care', 'Urgences', 'الطوارئ']],
        'legal' => [null, 'legal', ['Aiuto legale', 'Legal help', 'Aide juridique', 'المساعدة القانونية']],
        'free_legal_advice' => ['legal', null, ['Consulenza legale gratuita', 'Free legal advice', 'Conseil juridique gratuit', 'استشارة قانونية مجانية']],
        'anti_discrimination' => ['legal', null, ['Contro la discriminazione', 'Against discrimination', 'Contre la discrimination', 'مكافحة التمييز']],
        'education' => [null, 'school', ['Scuola e infanzia', 'School and childhood', 'École et petite enfance', 'المدرسة والطفولة']],
        'school_enrolment' => ['education', null, ['Iscrizione a scuola', 'School enrolment', 'Inscription scolaire', 'التسجيل في المدرسة']],
        'early_childhood' => ['education', null, ['Nidi e scuole dell’infanzia', 'Nurseries and kindergartens', 'Crèches et écoles maternelles', 'الحضانات ورياض الأطفال']],
        'homework_support' => ['education', null, ['Aiuto compiti e doposcuola', 'Homework help and after-school', 'Aide aux devoirs', 'المساعدة في الواجبات المدرسية']],
        'italian_language' => [null, 'language', ['Lingua italiana', 'Italian language', 'Langue italienne', 'اللغة الإيطالية']],
        'italian_courses' => ['italian_language', null, ['Corsi di italiano', 'Italian courses', 'Cours d’italien', 'دورات اللغة الإيطالية']],
        'adult_education' => ['italian_language', null, ['Istruzione per adulti e titoli di studio', 'Adult education and school certificates', 'Éducation des adultes et diplômes', 'تعليم الكبار والشهادات الدراسية']],
        'mediation' => [null, 'mediation', ['Mediazione e interpretariato', 'Mediation and interpreting', 'Médiation et interprétariat', 'الوساطة والترجمة الفورية']],
        'cultural_mediation' => ['mediation', null, ['Mediazione linguistico-culturale', 'Language and cultural mediation', 'Médiation linguistique et culturelle', 'الوساطة اللغوية والثقافية']],
        'social_support' => [null, 'support', ['Aiuto sociale', 'Social support', 'Aide sociale', 'المساعدة الاجتماعية']],
        'social_services' => ['social_support', null, ['Servizi sociali', 'Social services', 'Services sociaux', 'الخدمات الاجتماعية']],
        'food_clothes' => ['social_support', null, ['Cibo e vestiti', 'Food and clothes', 'Nourriture et vêtements', 'الطعام والملابس']],
        'information_desks' => ['social_support', null, ['Sportelli informativi per stranieri', 'Information desks for foreigners', 'Guichets d’information pour étrangers', 'مكاتب إرشاد للأجانب']],
        'family_women' => [null, 'family', ['Famiglia e donne', 'Family and women', 'Famille et femmes', 'الأسرة والنساء']],
        'anti_violence' => ['family_women', null, ['Centri antiviolenza', 'Anti-violence centres', 'Centres contre les violences', 'مراكز مناهضة العنف']],
        'maternity' => ['family_women', null, ['Gravidanza e maternità', 'Pregnancy and maternity', 'Grossesse et maternité', 'الحمل والأمومة']],
        'community' => [null, 'community', ['Comunità e associazioni', 'Communities and associations', 'Communautés et associations', 'الجاليات والجمعيات']],
        'community_associations' => ['community', null, ['Associazioni di comunità', 'Community associations', 'Associations communautaires', 'جمعيات الجاليات']],
    ];

    $locales = ['it', 'en', 'fr', 'ar'];
    $db = $c->get(Database::class);

    $db->transaction(static function (Database $db) use ($needs, $categories, $locales): void {
        $order = 0;
        foreach ($categories as $code => [$parent, $icon, $names]) {
            $order += 10;
            $parentId = $parent === null ? null : (int) $db->fetchValue('SELECT id FROM categories WHERE code = ?', [$parent]);
            $db->execute(
                'INSERT INTO categories (code, parent_id, icon, sort_order) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), icon = VALUES(icon)',
                [$code, $parentId, $icon, $order],
            );
            $id = (int) $db->fetchValue('SELECT id FROM categories WHERE code = ?', [$code]);
            foreach ($locales as $index => $locale) {
                $db->execute(
                    "INSERT IGNORE INTO category_translations (category_id, locale, name, status, source_hash) VALUES (?, ?, ?, ?, ?)",
                    [$id, $locale, $names[$index], $locale === 'it' ? 'approved' : 'to_review', hash('sha256', $names[0])],
                );
            }
        }

        $order = 0;
        foreach ($needs as $code => [$icon, $featured, $labels, $linked]) {
            $order += 10;
            $db->execute(
                'INSERT INTO needs (code, icon, sort_order, is_featured) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE icon = VALUES(icon), is_featured = VALUES(is_featured)',
                [$code, $icon, $order, $featured],
            );
            $id = (int) $db->fetchValue('SELECT id FROM needs WHERE code = ?', [$code]);
            foreach ($locales as $index => $locale) {
                $db->execute(
                    "INSERT IGNORE INTO need_translations (need_id, locale, label, status, source_hash) VALUES (?, ?, ?, ?, ?)",
                    [$id, $locale, $labels[$index], $locale === 'it' ? 'approved' : 'to_review', hash('sha256', $labels[0])],
                );
            }
            foreach ($linked as $categoryCode) {
                $db->execute(
                    'INSERT IGNORE INTO need_category (need_id, category_id) SELECT ?, id FROM categories WHERE code = ?',
                    [$id, $categoryCode],
                );
            }
        }
    });
};
