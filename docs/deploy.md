# Deploy e ricostruzione del database

Procedura verificata il **26/08/2026** su `colombini.metesoftware.it`, ricostruendo l'intero
schema da un database vuoto. Vale sia per il go-live sia per ogni ripartenza pulita. La sezione
"Backup del database" è stata verificata sul campo il **27/08/2026**, server e PC compresi.

## Sequenza minima

```bash
~/backup-db.sh                                 # vedi "Backup del database"
cd /var/www/colombini
sudo -u www-data git pull
sudo -u www-data php spark migrate --all
sudo -u www-data php spark db:seed AdminSeeder
```

Il `cd` viene **dopo** il backup, non prima: `mariadb-dump` parla con il database e non legge un
solo file del progetto. La cartella serve ai comandi `php spark`, che vanno dati lì dentro.

Fatto questo si entra nel gestionale con le credenziali del `.env`, e il resto della
configurazione — tipi intervento, parametri azienda — si fa dall'interfaccia.

Il dump è il primo passo e non l'ultimo: da quando la produzione ospita dati veri, l'operazione
che li mette a rischio è proprio `migrate`, e un backup preso *dopo* non serve a niente.

## Backup del database

Il backup **non è ancora automatico** — è il punto 18 della review — ma è già **non interattivo**:
né il dump sul server né il download sul PC chiedono più una password. Manca quindi solo chi li
faccia partire da solo (un cron notturno, un'attività pianificata), non l'impianto. Le decisioni
che restano — ogni quanto, per quanto tempo conservare le copie, una destinazione fuori dalla
VPS — sono materia da sistemista.

Quello che segue si lancia a mano e copre lo scenario più probabile: una migration che rovina
dati reali.

Oggi il database **è** l'intero backup. Le uniche cartelle di upload sono `public/uploads/`
(il logo aziendale, che si ricarica dall'interfaccia in mezzo minuto) e `writable/uploads/import/`
(i CSV già importati, il cui risultato vive nel database). Se in futuro si aggiungono allegati
veri — foto degli interventi, documenti — vanno tutti sotto un'unica cartella nota, e questa
sezione va estesa: è l'unico motivo per cui potrebbe non bastare più il solo dump.

### La cartella, una volta sola

```bash
sudo mkdir -p /var/backups/colombini
sudo chown $USER /var/backups/colombini
chmod 700 /var/backups/colombini
```

Fuori dalla webroot: un dump dentro `public/` sarebbe scaricabile da chiunque ne indovini il
nome, e contiene l'anagrafica reale dei clienti. Di proprietà del proprio utente e chiusa a
`700`, così `www-data` non può leggerla — se un giorno l'applicazione web venisse compromessa,
l'attaccante otterrebbe i permessi di quell'utente, e i backup sono la prima cosa che cerca.

### Il dump non va lanciato con `sudo`

`mariadb-dump` si autentica al **database** con utente e password del database: l'identità Unix
non c'entra, e `sudo` non serve. Metterlo davanti non è solo inutile, rompe il comando: la
redirezione `>` non fa parte di ciò che `sudo` esegue — la esegue la propria shell, con il
proprio utente, prima ancora che `sudo` parta. Con la cartella di `root`, il file non si riesce
a creare e si ottiene un `No such file or directory` che sembra una cartella mancante e non lo è.

Vale anche l'inverso della regola della sezione più sotto: `www-data` serve ai comandi che
riscrivono i file del progetto. Il dump non tocca il progetto, quindi non lo riguarda.

### Le credenziali stanno nel `.env`

Utente e nome del database non si tirano a indovinare — con quelli sbagliati si ottiene
`Access denied for user ... (using password: YES)`, che sembra un problema di password:

```bash
grep -i '^database' /var/www/colombini/.env
```

`database.default.username` e `database.default.database` sono i due valori da passare al
comando. Il file mostra anche la password in chiaro: serve solo per digitarla al prompt.

### Le opzioni, e la password

`--single-transaction` esegue il dump dentro una transazione: la copia è coerente a un istante
preciso e nessuna tabella viene bloccata, quindi il gestionale continua a rispondere mentre il
backup gira. `--routines` non serve oggi ma non costa nulla e mette al riparo domani.

La password **non va mai scritta nel comando**, e infatti nello script non compare: né lei né
l'utente, perché li legge `~/.my.cnf` (vedi più sotto). Una password passata in riga di comando
finisce nella cronologia della shell ed è leggibile nella lista dei processi da chiunque abbia
accesso alla macchina.

Esiste anche `-p` senza valore, che la chiede a schermo: va bene per un comando dato una volta a
mano, ma rende il dump **interattivo** e quindi inadatto a qualunque cosa debba girare da sola.
Il nome del database, invece, resta nel comando: non è un segreto e si legge dal `.env`.

### Verificare che il dump sia buono

Un file troncato pesa comunque qualcosa e sembra a posto. L'ultima riga di un dump completo è
`-- Dump completed`:

```bash
zcat "$(ls -t /var/backups/colombini/*.sql.gz | head -1)" | tail -1
```

Se quella riga non c'è, il backup non esiste — qualunque cosa dica la dimensione del file.

Il nome del file non va scritto a mano. Lo compone `$(date +%F-%H%M)` nell'istante in cui parte
il dump, quindi cambia a ogni esecuzione: riscriverlo a memoria un minuto dopo significa cercare
un file che non esiste. `ls -t` elenca i dump dal più recente, `head -1` ne tiene uno solo, e le
virgolette attorno a `$(...)` lo consegnano a `zcat` come un argomento unico.

### Lo script sul server

I comandi qui sopra hanno dei segnaposto perché questo file finisce su GitHub. Le versioni con
i valori veri servono comunque, e vanno tenute **fuori dal repository** — non in `.gitignore`,
proprio fuori dalla cartella del progetto: un file dentro `colombinisnc/` è sempre a un
`git add .` distratto di distanza dal diventare pubblico.

Sul server lo script sta nella home dell'utente, non in `/var/www`, ed è `~/backup-db.sh`:

```bash
#!/bin/bash
set -o pipefail

DEST=/var/backups/colombini/db-$(date +%F-%H%M).sql.gz

mariadb-dump --single-transaction --routines <database> | gzip > "$DEST"

if [ $? -ne 0 ]; then
    rm -f "$DEST"
    echo "Backup FALLITO — file incompleto rimosso: $DEST" >&2
    exit 1
fi

echo "Backup completato: $DEST"
zcat "$DEST" | tail -1
```

```bash
chmod 700 ~/backup-db.sh
```

Da lì in poi il backup è `~/backup-db.sh` e basta, senza parametri e senza prompt.

**`set -o pipefail`, non `set -e`.** In `mariadb-dump | gzip > "$DEST"` la shell giudica l'esito
della pipeline dall'**ultimo** comando, cioè `gzip` — che riesce sempre, perché comprime
volentieri anche il messaggio d'errore di un dump fallito. `set -e` guarda proprio quel valore e
non si accorgerebbe di niente; `pipefail` fa fallire la pipeline se fallisce un pezzo qualsiasi.
Va scritto dentro lo script: `set -e` digitato in una shell interattiva la chiude al primo
comando che fallisce, `ls` su un file inesistente compreso.

**Il `rm -f` non è pignoleria.** La redirezione `>` crea il file prima ancora che
`mariadb-dump` parta, quindi un dump fallito lascia comunque un `.sql.gz` con data e ora giuste.
Provato: un dump interrotto sull'apertura del database produce un file di **396 byte** — non
vuoto, ma con l'intestazione del dump già scritta e zero dati. È un SQL sintatticamente valido
che, ripristinato, gira senza un errore e non ricrea nemmeno una tabella. Peggio di un backup
mancante, perché sembra un backup.

**L'ultima riga fa la verifica da sé.** `zcat "$DEST" | tail -1` stampa `-- Dump completed on ...`
a ogni esecuzione: il nome del file non va ricordato, ce l'ha già la variabile. Senza `$DEST` lo
stesso controllo richiede il giro descritto sopra, con `ls -t`.

I dump si **accumulano**: non c'è nessuna rotazione e prima o poi vanno ripuliti a mano. È una
delle cose che l'impianto automatico dovrà risolvere da sé.

### La password sta in `~/.my.cnf`

Nello script non compaiono `-u` né `-p` perché la password sta in `~/.my.cnf`, che il client
MariaDB cerca da solo nella home dell'utente Unix che lo lancia. È il meccanismo ufficiale
previsto per questo, non un espediente. Il file esiste già sul server; queste righe servono per
ricrearlo su una macchina nuova:

```ini
[client]
user = <utente>
password = <password>
```

```bash
chmod 600 ~/.my.cnf
ls -l ~/.my.cnf     # deve rispondere -rw-------
```

Il `chmod` non è un passaggio facoltativo: in quel file c'è una password in chiaro, e senza `600`
la legge qualunque utente della macchina. Il controllo con `ls -l` serve a vederlo davvero — se
dopo le prime quattro posizioni compaiono altre `r`, il file è esposto.

Che venga letto si verifica prima di toccare lo script, così un eventuale problema si isola
subito. Senza `-u` e senza `-p`:

```bash
mariadb <database> -e "SELECT 1"
```

**Se il dump continua a chiedere la password, il file non c'entra: c'è ancora un `-p` nel
comando.** `-p` non significa "usa una password", significa *"fermati e chiedimela adesso"*, e
un'opzione scritta sulla riga di comando ha sempre la precedenza su qualunque file di
configurazione. Va tolto insieme a `-u`, lasciando il solo nome del database.

Il guadagno non è avere la password su disco — c'è già in chiaro nel `.env` accanto, leggibile
dallo stesso utente. È che sparisce dalla riga di comando, dove sarebbe visibile nella lista dei
processi a chiunque sia loggato sulla macchina, e nella cronologia della shell. E soprattutto
rende il dump **non interattivo**: un comando che aspetta l'Invio di un essere umano non può
girare da solo alle tre di notte, quindi questo è il prerequisito del cron, non un vezzo.

Da notare che il file vale per **tutti** i comandi MariaDB dati da quell'utente, non solo per il
backup: da lì in poi anche un `mariadb` nudo si collega con quelle credenziali.

### Portarne una copia sul PC di sviluppo

Un backup che vive sullo stesso disco che sta proteggendo non è un backup. Sul PC gira uno script
PowerShell che scarica l'ultimo dump dal server e verifica che sia arrivato intero.

Quella che segue è la procedura **completa**, da rifare tale e quale su un PC nuovo.

> **L'host SSH è `metesoftware.it`**, non `colombini.metesoftware.it`. Quest'ultimo è l'indirizzo
> **web** del gestionale, cioè `app.baseURL`, e non è il nome con cui si raggiunge la macchina.
> Sono due cose diverse che vivono sullo stesso server.

#### 1. La cartella, fuori dal repository

```powershell
New-Item -ItemType Directory -Force D:\BackupDBOPE
```

Lo script sta lì dentro insieme ai backup, e **mai** in `D:\Programmazione\Progetti\colombinisnc`:
quella è una cartella git, e un `git add .` distratto spedirebbe l'anagrafica reale dei clienti su
GitHub. Non basta `.gitignore` — un file che sta fuori dal progetto non può essere aggiunto per
sbaglio, e questa è una garanzia, non una precauzione.

#### 2. Verificare `ssh` e `scp`

Windows 10 e 11 includono il client OpenSSH, ma conviene accertarsene:

```powershell
Get-Command ssh, scp | Select-Object Name, Source
```

Devono rispondere con un percorso dentro `System32\OpenSSH`.

#### 3. La chiave SSH

Lo script apre **tre** connessioni al server: una per chiedere il nome dell'ultimo dump, una per
copiarlo, una per rileggerne la dimensione. Senza chiave, sono tre password a ogni backup — e
soprattutto niente potrebbe mai girare in automatico.

Prima si guarda se una chiave **esiste già**:

```powershell
Get-ChildItem $env:USERPROFILE\.ssh
```

Se c'è una coppia `id_ed25519` (privata) e `id_ed25519.pub` (pubblica), va benissimo così: una
chiave può autorizzare quanti server si vuole, non ne serve una per macchina. Si salta la
generazione e si va direttamente all'autorizzazione.

Solo se non c'è nessuna chiave:

```powershell
ssh-keygen -t ed25519 -C "<nome-del-pc>"
```

`ed25519` è l'algoritmo da usare oggi; `rsa` è quello di vent'anni fa. Alle tre domande: Invio per
accettare il percorso proposto, poi **passphrase vuota** (Invio, Invio). Una passphrase cifrerebbe
la chiave privata, ma verrebbe richiesta a ogni connessione — si tornerebbe al punto di partenza,
e nulla potrebbe girare da solo. La chiave resta protetta dai permessi di `C:\Users\<utente>\.ssh\`.

> ⚠️ Se `ssh-keygen` risponde `Overwrite (y/n)?`, una chiave c'è già: **rispondere `n`**. Non ci
> sono altre conferme dopo, e sovrascriverla significa perdere l'accesso a tutto ciò che
> riconosce la vecchia — GitHub compreso.

Poi si autorizza la chiave **pubblica** sul server. Su Windows non esiste `ssh-copy-id`, quindi
si fa a mano. È l'ultima volta che si digita la password del server:

```powershell
$key = (Get-Content $env:USERPROFILE\.ssh\id_ed25519.pub -Raw).Trim()
ssh <utente>@metesoftware.it "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$key' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
```

Il file `.pub` è quello che si può mostrare a chiunque; il gemello senza `.pub` è la chiave
privata e non deve uscire dal PC per nessun motivo. Tre dettagli che decidono se funziona:

- `-Raw` seguito da `.Trim()` evita che il fine riga di Windows (`\r\n`) finisca dentro
  `authorized_keys` rendendo la chiave illeggibile al server. È il classico inciampo Windows→Linux.
- `>>` **aggiunge** in fondo: non cancella eventuali chiavi già autorizzate.
- I due `chmod` non sono facoltativi. SSH ignora `authorized_keys` se i permessi sono larghi, e lo
  fa in silenzio: è la causa numero uno di "ho messo la chiave e continua a chiedere la password".

> ⚠️ **Quel comando va incollato su una riga sola.** Se PowerShell mostra il prompt di
> continuazione `>>`, un a capo è finito dentro le virgolette e viene spedito al server, che lo
> tratta come una riga di comando a sé: si vede `chmod: missing operand` seguito da
> `600: command not found`. In quel caso la chiave **è comunque stata scritta** (i comandi sono
> concatenati con `&&` e l'`echo` viene prima del pezzo rotto): è fallito solo l'ultimo `chmod`, e
> basta rimediare con `ssh <utente>@metesoftware.it "chmod 600 ~/.ssh/authorized_keys"`.

Verifica:

```powershell
ssh <utente>@metesoftware.it "ls -l ~/.ssh; wc -l ~/.ssh/authorized_keys"
```

Non deve chiedere la password — è quello il collaudo. `authorized_keys` deve risultare
`-rw-------` e contenere una riga per ogni chiave autorizzata: se ne conta due dove ne hai messa
una, la chiave si è spezzata e va rifatta.

#### 4. Lo script

`D:\BackupDBOPE\scarica-backup.ps1`:

```powershell
$Remote       = "<utente>@metesoftware.it"
$Origine      = "/var/backups/colombini"
$Destinazione = "D:\BackupDBOPE"

# Chiede al server come si chiama il dump più recente
$Ultimo = (ssh $Remote "ls -t $Origine/*.sql.gz | head -1").Trim()

if (-not $Ultimo) {
    Write-Host "Nessun dump trovato sul server." -ForegroundColor Red
    exit 1
}

$Nome = Split-Path $Ultimo -Leaf
Write-Host "Scarico $Nome ..."

scp "${Remote}:$Ultimo" $Destinazione

if ($LASTEXITCODE -ne 0) {
    Write-Host "Copia FALLITA." -ForegroundColor Red
    exit 1
}

# Confronta i byte sul server con quelli arrivati sul PC
$ByteServer = [int64](ssh $Remote "stat -c%s $Ultimo")
$BytePc     = (Get-Item (Join-Path $Destinazione $Nome)).Length

if ($ByteServer -ne $BytePc) {
    Write-Host "ATTENZIONE: $ByteServer byte sul server, $BytePc sul PC. File incompleto." -ForegroundColor Red
    exit 1
}

Write-Host "Scaricato e verificato: $Nome ($BytePc byte)" -ForegroundColor Green
```

Il nome del file non si scrive mai a mano nemmeno qui: `ssh` esegue `ls -t | head -1` **sul
server** e ne raccoglie l'output, esattamente come si fa là.

Le graffe in `${Remote}:$Ultimo` sono obbligatorie. Senza, PowerShell leggerebbe `$Remote:` come
un qualificatore di contenitore e darebbe un errore incomprensibile: le graffe dicono dove
finisce il nome della variabile e dove ricomincia il testo.

`$LASTEXITCODE` è il codice di uscita dell'ultimo programma esterno lanciato — qui `scp` — ed è
l'equivalente PowerShell del `$?` usato nello script bash del server.

Il confronto con `stat -c%s` chiude il cerchio: una copia interrotta a metà produce un file più
piccolo dell'originale, e senza quel controllo sembrerebbe riuscita. È la stessa diffidenza del
`-- Dump completed` lato server, applicata al trasferimento.

#### 5. Il permesso di eseguire script

Al primo tentativo Windows rifiuterà lo script con un messaggio del tipo *"l'esecuzione di script
è disattivata in questo sistema"*. Non è un errore dello script:

```powershell
Get-ExecutionPolicy -Scope CurrentUser
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

`RemoteSigned` lascia girare gli script scritti in locale e pretende una firma solo per quelli
scaricati da internet. `-Scope CurrentUser` limita la modifica al proprio utente, quindi non
servono privilegi di amministratore.

#### 6. La prova

```powershell
D:\BackupDBOPE\scarica-backup.ps1
```

Deve concludersi con `Scaricato e verificato: ...` **senza un solo prompt**. Se chiede ancora una
password, il problema è al punto 3, non qui.

#### Un nome breve per la connessione (facoltativo)

Per non ripetere utente e host a ogni comando si può dare un alias alla connessione, in
`C:\Users\<utente>\.ssh\config`:

```
Host colombini
    HostName metesoftware.it
    User <utente>
```

Da lì in poi valgono `ssh colombini` e `scp colombini:/var/backups/colombini/...`, e nello script
è sufficiente `$Remote = "colombini"`.

Le credenziali in sé meritano un gestore di password, non solo un file su disco: se il PC muore,
si recuperano.

### Ripristinare

Il dump ricrea le tabelle che contiene, ma non elimina quelle nate dopo: tornare indietro da
una migration che ha *aggiunto* una tabella lascerebbe quella tabella orfana, e lo schema non
corrisponderebbe più a nessuna versione reale. Il ripristino pulito parte quindi da un database
vuoto — si elimina e si ricrea come descritto in "Svuotare un database esistente", poi:

```bash
zcat /var/backups/colombini/db-2026-08-27-0819.sql.gz | mariadb <database>
sudo -u www-data php spark migrate:status   # deve corrispondere al codice presente sul server
```

Qui il nome del file si scrive per esteso, ed è l'unico punto in cui è giusto farlo: non si
ripristina "l'ultimo" dump, si ripristina **quello che si è scelto**. Utente e password non
compaiono perché li fornisce `~/.my.cnf`.

Il controllo finale non è formalità: se il codice sul server è più avanti del dump ripristinato,
mancano delle migration e l'applicazione andrà in errore su colonne che si aspetta.

### Il motore è MariaDB, non MySQL

In produzione gira **MariaDB 10.11**, in sviluppo MySQL 8: il dump è un dump MariaDB. Ripristinarlo
sul PC di sviluppo può funzionare, ma non è la stessa prova — un test di ripristino serio va fatto
su MariaDB. Il binario si chiama `mariadb-dump`; `mysqldump` esiste ancora come alias.

## Sempre come `www-data`, mai `sudo` e basta

PHP sul server gira come `www-data`, e i comandi vanno dati con la stessa identità.
Con `sudo git pull` o `sudo php spark` ogni file che il comando riscrive — i file aggiornati
dal checkout, la cache, i log — nasce di proprietà di `root`, e l'applicazione poi non riesce
più a scrivere dove le serve. Il sintomo arriva più tardi e sembra un errore
dell'applicazione: fallimenti di scrittura in `writable/`, cache che non si rigenera, upload
rifiutati.

Se è già successo, si rimedia restituendo tutto all'utente giusto:

```bash
sudo chown -R www-data:www-data /var/www/colombini
```

Non modificare mai a mano sul server un file versionato: la working copy deve restare identica
a `origin/main`, altrimenti il primo `reset --hard` se le porta via o va in conflitto. Per
ignorare qualcosa solo su quella macchina c'è `.git/info/exclude`, che ha lo stesso formato
del `.gitignore` ma è locale al clone.

Se dopo un amend il server resta indietro (`git pull` propone di fondere due storie
divergenti), non fondere: allinea la copia al remoto.

```bash
sudo -u www-data git fetch origin
sudo -u www-data git reset --hard origin/main
```

## `--all` non è facoltativo

`php spark migrate` **senza `--all` esegue solo il namespace `App`**: le migration di Shield
(`users`, `auth_*`) e di Settings (`settings`) vivono nei loro namespace dentro `vendor/` e
restano fuori. Su un database vuoto la prima tabella che dichiara una foreign key verso
`users` — `personale` — fallisce con `errno 150 "Foreign key constraint is incorrectly
formed"`, che sembra un errore nella migration e invece segnala solo che la tabella
referenziata non esiste.

Dal sorgente di `CodeIgniter\Commands\Database\Migrate`:

```php
if (array_key_exists('all', $params) || CLI::getOption('all')) {
    $runner->setNamespace(null);     // tutti i namespace
}
```

Attenzione anche alla forma: `-all` con un trattino solo viene ignorato in silenzio, senza
nessun avviso. Servono i due trattini.

## Ordine delle migration

L'ordine di esecuzione non è quello mostrato da `migrate:status`, che raggruppa per namespace.
`MigrationRunner::findMigrations()` costruisce una chiave per ogni migration — le cifre della
version seguite dal nome della classe — e le ordina con `ksort()`. Due conseguenze:

- i namespace si mescolano, e `20201228…` (Shield) precede sempre `20260613…` (App);
- **a parità di timestamp decide il nome della classe**. È il difetto corretto in v0.29.1:
  `CreateClientiTable` e `CreatePersonaleTable` avevano lo stesso timestamp e `clienti`
  finiva prima di `personale`, che referenzia. Se in futuro due migration nascono nello
  stesso minuto, vanno distanziate a mano.

## Svuotare un database esistente

Con un database già popolato — la demo, o un ambiente da azzerare — la via pulita è
eliminarlo e ricrearlo vuoto, poi rieseguire la sequenza minima.

`php spark migrate:refresh` **non** è equivalente: passa per i `down()` di tutte le migration,
codice che non viene quasi mai eseguito in sequenza completa e che può fermarsi a metà
lasciando lo schema in uno stato intermedio (è successo il 26/08 su
`AddArticoloIdToInterventiMateriali`, il cui `down()` eliminava la colonna senza prima
rimuovere la foreign key). Il rollback serve a tornare indietro di un passo durante lo
sviluppo, non a svuotare un ambiente.

## Cosa non eseguire in produzione

- **`DashboardTestSeeder`** — dati demo.
- **`BackfillMaterialiDescrizione`** — manutenzione one-shot su dati preesistenti, su un
  database nuovo non ha nulla da correggere.
- **`TipiInterventoSeeder`** — non è dannoso, ma i tipi intervento si configurano
  dall'interfaccia: usarlo solo se si vuole partire dai valori predefiniti.

## Verifica

`php spark migrate:status` deve mostrare ogni riga con data e batch, comprese quelle di
Shield e Settings. Se una resta `--- ---`, la migrazione non è completa.
