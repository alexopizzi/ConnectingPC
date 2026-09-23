# Changelog

Tutte le modifiche rilevanti del progetto sono documentate in questo file.
Formato: [Keep a Changelog](https://keepachangelog.com/it/1.1.0/) · Versioni: [Semantic Versioning](https://semver.org/lang/it/).

## [Non rilasciato]

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
