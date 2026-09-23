<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Territorio del progetto (D-024): Emilia-Romagna → Provincia di Piacenza → 3 distretti socio-sanitari
 * (Ponente, Città di Piacenza, Levante) → 46 comuni, con codice ISTAT e centroide (database/seeds/data/municipalities.php).
 */
return static function (Container $c): void {
    $municipalities = require __DIR__ . '/data/municipalities.php';
    $districts = [
        'ponente' => ['Distretto di Ponente', 44.98, 9.44],
        'piacenza' => ['Distretto Città di Piacenza', 45.053475, 9.694746],
        'levante' => ['Distretto di Levante', 44.93, 9.85],
    ];

    $db = $c->get(Database::class);
    $db->transaction(static function (Database $db) use ($municipalities, $districts): void {
        $db->execute("INSERT IGNORE INTO territories (type, name, is_project_area) VALUES ('region', 'Emilia-Romagna', 0)");
        $regionId = (int) $db->fetchValue("SELECT id FROM territories WHERE type = 'region' AND name = 'Emilia-Romagna'");
        $db->execute(
            "INSERT IGNORE INTO territories (parent_id, type, name, istat_code, is_project_area, centroid_lat, centroid_lng)
             VALUES (?, 'province', 'Provincia di Piacenza', '033', 1, 45.052600, 9.693000)",
            [$regionId],
        );
        $provinceId = (int) $db->fetchValue("SELECT id FROM territories WHERE type = 'province' AND name = 'Provincia di Piacenza'");

        $districtIds = [];
        foreach ($districts as $key => [$name, $lat, $lng]) {
            $db->execute(
                "INSERT INTO territories (parent_id, type, name, is_project_area, centroid_lat, centroid_lng) VALUES (?, 'district', ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), centroid_lat = VALUES(centroid_lat), centroid_lng = VALUES(centroid_lng)",
                [$provinceId, $name, $lat, $lng],
            );
            $districtIds[$key] = (int) $db->fetchValue("SELECT id FROM territories WHERE type = 'district' AND name = ?", [$name]);
        }

        foreach ($municipalities as [$name, $istat, $district, $lat, $lng]) {
            $db->execute(
                "INSERT INTO territories (parent_id, type, name, istat_code, is_project_area, centroid_lat, centroid_lng)
                 VALUES (?, 'municipality', ?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), istat_code = VALUES(istat_code),
                                         centroid_lat = VALUES(centroid_lat), centroid_lng = VALUES(centroid_lng)",
                [$districtIds[$district], $name, $istat, $lat, $lng],
            );
        }
    });
};
