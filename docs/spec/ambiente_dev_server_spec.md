# Ambiente di sviluppo sul server, con account Claude aziendale

## Contesto

Fino a ottobre 2026 Colombini si sviluppa sul PC di casa: PHP locale, MySQL 8, `php spark serve`
sull'IP della rete domestica. Due esigenze hanno messo in discussione questo assetto.

1. **Separare gli account Claude.** I progetti Colombini (questo e il vecchio `colombini-old`)
   devono essere gestiti **solo** con l'abbonamento Claude aziendale; tutti gli altri progetti
   con quello personale. Sul PC di casa i due account condividerebbero la stessa configurazione
   (`~/.claude`), e con essa memorie e cronologia.
2. **Lavorare anche dall'ufficio.** Con il codice in locale, un intervento al volo dal PC
   dell'ufficio richiederebbe di replicare lì l'intero ambiente (PHP, MySQL, composer, node,
   `.env`) e un secondo database di sviluppo con dati diversi. Le memorie di Claude, poi,
   restano sul PC dove sono state scritte.

Su `metesoftware.it` esiste già un precedente: il progetto da-kimi si sviluppa via VS Code
Remote-SSH direttamente sul server, con ambiente di sviluppo e di produzione separati. È
documentato in `/var/www/da-kimi-dev/docs/infrastruttura.md`, compresi i difetti che quel
documento stesso segnala come "da migliorare". Questa spec ne riprende l'impianto e corregge
quei difetti fin dall'inizio.

## Decisioni chiave

### 1. Lo sviluppo si sposta sul server

Codice, database di sviluppo e Claude Code stanno sul server; da qualunque PC si apre VS Code
in Remote-SSH sulla cartella remota. Un solo ambiente, un solo database di sviluppo, una sola
copia delle memorie, raggiungibili da casa e dall'ufficio senza pull/push per spostarsi.

Effetto collaterale voluto: lo sviluppo gira su **MariaDB 10.11 e PHP 8.4, gli stessi della
produzione**. Sparisce la differenza di motore descritta in `CLAUDE.md`.

### 2. Un utente Unix dedicato, senza sudo: `colombini-dev`

In da-kimi tutto gira come `nhildra`, che ha `sudo` e il cui Claude è collegato all'account
personale. Per Colombini questo non va, per due motivi indipendenti:

- **account**: Claude tiene login e memorie nella home di chi lo lancia. Un utente separato ha
  un suo `~/.claude`, dove si fa il login aziendale. È l'utente con cui ci si collega a
  decidere l'account, senza variabili d'ambiente né accorgimenti da ricordare;
- **produzione**: `nhildra` ha `sudo` e un `~/.my.cnf` che apre il database di produzione senza
  password (serve al backup). Un Claude che lavora come `nhildra` potrebbe toccare la
  produzione con un solo comando sbagliato.

`colombini-dev` non ha `sudo`, non è nel gruppo `www-data`, possiede soltanto
`/var/www/colombini-dev` e vede soltanto il database di sviluppo.

Conseguenza accettata: **il Claude aziendale non può fare deploy.** La produzione si aggiorna
come oggi, da `nhildra`, seguendo `docs/deploy.md`.

Per la produzione non serve un utente nuovo: gira già come `www-data`.

### 3. Database e utente MariaDB propri dello sviluppo

| | Produzione | Sviluppo |
|---|---|---|
| Database | `colombinisnc` (esiste) | `colombinisnc_dev` (nuovo) |
| Utente MariaDB | `colombini` (esiste) | `colombini_dev` (nuovo) |
| Permessi | invariati | `ALL PRIVILEGES` solo su `colombinisnc_dev.*`, senza `GRANT OPTION` |

In MariaDB un utente vede solo i database su cui ha permessi: le credenziali di sviluppo non
aprono la produzione. È il primo consiglio del documento di da-kimi, dove invece i ruoli sono
condivisi fra gli ambienti.

