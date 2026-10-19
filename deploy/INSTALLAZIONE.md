# ConnectingPC — installazione in produzione

Istruzioni per **connectingpc.alexopizzi.it** (Aruba Business, Plesk, PHP 8.3, MariaDB 11.4).
Non serve SSH: installazione e aggiornamenti si fanno dalla pagina protetta `/manutenzione`.
Riferimenti completi nel vault: note 80 (Installazione), 81 (Deploy e rollback), 32 (Ambiente).

## Contenuto del pacchetto
- `connectingpc-vX.Y.Z.zip` — applicazione completa con `vendor/` di produzione. Le cartelle sono alla radice dello zip.
- `SHA256SUMS` — impronta per verificare che lo zip non sia stato alterato.
- `connectingpc-vX.Y.Z.env.produzione` — modello di `.env` con `APP_KEY`, `HEALTH_TOKEN` e `MAINTENANCE_TOKEN`
  già generati. **È un file segreto**: non inviarlo per email e non lasciarlo nella cartella del sito.

Nel pacchetto **non** ci sono `.env`, dati, sessioni, log o dati dimostrativi: il sito parte con le sole
tassonomie (bisogni, categorie, comuni, lingue, comunità). I contenuti si inseriscono dall'area amministrativa.

## Prima installazione
1. **Plesk → connectingpc.alexopizzi.it → Impostazioni di hosting**
   - Versione PHP **8.3**.
   - **Document root: `connectingpc.alexopizzi.it/public`** (consigliato). Se non si può cambiare, il file
     `.htaccess` alla radice del pacchetto inoltra comunque tutto a `public/` e blocca il resto.
   - **SSL/TLS**: certificato Let's Encrypt e reindirizzamento permanente (301) da HTTP a HTTPS.
2. **Plesk → Database → Aggiungi database**: MariaDB, nome e utente dedicati, password robusta.
   Annotare host (di solito `localhost`), nome, utente e password.
3. **File Manager** nella cartella `connectingpc.alexopizzi.it/`: caricare lo zip, "Estrai file" nella cartella
   stessa, poi eliminare lo zip. I file di Plesk già presenti (`.php-ini`, `.php-version`, `cgi-bin`) restano.
4. **Configurazione**: aprire il file `…env.produzione`, compilare `DB_NAME`, `DB_USER`, `DB_PASS`,
   i parametri `MAIL_*` della casella mittente e `GEOCODER_EMAIL`; creare nel File Manager il file `.env`
   nella cartella `connectingpc.alexopizzi.it/` (accanto a `VERSION`) e incollarvi il contenuto. Permessi `600`.
5. Aprire **https://connectingpc.alexopizzi.it/manutenzione** e inserire il valore di `MAINTENANCE_TOKEN`.
   - Controllare la tabella **Requisiti dell'ambiente** (nessun `FAIL`).
   - Premere **Installa o aggiorna il database** (migrazioni, dati di base, stringhe, cache).
   - In **Primo super amministratore** inserire email e nome: compare un **link di attivazione** (72 ore,
     uso singolo). Aprirlo e scegliere la password.
6. **Verifiche**: `https://connectingpc.alexopizzi.it/health` (versione attesa), la home in italiano e in arabo,
   la mappa, l'accesso da `https://connectingpc.alexopizzi.it/it/accedi`, l'invio di un invito di prova.
7. Facoltativo ma consigliato: **Plesk → Operazioni pianificate**, una volta al giorno
   `/opt/plesk/php/8.3/bin/php /var/www/vhosts/alexopizzi.it/connectingpc.alexopizzi.it/bin/console requests:purge`
   (cancella i dati personali scaduti delle richieste in ingresso).
8. Quando non serve, si può disattivare la pagina di manutenzione svuotando `MAINTENANCE_TOKEN` nel `.env`.

## Aggiornamento a una versione successiva
1. **Backup**: esportazione del database da Plesk e copia di `.env` e `storage/uploads/`.
2. `/manutenzione` → **Metti il sito in manutenzione**.
3. Caricare il nuovo zip ed estrarlo sopra i file esistenti (`.env` e i dati di `storage/` non vengono toccati).
4. `/manutenzione` → **Installa o aggiorna il database**, poi **Svuota cache e OPcache**.
5. Verifiche come al punto 6, poi **Riapri il sito**.

**Ripristino (rollback)**: estrarre di nuovo lo zip della versione precedente e, solo se le migrazioni nuove
non sono compatibili, reimportare il backup del database (vault "81").
