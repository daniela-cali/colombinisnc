# 6. Abbonamenti e cantieri

## 6.1 Il concetto di abbonamento

`AbbonamentiController` · `AbbonamentiModel` · `AbbonamentiPeriodiModel`
Rotte `abbonamenti/*` · spec `abbonamenti_spec.md`

Un abbonamento è il **contratto** di manutenzione ricorrente con un cliente: manutenzione
piscina quindicinale, addolcitore trimestrale. Dal contratto si generano tutte le visite
previste per il periodo.

Vale la pena registrare che la concezione iniziale era rovesciata: un abbonamento sarebbe
nato come effetto collaterale silenzioso del salvataggio di un intervento marcato
"programmato". È stata abbandonata perché semanticamente invertita — **l'abbonamento è il
contratto, l'intervento è la sua esecuzione**, non il contrario. Il flusso corretto parte
dal cliente, crea il contratto e da quello genera gli interventi.

Una conseguenza pratica di quel ribaltamento: il Calendario ha dovuto precedere gli
Abbonamenti nell'ordine di sviluppo, perché la generazione in blocco si appoggia sul
concetto di *pool di interventi non pianificati*, che è il Calendario a introdurre.

Un cliente può avere più abbonamenti attivi contemporaneamente, tipicamente uno per
categoria di lavoro.

## 6.2 Periodi di frequenza

La frequenza di visita **cambia dentro lo stesso contratto annuale**: una piscina si visita
ogni due settimane in primavera e autunno, ogni settimana in estate. Per questo la
frequenza non è un campo dell'abbonamento ma di `abbonamenti_periodi`, di cui un
abbonamento ha uno o più.

L'alternativa scartata era spezzare il contratto in più abbonamenti collegati da un
`gruppo_id`: avrebbe reso rumorosa la scheda cliente (N righe per un solo contratto) e
scomodo il rinnovo annuale. Con i periodi, la scheda cliente mostra sempre **una riga per
contratto** e il dettaglio vive nella scheda dell'abbonamento.

Le sette frequenze disponibili, con le occorrenze annue che producono:

| Frequenza | Occorrenze/anno | Calcolo della scadenza |
|---|---|---|
| settimanale | ~52 | data inizio + n×7 giorni |
| quindicinale | ~26 | data inizio + n×14 giorni |
| mensile | 12 | ultimo giorno del mese |
| bimestrale | 6 | ultimo giorno del secondo mese del blocco |
| trimestrale | 4 | ultimo giorno del terzo mese del blocco |
| semestrale | 2 | ultimo giorno del sesto mese del blocco |
| annuale | 1 | data fine dell'abbonamento |

