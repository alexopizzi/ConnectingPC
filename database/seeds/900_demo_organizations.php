<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;

/*
 * DATI DIMOSTRATIVI FITTIZI (solo con `seed --demo`): nessun riferimento a organizzazioni o persone reali.
 */
return static function (Container $c): void {
    if (App\Core\App::environment() === 'production') {
        throw new RuntimeException('I dati dimostrativi non si caricano in produzione.');
    }
    $db = $c->get(Database::class);
    $demo = [
        ['Associazione Esempio Ponte', 'community_association', 1, 'enabled', 1],
        ['Cooperativa Esempio Orizzonti', 'cooperative', 0, 'enabled', 1],
        ['Centro Esempio Sospeso', 'cultural_centre', 1, 'suspended', 1],
    ];

    foreach ($demo as [$name, $type, $community, $access, $edit]) {
        if ($db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$name]) !== null) {
            continue;
        }
        $typeId = (int) $db->fetchValue('SELECT id FROM organization_types WHERE code = ?', [$type]);
        $db->insert('organizations', [
            'name' => $name,
            'organization_type_id' => $typeId,
            'is_community_based' => $community,
            'census_status' => 'censused',
            'verification_status' => 'verified',
            'access_status' => $access,
            'portal_edit_enabled' => $edit,
            'status_note' => 'Dato dimostrativo fittizio',
        ]);
    }
};
