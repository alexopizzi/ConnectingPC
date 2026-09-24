<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Support\Slug;

/*
 * DATI DIMOSTRATIVI FITTIZI (solo `seed --demo`, mai in produzione) — comunità, associazioni, mediatori (v0.7.0).
 * Nomi, persone, indirizzi e recapiti sono inventati. I mediatori coprono tutti i casi di visibilità:
 * profilo pubblico con consenso, solo operatori (conteggiato in forma aggregata), solo admin, "pubblico" senza consenso.
 */
return static function (Container $c): void {
    if (App::environment() === 'production') {
        throw new RuntimeException('I dati dimostrativi non si caricano in produzione.');
    }

    // nome => [tipo, di comunità?, comune, luogo pubblico?, comunità, paesi, lingue, [descrizione, attività, partecipazione]]
    $associations = [
        'Associazione Esempio Teranga' => ['community_association', 1, 'Piacenza', 1, ['senegalese', 'francophone_africa'], ['SN'], ['it', 'fr', 'wo'], [
            'Associazione fittizia di cittadini senegalesi nata per sostenere i connazionali appena arrivati a Piacenza.',
            'Accoglienza dei nuovi arrivati, doposcuola per ragazzi, feste della comunità, aiuto per orientarsi negli uffici.',
            'Puoi venire alla sede il sabato pomeriggio o scrivere una email. Tutte le attività sono gratuite.',
        ]],
        'Associazione Esempio Al Wafa' => ['cultural_association', 1, 'Piacenza', 1, ['arabic_speaking', 'moroccan', 'tunisian', 'egyptian'], ['MA', 'TN', 'EG'], ['it', 'ar', 'fr'], [
            'Associazione culturale fittizia di persone arabofone residenti in provincia.',
            'Corsi di lingua araba per bambini, corsi di italiano per donne, incontri culturali, sostegno alle famiglie.',
            'Le iscrizioni ai corsi aprono a settembre. Per informazioni telefona nel pomeriggio.',
        ]],
        'Associazione Esempio Shqiponja' => ['community_association', 1, 'Castel San Giovanni', 1, ['albanian'], ['AL', 'XK'], ['it', 'sq'], [
            'Associazione fittizia della comunità albanese e kosovara del distretto di Ponente.',
            'Eventi culturali, supporto per le pratiche di cittadinanza, gruppo sportivo di calcio.',
            'Contattaci per email: ti rispondiamo in italiano o in albanese.',
        ]],
        'Associazione Esempio Dacia' => ['community_association', 1, "Fiorenzuola d'Arda", 1, ['romanian'], ['RO', 'MD'], ['it', 'ro'], [
            'Associazione fittizia di cittadini romeni e moldavi del distretto di Levante.',
            'Informazioni su lavoro e contratti, corsi di lingua romena per bambini, feste tradizionali.',
            'Le attività si svolgono la domenica. Puoi partecipare anche se non sei socio.',
        ]],
        'Associazione Esempio Kalyna' => ['community_association', 1, 'Piacenza', 0, ['ukrainian'], ['UA'], ['it', 'uk', 'ru'], [
            'Associazione fittizia di sostegno alle persone ucraine arrivate in provincia di Piacenza.',
            'Raccolta di beni di prima necessità, supporto psicologico di gruppo, lezioni di italiano.',
            'Non abbiamo una sede aperta al pubblico: scrivici e ti diamo appuntamento.',
        ]],
        'Associazione Esempio Punjab Sangat' => ['religious_organization', 1, 'Podenzano', 1, ['indian_punjabi'], ['IN'], ['it', 'pa', 'hi', 'en'], [
            'Realtà religiosa fittizia con attività sociali per la comunità punjabi.',
            'Pasti comunitari aperti a tutti, aiuto nella ricerca di lavoro in agricoltura, corsi di italiano.',
            'Le porte sono aperte tutti i giorni. Per i corsi chiedi ai volontari.',
        ]],
        'Associazione Esempio Mundo Latino' => ['cultural_association', 1, 'Piacenza', 1, ['latin_american', 'ecuadorian'], ['EC', 'PE', 'DO'], ['it', 'es', 'pt'], [
            'Associazione culturale fittizia delle comunità latinoamericane di Piacenza.',
            'Musica e danza, torneo di calcio, sportello di orientamento per famiglie e badanti.',
            'Vieni a trovarci il venerdì sera o scrivici sui social.',
        ]],
        'Associazione Esempio Ponti di Culture' => ['volunteer_association', 0, 'Rottofreno', 1, ['intercultural'], [], ['it', 'en', 'fr', 'ar'], [
            'Associazione di volontariato fittizia che riunisce persone italiane e straniere di molte provenienze.',
            'Cene interculturali, laboratori per bambini, gruppi di conversazione in italiano, biblioteca multilingue.',
            'Chiunque può partecipare. Ci incontriamo il mercoledì sera.',
        ]],
        'Associazione Esempio Wuxing' => ['community_association', 1, 'Piacenza', 0, ['chinese'], ['CN'], ['it', 'zh'], [
            'Associazione fittizia della comunità cinese di Piacenza.',
            'Corsi di cinese per bambini, supporto per imprenditori, festa del Capodanno lunare.',
            'Scrivici una email per conoscere le attività.',
        ]],
        'Associazione Esempio Naija' => ['community_association', 1, 'Bobbio', 1, ['nigerian', 'intercultural'], ['NG', 'GH'], ['it', 'en', 'yo'], [
            'Associazione fittizia di cittadini nigeriani e dell’Africa occidentale anglofona.',
            'Orientamento ai servizi, incontri per i giovani, sostegno alle donne.',
            'Chiamaci o passa in sede il sabato mattina.',
        ]],
    ];
    // Comunità collegate agli enti dimostrativi esistenti (v0.4.0)
    $existing = [
        'Associazione Esempio Ponte' => [['senegalese', 'francophone_africa', 'intercultural'], ['SN', 'CI', 'ML']],
        'Rete Mediatori Esempio' => [['intercultural', 'arabic_speaking'], []],
        'Centro Esempio Sospeso' => [['pakistani'], ['PK']],
    ];

    // [nome, cognome, nome visualizzato, ente, tipi, disponibilità, visibilità, consenso?, lingue [codice => livello], ambiti, territori, bio, contatto]
    $mediators = [
        ['Amina', 'Esempio-Kane', 'Amina K.', 'Rete Mediatori Esempio', 'linguistic,cultural', 'available', 'public', true, ['ar' => 'native', 'fr' => 'c1', 'it' => 'c2'], ['health', 'school'], ['Distretto Città di Piacenza'], 'Mediatrice linguistico-culturale con esperienza in ospedale e nelle scuole.', 'amina.k@example.org'],
        ['Moussa', 'Esempio-Diop', 'Moussa D.', 'Associazione Esempio Teranga', 'linguistic,cultural', 'limited', 'public', true, ['wo' => 'native', 'fr' => 'native', 'it' => 'c1'], ['public_administration', 'employment'], ['Distretto Città di Piacenza', 'Distretto di Ponente'], 'Accompagna le persone negli uffici pubblici e nei colloqui di lavoro.', 'moussa.d@example.org'],
        ['Olena', 'Esempio-Koval', 'Olena K.', 'Associazione Esempio Kalyna', 'linguistic', 'available', 'public', true, ['uk' => 'native', 'ru' => 'native', 'it' => 'c1', 'en' => 'b2'], ['health', 'social_services', 'reception'], ['Provincia di Piacenza'], 'Interprete e mediatrice per famiglie ucraine accolte in provincia.', 'olena.k@example.org'],
        ['Arben', 'Esempio-Hoxha', 'Arben H.', null, 'linguistic', 'available', 'public', true, ['sq' => 'native', 'it' => 'c2'], ['justice', 'public_administration'], ['Distretto di Ponente'], 'Mediatore linguistico libero professionista, anche per udienze e uffici.', 'arben.h@example.org'],
        ['Lucía', 'Esempio-Paredes', 'Lucía P.', 'Associazione Esempio Mundo Latino', 'cultural,intercultural', 'unavailable', 'public', true, ['es' => 'native', 'pt' => 'c1', 'it' => 'c2'], ['school', 'housing'], ['Distretto Città di Piacenza'], 'Mediatrice interculturale nelle scuole; al momento non ha disponibilità.', 'lucia.p@example.org'],
        ['Gurpreet', 'Esempio-Singh', 'Gurpreet S.', 'Associazione Esempio Punjab Sangat', 'linguistic', 'available', 'public', true, ['pa' => 'native', 'hi' => 'c2', 'en' => 'c1', 'it' => 'b2'], ['employment', 'health'], ['Distretto di Levante'], 'Supporta lavoratori punjabi nei rapporti con i datori di lavoro e con i servizi sanitari.', 'gurpreet.s@example.org'],
        // Solo operatori: al pubblico compaiono solo in forma aggregata tramite l'organizzazione
        ['Fatima', 'Esempio-Benali', 'Fatima B.', 'Rete Mediatori Esempio', 'linguistic,cultural', 'available', 'operators', false, ['ar' => 'native', 'fr' => 'c2', 'it' => 'c1'], ['health', 'social_services'], ['Distretto di Levante'], 'Mediatrice per consultori e servizi sociali.', 'fatima.b@example.org'],
        ['Youssef', 'Esempio-Amrani', 'Youssef A.', 'Rete Mediatori Esempio', 'linguistic', 'limited', 'operators', false, ['ar' => 'native', 'it' => 'c1'], ['justice', 'reception'], ['Provincia di Piacenza'], 'Interprete per colloqui in questura e nei centri di accoglienza.', 'youssef.a@example.org'],
        ['Tesfay', 'Esempio-Gebre', 'Tesfay G.', 'Rete Mediatori Esempio', 'linguistic,cultural', 'available', 'operators', false, ['ti' => 'native', 'am' => 'c1', 'en' => 'b2', 'it' => 'b2'], ['reception', 'health'], ['Distretto Città di Piacenza'], 'Mediatore per persone richiedenti asilo del Corno d’Africa.', 'tesfay.g@example.org'],
        ['Mei', 'Esempio-Lin', 'Mei L.', 'Associazione Esempio Wuxing', 'linguistic', 'available', 'operators', false, ['zh' => 'native', 'it' => 'c1'], ['school', 'employment'], ['Distretto Città di Piacenza'], 'Mediatrice per le scuole e per le piccole imprese.', 'mei.l@example.org'],
        ['Irina', 'Esempio-Popescu', 'Irina P.', 'Associazione Esempio Dacia', 'linguistic', 'available', 'operators', false, ['ro' => 'native', 'it' => 'c2'], ['health', 'employment'], ['Distretto di Levante'], 'Mediatrice per il lavoro domestico e di cura.', 'irina.p@example.org'],
        ['Imran', 'Esempio-Qureshi', 'Imran Q.', 'Associazione Esempio Ponte', 'linguistic', 'limited', 'operators', false, ['ur' => 'native', 'pa' => 'c1', 'en' => 'c1', 'it' => 'b2'], ['public_administration'], ['Provincia di Piacenza'], 'Accompagna le persone pakistane negli uffici.', 'imran.q@example.org'],
        // "Pubblico" ma senza consenso registrato: il server lo tratta come "solo operatori"
        ['Aissatou', 'Esempio-Ba', 'Aissatou B.', 'Associazione Esempio Teranga', 'cultural', 'available', 'public', false, ['ff' => 'native', 'wo' => 'c2', 'fr' => 'c2', 'it' => 'b2'], ['school'], ['Distretto Città di Piacenza'], 'Mediatrice culturale nelle scuole; consenso alla pubblicazione non ancora raccolto.', 'aissatou.b@example.org'],
        // Solo admin: mai visibili fuori dall'area amministrativa
        ['Karim', 'Esempio-Haddad', 'Karim H.', 'Rete Mediatori Esempio', 'linguistic', 'unknown', 'admin', false, ['ar' => 'native', 'ku' => 'c1', 'it' => 'b2'], ['justice'], ['Provincia di Piacenza'], 'Profilo in verifica.', 'karim.h@example.org'],
        ['Samira', 'Esempio-Noori', 'Samira N.', null, 'linguistic', 'unknown', 'admin', false, ['fa' => 'native', 'ps' => 'c1', 'it' => 'b1'], ['reception'], ['Distretto di Ponente'], 'Profilo in verifica.', 'samira.n@example.org'],
    ];

    $db = $c->get(Database::class);
    $now = gmdate('Y-m-d H:i:s');

    $db->transaction(static function (Database $db) use ($associations, $existing, $mediators, $now): void {
        $communityId = static fn (string $code): int => (int) ($db->fetchValue('SELECT id FROM communities WHERE code = ?', [$code])
            ?? throw new RuntimeException("Comunità sconosciuta: $code"));
        $territory = static fn (string $name): array => $db->fetchOne('SELECT id, centroid_lat, centroid_lng FROM territories WHERE name = ?', [$name])
            ?? throw new RuntimeException("Territorio sconosciuto: $name");
        $link = static function (int $orgId, array $communities, array $countries) use ($db, $communityId): void {
            foreach ($communities as $code) {
                $db->execute('INSERT IGNORE INTO organization_communities (organization_id, community_id) VALUES (?, ?)', [$orgId, $communityId($code)]);
            }
            foreach ($countries as $country) {
                $db->execute('INSERT IGNORE INTO organization_countries (organization_id, country_code) VALUES (?, ?)', [$orgId, $country]);
            }
        };

        $index = 0;
        foreach ($associations as $name => [$type, $community, $town, $publicPlace, $communities, $countries, $languages, [$description, $activities, $participation]]) {
            $index++;
            if ($db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$name]) !== null) {
                continue;
            }
            $orgId = $db->insert('organizations', [
                'name' => $name, 'organization_type_id' => (int) $db->fetchValue('SELECT id FROM organization_types WHERE code = ?', [$type]),
                'is_community_based' => $community, 'census_status' => 'censused', 'listing_status' => 'listed',
                'verification_status' => 'verified', 'access_status' => 'not_enabled', 'publication_status' => 'published',
                'published_at' => $now, 'verified_at' => $now, 'status_note' => 'Dato dimostrativo fittizio',
                'website' => 'https://' . Slug::make($name) . '.example.org',
            ]);
            $db->insert('organization_translations', [
                'organization_id' => $orgId, 'locale' => 'it', 'slug' => Slug::make($name), 'status' => 'approved',
                'description' => $description, 'activities' => $activities, 'participation_info' => $participation,
            ]);
            foreach ($languages as $code) {
                $db->execute('INSERT IGNORE INTO organization_languages (organization_id, language_code) VALUES (?, ?)', [$orgId, $code]);
            }
            $link($orgId, $communities, $countries);

            $place = $territory($town);
            $offset = static fn (int $seed): float => ((($seed * 7919) % 200) - 100) / 50000;
            $siteId = $db->insert('sites', [
                'organization_id' => $orgId, 'name' => 'Sede ' . $town, 'address_line' => sprintf('Via Esempio delle Culture %d', $index * 4),
                'territory_id' => (int) $place['id'], 'lat' => round((float) $place['centroid_lat'] + $offset($index + 3), 6),
                'lng' => round((float) $place['centroid_lng'] + $offset($index + 29), 6), 'geo_source' => 'manual', 'geo_checked' => 1,
                'is_public_place' => $publicPlace, 'step_free_access' => ['yes', 'partial', 'unknown'][$index % 3],
                'publication_status' => 'published', 'published_at' => $now, 'verified_at' => $now,
            ]);
            if ($publicPlace) {
                foreach ([[6, '15:00', '19:00'], [3, '18:00', '21:00']] as [$day, $opens, $closes]) {
                    $db->insert('opening_hours', ['owner_type' => 'site', 'owner_id' => $siteId, 'weekday' => $day, 'opens_at' => $opens, 'closes_at' => $closes, 'by_appointment' => 0]);
                }
            }
            $db->insert('contact_points', ['owner_type' => 'organization', 'owner_id' => $orgId, 'kind' => 'email', 'value' => Slug::make(str_replace('Associazione Esempio ', '', $name)) . '@example.org', 'visibility' => 'public', 'sort_order' => 1]);
            $db->insert('contact_points', ['owner_type' => 'organization', 'owner_id' => $orgId, 'kind' => 'phone', 'value' => sprintf('+39 0523 000 %03d', 800 + $index), 'visibility' => 'public', 'sort_order' => 2]);
            // Persona di riferimento: recapito solo per gli amministratori
            $db->insert('contact_points', ['owner_type' => 'organization', 'owner_id' => $orgId, 'kind' => 'mobile', 'value' => sprintf('+39 333 000 %04d', $index), 'visibility' => 'admin', 'sort_order' => 3]);
        }

        foreach ($existing as $name => [$communities, $countries]) {
            $orgId = $db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$name]);
            if ($orgId !== null) {
                $link((int) $orgId, $communities, $countries);
            }
        }

        foreach ($mediators as [$first, $last, $display, $org, $types, $availability, $visibility, $consent, $languages, $domains, $territories, $bio, $email]) {
            if ($db->fetchValue('SELECT id FROM mediators WHERE first_name = ? AND last_name = ?', [$first, $last]) !== null) {
                continue;
            }
            $orgId = $org === null ? null : $db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$org]);
            $id = $db->insert('mediators', [
                'first_name' => $first, 'last_name' => $last, 'public_display_name' => $display,
                'organization_id' => $orgId === null ? null : (int) $orgId, 'mediation_types' => $types,
                'availability' => $availability, 'profile_visibility' => $visibility,
                'public_consent_at' => $consent ? $now : null, 'consent_reference' => $consent ? 'Modulo dimostrativo n. ' . $last : null,
                'verification_status' => $visibility === 'admin' ? 'pending' : 'verified', 'verified_at' => $visibility === 'admin' ? null : $now,
                'qualifications_admin' => 'Attestato fittizio di mediazione linguistico-culturale (dato riservato agli amministratori).',
                'admin_notes' => 'Dato dimostrativo fittizio',
                'publication_status' => 'published', 'published_at' => $now,
            ]);
            $db->insert('mediator_translations', ['mediator_id' => $id, 'locale' => 'it', 'bio' => $bio, 'status' => 'approved']);
            foreach ($languages as $code => $level) {
                $db->insert('mediator_languages', ['mediator_id' => $id, 'language_code' => $code, 'proficiency' => $level]);
            }
            foreach ($domains as $code) {
                $db->execute('INSERT INTO mediator_domains (mediator_id, mediation_domain_id) SELECT ?, id FROM mediation_domains WHERE code = ?', [$id, $code]);
            }
            foreach ($territories as $name) {
                $db->insert('mediator_territories', ['mediator_id' => $id, 'territory_id' => (int) $territory($name)['id']]);
            }
            $db->insert('contact_points', ['owner_type' => 'mediator', 'owner_id' => $id, 'kind' => 'email', 'value' => $email, 'visibility' => $visibility === 'admin' ? 'admin' : ($consent ? 'public' : 'operators'), 'sort_order' => 1]);
            $db->insert('contact_points', ['owner_type' => 'mediator', 'owner_id' => $id, 'kind' => 'mobile', 'value' => sprintf('+39 340 000 %04d', $id), 'visibility' => $visibility === 'admin' ? 'admin' : 'operators', 'sort_order' => 2]);
        }
    });
};