Un solo utente per lo sviluppo, non la coppia admin/app di da-kimi (uno per le migrazioni,
uno con soli permessi sui dati per il sito): in sviluppo la distinzione costa più di quanto
protegga. Ha senso semmai sulla produzione, e resta fuori scope.

### 4. Prima di tutto, chiudere le porte già aperte

La ricognizione del 02/10/2026 ha trovato due difetti che **renderebbero inutile la
separazione** se non corretti prima:

- `/var/www/colombini/.env` (produzione) e `/var/www/colombini-old/.env` hanno permessi `644`:
  qualunque utente del server legge la password di `colombini`, che ha
  `ALL PRIVILEGES` **sia** sul database di produzione **sia** su quello del vecchio gestionale.
  `colombini-dev` potrebbe leggerla;
- il vecchio gestionale è online su `colombini-old.metesoftware.it` in modalità `development`,
  aperto a chiunque e bersaglio di tentativi di accesso falliti (11/09 di notte, 28/09). Le
  pagine di errore in `development` mostrano dettagli tecnici.

Inoltre, durante la ricognizione `SHOW GRANTS` ha stampato l'hash della password di
`colombini` nella conversazione con Claude. Il rischio è basso, ma la password si cambia
comunque nella stessa fase.

**Il vecchio gestionale resta online.** Non si sviluppa più, ma il codice da solo non sempre
basta: a volte serve aprirlo e usarlo per capire come funzionava una feature già fatta lì.
Quindi non si spegne: riceve un utente MariaDB **suo**, `colombini_old`, che vede solo il
database `colombini` e può leggere e scrivere i dati ma non cambiare la struttura, e va dietro
la stessa password Nginx dello sviluppo. Il database `colombini` resta com'è. L'utente
`colombini` torna a essere solo quello della produzione.

### 5. Sottodominio di sviluppo protetto da password

`colombini-dev.metesoftware.it`, come `da-kimi-dev`: record A verso il server, virtual host
Nginx, certificato Let's Encrypt. In più, a differenza di da-kimi, **`auth_basic`** davanti a
tutto: una password Nginx prima ancora del login di Shield.

Così lo sviluppo si raggiunge da qualunque dispositivo, telefono compreso e anche fuori dal
Wi-Fi di casa. Ed essendo in HTTPS si può finalmente provare l'installazione come app su
Android: uno dei tre limiti noti della v0.35.0 era che Chrome ignora il manifest su http.

### 6. Il sito di sviluppo gira come `colombini-dev`, in un pool PHP-FPM suo

Il gestionale scrive in `writable/` (sessioni, cache, log) e in `public/uploads/` (il logo). In
sviluppo a scrivere sarebbero due utenti: PHP, che col pool condiviso gira come `www-data`, e
`colombini-dev` quando lancia `php spark` dal terminale. I file dell'uno non sarebbero
modificabili dall'altro: se il primo log del giorno lo crea un comando `spark`, il sito non
riesce più a scriverci per tutta la giornata; `cache:clear` fallisce sui file creati dal sito.

Il metodo di da-kimi (gruppo `www-data` con setgid su `writable/`) non basta, perché lì il
terminale e il sito sono comunque due utenti diversi. Aggiungere `colombini-dev` al gruppo
`www-data` risolverebbe, ma **è escluso**: il `.env` di produzione è leggibile dal gruppo
`www-data`, e si riaprirebbe il buco chiuso nella Fase 0.

Soluzione: un **pool PHP-FPM dedicato**, `colombini-dev`, che esegue il PHP del sito di
sviluppo con l'utente `colombini-dev`. Sito e terminale diventano la stessa identità, e
`writable/` resta semplicemente suo. In più il sito di sviluppo non può leggere la produzione
nemmeno in caso di difetto, cosa che col pool condiviso (`www-data`) non sarebbe vera. Nginx
continua a servire i file statici come `www-data`, grazie al gruppo sulla cartella; passa a
PHP-FPM solo le richieste PHP, sul socket del nuovo pool.

