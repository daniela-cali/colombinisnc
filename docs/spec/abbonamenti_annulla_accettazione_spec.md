# Spec: Abbonamenti — Correggere gli errori: annullamento dell'accettazione, eliminazione, rinnovo libero

## Contesto

Il principio da cui parte questa spec è che ogni utente deve poter rimediare a un proprio errore dentro l'applicazione, senza chiedere un intervento diretto sul database.

Oggi non può, per quattro mancanze che sono emerse discutendone e che si tengono l'una con l'altra.

**1. Un abbonamento accettato per errore non si corregge.** Modificare i periodi di frequenza di un abbonamento già accettato non tocca gli interventi già generati: `AbbonamentiController::update()` sostituisce le righe di `abbonamenti_periodi`, ma gli interventi sono stati creati una volta sola, all'accettazione, da `AbbonamentiModel::generaInterventi()`. Da quel momento le due cose divergono senza che niente lo segnali — la scheda dice "mensile" e in giro ci sono 52 visite settimanali.

**2. Un abbonamento non si elimina.** Non esiste nessun `delete()` su `AbbonamentiController`, né la rotta corrispondente. Non è che l'eliminazione sia impedita quando ci sono interventi: non c'è proprio. Una proposta inserita per sbaglio resta nel database per sempre.

**3. Il rinnovo si può preparare solo quando l'abbonamento è già scaduto.** Gli addolcitori vanno dal 1° gennaio al 31 dicembre: con la regola attuale il rinnovo dell'anno successivo si può preparare solo dal 1° gennaio, mentre l'esigenza reale è farli partire tutti a fine dicembre. Le due view sono per giunta incoerenti tra loro — l'index offre Rinnova solo su `scaduto` (riga 195), la scheda su `scaduto` e `disdetto` (riga 183).

**4. Un abbonamento sospeso non scade mai.** Il `CASE` che calcola `stato_calcolato` e la query `leggiScaduti()` del batch notturno filtrano entrambi `stato = 'attivo'`. Un abbonamento sospeso che supera la propria data di fine resta sospeso a tempo indeterminato: fuori da ogni elenco di scadenze, senza bottone di rinnovo, invisibile.

## Decisioni chiave

### 1. L'inverso dell'accettazione, non un secondo meccanismo

La soluzione al primo problema non è un bottone "Rigenera interventi". È **riportare l'abbonamento in stato `proposta`**, annullando l'accettazione.

L'accettazione è l'unico punto in cui gli interventi nascono (spec `abbonamenti_proposte_spec.md`, decisione 4). Il suo inverso è quindi l'unico punto in cui devono morire. Da questa scelta discendono tre conseguenze, che altrimenti sarebbero tre problemi separati:

1. **Un'invariante forte**: un abbonamento in stato `proposta` non ha, per costruzione, nessun intervento. Nascono all'accettazione e vengono distrutti dall'annullamento. L'eliminazione da `proposta` non ha quindi bisogno di nessun controllo sugli interventi — non ce ne sono.
2. **La divergenza dei periodi si risolve da sé**: per cambiare le frequenze di un abbonamento accettato lo si riporta in proposta, si modifica, si riaccetta. Gli interventi si rigenerano dai periodi correnti passando dal codice che già esiste.
3. **Un solo punto di distruzione** degli interventi generati, invece di due strade che convergono sullo stesso effetto.

### 2. La guardia sta sull'annullamento

L'annullamento dell'accettazione è **rifiutato se esiste anche un solo intervento in stato `pianificato`, `in_corso` o `completato`**.

Si può annullare finché l'abbonamento è solo una previsione di lavoro. Nel momento in cui l'ufficio dà una data a una visita, quell'informazione è uscita dall'azienda: il cliente sa che qualcuno passerà giovedì. Un intervento completato è storico, con i suoi materiali.

