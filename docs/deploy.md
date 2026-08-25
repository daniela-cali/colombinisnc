# Deploy e ricostruzione del database

Procedura verificata il **26/08/2026** su `colombini.metesoftware.it`, ricostruendo l'intero
schema da un database vuoto. Vale sia per il go-live sia per ogni ripartenza pulita.

## Sequenza minima

```bash
cd /var/www/colombini
sudo -u www-data git pull
sudo -u www-data php spark migrate --all
sudo -u www-data php spark db:seed AdminSeeder
```

Fatto questo si entra nel gestionale con le credenziali del `.env`, e il resto della
configurazione — tipi intervento, parametri azienda — si fa dall'interfaccia.

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