Era fra i miglioramenti suggeriti dal documento di da-kimi e inizialmente fuori scope: qui
diventa necessario. Il pool è `ondemand`, quindi senza richieste non tiene processi in memoria.

### 7. Il database di sviluppo parte dalla copia di quello di casa

Il database locale contiene solo dati di prova (vedi "Go-live in produzione" in `CLAUDE.md`):
copiarlo non sposta dati veri in un ambiente meno protetto, e si ritrova l'ambiente che si
conosce. La collation `utf8mb4_general_ci`, fissata a suo tempo in `app/Config/Database.php`
proprio perché esiste in entrambi i motori, è ciò che rende il dump portabile da MySQL 8 a
MariaDB.

Scartata la copia della produzione: oggi è vuota, e quando non lo sarà porterebbe i dati veri
dei clienti in sviluppo.

## Alternative scartate

- **Due configurazioni Claude sul PC di casa** (`CLAUDE_CONFIG_DIR` per l'account aziendale,
  impostata per progetto nel `.vscode/settings.json`). Risolveva la separazione degli account
  ma non il lavoro dall'ufficio: ogni PC avrebbe avuto ambiente, database e memorie propri.
- **Sincronizzare le memorie fra i PC** con OneDrive o simili. Fragile: il nome della cartella
  delle memorie dipende dal percorso del progetto su ciascun PC, e due sessioni attive su PC
  diversi scriverebbero sugli stessi file.
- **Replicare da-kimi così com'è**, lavorando come `nhildra`: account Claude sbagliato e
  produzione raggiungibile, vedi decisione 2.

## Stato dei lavori

**Al 02/10/2026: fasi 0-4 completate e verificate.** Si riprende dalla Fase 5.

- **Fase 0**: `.env` di produzione e del vecchio gestionale a `640`; utente `colombini_old`
  (solo dati, solo sul database `colombini`), usato dal vecchio gestionale; `colombini` non
  vede più il vecchio database; password di `colombini` cambiata in `.env` e `~/.my.cnf` di
  `nhildra`. Provati login in produzione, login nel vecchio gestionale e backup.
- **Fase 1**: utente `colombini-dev` senza `sudo`, con le chiavi `mio-pc` (casa) e
  `pc-ufficio`. Verificato che non legge `.env`, backup, home di `nhildra`, né scrive in
  produzione.
- **Fase 2**: `colombinisnc_dev` + `colombini_dev`, credenziali in `~/.my.cnf` di
  `colombini-dev` con `database = colombinisnc_dev`. Vede solo il suo database.
- **Fase 3**: deploy key `colombini-dev su metesoftware` (Read/write) e `~/.ssh/config` con
  `Host github.com`; clone in `/var/www/colombini-dev` (`750`, gruppo `www-data`), identità
  git `Daniela`, `composer install`, `public/uploads/` con il logo, `.env` (`600`).
- **Fase 4**: dump del database di casa importato; conteggi identici su tutte le 25 tabelle.

Da fare: Fase 5 (record DNS, pool PHP-FPM, virtual host, certificato, `auth_basic` anche sul
vecchio gestionale), Fase 6 (Claude Code aziendale, copia delle memorie), Fase 7 (VS Code
Remote-SSH), poi le modifiche ai documenti del repository elencate più sotto.

Note emerse durante il lavoro, da non riscoprire:

- **Comandi lunghi copiati dalla chat arrivano spezzati** nel terminale e partono a metà
  (`install` senza destinazione, `REVOKE` senza virgolette di chiusura). Nei comandi Linux da
  dare a mano: righe corte. Dentro `mariadb` il problema non esiste, perché esegue solo al `;`.