Restano quindi ammessi `da_pianificare` (mai toccato), `sospeso` (in pausa perché l'abbonamento è sospeso, mai pianificato) e `annullato` (già scartato).

Il messaggio di rifiuto deve dire **quanti** interventi bloccano e in che stato sono, non limitarsi a "operazione non consentita": è l'unico modo perché l'utente capisca che deve prima spostare o annullare quelle visite.

Questa guardia è anche il motivo per cui l'annullamento non è pericoloso nella pratica: un abbonamento realmente in corso ha per forza interventi completati, quindi si blocca da sé. Nei fatti l'operazione riesce solo su un abbonamento che non è mai partito — che è esattamente il caso che vogliamo poter correggere.

### 3. Metodo separato, non una transizione dentro `cambiaStato()`

`annullaAccettazione()` è un metodo a sé, con la sua rotta, e **non** una riga in più nell'array `$transizioni` di `cambiaStato()`.

È la stessa scelta già fatta per `accetta()` (spec proposte, decisione 4), applicata al contrario: questa transizione ha un effetto collaterale che nessun'altra ha — la cancellazione massiva di righe figlie. Nasconderlo dentro un `if` di un metodo pensato per cambi di stato leggeri renderebbe invisibile la cosa più pericolosa che il metodo fa.

`cambiaStato()` resta identico a oggi.

### 4. Si annulla da `attivo` e da `sospeso`

Da `sospeso` gli interventi futuri sono in stato `sospeso`, che la guardia ammette, quindi funziona senza casi speciali; obbligare a riattivare prima sarebbe un passaggio in più senza ragione.

`scaduto` e `disdetto` restano fuori: sono stati terminali. Un contratto finito non è un errore da correggere ma storia da conservare, e se davvero fu accettato per sbaglio l'unica cosa sensata è lasciarlo come storico.

Non serve distinguere tra stato salvato e `stato_calcolato`: il batch notturno `batch:abbonamenti-scaduti` scrive `stato = 'scaduto'` con la stessa condizione del `CASE`, quindi dal giorno dopo la scadenza i due valori coincidono. La finestra in cui divergono è la singola notte tra la data di fine e l'esecuzione del cron.

### 5. Le tabelle figlie vanno cancellate esplicitamente

`interventi_materiali.intervento_id` e `interventi_note.intervento_id` sono entrambe dichiarate `ON DELETE RESTRICT`. Cancellare un intervento che ha materiali o note viene quindi **bloccato dal database**: la transazione deve cancellare prima quelli.

Un intervento mai pianificato che ha già materiali o note è un caso di frontiera, ma possibile — una nota si può scrivere in qualsiasi momento. Si cancellano insieme all'intervento: sono annotazioni su lavoro che non esisterà, e l'alternativa (rifiutare l'annullamento) produrrebbe un messaggio incomprensibile per l'utente, "ci sono note collegate", su un abbonamento che sta cercando di correggere.

Scartata l'idea di scollegare i materiali invece di cancellarli — `interventi_materiali.intervento_id` è nullable proprio per ospitare i "materiali sospesi" di un cliente. Trasformarli in materiali sospesi lascerebbe righe orfane di un abbonamento che l'utente ha appena dichiarato sbagliato.

`abbonamenti_periodi` invece è `ON DELETE CASCADE`: i periodi seguono l'abbonamento da soli, senza codice.

### 6. Eliminazione solo da `proposta`

L'eliminazione è ammessa **solo** in stato `proposta`, dove l'invariante della decisione 1 garantisce che non ci sia niente di collegato. Una regola sola, spiegabile in una riga di help: *"per eliminare un abbonamento riportalo prima in proposta"*.

Non si elimina da `rifiutata`, benché anche lì non ci siano interventi: `rifiutata` è uno stato di business che si è scelto di conservare come storico (spec proposte, decisione 6), non un cestino. Chi vuole davvero eliminare una proposta rifiutata passa da `rifiutata → proposta`, transizione che esiste già.

### 7. Il rinnovo si prepara in qualsiasi momento

Il rinnovo diventa disponibile su **`attivo`, `scaduto` e `disdetto`**, non più solo a scadenza avvenuta, e la condizione diventa la stessa nell'index e nella scheda.

L'esigenza è concreta: gli addolcitori vanno dal 1° gennaio al 31 dicembre, e i rinnovi dell'anno dopo devono poter partire tutti a fine dicembre invece che dal 1° gennaio. Esistono poi contratti infrannuali, che con la regola vecchia sarebbero rinnovabili solo a metà anno.

Restano esclusi:

- **`proposta` e `rifiutata`** — rinnovare qualcosa che non è mai stato accettato non significa niente;
- **`sospeso`** — prima si riattiva. Un contratto in pausa non è una base valida da cui proiettare l'anno successivo, e la riattivazione è un gesto esplicito che chiarisce cosa si sta rinnovando.

Resta la protezione già presente contro il doppio rinnovo: se `successore_id` è valorizzato si mostra "Vai al rinnovo" al posto di "Rinnova". Non è solo una scelta della view — dalla v0.26.0 esiste una chiave `uq_abbonamenti_abbonamento_precedente_id` che rende il vincolo strutturale: un abbonamento può avere al massimo un successore, e il database rifiuterebbe il secondo. Trattandosi di una colonna nullable, i molti abbonamenti senza predecessore non si ostacolano tra loro.

Il volume di proposte che si accumula a fine anno è già governato dalle tendine di filtro per anno nell'index, che esistono.

#### Non si rinnova un abbonamento non ancora iniziato

Aprire il rinnovo a `attivo` introduce un rischio che prima non c'era: la **catena in avanti**. Il controllo su `successore_id` impedisce di rinnovare due volte lo stesso abbonamento, ma non impedisce di risalire la catena — si rinnova il 2027 ottenendo il 2028, poi si va sul 2028, lo si accetta e lo si rinnova, ottenendo il 2029. Si finisce a lavorare con due anni di anticipo, con gli interventi del 2029 già nel pool.

Regola: **`rinnova()` rifiuta se `data_inizio` dell'abbonamento di partenza è successiva a oggi.**

Una condizione sola che chiude il caso: il 2028 creato nel 2027 parte il 1° gennaio 2028, quindi non è rinnovabile finché non siamo nel 2028. Il flusso desiderato resta intatto, perché a fine dicembre 2027 si rinnova il 2027, che è iniziato a gennaio.

È un blocco, non una richiesta di conferma. Un vincolo oggettivo va applicato davvero lato server e non segnalato soltanto — stessa scelta già fatta per le assenze del personale sull'assegnazione degli interventi. Una finestra di conferma, per giunta, verrebbe cliccata via senza leggerla proprio nel momento in cui il rischio è massimo: a fine dicembre, preparando decine di rinnovi in fila.

Il controllo va nel controller, non solo nella condizione della view: `rinnova()` è una GET che pre-compila il form, ma la creazione avviene in `store()`, e `abbonamento_precedente_id` è tra gli `$allowedFields` quindi arriva dal POST. In implementazione va valutato se ripetere la verifica anche in `store()` — è la stessa famiglia del punto 14 della review (mass assignment), che resta fuori scope qui.

### 8. Anche gli abbonamenti sospesi scadono

La scadenza di un abbonamento sospeso non manca: `periodiCoprono()` impone che `max(periodi.data_fine)` coincida con `abbonamenti.data_fine`, quindi la data di fine **è** per costruzione quella dell'ultimo periodo — non sono due dati che possono divergere, la validazione li tiene allineati.

Il problema è che nessuno la guarda. Sia il `CASE` di `stato_calcolato` sia `leggiScaduti()` filtrano `stato = 'attivo'`. La condizione diventa quindi `stato IN ('attivo', 'sospeso')` in entrambi i posti.

Conseguenza da conoscere: un abbonamento sospeso che supera la data di fine viene marcato `scaduto` dal batch, e perde il bottone "Riattiva". È corretto — il periodo contrattuale è finito, riattivarlo non avrebbe un arco temporale su cui insistere — ma va detto nell'help, perché è un comportamento che cambia rispetto a oggi.

La stessa regola vive in **quattro** punti, non nei tre che la review aveva contato: i `CASE` identici di `perCliente()`, `trovaConDettagli()` ed `elencoConDettagli()`, più i `where` di `leggiScaduti()`. Vanno modificati insieme, e conviene estrarre la condizione in un unico punto del model invece di lasciarne quattro copie che il prossimo cambiamento farà divergere.

Resta invece **attivo soltanto** `inScadenza()`, la card "Abbonamenti in scadenza" della dashboard. Non è un'incoerenza: quella card è l'elenco dei rinnovi da preparare, e un abbonamento sospeso non è nel flusso dei rinnovi finché non viene riattivato (decisione 7). Mostrarcelo proporrebbe un'azione che poi verrebbe rifiutata.

### 9. `abbonamento_precedente_id` passa a `RESTRICT`

Migration che porta la chiave esterna `abbonamenti.abbonamento_precedente_id` da `ON DELETE SET NULL` a `ON DELETE RESTRICT`.

La ragione principale è di coerenza: è **l'unica chiave esterna del progetto configurata per cedere**. `interventi → abbonamenti`, `interventi_materiali → interventi`, `interventi_note → interventi` sono tutte `RESTRICT`, e il criterio implicito del progetto è che il database rifiuta e l'applicazione spiega. Qui invece l'eliminazione riuscirebbe azzerando in silenzio il collegamento sul successore: il rinnovo comparirebbe come un contratto nato dal nulla, e la catena storica di quel cliente perderebbe un anello senza che nessuno se ne accorga. È il tipo di perdita che non si nota al momento e non si può ricostruire dopo, perché l'informazione non è ridondata altrove.

Nella pratica il caso è remoto, e vale la pena scrivere perché. Perché si presenti servirebbe un abbonamento accettato, mai lavorato, **e** già rinnovato: un abbonamento realmente in corso ha interventi completati e la guardia della decisione 2 gli impedisce di tornare in `proposta`, quindi non arriva mai all'eliminazione. Ci si riesce solo sommando due gesti sbagliati — accettare per distrazione e rinnovare subito dopo.

La migration si fa comunque, ma non per coprire quel caso: si fa perché così **non serve avere ragione** su quanto sia raggiungibile, né oggi né dopo una futura modifica al flusso dei rinnovi. Il controllo applicativo resta solo per presentare un messaggio comprensibile — "questo abbonamento è già stato rinnovato" con il link al rinnovo — invece di lasciar affiorare un errore SQL.

Il caso opposto resta permesso: eliminare un abbonamento che **è** il rinnovo di un altro non rompe niente, perché nessuna riga punta a lui e il predecessore non conserva un riferimento in avanti (`successore_id` è calcolato da una subquery, non memorizzato).

### 10. I codici degli interventi non tornano indietro

Rigenerando, `generaCodice()` assegna codici nuovi: quelli consumati dalla prima accettazione non vengono riusati. È accettabile — il codice identifica l'intervento, non la sua posizione in una sequenza contabile — ma va detto nella conferma, perché l'utente potrebbe aver annotato un codice altrove.

Nota collegata: `generaCodice()` è il punto 4 della review (`docs/review/2026-08-16-review-progetto.md`), ancora aperto, e questa feature ne aumenta la frequenza d'uso.

### 11. Permessi

Tutte le azioni stanno sotto `abbonamenti.manage`, come creazione e modifica. Chi può creare un abbonamento deve poter correggere il proprio errore: riservarle agli amministratori significherebbe che l'ufficio, che è chi materialmente sbaglia, deve chiedere a qualcun altro.

### 12. Interfaccia

I bottoni stanno nella scheda dell'abbonamento (`show.php`), nei blocchi `card-footer` condizionati per stato già presenti.

- **"Annulla accettazione"**, con stato `attivo` o `sospeso`. Il nome dice cosa si sta facendo; "Riporta in proposta" direbbe solo dove si finisce, tacendo la parte che cancella righe.
- **"Elimina abbonamento"**, solo con stato `proposta`.
- **"Rinnova"**, con la nuova condizione della decisione 7, uniformata tra index e scheda.

Le due azioni distruttive con `confirm()` che dichiara le conseguenze in numeri ("verranno cancellati N interventi"), non in astratto. Per la convenzione CLAUDE.md sui valori PHP dentro gli attributi `onXXX`, il conteggio va estratto in una variabile PHP semplice prima dell'attributo.

### 13. Le regole duplicate si consolidano in un punto solo

Lavorando su questa spec sono emerse due regole di dominio scritte più volte in posti diversi. Entrambe vengono toccate dalle decisioni precedenti, quindi è il momento di unificarle: lasciarle sparse significa modificarne una e dimenticare le altre.

**La condizione di scadenza** — oggi in tre punti di `AbbonamentiModel`: il `CASE` di `trovaConDettagli()`, il `CASE` identico di `elencoConDettagli()`, e i due `where` di `leggiScaduti()` che alimenta il batch notturno. La decisione 8 li allarga tutti e tre a `sospeso`.

Si consolida definendo nel model l'elenco degli stati che possono scadere (`STATI_SCADIBILI = [STATO_ATTIVO, STATO_SOSPESO]`) e il frammento SQL del calcolo, usati dalle due `select()` e dalla query del batch. Un solo posto da modificare il giorno in cui la regola cambia — per esempio se si volesse una tolleranza di qualche giorno prima di considerare scaduto un contratto.

Resta fuori l'allineamento tra `CURDATE()` di MySQL e `date('Y-m-d')` di PHP, che è il punto 8 della review e va affrontato su tutte le query del progetto, non solo su queste.

**La condizione "questo abbonamento è rinnovabile"** — oggi in due view, e già divergenti tra loro: l'index la scrive come `scaduto`, la scheda come `scaduto` o `disdetto`. Con la decisione 7 le regole diventano quattro (stati ammessi, esclusione di `sospeso`, nessun successore già esistente, periodo già cominciato) e i punti che devono conoscerle diventano tre, perché anche `rinnova()` deve rifiutare lato server ciò che il bottone non offre.

Si consolida in un predicato puro sul model — `AbbonamentiModel::rinnovabile(array $abbonamento): bool` — che non tocca il database e riceve una riga già letta. Lo chiamano le due view per decidere se mostrare il bottone e `rinnova()` per rifiutare la richiesta: bottone e rotta non possono più dire cose diverse, che è esattamente il difetto che l'incoerenza attuale tra index e scheda dimostra già oggi.

**Osservata ma non affrontata qui**: la corrispondenza tra stato e azioni disponibili è divisa tra l'array `$transizioni` di `cambiaStato()` e i blocchi condizionali di `show.php`. È la stessa classe di problema, ma consolidarla significa ridisegnare come la view interroga la macchina a stati — un refactoring a sé, che non va infilato in un branch già largo.

### 14. La disdetta annulla anche le visite già pianificate

Emersa leggendo gli stati mentre si lavorava ai bottoni della scheda, e affrontata qui perché tocca gli stessi metodi: `annullaPerAbbonamento()` filtrava `stato IN (da_pianificare, sospeso)`, quindi una visita a cui era già stata assegnata una data sopravviveva alla disdetta e restava in calendario. Il contratto finiva, il tecnico ci andava lo stesso.

Ora la disdetta le include. La regola è temporale, non di stato: **tutto ciò che cade dopo oggi non si fa più**, che sia in calendario o no. Siccome per quelle visite la data è già stata comunicata al cliente, il messaggio di ritorno conta separatamente quante erano pianificate e chiude con "avvisa il cliente" — l'annullamento a sistema non sostituisce la telefonata. Il conteggio si legge prima dell'UPDATE, dopo il quale lo stato di partenza non è più ricostruibile.

**Le visite arretrate restano fuori.** Una `data_scadenza` già passata e mai lavorata non viene toccata: il caso è raro e si risolve a mano, mentre un filtro automatico all'indietro cancellerebbe mesi di storia mai chiusa in un colpo solo, senza che nessuno la riveda.

**Il rifiuto del ripristino non cambia.** Lo stesso metodo serve anche il caso "riattivo l'abbonamento ma non recupero le visite rimaste in pausa": lì si sta decidendo delle sole sospese, non di svuotare il calendario, quindi i pianificati non c'entrano. La differenza sta in un parametro esplicito `$inclusiPianificati`, default `false`: il chiamante dichiara cosa intende, invece di ereditare un comportamento allargato per il caso dell'altro.

Resta vero che disdetta e annullamento dell'accettazione non si sovrappongono, ed è la domanda che ha fatto emergere il difetto: la disdetta chiude un contratto realmente esistito e lascia gli interventi annullati come traccia visibile; l'annullamento cancella fisicamente le righe di un contratto che non doveva esistere in quella forma.

## Alternative scartate

- **Un bottone "Rigenera interventi"** che cancella i futuri e li ricrea dai periodi correnti. È il modo diretto di risolvere la divergenza, ma introduce un secondo percorso di distruzione degli interventi accanto a quello dell'annullamento, con una guardia da tenere allineata, e apre tre decisioni che l'annullamento non pone: quali interventi toccare, cosa fare di quelli già pianificati, cosa fare dei completati. Passando dallo stato `proposta` si ottiene lo stesso risultato riusando `accetta()`.
- **Bloccare la modifica dei periodi dopo l'accettazione**, rendendoli in sola lettura. Elimina la divergenza per costruzione ed è la soluzione più economica, ma toglie la possibilità di correggere un banale errore di battitura su una data — proprio il caso che questa spec vuole coprire.
- **Snippet SQL documentato in `docs/`** come unica via di rimedio. Costo zero, ma richiede accesso al database in produzione e non risolve la pulizia dei dati inseriti per sbaglio.
- **Eliminazione diretta di un abbonamento attivo con i suoi interventi**, protetta dalla stessa guardia della decisione 2. Era l'ipotesi iniziale: funziona, ma duplica la logica di cancellazione in due metodi e lascia l'incoerenza di poter distruggere in un colpo solo ciò che si costruisce in due passaggi distinti.
- **Una guardia applicativa al posto della migration** per la catena dei rinnovi (decisione 9): sarebbe codice che nella pratica non si esegue mai, e la sua correttezza dipenderebbe dal restare allineata a ogni futura modifica del flusso dei rinnovi.
- **Soft delete con un flag `eliminato`** — escluso dalla convenzione CLAUDE.md, che prevede hard delete con controllo applicativo preventivo.

## Riepilogo modifiche file per file

1. `app/Database/Migrations/...AbbonamentoPrecedenteRestrict.php` (nuova) — `abbonamenti.abbonamento_precedente_id` da `ON DELETE SET NULL` a `ON DELETE RESTRICT`, con `down()` che ripristina.
2. `app/Models/AbbonamentiModel.php` — la condizione di scadenza estratta in un unico punto e usata dal `CASE` di `trovaConDettagli()`, da quello di `elencoConDettagli()` e dai `where` di `leggiScaduti()`, tutti allargati a `stato IN ('attivo', 'sospeso')` (decisione 8).
3. `app/Models/InterventiModel.php` — `annullaPerAbbonamento()` prende `bool $inclusiPianificati = false` e restituisce `['totale' => int, 'pianificati' => int]` invece di `void` (decisione 14); `AbbonamentiModel::annullaInterventi()` propaga entrambi. Nuovo `eliminaPerAbbonamento(int $abbonamentoId): int`: cancella in ordine note, materiali e interventi dell'abbonamento, restituendo il numero di interventi cancellati. Da verificare l'esistenza dei model figli (`InterventiNoteModel`, `InterventiMaterialiModel`) e usare quelli, non query dirette sulle stesse tabelle.
4. `app/Controllers/AbbonamentiController.php` — nuovo `annullaAccettazione(int $id)`: verifica lo stato, legge gli interventi con `perAbbonamento()` (una query sola, serve sia per la guardia sia per il conteggio nel messaggio), rifiuta indicando numero e stato di quelli che bloccano, altrimenti in transazione cancella e riporta `stato = proposta`. Nuovo `elimina(int $id)`: ammesso solo da `proposta`; se `successore_id` è valorizzato rifiuta con messaggio e link al rinnovo (decisione 9), altrimenti elimina la riga — i periodi seguono in `CASCADE` e non ci sono interventi per l'invariante della decisione 1. `rinnova()` accetta solo abbonamenti il cui periodo è già cominciato, cioè rifiuta quando la data di inizio è ancora nel futuro (decisione 7); la creazione da zero resta senza vincoli sulle date.
5. `app/Config/Routes.php` — due rotte POST nel gruppo `abbonamenti`, dentro il sottogruppo `permission:abbonamenti.manage`.
6. `app/Views/abbonamenti/show.php` — i due bottoni nuovi con conferma numerica; condizione del bottone Rinnova aggiornata (decisione 7); testo del `confirm()` di Disdici allineato alla decisione 14. Nel ramo disdetta di `cambiaStato()` il messaggio di ritorno conta gli annullati e avvisa se ce n'erano di già pianificati.
7. `app/Views/abbonamenti/index.php` — condizione del bottone Rinnova allineata a quella della scheda.
8. `app/Views/help/abbonamenti.php` — come si corregge un abbonamento accettato per errore, perché si passa da `proposta`, quando l'annullamento viene rifiutato e cosa fare in quel caso, il rinnovo disponibile in anticipo, e il fatto che un abbonamento sospeso ora scade.
9. `CHANGELOG.md` e `docs/ANALISI.md` §7.1 — a chiusura, come da convenzione.

## Fuori scope

- **Rigenerazione parziale dei periodi**, cioè conservare gli interventi già pianificati e rigenerare solo gli altri. È la richiesta che sembra naturale ma non ha una risposta univoca: un intervento pianificato che cade fuori dai nuovi periodi cosa diventa? Si affronta se e quando il caso si presenta.
- **`CURDATE()` di MySQL contro `date('Y-m-d')` di PHP** — punto 8 della review, aperto e indipendente da questa spec: va affrontato su tutte le query che confrontano date, non solo su quelle degli abbonamenti. Verificato però, discutendone qui, che **si risolve interamente lato PHP e non richiede nessuna verifica sul server di produzione**, contrariamente a quanto ipotizzava la review. Le occorrenze in codice applicativo sono 11 (6 in `InterventiModel`, 5 in `AbbonamentiModel`) e si sostituiscono passando la data calcolata da PHP: fatto questo, l'orologio di MySQL non decide più niente e il suo fuso diventa irrilevante. Le altre 6 occorrenze stanno dentro il `CREATE VIEW` di `v_abbonamenti_clienti` e `v_abbonamenti_clienti_interventi` — SQL memorizzato, che un parametro PHP non può raggiungere — ma quelle viste **non sono interrogate da nessun codice applicativo**: esistono apposta per le query manuali su DBeaver, come dichiara `docs/schema.html`. Il loro `CURDATE()` resta quindi dov'è, e non è un problema di correttezza: nessuna decisione dell'applicazione ci passa attraverso. Restano infine due occorrenze in migration one-shot, ininfluenti. Scartata di conseguenza l'altra correzione proposta dalla review, `SET time_zone` sulla connessione: più corta ma peggiore, perché lascia in piedi una dipendenza da configurazione esterna che un cambio di server o un deploy distratto farebbe tornare a rompersi in silenzio.
- **`generaCodice()` e la sequenza dei codici** — punto 4 della review, aperto e indipendente.
- **Storico degli annullamenti** (chi ha annullato un'accettazione e quando). `updated_by`/`updated_at` registrano l'ultimo intervento sull'abbonamento; una tabella di log si costruisce quando una funzionalità la richiede, non per prudenza.
- **Eliminazione di interventi singoli** slegati da questo flusso.
- **Eliminazione da stato `rifiutata`** — vedi decisione 6.
- **Rinnovo da stato `sospeso`** — vedi decisione 7: prima si riattiva.
