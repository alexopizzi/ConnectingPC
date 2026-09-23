# AGENTS.md — Istruzioni per chiunque lavori su ConnectingPC

Questo file vale per **qualsiasi assistente IA** (Claude, Codex, Copilot, Gemini, Cursor, …) e per ogni sviluppatore umano.
Leggilo per intero prima di fare qualsiasi cosa.

## 1. La fonte di verità è il vault Obsidian, non questo repository

La documentazione tecnica e progettuale **non** sta qui: sta nel vault Obsidian del progetto.

| Accesso | Percorso |
|---|---|
| Locale (Windows, sincronizzato) | `C:\Progetti\DEV\MCP - dev.alexopizzi.it\progetti\ConnectingPC\` |
| Server (via bridge MCP «Bridge - Alex Opizzi») | `/var/www/vhosts/alexopizzi.it/dav.alexopizzi.it/vault/Vault/progetti/ConnectingPC/` |

Struttura: `knowledgebase/` (note numerate) e `documenti/` (fonti originali, tra cui `Brief iniziale.md`).

**All'inizio di ogni sessione, in quest'ordine:**
1. `knowledgebase/00 - Indice.md` — mappa di tutta la documentazione.
2. `knowledgebase/01 - Guida operativa per sviluppatori e IA.md` — regole di lavoro e definizione di "fatto".
3. `knowledgebase/11 - Stato analisi e promemoria.md` — fase corrente, prossimi passi, punti aperti.
4. Le ultime voci di `10 - Decisioni di progetto.md` e `12 - Problemi e soluzioni.md`.
5. In questo repository: `VERSION`, `CHANGELOG.md`, `git log --oneline -10`, `git status`.

Se non riesci a leggere il vault, **fermati e chiedi** come accedervi: non procedere basandoti su supposizioni.

## 2. Prima di modificare l'architettura
1. Consulta la nota tematica nel vault e le decisioni collegate (`D-nnn`).
2. Se la modifica contraddice una decisione, registrane una nuova che la sostituisce (non riscrivere la storia).
3. Implementa.
4. Aggiorna contestualmente le note del vault toccate dalla modifica.

**Codice e documentazione evolvono insieme.** Un intervento senza aggiornamento del vault non è concluso.

## 3. Regole di sviluppo vincolanti
- Produzione: hosting condiviso **Aruba Business (Plesk, Linux, Apache, PHP 8.3 FastCGI, MariaDB 11.4)**. Niente root, Docker, Node.js, Redis, worker o processi permanenti in produzione.
- PHP 8.3 senza framework full-stack; Composer solo in build (`vendor/` incluso nel pacchetto di rilascio).
- Logica separata dalla presentazione: controller → servizi di dominio → repository; i template non fanno query.
- **Autorizzazione e validazione sempre lato server** (componente `Gate`); il frontend non protegge nulla.
- Solo prepared statement PDO; escaping di ogni output; niente HTML dagli utenti.
- Nessun segreto nel repository: configurazione in `.env` (modello in `.env.example`).
- Multilingua nativo: **nessuna stringa visibile hardcoded**, tutto passa dal sistema di traduzione (eccezione temporanea: la pagina tecnica dello scheletro v0.1.0).
- Operazioni significative → audit log.
- Accessibilità WCAG 2.2 AA, mobile-first; la mappa non è mai l'unico accesso alle informazioni.
- Privacy by default: dati personali con visibilità `admin` finché non c'è un consenso registrato.
- Non cancellare codice funzionante senza motivo; evitare duplicazioni, overengineering e dipendenze inutili.

## 4. Ambiente di sviluppo (Docker)
```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php bin/console env:check
```
App http://localhost:8090 · phpMyAdmin http://localhost:8091 · Mailpit http://localhost:8025 · MariaDB `localhost:3310`.
Dettagli: vault `35 - Sviluppo locale, Docker, Git e versioni.md`.

## 5. Versioni, commit e push (obbligatori a ogni intervento concluso)
- **SemVer** in `VERSION`; serie `0.x` fino al primo rilascio in produzione (`1.0.0`).
- `CHANGELOG.md` in formato *Keep a Changelog* (in italiano).
- Commit in italiano con prefisso: `feat:` `fix:` `docs:` `refactor:` `test:` `chore:` `build:` `security:`.
- A ogni rilascio: aggiorna `VERSION` e `CHANGELOG.md` → commit → `git tag -a vX.Y.Z -m "…"` → `git push origin main --follow-tags`.
- Aggiorna nel vault `13 - Versioni e rilasci.md` e `11 - Stato analisi e promemoria.md`.
- Mai committare `.env`, dump SQL, file caricati, `vendor/`, `storage/*`, `dist/`.

## 6. Definizione di "fatto"
- [ ] Funziona in Docker; test pertinenti eseguiti (`php -l`, PHPUnit quando presente).
- [ ] Vault aggiornato (note tematiche, stato, decisioni, problemi).
- [ ] `CHANGELOG.md` e `VERSION` aggiornati.
- [ ] Commit, tag (se rilascio) e **push** eseguiti.

## 7. Come rispondere al committente
Italiano standard, diretto e conciso. Distingui sempre ciò che è verificato da ciò che è ipotesi. Riporta file modificati, test eseguiti, cosa manca ancora; non dichiarare completato ciò che non lo è.