- **Il dump da MySQL 8 va adattato per MariaDB**: le tre viste `v_*` portano
  `collation_connection = utf8mb4_0900_ai_ci` (sconosciuta a MariaDB) e
  `DEFINER=colombini@localhost` (che `colombini_dev` non può assegnare). Si correggono con
  `sed` sul file prima dell'import: le tabelle erano già tutte in `utf8mb4_general_ci`.
- **`php spark migrate:status` non è di sola lettura**: su un database vuoto crea la tabella
  `migrations`.
- **Il database di sviluppo contiene `clienti_adhoc`**, cioè l'anagrafica reale importata da
  Ad Hoc, non solo dati di prova.
- Dopo il `640` sul `.env` di produzione, il `grep` su quel file in `docs/deploy.md` richiede
  `sudo`.

## Procedura

Divisione dei compiti: **le verifiche le fa Claude** via SSH, in sola lettura, come
`nhildra`. **I comandi che cambiano il server li lancia Daniela**, perché richiedono `sudo`.
Dopo ogni fase Claude ricontrolla che il risultato sia quello atteso, prima di passare alla
successiva. Le password si generano al momento e si scrivono solo nei file indicati, mai nella
conversazione.

### Fase 0 — Sicurezza preliminare

L'ordine conta: il vecchio gestionale passa al suo utente **prima** che `colombini` perda i
permessi sul suo database, così non resta mai scollegato.

1. `.env` di produzione leggibile solo da `www-data`:
   `sudo chmod 640 /var/www/colombini/.env`. Il proprietario è già `www-data:www-data`, e i
   comandi `spark` di produzione si lanciano già come `www-data` (`docs/deploy.md`).
2. Utente proprio del vecchio gestionale, da `sudo mariadb`:

   ```sql
   CREATE USER 'colombini_old'@'localhost' IDENTIFIED BY '<segreto>';
   GRANT SELECT, INSERT, UPDATE, DELETE ON colombini.* TO 'colombini_old'@'localhost';
   ```

   Niente `CREATE`, `ALTER`, `DROP`: lì non si eseguono più migrazioni.
3. `/var/www/colombini-old/.env` con le credenziali di `colombini_old`, poi
   `sudo chown root:www-data` e `sudo chmod 640`: lo legge PHP, non gli altri utenti.
   Verifica: il vecchio gestionale fa il login.
4. Togliere a `colombini` i permessi sul vecchio database:
   `REVOKE ALL PRIVILEGES ON colombini.* FROM 'colombini'@'localhost';`
5. Nuova password per `colombini`: `ALTER USER 'colombini'@'localhost' IDENTIFIED BY
   '<segreto>';`, aggiornata **subito** in due posti, altrimenti si rompono sito e backup:
   `/var/www/colombini/.env` e `~/.my.cnf` di `nhildra`.
6. Verifica: il sito di produzione risponde e fa il login; `~/backup-db.sh` produce un dump;
   il vecchio gestionale funziona ancora.

La password Nginx sul vecchio gestionale si aggiunge nella Fase 5, insieme a quella dello
sviluppo.

### Fase 1 — Utente `colombini-dev`

1. `sudo adduser --disabled-password --gecos "" colombini-dev`. Accesso solo con chiave SSH,
   nessuna password; non viene aggiunto a nessun gruppo.
2. Chiave pubblica del PC di casa (`~/.ssh/id_ed25519.pub`) in
   `/home/colombini-dev/.ssh/authorized_keys`, cartella `700` e file `600`, proprietario
   `colombini-dev`. La chiave del PC dell'ufficio si aggiunge allo stesso file quando serve.
3. Verifica dal PC di casa: `ssh colombini-dev@metesoftware.it` entra; `sudo -v` viene
   rifiutato.

### Fase 2 — Database di sviluppo

Da `sudo mariadb`:

```sql
CREATE DATABASE colombinisnc_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'colombini_dev'@'localhost' IDENTIFIED BY '<segreto>';
GRANT ALL PRIVILEGES ON colombinisnc_dev.* TO 'colombini_dev'@'localhost';
```

