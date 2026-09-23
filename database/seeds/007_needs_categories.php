<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Tassonomia di bisogni e categorie (vault "65"), allineata alle aree e sottocategorie della guida
 * "Servizi utili a Piacenza e provincia" del progetto FAMI 966 (D-031), più Casa e Mediazione richieste dal brief.
 * BOZZA da validare con Area IV e operatori. Traduzioni en/fr/ar: bozze prodotte dall'IA da revisionare
 * (stato to_review); le etichette delle tassonomie sono mostrate se non sono bozze (D-028).
 * Idempotente: aggiorna icone e gerarchia, non sovrascrive i testi modificati in admin, disattiva i codici non più usati.
 */
return static function (Container $c): void {
    // codice => [genitore|null, icona, [it, en, fr, ar]]
    $categories = [
        'documents' => [null, 'id-card', ['Documenti', 'Documents', 'Papiers et documents', 'الوثائق']],
        'residence_permit' => ['documents', null, ['Permesso di soggiorno', 'Residence permit', 'Titre de séjour', 'تصريح الإقامة']],
        'identity_card' => ['documents', null, ['Carta d’identità', 'Identity card', 'Carte d’identité', 'بطاقة الهوية']],
        'registry_residence' => ['documents', null, ['Residenza e anagrafe', 'Registered residence', 'Résidence et état civil', 'الإقامة والسجل المدني']],
        'health_card' => ['documents', null, ['Tessera sanitaria', 'Health card', 'Carte sanitaire', 'البطاقة الصحية']],
        'tax_code' => ['documents', null, ['Codice fiscale', 'Tax code', 'Code fiscal', 'الرمز الضريبي']],
        'driving_licence' => ['documents', null, ['Patente', 'Driving licence', 'Permis de conduire', 'رخصة القيادة']],
        'digital_identity' => ['documents', null, ['Identità digitale (SPID, CIE)', 'Digital identity (SPID, CIE)', 'Identité numérique (SPID, CIE)', 'الهوية الرقمية (SPID، CIE)']],
        'family_reunification' => ['documents', null, ['Ricongiungimento familiare', 'Family reunification', 'Regroupement familial', 'لمّ شمل الأسرة']],
        'citizenship' => ['documents', null, ['Cittadinanza italiana', 'Italian citizenship', 'Nationalité italienne', 'الجنسية الإيطالية']],
        'international_protection' => ['documents', null, ['Protezione internazionale e asilo', 'International protection and asylum', 'Protection internationale et asile', 'الحماية الدولية واللجوء']],

        'education' => [null, 'school', ['Istruzione', 'Education', 'Éducation', 'التعليم']],
        'study_services' => ['education', null, ['Scuole e servizi per lo studio', 'Schools and study services', 'Écoles et services pour les études', 'المدارس وخدمات الدراسة']],
        'evening_schools' => ['education', null, ['Scuole serali', 'Evening schools', 'Cours du soir', 'المدارس المسائية']],
        'higher_education' => ['education', null, ['Istruzione e formazione superiore', 'Higher education and training', 'Enseignement et formation supérieurs', 'التعليم والتكوين العالي']],
        'education_guidance' => ['education', null, ['Orientamento', 'Guidance', 'Orientation', 'التوجيه']],
        'work_training' => ['education', null, ['Formazione lavoro', 'Job training', 'Formation professionnelle', 'التكوين المهني']],
        'italian_courses' => ['education', null, ['Corsi di lingua italiana', 'Italian language courses', 'Cours de langue italienne', 'دورات اللغة الإيطالية']],

        'work' => [null, 'briefcase', ['Lavoro', 'Work', 'Travail', 'العمل']],
        'job_centres' => ['work', null, ['Centri per l’impiego', 'Job centres', 'Centres pour l’emploi', 'مراكز التوظيف']],
        'job_orientation' => ['work', null, ['Orientamento al lavoro', 'Career guidance', 'Orientation professionnelle', 'التوجيه المهني']],
        'trade_unions' => ['work', null, ['Sindacati: servizi e tutela dei lavoratori', 'Trade unions: services and workers’ rights', 'Syndicats : services et défense des travailleurs', 'النقابات: الخدمات وحماية العمال']],
        'national_worker_bodies' => ['work', null, ['Enti nazionali di tutela dei lavoratori', 'National bodies for workers’ protection', 'Organismes nationaux de protection des travailleurs', 'الهيئات الوطنية لحماية العمال']],
        'employment_agencies' => ['work', null, ['Agenzie per il lavoro', 'Employment agencies', 'Agences pour l’emploi', 'وكالات التشغيل']],
        'civil_service' => ['work', null, ['Servizio civile', 'Civil service', 'Service civique', 'الخدمة المدنية']],
        'volunteering' => ['work', null, ['Volontariato', 'Volunteering', 'Bénévolat', 'العمل التطوعي']],
        'startup_coworking' => ['work', null, ['Start up e coworking', 'Start-ups and coworking', 'Start-up et coworking', 'الشركات الناشئة ومساحات العمل المشتركة']],

        'legal' => [null, 'legal', ['Assistenza legale', 'Legal assistance', 'Assistance juridique', 'المساعدة القانونية']],
        'free_legal_advice' => ['legal', null, ['Assistenza legale gratuita', 'Free legal assistance', 'Assistance juridique gratuite', 'مساعدة قانونية مجانية']],
        'anti_discrimination' => ['legal', null, ['Contro la discriminazione', 'Against discrimination', 'Contre la discrimination', 'مكافحة التمييز']],

        'families' => [null, 'family', ['Famiglie', 'Families', 'Familles', 'الأسر']],
        'family_centre' => ['families', null, ['Centro per le famiglie', 'Family centre', 'Centre pour les familles', 'مركز الأسر']],
        'preschool_enrolment' => ['families', null, ['Iscrizione alla scuola dell’infanzia e al nido', 'Nursery and kindergarten enrolment', 'Inscription à la crèche et à la maternelle', 'التسجيل في الحضانة وروضة الأطفال']],
        'after_school' => ['families', null, ['Doposcuola', 'After-school', 'Aide aux devoirs', 'الدعم المدرسي']],

        'tax' => [null, 'tax', ['Servizi fiscali e CAF', 'Tax services and CAF', 'Services fiscaux et CAF', 'الخدمات الضريبية ومراكز CAF']],
        'taxation' => ['tax', null, ['Fiscalità', 'Taxes', 'Fiscalité', 'الضرائب']],
        'caf' => ['tax', null, ['CAF e patronati', 'CAF and welfare advice centres', 'CAF et bureaux d’assistance sociale', 'مراكز CAF ومكاتب المساعدة الاجتماعية']],

        'health' => [null, 'health', ['Salute', 'Health', 'Santé', 'الصحة']],
        'ausl' => ['health', null, ['Azienda sanitaria (AUSL)', 'Local health authority (AUSL)', 'Agence sanitaire locale (AUSL)', 'الهيئة الصحية المحلية (AUSL)']],
        'other_clinics' => ['health', null, ['Altre strutture e ambulatori', 'Other facilities and clinics', 'Autres structures et dispensaires', 'مرافق وعيادات أخرى']],
        'health_paperwork' => ['health', null, ['Informazioni e pratiche sanitarie', 'Health information and paperwork', 'Informations et démarches de santé', 'المعلومات والمعاملات الصحية']],
        'family_counselling' => ['health', null, ['Consultori, gravidanza e maternità', 'Family health centres, pregnancy and maternity', 'Planning familial, grossesse et maternité', 'مراكز الاستشارة الأسرية والحمل والأمومة']],

        'social' => [null, 'support', ['Servizi sociali e socio-sanitari', 'Social and social-health services', 'Services sociaux et socio-sanitaires', 'الخدمات الاجتماعية والصحية الاجتماعية']],
        'personal_services' => ['social', null, ['Servizi alla persona', 'Personal services', 'Services à la personne', 'الخدمات الشخصية']],
        'social_help_centres' => ['social', null, ['Centri di aiuto sociale', 'Social help centres', 'Centres d’aide sociale', 'مراكز المساعدة الاجتماعية']],
        'food_clothes' => ['social', null, ['Cibo e vestiti', 'Food and clothes', 'Nourriture et vêtements', 'الطعام والملابس']],

        'housing' => [null, 'home', ['Casa', 'Housing', 'Logement', 'السكن']],
        'housing_help_desk' => ['housing', null, ['Sportelli casa e affitto', 'Housing and rent help desks', 'Guichets logement et loyer', 'مكاتب السكن والإيجار']],
        'housing_emergency' => ['housing', null, ['Emergenza abitativa e accoglienza', 'Emergency housing and shelter', 'Urgence logement et accueil', 'السكن الطارئ والإيواء']],

        'mediation' => [null, 'mediation', ['Mediazione e orientamento', 'Mediation and guidance', 'Médiation et orientation', 'الوساطة والتوجيه']],
        'cultural_mediation' => ['mediation', null, ['Mediazione linguistico-culturale', 'Language and cultural mediation', 'Médiation linguistique et culturelle', 'الوساطة اللغوية والثقافية']],
        'information_desks' => ['mediation', null, ['Sportelli informativi per stranieri', 'Information desks for foreigners', 'Guichets d’information pour étrangers', 'مكاتب إرشاد للأجانب']],

        'aggregation' => [null, 'community', ['Aggregazione e comunità', 'Meeting places and communities', 'Lieux de rencontre et communautés', 'أماكن اللقاء والجاليات']],
        'aggregation_centres' => ['aggregation', null, ['Centri di aggregazione', 'Community centres', 'Centres de rencontre', 'مراكز اللقاء']],
        'community_associations' => ['aggregation', null, ['Associazioni di comunità', 'Community associations', 'Associations communautaires', 'جمعيات الجاليات']],

        'transport' => [null, 'transport', ['Trasporti', 'Transport', 'Transports', 'النقل']],
        'public_transport' => ['transport', null, ['Trasporti pubblici', 'Public transport', 'Transports publics', 'النقل العام']],

        'safety' => [null, 'safety', ['Sicurezza', 'Safety', 'Sécurité', 'الأمان']],
        'emergency' => ['safety', null, ['Emergenza', 'Emergency', 'Urgence', 'الطوارئ']],
        'protection' => ['safety', null, ['Protezione e antiviolenza', 'Protection and anti-violence', 'Protection et lutte contre les violences', 'الحماية ومناهضة العنف']],
    ];

    // codice => [icona, in evidenza, [it, en, fr, ar], categorie collegate]
    $needs = [
        'documents' => ['id-card', 1, ['Documenti e permesso di soggiorno', 'Documents and residence permit', 'Papiers et titre de séjour', 'الوثائق وتصريح الإقامة'], ['documents']],
        'work' => ['briefcase', 1, ['Cerco lavoro', 'I am looking for a job', 'Je cherche un travail', 'أبحث عن عمل'], ['work']],
        'housing' => ['home', 1, ['Ho bisogno di una casa', 'I need a home', 'J’ai besoin d’un logement', 'أحتاج إلى سكن'], ['housing']],
        'health' => ['health', 1, ['Salute e medico', 'Health and doctor', 'Santé et médecin', 'الصحة والطبيب'], ['health']],
        'italian_language' => ['language', 1, ['Imparare l’italiano', 'Learn Italian', 'Apprendre l’italien', 'تعلّم اللغة الإيطالية'], ['italian_courses', 'evening_schools']],
        'school_children' => ['school', 1, ['Scuola e figli', 'School and children', 'École et enfants', 'المدرسة والأطفال'], ['study_services', 'preschool_enrolment', 'after_school']],
        'admin_procedures' => ['office', 1, ['Pratiche, tasse e uffici', 'Paperwork, taxes and offices', 'Démarches, impôts et bureaux', 'المعاملات والضرائب والمكاتب'], ['tax', 'registry_residence', 'tax_code', 'identity_card', 'health_paperwork']],
        'legal' => ['legal', 1, ['Aiuto legale', 'Legal help', 'Aide juridique', 'المساعدة القانونية'], ['legal']],
        'social_support' => ['support', 1, ['Aiuto sociale', 'Social support', 'Aide sociale', 'المساعدة الاجتماعية'], ['social']],
        'mediation' => ['mediation', 1, ['Ho bisogno di un mediatore', 'I need a mediator', 'J’ai besoin d’un médiateur', 'أحتاج إلى وسيط ثقافي'], ['mediation']],
        'family' => ['family', 1, ['Famiglia e figli piccoli', 'Family and young children', 'Famille et jeunes enfants', 'الأسرة والأطفال الصغار'], ['families', 'family_counselling']],
        'community' => ['community', 1, ['Associazioni e comunità', 'Associations and communities', 'Associations et communautés', 'الجمعيات والجاليات'], ['aggregation']],
        'training' => ['training', 1, ['Studio e formazione', 'Study and training', 'Études et formation', 'الدراسة والتكوين'], ['higher_education', 'education_guidance', 'work_training']],
        'transport' => ['transport', 1, ['Spostarmi: autobus e treni', 'Getting around: buses and trains', 'Me déplacer : bus et trains', 'التنقل: الحافلات والقطارات'], ['transport', 'driving_licence']],
        'safety' => ['safety', 1, ['Sicurezza ed emergenze', 'Safety and emergencies', 'Sécurité et urgences', 'الأمان وحالات الطوارئ'], ['safety']],
    ];

    $locales = ['it', 'en', 'fr', 'ar'];
    $db = $c->get(Database::class);

    $db->transaction(static function (Database $db) use ($needs, $categories, $locales): void {
        $order = 0;
        foreach ($categories as $code => [$parent, $icon, $names]) {
            $order += 10;
            $parentId = $parent === null ? null : (int) $db->fetchValue('SELECT id FROM categories WHERE code = ?', [$parent]);
            $db->execute(
                'INSERT INTO categories (code, parent_id, icon, sort_order, is_active) VALUES (?, ?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), icon = VALUES(icon), sort_order = VALUES(sort_order), is_active = 1',
                [$code, $parentId, $icon, $order],
            );
            $id = (int) $db->fetchValue('SELECT id FROM categories WHERE code = ?', [$code]);
            foreach ($locales as $index => $locale) {
                $db->execute(
                    'INSERT IGNORE INTO category_translations (category_id, locale, name, status, source_hash) VALUES (?, ?, ?, ?, ?)',
                    [$id, $locale, $names[$index], $locale === 'it' ? 'approved' : 'to_review', hash('sha256', $names[0])],
                );
            }
        }
        // Codici della tassonomia precedente non più usati: disattivati, non cancellati (restano i collegamenti storici).
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $db->execute("UPDATE categories SET is_active = 0 WHERE code NOT IN ($placeholders)", array_keys($categories));

        $order = 0;
        foreach ($needs as $code => [$icon, $featured, $labels, $linked]) {
            $order += 10;
            $db->execute(
                'INSERT INTO needs (code, icon, sort_order, is_featured) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE icon = VALUES(icon), sort_order = VALUES(sort_order), is_featured = VALUES(is_featured)',
                [$code, $icon, $order, $featured],
            );
            $id = (int) $db->fetchValue('SELECT id FROM needs WHERE code = ?', [$code]);
            foreach ($locales as $index => $locale) {
                $db->execute(
                    'INSERT INTO need_translations (need_id, locale, label, status, source_hash) VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE label = IF(translated_by IS NULL AND reviewed_by IS NULL, VALUES(label), label)',
                    [$id, $locale, $labels[$index], $locale === 'it' ? 'approved' : 'to_review', hash('sha256', $labels[0])],
                );
            }
            // Collegamenti bisogno → categorie riallineati alla tassonomia corrente
            $db->execute('DELETE FROM need_category WHERE need_id = ?', [$id]);
            foreach ($linked as $categoryCode) {
                $db->execute(
                    'INSERT IGNORE INTO need_category (need_id, category_id) SELECT ?, id FROM categories WHERE code = ?',
                    [$id, $categoryCode],
                );
            }
        }
    });
};
