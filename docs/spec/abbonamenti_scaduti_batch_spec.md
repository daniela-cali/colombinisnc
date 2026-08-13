# Spec — Batch notturno "Abbonamenti scaduti"

## 1. Contesto e problema di partenza

Oggi lo stato `scaduto` esiste solo come valore **calcolato a runtime**, duplicato in tre metodi di `AbbonamentiModel` (`elencoConDettagli()`, `perCliente()`, `inScadenza()`), tramite lo stesso pattern:

```sql
CASE WHEN data_fine < CURDATE() AND stato = 'attivo' THEN 'scaduto' ELSE stato END
```

La colonna `abbonamenti.stato` **non transita mai da sola** a `'scaduto'`: resta `'attivo'` per sempre nel DB anche molto dopo la scadenza, finché non arriva un update esplicito. Né `rinnova()` (che crea un nuovo record, non tocca il vecchio) né `cambiaStato()` (che non ha nessuna logica legata alla data) lo aggiornano.

Non è un bug visibile oggi: tutte le viste che mostrano lo stato (pillola filtro, badge, bottone "Rinnova" nell'index) passano da quei tre metodi, quindi sono già corrette. Il rischio è **latente**: qualunque query futura che legga `stato` direttamente (`WHERE stato = 'attivo'` in un report, un export, un conteggio per una card dashboard, un endpoint nuovo) darebbe un risultato sbagliato includendo abbonamenti già scaduti, a meno di ricordarsi di riscrivere lo stesso `CASE WHEN` ogni volta.

## 2. Decisione

Persistere davvero lo stato tramite un comando Spark lanciato ogni notte via cron (`app/Commands/AbbonamentiScaduti.php` — `php spark batch:abbonamenti-scaduti`), sullo stesso modello già in uso: cron che chiama `php spark <comando>` direttamente sulla macchina.

Rispetto all'idea iniziale di un update "cieco" e automatico, il comando implementato è **interattivo di default**: legge gli abbonamenti scaduti, li mostra a video in tabella, chiede conferma prima di aggiornarli. Per l'esecuzione da cron (dove non c'è nessuno a rispondere al prompt) è previsto un flag `-force` che salta la domanda e procede direttamente.

### Query di riferimento

```sql
UPDATE abbonamenti
SET stato = 'scaduto'
WHERE stato = 'attivo'
  AND data_fine < CURDATE()
```

Naturalmente idempotente: un secondo run non trova più righe da aggiornare, quindi è sicuro anche se il cron dovesse girare due volte per errore.

### Decisioni chiave

1. **Nessuna distinzione per `tipo_intervento_id` (piscine vs altri) dentro il batch.** Gli abbonamenti piscine restano "scaduti" per mesi tra una stagione e l'altra — è normale, ma resta comunque vero che il contratto di quel periodo è concluso. Distinguere "scaduto stagionale, normale" da "scaduto da seguire davvero" è un giudizio di business che riguarda una futura vista/report ("da rinnovare"), non il batch: il batch si limita a riflettere la realtà della data ed è cieco al tipo.

2. **Le tre query con `CASE WHEN` restano invariate.** Coprono la finestra di al massimo 24h tra la scadenza reale e il passaggio del cron notturno — toglierle renderebbe la UI "in ritardo di un giorno" rispetto a oggi, un peggioramento senza motivo.

3. **`cambiaStato()` non viene modificato.** L'array `$transizioni` non ha (e non deve avere) una chiave `'scaduto'`: un abbonamento scaduto non transita mai manualmente altrove, si "rinnova" creando un nuovo record via `rinnova()`. Il guard esistente (`isset($transizioni[$statoAttuale])`) blocca già correttamente qualunque tentativo di cambiare stato su una riga scaduta, senza bisogno di aggiungere nulla.

4. **Query nel model, non nel comando** — coerente con la convenzione di progetto "query nei model, usare `$this`". Due metodi invece di uno solo, per permettere la conferma interattiva nel mezzo:
   - `AbbonamentiModel::leggiScaduti(): array` — legge (senza modificare nulla) gli abbonamenti attivi con `data_fine` già passata: id, cliente, tipo, stato, data_fine.
   - `AbbonamentiModel::updateScaduti(array $ids): int` — applica l'update solo sugli id passati (quelli letti da `leggiScaduti()` e confermati), restituisce il numero di righe toccate.

5. **Nessuna migration.** `stato` è già `VARCHAR(20)` con `'scaduto'` tra i valori ammessi (vedi migration `AddStatoPropostaRifiutataAbbonamenti`), nessuna modifica di schema necessaria.

6. **Conferma interattiva + flag `-force` per il cron.** Di default `CLI::prompt()` chiede "Vuoi aggiornare i record indicati?" prima di applicare l'update — utile per lanciare il comando a mano e controllare l'elenco prima di confermare. `CLI::getOption('force')` (invocabile come `-force` o `--force`, il parser di CodeIgniter accetta entrambi) salta la domanda e procede come se la risposta fosse "sì": è il modo per rendere il comando eseguibile da cron, dove non c'è un utente a rispondere. Il flag è documentato nella proprietà `$options` del comando, visibile con `php spark help batch:abbonamenti-scaduti`.

7. **Log di ogni esecuzione, con helper generico riusabile.** Ogni fase del batch (inizio, elenco trovato, conferma/annullamento, esito, fine) viene scritta su file tramite `custom_log(string $from, string|array $message)` (`app/Helpers/custom_log_helper.php`). L'helper non è specifico di questo batch: scrive in `writable/custom_log/<from>/AAAAMMGG.log` (un file al giorno per contesto, sottocartella creata automaticamente al primo utilizzo, ogni riga timestampata `[HH:MM:SS]`), pensato per essere riusato da qualunque comando o processo futuro che debba lasciare una traccia su file, non solo dai batch notturni.

## 3. Alternative scartate

- **Soglia di grazia per tipi stagionali dentro il batch stesso**: scartata per ora — introdurrebbe una regola di business nuova (quanti giorni di grazia, quali tipi sono "stagionali") che oggi non esiste nello schema (`tipi_intervento` non ha un flag del genere). Rimandata a quando servirà davvero una vista "da rinnovare" con questa logica.
- **Sostituire il `CASE WHEN` nelle query con il solo valore raw**: scartata — perderebbe la freschezza "in giornata" che oggi la UI garantisce, a favore di un aggiornamento visibile solo dal giorno dopo la scadenza.

## 4. Riepilogo modifiche file per file

1. `app/Models/AbbonamentiModel.php` — nuovi metodi `leggiScaduti(): array` e `updateScaduti(array $ids): int`.
2. `app/Commands/AbbonamentiScaduti.php` — `run()` legge gli scaduti, mostra tabella CLI, chiede conferma (bypassabile con `-force`), aggiorna, logga ogni fase; proprietà `$options` per documentare il flag in `spark help`.
3. `app/Helpers/custom_log_helper.php` (nuovo) — funzione `custom_log(string $from, string|array $message): bool`, generica e non legata a questo batch: crea la sottocartella `writable/custom_log/<from>/` al primo utilizzo, un file per giorno, righe timestampate.
4. `app/Config/Constants.php` — nuova costante `LOGPATH` (`writable/custom_log/`), radice di tutti i log applicativi scritti tramite `custom_log()`. Nome scelto per non confondersi con `writable/logs/`, già usata internamente dal framework CodeIgniter.
5. `.gitignore` — aggiunta `/writable/custom_log/*` con eccezione per `index.html` (stesso pattern già in uso per `writable/logs/`, `writable/cache/`, ecc.), così la cartella esiste sempre anche su un clone pulito del repository.

## 5. Fuori scope

- Soglia/logica per tipi stagionali (piscine) — rimandata a una futura vista "da rinnovare".
- Configurazione dell'entry crontab su Debian/produzione — il comando Spark è pronto (con `-force` per l'esecuzione non interattiva), l'entry va aggiunta a parte in fase di deploy, fuori dal codice versionato.
- Notifiche o alert quando un abbonamento passa a scaduto.