Credenziali in `~/.my.cnf` di `colombini-dev` (`600`, sezione `[client]`), come già per
`nhildra`. Verifica: `colombini-dev` vede **solo** `colombinisnc_dev` in `SHOW DATABASES`.

### Fase 3 — Cartella e codice

1. `sudo install -d -o colombini-dev -g www-data -m 750 /var/www/colombini-dev`
   (`/var/www` è di `root`, quindi la cartella la crea `sudo`). `750` e non il `755` di
   da-kimi: `www-data` deve leggere il codice, gli altri utenti del server no.
2. Come `colombini-dev`: chiave SSH dedicata per GitHub (`~/.ssh/github_colombinisnc`)
   registrata come **deploy key con permesso di scrittura** sul solo repository
   `daniela-cali/colombinisnc`, e alias in `~/.ssh/config` come in da-kimi. Una deploy key
   apre un solo repository, a differenza di una chiave dell'account GitHub.
3. `git clone` in `/var/www/colombini-dev`, `composer install`.
4. `writable/` resta di `colombini-dev` così come esce dal clone: con il pool dedicato
   (decisione 6) è lui a scriverci, sia dal sito sia dal terminale. `public/uploads/` non è
   nel repository: la crea `colombini-dev`, e ci si copia il logo dal PC di casa.
5. `.env` di sviluppo, ricavato da quello di casa: `CI_ENVIRONMENT = development`,
   `app.baseURL = 'https://colombini-dev.metesoftware.it/'`, credenziali di `colombini_dev`
   (la password presa dal suo `~/.my.cnf`, senza passare dalla conversazione). Permessi
   `600`: con il pool dedicato lo legge solo `colombini-dev`.

### Fase 4 — Dati

1. Prima del dump, controllo delle collation sul database locale: nessuna tabella o colonna
   deve usare `utf8mb4_0900_ai_ci`, che MariaDB non conosce.
2. Dump locale (sola lettura, lo fa Claude):
   `mysqldump --single-transaction --no-tablespaces colombinisnc`.
3. Copia sul server nella home di `colombini-dev` e import in `colombinisnc_dev`, lanciato da
   Daniela come per ogni scrittura sul database. Da verificare all'import la colonna generata
   `clienti.denominazione`: `mysqldump` la esclude dagli `INSERT` e MariaDB la ricalcola.
4. Verifica: `php spark migrate:status` coerente con il codice, conteggi uguali a quelli di
   casa.

### Fase 5 — Sottodominio

1. Record A `colombini-dev` → `87.106.195.249` nel pannello DNS di `metesoftware.it`.
2. Pool PHP-FPM `/etc/php/8.4/fpm/pool.d/colombini-dev.conf` (decisione 6): `user` e
   `group` `colombini-dev`, socket `/run/php/php8.4-fpm-colombini-dev.sock` di proprietà di
   `www-data` (`0660`) perché Nginx possa usarlo, `pm = ondemand`. Verifica con
   `sudo php-fpm8.4 -t`, poi `sudo systemctl reload php8.4-fpm`.
3. Virtual host `/etc/nginx/sites-available/colombini-dev.metesoftware.it`, copiato da quello
   di produzione con `root /var/www/colombini-dev/public` e `fastcgi_pass` sul socket del
   nuovo pool, più il blocco dei file nascosti
   (`location ~ /\.(?!well-known) { deny all; }`) e `auth_basic` con un file di password in
   `/etc/nginx/`.
4. `sudo certbot --nginx -d colombini-dev.metesoftware.it`.
5. Lo stesso `auth_basic`, con lo stesso file di password, nel virtual host di
   `colombini-old.metesoftware.it`: una sola password da ricordare per i due siti di servizio.
6. Verifica, su entrambi i siti: senza password Nginx risponde 401; con la password compare il
   login; http viene rediretto su https.

### Fase 6 — Claude Code aziendale

1. Come `colombini-dev`, installer nativo di Claude Code; primo avvio e login con l'account
   **aziendale**.
