<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * Territorio del progetto (D-024): Emilia-Romagna → Provincia di Piacenza → 46 comuni.
 * Codici ISTAT e centroidi: DA COMPLETARE importando l'elenco ufficiale ISTAT (vault "26", "40");
 * qui è valorizzato solo il centroide di Piacenza, usato come centro predefinito della mappa.
 */
return static function (Container $c): void {
    $municipalities = [
        'Agazzano', 'Alseno', 'Alta Val Tidone', 'Besenzone', 'Bettola', 'Bobbio', 'Borgonovo Val Tidone',
        'Cadeo', 'Calendasco', 'Caorso', 'Carpaneto Piacentino', 'Castel San Giovanni', "Castell'Arquato",
        'Castelvetro Piacentino', 'Cerignale', 'Coli', 'Corte Brugnatella', 'Cortemaggiore', 'Farini', 'Ferriere',
        "Fiorenzuola d'Arda", 'Gazzola', 'Gossolengo', 'Gragnano Trebbiense', 'Gropparello', "Lugagnano Val d'Arda",
        "Monticelli d'Ongina", 'Morfasso', 'Ottone', 'Piacenza', 'Pianello Val Tidone', 'Piozzano', 'Podenzano',
        "Ponte dell'Olio", 'Pontenure', 'Rivergaro', 'Rottofreno', 'San Giorgio Piacentino', 'San Pietro in Cerro',
        'Sarmato', 'Travo', 'Vernasca', 'Vigolzone', "Villanova sull'Arda", 'Zerba', 'Ziano Piacentino',
    ];

    $db = $c->get(Database::class);
    $db->transaction(static function (Database $db) use ($municipalities): void {
        $db->execute("INSERT IGNORE INTO territories (type, name, is_project_area) VALUES ('region', 'Emilia-Romagna', 0)");
        $regionId = (int) $db->fetchValue("SELECT id FROM territories WHERE type = 'region' AND name = 'Emilia-Romagna'");
        $db->execute(
            "INSERT IGNORE INTO territories (parent_id, type, name, is_project_area, centroid_lat, centroid_lng)
             VALUES (?, 'province', 'Provincia di Piacenza', 1, 45.052600, 9.693000)",
            [$regionId],
        );
        $provinceId = (int) $db->fetchValue("SELECT id FROM territories WHERE type = 'province' AND name = 'Provincia di Piacenza'");

        foreach ($municipalities as $name) {
            $db->execute(
                "INSERT IGNORE INTO territories (parent_id, type, name, is_project_area) VALUES (?, 'municipality', ?, 1)",
                [$provinceId, $name],
            );
        }
        $db->execute("UPDATE territories SET centroid_lat = 45.052600, centroid_lng = 9.693000 WHERE type = 'municipality' AND name = 'Piacenza'");
    });
};
