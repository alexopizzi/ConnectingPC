<?php

declare(strict_types=1);

/*
 * Catalogo dei permessi e ruoli di sistema (vault "51 - Ruoli e permessi", D-008, D-022).
 * I permessi sono definiti dal codice (ognuno corrisponde a un controllo reale);
 * i ruoli sono dati: il seed 003 li sincronizza nel database, dove se ne possono aggiungere altri.
 */
return [
    'permissions' => [
        'admin.access' => 'Entrare nell’area amministrativa',
        'portal.access' => 'Entrare nell’area riservata della propria organizzazione',
        'settings.manage' => 'Impostazioni di piattaforma e manutenzione',
        'users.manage' => 'Creare, invitare e sospendere utenti',
        'roles.manage' => 'Gestire ruoli e assegnare ruoli amministrativi',
        'locales.manage' => 'Attivare lingue e gestire le stringhe dell’interfaccia',
        'taxonomy.manage' => 'Bisogni, categorie, tipi di ente, comunità, ambiti, territori, sinonimi',
        'organizations.view_all' => 'Vedere tutte le organizzazioni, compresi i dati amministrativi',
        'organizations.manage' => 'Creare e modificare qualsiasi organizzazione',
        'organizations.verify' => 'Registrare l’esito della verifica delle organizzazioni',
        'organizations.enable' => 'Abilitare, sospendere o disabilitare le organizzazioni; politica di pubblicazione',
        'org.profile.edit' => 'Modificare il profilo della propria organizzazione',
        'org.sites.edit' => 'Gestire le sedi nel proprio ambito',
        'org.services.edit' => 'Gestire i servizi nel proprio ambito',
        'org.mediators.edit' => 'Gestire i mediatori collegati nel proprio ambito',
        'org.users.manage' => 'Invitare o sospendere utenti della propria organizzazione',
        'content.submit' => 'Inviare modifiche in revisione',
        'content.publish' => 'Pubblicare direttamente',
        'content.review' => 'Approvare o respingere modifiche nel proprio ambito',
        'content.verify' => 'Confermare che una scheda è aggiornata',
        'translations.edit' => 'Tradurre contenuti nel proprio ambito',
        'translations.approve' => 'Approvare traduzioni',
        'mediators.manage' => 'Gestire tutti i mediatori e i relativi consensi',
        'restricted.view' => 'Vedere i dati riservati agli operatori',
        'requests.manage' => 'Registro delle richieste in ingresso',
        'audit.view' => 'Consultare l’audit log',
        'quality.view' => 'Cruscotto qualità dei dati',
    ],

    // Permessi che per gli utenti di organizzazione richiedono portal_edit_enabled = 1
    'organization_edit_permissions' => [
        'org.profile.edit', 'org.sites.edit', 'org.services.edit', 'org.mediators.edit', 'org.users.manage',
        'content.submit', 'content.publish', 'content.verify', 'translations.edit', 'translations.approve',
    ],

    // Permessi che per gli utenti di organizzazione richiedono la politica di pubblicazione "direct" (D-022)
    'organization_publish_permissions' => ['content.publish', 'translations.approve'],

    // Ruoli che solo il super amministratore può assegnare
    'protected_roles' => ['super_admin', 'admin'],

    'roles' => [
        'super_admin' => [
            'name' => 'Super amministratore',
            'description' => 'Controllo completo della piattaforma',
            'scopes' => ['global'],
            'permissions' => '*',
        ],
        'admin' => [
            'name' => 'Amministratore',
            'description' => 'Gestione generale della piattaforma',
            'scopes' => ['global'],
            'permissions' => [
                'admin.access', 'users.manage', 'locales.manage', 'taxonomy.manage',
                'organizations.view_all', 'organizations.manage', 'organizations.verify', 'organizations.enable',
                'org.profile.edit', 'org.sites.edit', 'org.services.edit', 'org.mediators.edit', 'org.users.manage',
                'content.submit', 'content.publish', 'content.review', 'content.verify',
                'translations.edit', 'translations.approve', 'mediators.manage', 'restricted.view',
                'requests.manage', 'audit.view', 'quality.view',
            ],
        ],
        'editor' => [
            'name' => 'Redattore',
            'description' => 'Gestisce e revisiona i contenuti nel proprio ambito',
            'scopes' => ['global', 'category', 'territory', 'organization'],
            'permissions' => [
                'admin.access', 'taxonomy.manage', 'organizations.view_all', 'organizations.manage', 'organizations.verify',
                'org.profile.edit', 'org.sites.edit', 'org.services.edit', 'org.mediators.edit',
                'content.submit', 'content.publish', 'content.review', 'content.verify',
                'translations.edit', 'translations.approve', 'mediators.manage', 'restricted.view',
                'requests.manage', 'quality.view',
            ],
        ],
        'translator' => [
            'name' => 'Traduttore',
            'description' => 'Traduce e approva le traduzioni in una lingua',
            'scopes' => ['locale'],
            'permissions' => ['admin.access', 'content.submit', 'translations.edit', 'translations.approve', 'restricted.view'],
        ],
        'org_referent' => [
            'name' => 'Referente organizzazione',
            'description' => 'Gestisce tutti i dati della propria organizzazione',
            'scopes' => ['organization'],
            'permissions' => [
                'portal.access', 'org.profile.edit', 'org.sites.edit', 'org.services.edit', 'org.mediators.edit',
                'content.submit', 'content.publish', 'content.verify', 'translations.edit', 'translations.approve',
                'restricted.view',
            ],
        ],
        'org_user' => [
            'name' => 'Utente organizzazione',
            'description' => 'Gestisce servizi, sedi e traduzioni della propria organizzazione',
            'scopes' => ['organization'],
            'permissions' => [
                'portal.access', 'org.sites.edit', 'org.services.edit',
                'content.submit', 'content.publish', 'content.verify', 'translations.edit', 'translations.approve',
                'restricted.view',
            ],
        ],
    ],
];
