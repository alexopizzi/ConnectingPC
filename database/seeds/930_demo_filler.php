<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Support\Slug;

/*
 * CONTENUTI DI RIEMPIMENTO (solo `seed --demo`, mai in produzione) — D-031.
 * Per ogni sottocategoria attiva e per ciascuno dei 3 distretti crea un servizio di prova, se il distretto
 * non ne ha già uno per quella sottocategoria. Enti "Rete … Esempio", indirizzi, telefoni ed email inventati;
 * coordinate vicine al centroide reale del comune (vedi data/municipalities.php). Testi generici nelle 4 lingue.
 */
return static function (Container $c): void {
    if (App::environment() === 'production') {
        throw new RuntimeException('I dati dimostrativi non si caricano in produzione.');
    }

    $districts = [
        'ponente' => ['Distretto di Ponente', ['Castel San Giovanni', 'Borgonovo Val Tidone', 'Bobbio', 'Rivergaro', 'Rottofreno', 'Pianello Val Tidone'],
            ['it' => 'nel distretto di Ponente', 'en' => 'in the Ponente district', 'fr' => 'dans le district de Ponente', 'ar' => 'في منطقة بونينتي']],
        'piacenza' => ['Distretto Città di Piacenza', ['Piacenza'],
            ['it' => 'nella città di Piacenza', 'en' => 'in the city of Piacenza', 'fr' => 'dans la ville de Plaisance', 'ar' => 'في مدينة بياتشنزا']],
        'levante' => ['Distretto di Levante', ["Fiorenzuola d'Arda", 'Cortemaggiore', 'Carpaneto Piacentino', 'Podenzano', "Ponte dell'Olio", 'Castell\'Arquato'],
            ['it' => 'nel distretto di Levante', 'en' => 'in the Levante district', 'fr' => 'dans le district de Levante', 'ar' => 'في منطقة ليفانتي']],
    ];
    $test = ['it' => 'servizio di prova', 'en' => 'test service', 'fr' => 'service de test', 'ar' => 'خدمة تجريبية'];
    $summary = [
        'it' => 'Scheda di prova per «%s» %s. I contenuti sono inventati e andranno sostituiti con quelli reali.',
        'en' => 'Test entry for “%s” %s. The content is invented and will be replaced with real information.',
        'fr' => 'Fiche de test pour « %s » %s. Le contenu est inventé et sera remplacé par des informations réelles.',
        'ar' => 'بطاقة تجريبية لخدمة «%s» %s. المحتوى مُختلَق وسيُستبدل بمعلومات حقيقية.',
    ];
    $orgTypes = [
        'documents' => 'public_service', 'education' => 'school', 'work' => 'public_service', 'legal' => 'volunteer_association',
        'families' => 'municipality', 'tax' => 'patronage', 'health' => 'health_authority', 'social' => 'municipality',
        'housing' => 'cooperative', 'mediation' => 'association', 'aggregation' => 'cultural_association',
        'transport' => 'public_service', 'safety' => 'public_service',
    ];
    $hourPatterns = [
        [[1, 5, '09:00', '12:30', 0]],
        [[1, 5, '08:30', '13:00', 0], [2, 2, '14:30', '17:00', 0]],
        [[2, 2, '15:00', '18:30', 1], [4, 4, '15:00', '18:30', 1]],
        [[1, 1, '09:00', '12:00', 0], [3, 3, '09:00', '12:00', 0], [5, 5, '14:00', '17:00', 0]],
        [[1, 5, '09:00', '18:00', 0], [6, 6, '09:00', '12:00', 0]],
    ];
    $languagePool = [['en', 'staff'], ['fr', 'staff'], ['ar', 'mediator'], ['es', 'staff'], ['sq', 'staff'], ['ro', 'staff'], ['uk', 'mediator'], ['ur', 'mediator'], ['zh', 'mediator'], ['wo', 'mediator']];
    $costs = ['free', 'free', 'free', 'partly_free', 'paid'];
    $mediations = ['available', 'on_request', 'not_available', 'on_request'];
    $bookings = ['not_needed', 'recommended', 'required'];
    $accesses = ['in_person', 'in_person,phone', 'in_person,online', 'phone,email', 'in_person,phone,email'];

    $db = $c->get(Database::class);
    $now = gmdate('Y-m-d H:i:s');

    $db->transaction(static function (Database $db) use ($districts, $test, $summary, $orgTypes, $hourPatterns, $languagePool, $costs, $mediations, $bookings, $accesses, $now): void {
        // Nomi delle categorie in 4 lingue
        $names = [];
        foreach ($db->fetchAll('SELECT category_id, locale, name FROM category_translations') as $row) {
            $names[(int) $row['category_id']][(string) $row['locale']] = (string) $row['name'];
        }
        $tops = $db->fetchAll('SELECT id, code FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order');
        $subs = $db->fetchAll('SELECT c.id, c.code, c.parent_id, p.code AS parent_code FROM categories c JOIN categories p ON p.id = c.parent_id WHERE c.is_active = 1 ORDER BY c.sort_order');
        $needsByCategory = [];
        foreach ($db->fetchAll('SELECT need_id, category_id FROM need_category') as $row) {
            $needsByCategory[(int) $row['category_id']][] = (int) $row['need_id'];
        }
        $town = static function (string $name) use ($db): array {
            $row = $db->fetchOne("SELECT id, centroid_lat, centroid_lng FROM territories WHERE type = 'municipality' AND name = ?", [$name])
                ?? throw new RuntimeException("Comune sconosciuto: $name");

            return [(int) $row['id'], (float) $row['centroid_lat'], (float) $row['centroid_lng']];
        };

        $counter = 0;
        foreach ($districts as $districtKey => [$districtName, $towns, $where]) {
            $districtId = (int) $db->fetchValue("SELECT id FROM territories WHERE type = 'district' AND name = ?", [$districtName]);

            // Un ente di prova per area in ogni distretto, con una sede
            $orgSites = [];
            foreach ($tops as $index => $top) {
                $topId = (int) $top['id'];
                $orgName = sprintf('Rete %s Esempio – %s', $names[$topId]['it'] ?? $top['code'], $districtName);
                $orgId = $db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$orgName]);
                if ($orgId === null) {
                    $typeId = (int) $db->fetchValue('SELECT id FROM organization_types WHERE code = ?', [$orgTypes[$top['code']] ?? 'other']);
                    $orgId = $db->insert('organizations', [
                        'name' => $orgName, 'organization_type_id' => $typeId, 'census_status' => 'censused', 'listing_status' => 'listed',
                        'verification_status' => 'verified', 'access_status' => 'not_enabled', 'publication_status' => 'published',
                        'published_at' => $now, 'verified_at' => $now, 'status_note' => 'Contenuto di riempimento fittizio',
                        'is_community_based' => $top['code'] === 'aggregation' ? 1 : 0,
                    ]);
                    $db->insert('organization_translations', [
                        'organization_id' => $orgId, 'locale' => 'it', 'slug' => Slug::make($orgName), 'status' => 'approved',
                        'description' => sprintf('Ente fittizio che raccoglie i servizi di prova dell’area «%s» %s.', $names[$topId]['it'] ?? '', $where['it']),
                    ]);
                    $db->execute('INSERT IGNORE INTO organization_languages (organization_id, language_code) VALUES (?, ?)', [$orgId, 'it']);

                    [$territoryId, $lat, $lng] = $town($towns[$index % count($towns)]);
                    $offset = static fn (int $seed): float => ((($seed * 7919) % 200) - 100) / 40000;
                    $siteId = $db->insert('sites', [
                        'organization_id' => $orgId, 'name' => 'Sportello ' . $towns[$index % count($towns)],
                        'address_line' => sprintf('Via Esempio %d', 10 + $index * 3), 'territory_id' => $territoryId,
                        'lat' => round($lat + $offset($index + 1), 6), 'lng' => round($lng + $offset($index + 17), 6),
                        'geo_source' => 'manual', 'geo_checked' => 1, 'step_free_access' => ['yes', 'partial', 'unknown'][$index % 3],
                        'publication_status' => 'published', 'published_at' => $now, 'verified_at' => $now,
                    ]);
                    foreach ($hourPatterns[$index % count($hourPatterns)] as [$from, $to, $opens, $closes, $appointment]) {
                        for ($day = $from; $day <= $to; $day++) {
                            $db->insert('opening_hours', ['owner_type' => 'site', 'owner_id' => $siteId, 'weekday' => $day, 'opens_at' => $opens, 'closes_at' => $closes, 'by_appointment' => $appointment]);
                        }
                    }
                    $db->insert('contact_points', ['owner_type' => 'site', 'owner_id' => $siteId, 'kind' => 'phone', 'value' => sprintf('+39 0523 000 %03d', 300 + $siteId % 700), 'visibility' => 'public', 'sort_order' => 1]);
                    $db->insert('contact_points', ['owner_type' => 'site', 'owner_id' => $siteId, 'kind' => 'email', 'value' => sprintf('%s.%s@example.org', $top['code'], $districtKey), 'visibility' => 'public', 'sort_order' => 2]);
                    $orgSites[$top['code']] = [(int) $orgId, $siteId, $towns[$index % count($towns)]];
                } else {
                    $site = $db->fetchOne('SELECT s.id, t.name FROM sites s JOIN territories t ON t.id = s.territory_id WHERE s.organization_id = ? ORDER BY s.id LIMIT 1', [(int) $orgId]);
                    $orgSites[$top['code']] = [(int) $orgId, (int) $site['id'], (string) $site['name']];
                }
            }

            foreach ($subs as $sub) {
                $subId = (int) $sub['id'];
                $already = (int) $db->fetchValue(
                    "SELECT COUNT(*) FROM service_categories sc
                       JOIN service_sites ss ON ss.service_id = sc.service_id
                       JOIN sites si ON si.id = ss.site_id
                       JOIN territories t ON t.id = si.territory_id
                      WHERE sc.category_id = ? AND t.parent_id = ?",
                    [$subId, $districtId],
                );
                if ($already > 0 || !isset($orgSites[$sub['parent_code']])) {
                    continue;
                }
                $counter++;
                [$orgId, $siteId, $townName] = $orgSites[$sub['parent_code']];

                $serviceId = $db->insert('services', [
                    'organization_id' => $orgId, 'primary_category_id' => $subId,
                    'access_modes' => $accesses[$counter % count($accesses)], 'booking' => $bookings[$counter % count($bookings)],
                    'cost_type' => $costs[$counter % count($costs)], 'mediation' => $mediations[$counter % count($mediations)],
                    'publication_status' => 'published', 'published_at' => $now, 'verified_at' => $now,
                    'next_review_at' => gmdate('Y-m-d', strtotime('+' . (30 + $counter % 300) . ' days')),
                ]);
                foreach (['it', 'en', 'fr', 'ar'] as $locale) {
                    $subName = $names[$subId][$locale] ?? $names[$subId]['it'];
                    $name = sprintf('%s – %s (%s)', $subName, $townName, $test[$locale]);
                    $row = [
                        'service_id' => $serviceId, 'locale' => $locale, 'slug' => Slug::make($name), 'name' => $name,
                        'summary' => sprintf($summary[$locale], $subName, $where[$locale]), 'status' => 'approved',
                        'source_hash' => hash('sha256', $names[$subId]['it'] ?? ''),
                    ];
                    if ($locale === 'it') {
                        $row += [
                            'description' => "Servizio dimostrativo creato automaticamente per provare la piattaforma.\nLe informazioni reali saranno inserite dall’ente o dai gestori.",
                            'target_audience' => 'Persone straniere residenti o domiciliate ' . $where['it'] . '.',
                            'documents' => "- Documento d’identità o passaporto\n- Permesso di soggiorno (se ce l’hai)\n- Codice fiscale",
                            'access_info' => 'Telefona o scrivi prima di andare, per verificare orari e documenti.',
                            'cost_info' => 'Informazione di prova: verificare con l’ente.',
                            'keywords' => mb_strtolower($subName) . ' ' . $districtKey,
                        ];
                    }
                    $db->insert('service_translations', $row);
                }
                $db->insert('service_categories', ['service_id' => $serviceId, 'category_id' => $subId]);
                $db->insert('service_sites', ['service_id' => $serviceId, 'site_id' => $siteId, 'is_main' => 1]);
                foreach (array_unique([...($needsByCategory[$subId] ?? []), ...($needsByCategory[(int) $sub['parent_id']] ?? [])]) as $needId) {
                    $db->insert('service_needs', ['service_id' => $serviceId, 'need_id' => $needId, 'relevance' => 5]);
                }
                for ($i = 0; $i < 1 + $counter % 3; $i++) {
                    [$code, $mode] = $languagePool[($counter + $i * 3) % count($languagePool)];
                    $db->execute('INSERT IGNORE INTO service_languages (service_id, language_code, mode) VALUES (?, ?, ?)', [$serviceId, $code, $mode]);
                    $db->execute('INSERT IGNORE INTO organization_languages (organization_id, language_code) VALUES (?, ?)', [$orgId, $code]);
                }
            }
        }
    });
};
