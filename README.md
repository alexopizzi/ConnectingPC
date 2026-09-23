# ConnectingPC

Piattaforma pubblica e multilingua per la mappatura, la ricerca e la gestione dei servizi rivolti alla popolazione straniera nel territorio di Piacenza. Collega bisogni, servizi, organizzazioni, sedi e territori, e comprende la rete di mediatori e associazioni di comunità.

> **Documentazione**: la fonte di verità tecnica e progettuale è il **vault Obsidian** del progetto.
> Prima di lavorare leggi [`AGENTS.md`](AGENTS.md).

## Stato
Versione **0.1.0**: analisi iniziale e scheletro infrastrutturale. Nessuna funzionalità applicativa. Vedi [`CHANGELOG.md`](CHANGELOG.md).

## Stack
PHP 8.3 · MariaDB 11.4 · Apache (`.htaccess`) · HTML server-side + JavaScript vanilla · Leaflet/OpenStreetMap.
Produzione su hosting condiviso Aruba Business (Plesk). Docker solo per lo sviluppo locale.

## Avvio rapido (sviluppo)
```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php bin/console env:check
```

| Servizio | URL |
|---|---|
| Applicazione | http://localhost:8090 |
| Stato / requisiti | http://localhost:8090/health?detail=1 |
| phpMyAdmin | http://localhost:8091 |
| Mailpit | http://localhost:8025 |
| MariaDB | `localhost:3310` |

## Struttura
```text
app/        codice applicativo (namespace App\)
bin/        console CLI
config/     configurazione applicativa versionata
database/   migrazioni e seed
docker/     immagini per lo sviluppo locale
lang/       seed delle stringhe dell'interfaccia
public/     document root (unico punto esposto)
storage/    log, cache, sessioni, upload (non pubblica)
templates/  template PHP
tests/      test
```
