# Changelog

Tutte le modifiche rilevanti del progetto sono documentate in questo file.
Formato: [Keep a Changelog](https://keepachangelog.com/it/1.1.0/) · Versioni: [Semantic Versioning](https://semver.org/lang/it/).

## [Non rilasciato]

## [0.10.0] - 2026-09-24
### Aggiunto
- Migrazione `0006_requests_reviews`: registro delle richieste in ingresso e esiti della revisione.
- Admin: coda di revisione (approva e pubblica, respingi con nota; approvazione delle traduzioni da revisionare).
- Admin: registro delle richieste ricevute (censimento, aggiornamenti, account, abilitazione, errori), con assegnazione ed esito; comando `requests:purge` per cancellare i dati personali scaduti.
- Admin: opzioni di piattaforma (politica di pubblicazione predefinita, intervallo di verifica, conservazione delle richieste).
- Area riservata: elenco dei contenuti in bozza, in revisione, respinti (con la nota dei gestori) e da verificare.
- Filtri pubblici e API: sede senza gradini, tipo di ente, modalità di accesso.
- Test su revisione, richieste e filtri (54 test).

### Modificato
- La conferma "informazioni aggiornate" di un servizio fissa la prossima revisione secondo l'intervallo impostato.

## [0.9.0] - 2026-09-24
### Aggiunto
- Area riservata: gestione della propria organizzazione (profilo, testi e traduzioni, lingue, comunità, paesi, recapiti), delle sedi con orari, dei servizi e dei mediatori, con pubblicazione diretta secondo la politica dell'organizzazione.
- Revoca del consenso di un mediatore con un'azione dedicata, sempre disponibile (anche con politica di revisione).
- Admin: "Modifiche recenti" delle organizzazioni, con le differenze registrate nell'audit log.
- Stringhe di gestione (`lang/manage`) in inglese, francese e arabo (bozze da revisionare).

### Modificato
- Controller e template di gestione condivisi fra area amministrativa e area riservata (`App\Http\Controllers\Manage`, `templates/manage`); elenchi di opzioni (categorie, bisogni, comunità, ambiti) nella lingua dell'utente.
- La scheda mostra solo i moduli che l'utente può usare; nell'area riservata le schede di altre organizzazioni rispondono 404.

## [0.8.0] - 2026-09-24
### Aggiunto
- Area amministrativa del catalogo:
  - organizzazioni: elenco con filtri, creazione, profilo, assi di stato, abilitazione, politica di pubblicazione, testi in 4 lingue, lingue, comunità, paesi, recapiti con visibilità;
  - sedi: indirizzo, comune, coordinate (avviso fuori provincia), accessibilità, orari, recapiti, testi;
  - servizi: elenco con filtri (area, stato, revisione scaduta), dati di accesso, categorie, bisogni, lingue, sedi, conferma di verifica, testi e traduzioni;
  - mediatori: consenso e visibilità, lingue con livello, ambiti, zone, dati riservati agli amministratori, testi, recapiti;
  - sinonimi della ricerca, cruscotto qualità dei dati, impostazioni dei recapiti dei gestori.
- Servizi di dominio condivisi (`App\Domain\Management`) con permessi nell'ambito dell'organizzazione, stato di pubblicazione secondo la politica, audit con differenze; pronti per l'area riservata.
- Traduzioni: stato per lingua, approvazione solo con `translations.approve`, traduzioni "da aggiornare" quando cambia il testo originale.
- Moduli condivisi in `templates/partials/manage` (recapiti, testi e traduzioni, pubblicazione); stringhe `lang/admin` e `lang/manage` (italiano).
- Test di integrazione sulla gestione (47 test).

### Sicurezza
- Chi pubblica con revisione non può modificare un contenuto già pubblicato finché non saranno disponibili le richieste di modifica: la versione online resta invariata.
- La revoca del consenso di un mediatore rende subito non pubblici profilo e recapiti.

## [0.7.0] - 2026-09-24
### Aggiunto
- Migrazione `0005_communities_mediators`: comunità con traduzioni e paesi, collegamenti organizzazione–comunità e organizzazione–paese, ambiti di mediazione, mediatori con traduzioni, lingue con livello, ambiti e territori.
- Seed `009`: 17 comunità (nazionali, linguistiche, culturali, interculturali) e 8 ambiti di mediazione in 4 lingue.
- Directory pubblica "Associazioni e comunità" (`/{lingua}/associazioni-comunita`): ricerca testuale (nome, comunità, paesi, lingue, attività) e filtri per comunità, paese, lingua, comune o distretto, tipo, "solo realtà create da cittadini stranieri".
- Pagina pubblica dei mediatori (`/{lingua}/mediatori`): profili individuali solo con consenso registrato; gli altri mediatori compaiono solo in forma aggregata tramite l'organizzazione, con i recapiti dell'organizzazione.
- Pagina dei mediatori per gli operatori (`/{lingua}/area-riservata/mediatori`, permesso `restricted.view`) con nome completo e recapiti riservati agli operatori; collegamento dal cruscotto dell'area riservata.
- Scheda organizzazione: comunità, paesi collegati, attività e "come partecipare".
- Dati dimostrativi fittizi (`940_demo_communities_mediators`): 10 associazioni di comunità e 15 mediatori che coprono tutti i casi di visibilità.
- Stringhe del modulo `lang/communities` in it/en/fr/ar; nomi dei paesi da ICU (`View::countryName`).
- Test di integrazione su ricerca della directory e regole di visibilità dei mediatori (40 test).

### Modificato
- Letture comuni (traduzioni con ripiego, etichette, recapiti per visibilità, gerarchia dei territori) spostate in `App\Domain\Content\ContentReader`, usato dal catalogo e dai nuovi repository.
- Selettore di comune o distretto estratto nel partial `territory-select`.

### Rimosso
- Pagine segnaposto "in preparazione" di associazioni e mediatori e la stringa `section.soon`.

## [0.6.0] - 2026-09-24
### Aggiunto
- Mappa dei servizi (`/{lingua}/mappa`) con Leaflet 1.9.4 e Leaflet.markercluster 1.5.3 ospitati localmente, marker raggruppati, popup con i servizi della sede e "Portami qui", elenco equivalente sempre visibile, collegamento "salta la mappa", limiti dell'area della provincia.
- Geolocalizzazione facoltativa solo nel browser ("Usa la mia posizione" o scelta del comune): ordinamento per distanza nell'elenco e sulla mappa; la posizione non viene mai inviata al server.
- Proxy con cache delle tile OSM (`/tiles/{z}/{x}/{y}.png`) limitato alla provincia, attivo con `MAP_TILE_URL=/tiles/{z}/{x}/{y}.png`.
- API pubbliche in sola lettura: `/api/v1/map/points`, `/api/v1/search/suggest`, `/api/v1/needs`, `/api/v1/services`; middleware `throttle`.
- Suggerimenti di ricerca durante la digitazione (bisogni e servizi).
- Filtri condivisi fra elenco, mappa e API (`CatalogFilters`), parametro `bisogno`.

## [0.5.0] - 2026-09-24
### Aggiunto
- Tassonomia allineata alla guida FAMI 966 "Servizi utili a Piacenza e provincia": 15 aree, 45 sottocategorie, 15 bisogni (nuovi: trasporti, sicurezza) in 4 lingue.
- Territori: 3 distretti socio-sanitari (Ponente, Città di Piacenza, Levante), codici ISTAT e centroidi dei 46 comuni (`database/seeds/data/municipalities.php`); filtro "comune o intero distretto".
- Contenuti di riempimento fittizi (`930_demo_filler`): un servizio di prova per ogni sottocategoria in ogni distretto (totale 53 enti, 60 sedi, 122 servizi).
- Pagine informative con testi provvisori in 4 lingue: Partecipa, Il progetto, Contatti (numeri utili), Privacy (bozza, titolare da definire), Accessibilità (bozza di dichiarazione).
- Recapiti provvisori dei gestori nelle impostazioni (`contacts.managers`).
- Moduli di stringhe UI in `lang/<modulo>/<lingua>.php`.

## [0.4.0] - 2026-09-23
### Aggiunto
- Migrazione `0004_catalog`: territori, bisogni, categorie, sedi, servizi e traduzioni, lingue parlate, contatti con visibilità, orari, dizionario di ricerca.
- Dati di base: 46 comuni della provincia di Piacenza, 13 bisogni e 42 categorie in 4 lingue, 31 lingue parlate, dizionario di sinonimi in it/en/fr/ar.
- Catalogo pubblico: home con i bisogni e il filtro "qualcuno che parla la mia lingua"; elenco dei servizi con filtri (argomento, comune, lingua, mediatore, gratuito); percorso per bisogno; ricerca con sinonimi e testi normalizzati (anche in arabo); scheda servizio con orari e stato "aperto ora", contatti cliccabili e "Portami qui"; scheda organizzazione.
- Ripiego delle traduzioni campo per campo sulla lingua sorgente, marcato con `lang`/`dir` e avviso.
- Catalogo dimostrativo fittizio (`setup --demo`): 14 enti, 22 sedi, 18 servizi.
- Test del catalogo (34 test in totale).

### Corretto
- Rotte con quantificatori tra graffe nella regex (`{code:[a-z_]{2,50}}`), che causavano 404.
- Nomi qualificati `App\…` risolti sull'alias `App` in `services.php` e `commands.php`.

## [0.3.0] - 2026-09-23
### Aggiunto
- Migrazioni `0002_users_rbac` (utenti, ruoli, permessi, assegnazioni con ambito, token monouso) e `0003_organizations` (tipi e organizzazioni con assi di stato separati).
- Ruoli e permessi da `config/permissions.php` (nuovo permesso `portal.access`); `Gate` con ambito globale/organizzazione/categoria/territorio/lingua, stato dell'organizzazione e politica di pubblicazione (diretta predefinita).
- Autenticazione: account solo su invito (link monouso 72 h), login con rate limiting, recupero password (link 60 min), sessioni con scadenza per inattività e `session_version`, email localizzate via SMTP (PHPMailer; Mailpit in locale).
- Area amministrativa: cruscotto, utenti (crea e invita, stato, ruoli con ambito, reinvio invito), matrice ruoli/permessi, audit log con filtri.
- Area riservata delle organizzazioni: cruscotto con le proprie organizzazioni.
- Comando `user:create-admin` per il primo super amministratore.
- Dati dimostrativi fittizi (`setup --demo`, vietati in produzione): 3 organizzazioni e 7 utenti, uno per ruolo.
- Test: Gate, regole password, login e rate limiting, token di invito e recupero (28 test).

### Corretto
- Il codice 419 (pagina scaduta) viene inviato come 403: Apache trasformava 419 in 500.

## [0.2.0] - 2026-09-23
### Aggiunto
- Core applicativo: container dei servizi, configurazione (`config/`), accesso al database con strict mode e UTC, logger su file con identificativo di richiesta, cache su file.
- HTTP: `Request`, `Response`, router con gruppi e parametri vincolati, kernel con middleware (`locale`, `session`, `csrf`), pagine di errore localizzate, header di sicurezza (CSP, nosniff, frame, referrer, permissions).
- Multilingua: lingue it/en/fr/ar (arabo RTL), lingua nell'URL, `/` → lingua del browser, selettore con nomi nativi, `hreflang`, traduttore ICU con plurali, stringhe UI in `lang/` importate nel database con stato di traduzione.
- Layout pubblico provvisorio accessibile e mobile-first; home con ricerca, accessi rapidi, blocco emergenza e invito a partecipare; sezioni pubbliche "in preparazione".
- Audit log (`AuditLogger`) con differenze, redazione dei segreti e IP pseudonimizzato.
- Migrazioni SQL versionate (`0001_core`), seed idempotenti (lingue, impostazioni: pubblicazione diretta predefinita).
- Console: `setup`, `migrate`, `migrate:status`, `seed`, `i18n:import`, `cache:clear`, `env:check`, `key:generate`, `version`.
- PHPUnit con database di test separato (`connectingpc_test`); 16 test.
- Dipendenze: PHPMailer 6 (email, dalla 0.3.0), PHPUnit 11 (sviluppo).

### Modificato
- `public/.htaccess`: header di sicurezza con `setifempty` (solo file statici); la pagina tecnica dello scheletro è sostituita dal layout pubblico; `/health` gestito dal router.

### Note
- Le traduzioni en/fr/ar sono bozze prodotte dall'IA, da far revisionare a madrelingua.

## [0.1.1] - 2026-09-23
### Modificato
- Documentazione (vault): registrate le decisioni del committente — pubblicazione diretta delle organizzazioni accreditate limitata ai propri dati (D-022), lingue italiano/inglese/francese/arabo (D-023), area Piacenza e provincia (D-024), titolare del trattamento rinviato a fine progetto (D-025).
- `README.md`: la versione corrente si legge da `VERSION`.

## [0.1.0] - 2026-09-23
### Aggiunto
- Analisi iniziale completa nel vault Obsidian (note 00–83): requisiti, attori, casi d'uso, modello concettuale, architettura, modello dati e DDL proposto, ruoli e permessi, workflow, multilingua, mappa, ricerca, privacy, sicurezza, accessibilità, operazioni.
- Verifica dell'ambiente di riferimento Aruba Business (PHP 8.3.33 FastCGI, MariaDB 11.4.12).
- Ambiente Docker di sviluppo: PHP 8.3 + Apache, MariaDB 11.4, phpMyAdmin, Mailpit.
- Istruzioni per assistenti IA e sviluppatori (`AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `.github/copilot-instructions.md`) con rimando obbligatorio al vault.
- Scheletro dell'applicazione: struttura delle cartelle, front controller provvisorio, `/health` con verifica dei requisiti, `bin/console env:check`, connessione DB in strict mode e UTC.
- File di progetto: `VERSION`, `.env.example`, `.gitignore`, `.gitattributes`, `.editorconfig`, `composer.json`.