2. Le memorie del PC di casa si copiano una volta sola nella cartella di progetto del server
   (`~/.claude/projects/-var-www-colombini-dev/memory/`, nome derivato dal percorso come in
   da-kimi). Da lì in poi esiste una copia sola.
3. Il vecchio progetto resta consultabile in `/var/www/colombini-old` in sola lettura, come
   riferimento.

### Fase 7 — VS Code

Nel `~/.ssh/config` del PC di casa (e poi dell'ufficio) un host `colombini-dev` verso
`metesoftware.it` con utente `colombini-dev`. VS Code → Remote-SSH → cartella
`/var/www/colombini-dev`.

### Fase 8 — Node (facoltativa)

Node serve solo per aggiornare i pacchetti frontend: gli asset sono committati
(`public/assets/vendor/`). Si installa per il solo `colombini-dev` con nvm, senza `sudo`,
quando servirà la prima volta.

## Riepilogo modifiche nel repository

Da fare **dopo** il passaggio, già con il Claude aziendale sul server:

- `CLAUDE.md`: sezione Stack (sviluppo su MariaDB 10.11 sul server, non più MySQL 8 in locale:
  sparisce la nota sullo sviluppo "più severo" della produzione); produzione oggi ancora vuota
  (la sezione Roadmap la descrive come già "con dati veri in caricamento"); percorso del vecchio
  progetto; il PromemoriaModel citato come bug noto è già corretto.
- `.claude/skills/ambiente-dev/SKILL.md`: quasi interamente da riscrivere (niente più
  `php -S` locale né accesso dal telefono in LAN).
- `docs/deploy.md`: sezione sull'ambiente di sviluppo, `.env` di produzione a `640`, password
  di `colombini` cambiata, vecchio gestionale offline.
- Pulizia delle memorie: regole stabili in `CLAUDE.md`, punti aperti in `docs/backlog.md`
  (che sostituisce `docs/spec/idee.txt`), memorie scadute cancellate.

## Fuori scope

- **Aggiornamento dello stack**, primo lavoro da fare nel nuovo ambiente. Rilevato il
  02/10/2026: PHP 8.4 in tutti gli ambienti, ma `composer.json` dichiara ancora `^8.2`;
  aggiornamenti minori per CodeIgniter (4.7.3 → 4.7.4), Shield (1.3.0 → 1.4.1), dompdf,
  AdminLTE (4.0.2 → 4.10.0), Font Awesome, Tom Select; versioni major per DataTables (2 → 3),
  FullCalendar (6 → 7) e PHPUnit (10 → 13), da valutare una per una.
- **Accesso SSH con password ancora attivo.** Emerso il 02/10/2026 durante la Fase 1:
  `/etc/ssh/sshd_config.d/50-cloud-init.conf` contiene `PasswordAuthentication yes`, che su
  Debian prevale sul `no` di `sshd_config` perché viene letto prima. Il registro di `ssh`
  mostra tentativi continui di bot con utenti e password a caso. Gli utenti con `sudo`
  (`nhildra`, `whitedragon`) sono quindi esposti a tentativi di password. Non si disattiva
  in blocco perché `whitedragon` entra con password: da valutare a parte un blocco `Match` che
  lasci la password solo a lui, oppure fail2ban, oppure il passaggio di `whitedragon` alla
  chiave. `colombini-dev` non ha password, quindi non è esposto.
- Utenti MariaDB separati admin/app sulla produzione.
- Un pool PHP-FPM dedicato anche per la produzione e gli altri siti (oggi `www-data` è unico
  per tutti tranne lo sviluppo di Colombini, vedi decisione 6).
- Il vecchio gestionale: resta online e consultabile, nessuna modifica al codice né al
  database `colombini`. Cambiano solo l'utente con cui si collega e la password Nginx davanti.
- Il destino della copia locale sul PC di casa: resta come clone git, da decidere dopo.
