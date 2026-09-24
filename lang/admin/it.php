<?php

declare(strict_types=1);

/* Area amministrativa del catalogo (v0.8.0) — solo italiano, come il resto dell'area amministrativa. */
return [
    'admin.nav.organizations' => 'Organizzazioni',
    'admin.nav.services' => 'Servizi',
    'admin.nav.mediators' => 'Mediatori',
    'admin.nav.synonyms' => 'Sinonimi della ricerca',
    'admin.nav.quality' => 'Qualità dei dati',
    'admin.nav.settings' => 'Impostazioni',
    'admin.sections' => 'Sezioni della pagina',
    'admin.back_to' => '← Torna a {name}',
    'admin.view_public' => 'Vedi la pagina pubblica',

    'admin.organizations.title' => 'Organizzazioni',
    'admin.organizations.create' => 'Nuova organizzazione',
    'admin.organizations.create_hint' => 'Dopo la creazione potrai completare testi, recapiti, sedi e servizi. La scheda resta in bozza e nascosta finché non la pubblichi.',
    'admin.organizations.back' => '← Tutte le organizzazioni',
    'admin.organizations.counts' => 'Contenuti',
    'admin.organizations.counts_value' => '{services} servizi · {sites} sedi',
    'admin.organizations.community_based_short' => 'di comunità',
    'admin.organizations.profile' => 'Profilo',
    'admin.organizations.status' => 'Stato e abilitazione',
    'admin.organizations.status_hint' => 'Censimento, elenco pubblico e verifica richiedono il permesso di verifica; abilitazione, modifica dall’area riservata e politica di pubblicazione richiedono il permesso di abilitazione. I valori per cui non hai il permesso restano invariati.',
    'admin.organizations.links' => 'Lingue, comunità e paesi',
    'admin.organizations.verified_at' => 'Ultima verifica: {date}',

    'admin.sites.title' => 'Sedi',
    'admin.sites.create' => 'Nuova sede',
    'admin.sites.no_coordinates' => 'senza coordinate',
    'admin.sites.check_on_map' => 'Controlla la posizione su OpenStreetMap',

    'admin.services.title' => 'Servizi',
    'admin.services.create' => 'Nuovo servizio',
    'admin.services.create_hint' => 'Per creare un servizio apri la scheda dell’organizzazione che lo offre.',
    'admin.services.name_hint' => 'Nella lingua sorgente dell’organizzazione. Gli altri testi e le traduzioni si completano dopo il salvataggio.',
    'admin.services.no_sites' => 'L’organizzazione non ha ancora sedi: aggiungile dalla sua scheda.',
    'admin.services.organization' => 'Organizzazione',
    'admin.services.review_due' => 'Solo da verificare (data di revisione passata)',

    'admin.mediators.title' => 'Mediatori',
    'admin.mediators.create' => 'Nuovo mediatore',
    'admin.mediators.back' => '← Tutti i mediatori',
    'admin.mediators.privacy_hint' => 'Dati di persone fisiche: il profilo è pubblico solo con consenso registrato; senza consenso il mediatore compare al pubblico solo in forma aggregata tramite l’organizzazione.',
    'admin.mediators.contacts_hint' => 'Un recapito "pubblico" richiede il consenso registrato. Se il consenso viene revocato, i recapiti pubblici diventano automaticamente "solo operatori".',

    'admin.synonyms.title' => 'Sinonimi della ricerca',
    'admin.synonyms.intro' => 'Parole ed espressioni che le persone usano davvero ("permesso", "carta di soggiorno", "casa popolare") collegate ai bisogni. I termini si salvano normalizzati (minuscole, senza accenti).',
    'admin.synonyms.add' => 'Aggiungi un sinonimo',
    'admin.synonyms.list' => 'Sinonimi registrati',
    'admin.synonyms.term' => 'Parola o espressione',
    'admin.synonyms.need' => 'Bisogno collegato',
    'admin.synonyms.weight' => 'Peso (1–10)',
    'admin.synonyms.delete' => 'Elimina',

    'admin.quality.title' => 'Qualità dei dati',
    'admin.quality.intro' => 'Controlli automatici sui contenuti pubblicati: servizi da verificare, traduzioni mancanti o da aggiornare, sedi senza coordinate, organizzazioni da censire.',
    'admin.quality.missing_translations' => 'Servizi pubblicati senza traduzione approvata, per lingua',
    'admin.quality.review_due' => 'Servizi con data di revisione passata',
    'admin.quality.never_verified' => 'Servizi pubblicati mai verificati',
    'admin.quality.services_without_sites' => 'Servizi senza sede né indirizzo online',
    'admin.quality.outdated_translations' => 'Traduzioni da aggiornare (testo originale modificato)',
    'admin.quality.sites_without_coordinates' => 'Sedi aperte al pubblico senza coordinate verificate',
    'admin.quality.organizations_to_census' => 'Organizzazioni da censire o da verificare',

    'admin.settings.title' => 'Impostazioni',
    'admin.settings.managers_hint' => 'Recapiti mostrati nelle pagine Contatti, Partecipa e Accessibilità. Da sostituire con quelli ufficiali quando il titolare sarà definito.',
];
