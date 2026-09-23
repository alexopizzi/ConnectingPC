<?php

declare(strict_types=1);

/*
 * Stringhe dell'interfaccia — ITALIANO (lingua sorgente).
 * Seed per il database: `php bin/console i18n:import`. La fonte viva è il database (D-007).
 * Nelle stringhe con parametri usare l'apostrofo tipografico ’ (vedi App\I18n\Translator).
 */
return [
    'app.name' => 'ConnectingPC',
    'app.tagline' => 'Servizi per le persone straniere a Piacenza e provincia',

    'layout.skip_to_content' => 'Vai al contenuto',
    'layout.menu' => 'Menu',
    'layout.language' => 'Lingua',
    'layout.main_navigation' => 'Navigazione principale',

    'nav.home' => 'Pagina iniziale',
    'nav.search' => 'Cerca',
    'nav.services' => 'Servizi',
    'nav.map' => 'Mappa',
    'nav.communities' => 'Associazioni e comunità',
    'nav.mediators' => 'Mediatori',
    'nav.participate' => 'Partecipa',
    'nav.project' => 'Il progetto',
    'nav.contacts' => 'Contatti',
    'nav.privacy' => 'Privacy',
    'nav.accessibility' => 'Accessibilità',

    'footer.about' => 'ConnectingPC mette in rete i servizi per le persone straniere nel territorio di Piacenza.',
    'footer.version' => 'Versione {version}',

    'home.title' => 'Trova il servizio che ti serve',
    'home.intro' => 'Cerca un servizio a Piacenza e provincia. Puoi scrivere cosa ti serve con parole semplici.',
    'home.needs_title' => 'Scegli un argomento',
    'home.needs_soon' => 'L’elenco degli argomenti sarà disponibile a breve.',
    'home.quick_title' => 'Altri modi per cercare',
    'home.quick.map' => 'Vedi la mappa',
    'home.quick.communities' => 'Associazioni e comunità',
    'home.quick.mediators' => 'Trova un mediatore',
    'home.quick.language' => 'Qualcuno che parla la mia lingua',

    'search.label' => 'Cosa ti serve?',
    'search.placeholder' => 'Per esempio: rinnovare il permesso di soggiorno',
    'search.submit' => 'Cerca',
    'search.soon' => 'La ricerca sarà disponibile a breve.',
    'search.you_searched' => 'Hai cercato: {query}',

    'emergency.title' => 'Emergenza',
    'emergency.text' => 'Se sei in pericolo chiama subito il 112. La chiamata è gratuita.',
    'emergency.call' => 'Chiama il 112',

    'participate.title' => 'Sei un ente o un’associazione?',
    'participate.text' => 'Scopri come inserire la tua organizzazione nella rete.',
    'participate.cta' => 'Partecipa alla rete',

    'section.soon' => 'Questa sezione è in preparazione.',
    'common.back_home' => 'Torna alla pagina iniziale',
    'translation.fallback_notice' => 'Questa informazione non è ancora disponibile in {language}.',

    'error.400.title' => 'Richiesta non valida',
    'error.400.text' => 'Non siamo riusciti a capire la richiesta. Riprova.',
    'error.403.title' => 'Accesso non consentito',
    'error.403.text' => 'Non hai il permesso di vedere questa pagina.',
    'error.404.title' => 'Pagina non trovata',
    'error.404.text' => 'La pagina che cerchi non esiste o è stata spostata.',
    'error.405.title' => 'Operazione non consentita',
    'error.405.text' => 'Questa operazione non si può fare su questa pagina.',
    'error.419.title' => 'Pagina scaduta',
    'error.419.text' => 'Per sicurezza la pagina è scaduta. Torna indietro, ricarica la pagina e riprova.',
    'error.429.title' => 'Troppi tentativi',
    'error.429.text' => 'Hai fatto troppi tentativi. Aspetta qualche minuto e riprova.',
    'error.500.title' => 'Si è verificato un errore',
    'error.500.text' => 'Qualcosa non ha funzionato. Riprova tra poco.',
    'error.503.title' => 'Sito in manutenzione',
    'error.503.text' => 'Stiamo aggiornando il sito. Torna tra qualche minuto.',
    'error.request_id' => 'Codice dell’errore: {id}',
];