Ogni periodo porta anche `con_pulizia_fondo`, l'opzione operativa che vale per quel tratto
di calendario (tipicamente solo l'estate). Il campo compare nel form solo se il tipo di
intervento ha `ha_pulizia_fondo = 1`.

> Se un giorno le opzioni per-periodo si moltiplicassero (dosaggio chimico, analisi
> dell'acqua…), l'evoluzione prevista è un campo `opzioni JSON` sul periodo più un
> `opzioni_config JSON` sul tipo di intervento, per renderle configurabili senza migration.
> Vale la pena farlo solo da tre opzioni eterogenee in su.

### La copertura dei periodi è obbligatoria

I periodi devono coprire l'intero arco del contratto: un tratto senza periodo è un tratto
senza frequenza, quindi senza visite generate. Tre aiuti nel form e un blocco:

- la **prima riga eredita sempre** la data di inizio dell'abbonamento, e resta sincronizzata
  con quel campo (con un solo periodo, qualunque altro valore lascerebbe scoperto del
  calendario: non è una scelta dell'utente);
- il bottone "Aggiungi periodo" propone come inizio **il giorno successivo** alla fine
  dell'ultimo periodo;
- la **data di fine non viene mai precompilata**, nemmeno sulla prima riga. La scelta è
  deliberatamente asimmetrica rispetto al punto precedente: la fine di un periodo è quasi
  sempre una decisione reale dell'utente, e un valore precompilato e dimenticato sarebbe
  silenziosamente sbagliato;
- il salvataggio è **bloccato**, sia dal browser sia dal server
  (`AbbonamentiController::periodiCoprono()`), se il primo o l'ultimo periodo non combaciano
  con le date del contratto. Il controllo lato browser è una comodità aggirabile: l'unica
  garanzia reale è quella lato server.

I confronti si fanno su **stringhe** in formato `Y-m-d`, che si ordinano cronologicamente
da sole senza bisogno di istanziare oggetti data.

> Un bug incontrato qui merita una riga, perché è facile ripeterlo: il calcolo del "giorno
> successivo" costruiva la data a mezzanotte locale e poi la formattava con
> `toISOString()`, che converte in UTC. Con fuso orario italiano la mezzanotte del giorno
> dopo è ancora il giorno prima in UTC, e il risultato tornava identico al valore di
> partenza. Si leggono `getFullYear()`/`getMonth()`/`getDate()`, che restano locali.

## 6.3 Stati e transizioni

```
                    ┌──────────┐
                    │ proposta │ ◄──────┐
                    └────┬─────┘        │ ripensamento
          accetta        │      rifiuta │
    (genera le visite)   │              │
                         ├──────────► rifiutata (terminale)
                         ▼
                    ┌──────────┐
                    │  attivo  │ ◄──────► sospeso
                    └────┬─────┘             │
                         │                   │
                         ├───────────────────┴──► disdetto (terminale)
                         ▼
                    ┌──────────┐
                    │ scaduto  │  data di fine superata
                    └──────────┘
```

Ogni abbonamento — nuovo o rinnovo — **nasce come proposta**, senza generare alcun
intervento. Nella pratica un abbonamento proposto a un cliente non è un contratto finché il
cliente non accetta: prima quel passaggio avveniva fuori dal gestionale.

Gli effetti collaterali di ogni transizione:

| Transizione | Effetto sugli interventi figli |
|---|---|
| `proposta` → `attivo` (accettazione) | **genera tutte le visite previste** |
| `proposta` → `rifiutata` | nessuno: le visite non esistono ancora |
| `attivo` → `sospeso` | quelli futuri ancora da pianificare passano a `sospeso` |
| `sospeso` → `attivo` | l'utente sceglie: ripristinarli nel pool oppure annullarli |
| → `disdetto` | quelli da pianificare o sospesi vengono annullati, in transazione |
| → `scaduto` | nessuno: è la fine naturale, dovrebbero già essere chiusi |

**`rifiutata` non è `disdetto`**, e tenerli distinti conta: *disdetto* significa "è stato un
contratto attivo, poi cancellato", con interventi da annullare; una proposta rifiutata non
è mai stata un contratto. Confonderli renderebbe ambigui i filtri e i report futuri.

L'accettazione è un metodo a sé (`accetta()`), **non** una transizione in più dentro
`cambiaStato()`. È l'unica che ha un effetto collaterale massivo — la creazione di decine di
righe — e tenerla separata lo rende esplicito, invece di nasconderlo in un `if` speciale
dentro un metodo pensato per cambi di stato leggeri.

### Accettazione multipla

A inizio anno i rinnovi da confermare sono decine. L'elenco permette di selezionarli con
delle caselle e accettarli in blocco, ma **con una transazione per abbonamento, non una
sola per tutti**: un problema su un singolo contratto non deve annullare l'accettazione
degli altri, e i lock restano brevi proprio nello scenario che scrive di più. L'esito è
riassunto in un unico messaggio con accettati e falliti.

## 6.4 Generazione delle visite

`AbbonamentiModel::generaInterventi()` itera sui periodi in ordine, calcola le scadenze di
ciascuno secondo la propria frequenza e crea gli interventi. Ogni occorrenza nasce così:

```php
[
    'cliente_id'         => ...,
    'abbonamento_id'     => ...,
    'tipo_intervento_id' => ...,          // lo stesso per tutte le visite del contratto
    'priorita'           => 'abbonamento',
    'stato'              => 'da_pianificare',
    'data_pianificata'   => null,         // la assegna il dispatcher dal Calendario
    'data_scadenza'      => $fineDelPeriodoDiCompetenza,
    'pulizia_fondo'      => $periodo['con_pulizia_fondo'],
]
```

Tutte le visite dell'anno nascono in una volta sola, non una alla chiusura della precedente.
La scrittura usa `insertBatch()`: da N query a una sola per abbonamento accettato, il che
conta parecchio con un settimanale da 52 occorrenze moltiplicato per i rinnovi di gennaio.

> `insertBatch()` **non esegue i callback `$beforeInsert`** del model. Codice progressivo,
> `created_by` e `updated_by` sono quindi replicati a mano riga per riga prima
> dell'inserimento. È il tipo di dettaglio che si rompe in silenzio se qualcuno aggiunge
> logica al `normalizza()` degli interventi dando per scontato che valga sempre.

### Le scadenze duplicate al confine fra periodi

Ogni periodo forza la propria ultima scadenza a coincidere con la sua data di fine. Se un
periodo comincia **lo stesso giorno** in cui finisce il precedente, e quella data cade su un
punto di allineamento naturale della nuova frequenza, la stessa data viene generata due
volte.

Esempio verificato: periodo mensile 01/01→31/05 che genera `31/01, 28/02, 31/03, 30/04,
31/05`, seguito da un quindicinale che parte dal 31/05 — il cursore iniziale si posiziona
sull'ultimo giorno del mese, che *è* il 31/05, e lo rigenera.

Il sintomo era mascherato: la query "prossima visita" del capitolo 5.2 rileva l'ambiguità
e ricade sulla gestione manuale. Ma la garanzia *"un abbonamento ha una sola prossima
visita"* non era rispettata, e ogni funzionalità futura che vi si appoggia avrebbe dovuto
reintrodurre la gestione dell'eccezione solo per compensare.

La correzione non tocca il calcolo delle singole frequenze — il problema è
nell'orchestrazione. Si tiene traccia dell'ultima scadenza inserita e si scarta qualunque
scadenza successiva non strettamente maggiore:

```php
if ($ultimaScadenza !== null && $scadenza <= $ultimaScadenza) {
    continue; // duplicato al confine fra periodi
}
```

Scartare in silenzio è corretto: è la stessa identica data generata due volte per
continuità, non un errore di configurazione da segnalare. Non serve un vincolo di unicità
sul database, che farebbe fallire l'inserimento con un errore SQL invece di evitare il
problema, e complicherebbe casi legittimi come una visita extra con scadenza coincidente.

Verificando questo fix è emerso che la convenzione corretta è quella **senza giorno di
confine condiviso** (il periodo successivo parte dal giorno dopo), che copre tutto il
calendario senza buchi e senza duplicati. È quella che il form promuove attivamente; il
controllo qui descritto resta come rete di sicurezza per dati inseriti a mano o import
futuri.

## 6.5 Rinnovo

Il bottone Rinnova compare su un abbonamento che non sia disdetto e che non abbia già un
successore. Apre il form precompilato con i dati del contratto corrente e le date spostate
di un anno, tutto modificabile; il nuovo record punta al precedente con
`abbonamento_precedente_id`, ricostruendo la catena storica.

Anche il rinnovo **nasce come proposta**, pure per i clienti consolidati che accettano di
fatto sempre: l'obiettivo è un iter uniforme e tracciato, non un'eccezione silenziosa.

L'indice `UNIQUE` su `abbonamento_precedente_id` garantisce a livello di database che un
abbonamento abbia al massimo un rinnovo. Da uno scaduto che ne ha già uno, il bottone
diventa il link "Vai al rinnovo".

## 6.6 Visite extra e pulizia del fondo

Una **visita extra** è un intervento fuori dal piano ma collegato all'abbonamento: si crea
dalla scheda del contratto, ha `extra = 1`, il tipo precompilato in sola lettura, e nasce
con data pianificata e scadenza coincidenti. Usa **la stessa `priorita = 'abbonamento'`**
delle occorrenze regolari, proprio per partecipare al circuito della prossima visita e dei
materiali sospesi senza filtri aggiuntivi. Il flag serve come marcatore per la fatturazione
separata.

La **pulizia del fondo** è un singolo flag booleano sull'intervento, non duplicato altrove.
Le occorrenze regolari lo ereditano dal periodo di competenza; le visite extra nascono con
il flag spento; alla chiusura è comunque modificabile, il che copre sia "prevista ma non
fatta quel giorno" sia "non prevista ma fatta".

Se una pulizia sia stata *extra* — quindi fatturabile a parte — non viene salvato da nessuna
parte: si calcola a posteriori confrontando il valore registrato sull'intervento con quello
previsto dal periodo corrispondente. È un calcolo da fare in fase di report, non una colonna
ridondante da tenere aggiornata.

## 6.7 Il batch degli abbonamenti scaduti

`app/Commands/AbbonamentiScaduti.php` · `php spark batch:abbonamenti-scaduti`
Spec `abbonamenti_scaduti_batch_spec.md`

Lo stato `scaduto` è nato come valore **calcolato a runtime**, con lo stesso `CASE WHEN`
ripetuto in tre metodi del model:

```sql
CASE WHEN data_fine < CURDATE() AND stato = 'attivo' THEN 'scaduto' ELSE stato END
```

La colonna `stato`, però, non transitava mai da sola: restava `attivo` per sempre. Non era
un bug visibile — tutte le viste passano da quei tre metodi — ma un rischio latente:
qualunque query futura che leggesse `stato` direttamente (un report, un export, una card
della dashboard) avrebbe incluso contratti già scaduti, a meno di ricordarsi di riscrivere
lo stesso `CASE WHEN` ogni volta.

Il comando persiste davvero lo stato, ed è pensato per girare ogni notte da cron. Tre scelte
degne di nota:

- **è interattivo per impostazione predefinita**: legge gli scaduti, li mostra in tabella e
  chiede conferma. Il flag `-force` salta la domanda ed è il modo di eseguirlo da cron, dove
  non c'è nessuno a rispondere;
- **è naturalmente idempotente**: una seconda esecuzione non trova più righe da aggiornare,
  quindi è innocuo anche se il cron partisse due volte;
- **le tre query con `CASE WHEN` restano**: coprono le al massimo 24 ore fra la scadenza
  reale e il passaggio del batch. Toglierle renderebbe l'interfaccia in ritardo di un
  giorno rispetto a oggi.

Il batch è cieco al tipo di intervento: gli abbonamenti piscina restano scaduti per mesi fra
una stagione e l'altra, ed è corretto — quel contratto è concluso. Distinguere *scaduto
stagionale* da *scaduto da seguire* è un giudizio di business che riguarda una futura vista
"da rinnovare", non il batch.

Ogni fase viene registrata su file tramite l'helper generico `custom_log()`, in
`writable/custom_log/<contesto>/AAAAMMGG.log`.

## 6.8 Il documento di proposta

I campi `operazioni_incluse` (sull'abbonamento) e `operazioni_standard` (sul tipo di
intervento) esistono in vista della **generazione automatica del documento di proposta**
in formato Word, che è la fase 2 e non è ancora implementata.

Il funzionamento attuale: il tipo di intervento porta il testo standard delle operazioni
(per le piscine sono otto righe), che precompila il campo dell'abbonamento alla scelta del
tipo — con lo stesso meccanismo già usato per la descrizione degli interventi: una mappa
`id → testo` in JavaScript, applicata solo se il campo è ancora vuoto, così un testo già
scritto non viene mai sovrascritto.

Restando **testo libero** si possono aggiungere o togliere righe per il singolo cliente.
Un catalogo strutturato di operazioni con caselle di selezione è stato scartato:
introdurrebbe una tabella di voci riutilizzabili senza un bisogno confermato, quando il
testo libero copre già il caso reale ("di solito fisso, a volte va corretto").

Anche `modalita_pagamento` è testo libero e non una tabella di lookup: gli accordi sono
specifici del singolo contratto (*"a metà servizio, agosto 2026"*). Non è stata prevista
alcuna chiave verso una futura tabella dei pagamenti — quella tabella non esiste nemmeno
sulla carta, e indovinarne oggi la forma rischia di essere sbagliato comunque.

Analizzando il modello Word reale è emerso che **non contiene tabelle a righe variabili**:
quello che sembrava un elenco dinamico è un elenco puntato fisso. È il motivo per cui in
fase 1 bastano tre campi.

## 6.9 Cantieri

`CantieriController` · `CantieriModel` · `CantieriNoteModel` · rotte `cantieri/*`

Un cantiere raggruppa più interventi legati a un unico progetto per un cliente: nuova
costruzione, ristrutturazione o manutenzione straordinaria. Si distingue dagli interventi
autonomi — manutenzioni, abbonamenti — che non appartengono a nessun progetto.

### Il modello

Due tabelle (`cantieri` e `cantieri_note`) più la colonna `cantiere_id` su `interventi`,
gemella di `abbonamento_id`.

La decisione strutturale è che **note e interventi sono fratelli figli del cantiere, non
collegati fra loro**. Le note sono prosa libera datata; quando un appunto diventa lavoro
concreto si crea un intervento e lo si aggancia al cantiere. Dal diario si può generare
l'intervento direttamente da una nota, precompilando cliente, cantiere e descrizione con il
testo della nota.

### Il flusso

1. si apre il cantiere sul cliente, con titolo, tipo ed eventuale tipo di intervento
   predefinito;
2. si aggiungono note al diario man mano che il lavoro avanza;
3. quando serve un lavoro concreto si crea un intervento agganciato al cantiere — il tipo
   arriva precompilato dal predefinito, ma resta modificabile;
4. l'intervento entra nel pool del Calendario e segue il flusso normale;
5. il cambio di stato del cantiere (aperto, sospeso, chiuso) **non produce alcun effetto a
   cascata** sugli interventi.

L'eliminazione è bloccata finché ci sono interventi collegati.

### Luogo e referente propri

Il cantiere può avere indirizzo, città, referente e posizione geografica **diversi da quelli
del cliente**; quando i campi sono vuoti valgono automaticamente quelli del cliente. Il
ripiego mantiene il caso comune senza attrito e copre due situazioni reali emerse
analizzando lo storico: un intermediario che segue piscine per conto di proprietari terzi,
e un cliente con due proprietà distinte.

Il referente è la persona da contattare per **quel** cantiere — custode, amministratore,
impresa edile — e spesso non è il cliente. È diviso in nome e telefono, con il numero
isolato per il link `tel:` della scheda intervento e **nessun ripiego** sul telefono del
cliente: chiamare il cliente pensando di chiamare il referente sarebbe peggio che non avere
il bottone.

La geolocalizzazione propria non è una comodità ma la correzione di un difetto reale:
`InterventiModel::agendaTecnicoPeriodo()`, la query dietro l'agenda mobile, leggeva
indirizzo e coordinate **solo** da `clienti` ignorando `cantiere_id`. Per un intervento su
un cantiere con luogo diverso, "Apri in Google Maps" mandava il tecnico all'indirizzo
sbagliato. Ora la query fa una join su `cantieri` e usa `COALESCE` per preferire la
posizione del cantiere quando c'è.

Alternative valutate e scartate: una tabella `impianti` (il modello giusto a lungo termine
per il multi-proprietà, ma una funzionalità grossa e sproporzionata rispetto a due o tre
cantieri su ventuno — e le colonne su `cantieri` non la precludono, se nascerà i campi
migrano lì); una tabella di referenti multipli con ruolo (i casi esistono, ma l'80% dei
cantieri ha un solo nome, e passare da campo a tabella in futuro è una migrazione banale);
un'anagrafica contatti globale riutilizzabile.

### Stampa PDF

A differenza di quella del cliente, la stampa del cantiere è un **riepilogo completo**:
anagrafica cliente e dati cantiere affiancati, **diario integrale** e **tutti** gli
interventi collegati in ogni stato, con i relativi materiali. La differenza di impostazione
è voluta: la scheda cliente serve prima di un intervento, quella del cantiere serve a
ricostruire la storia di un progetto.
