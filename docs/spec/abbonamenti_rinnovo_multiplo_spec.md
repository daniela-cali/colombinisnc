# Spec: Abbonamenti — Rinnovo multiplo in coda

> Decisioni prese nel brainstorming del 07/10/2026. Si appoggia al prezzo del rinnovo della
> v0.36.1 (`AbbonamentiModel::prezzoRinnovo()`) e al percorso di go-live deciso lo stesso
> giorno (`CLAUDE.md`, «Go-live in produzione»).

## Contesto

A dicembre 2026 il 2027 nascerà rinnovando gli abbonamenti 2026 caricati in produzione:
circa 70 impianti e 80 piscine. Oggi il rinnovo si fa uno per volta: dalla riga dell'elenco
o dalla scheda si apre il form precompilato, si controlla, si salva, e si torna all'elenco a
cercare il successivo. Con 150 abbonamenti il tempo perso sta nel tornare all'elenco e
ritrovare il punto.

Il vecchio gestionale non aveva nessun rinnovo, quindi non c'è un pattern da riprendere.

## Decisioni chiave

### 1. Ogni rinnovo passa dal suo form

Il rinnovo multiplo non crea le proposte alla cieca: apre in sequenza il form di rinnovo di
ciascun abbonamento selezionato, e l'operatore lo approva. È il form di oggi, con gli stessi
dati precompilati. Il rinnovo multiplo toglie solo il ritorno all'elenco fra un form e
l'altro.

Il motivo: non sappiamo ancora quanti abbonamenti cambiano davvero da un anno all'altro,
soprattutto i periodi delle piscine. Il rinnovo automatico senza form resta un'idea per dopo,
con un flag sull'abbonamento che dica se può essere rinnovato senza controllo (vedi «Fuori
scope»).

### 2. Cosa passa all'anno dopo

Come nel rinnovo singolo, che non cambia:
- **copiati**: tipo di intervento, operazioni, apparecchiature, note, modalità di pagamento;
- **spostati di un anno**: date dell'abbonamento e dei periodi;
- **aumentato**: il prezzo, con `prezzoRinnovo()`.

La modalità di pagamento resta testo libero e **si scrive senza anno** («a metà servizio:
giugno»), così copiarla è corretto. La tabella delle condizioni di pagamento è rimandata,
vedi `docs/backlog.md`.

### 3. Selezione dall'elenco, rispettando i filtri

La casella di selezione, oggi presente solo sulle proposte, compare anche sulle righe
**rinnovabili**, cioè dove oggi c'è il pulsante Rinnova (`$a['rinnovabile']`). Le due
caselle stanno nella stessa colonna, ma **una selezione è di un tipo solo**:

- la prima riga spuntata decide il tipo. Le caselle dell'altro tipo si disattivano finché
  la selezione non torna vuota;
- si attivano solo i pulsanti del tipo scelto: **Accetta selezionati** e **Scarica
  proposte** per le proposte, **Rinnova selezionati (n)** per le rinnovabili, con il numero.

Scartata la prima idea, che lasciava convivere i due tipi e faceva agire ogni pulsante solo
sulle righe sue: un pulsante che usa una parte di ciò che si vede spuntato, ignorando il
resto in silenzio, costringe a leggere i conteggi per non sbagliare. Con un tipo solo, ciò
che è spuntato è esattamente ciò su cui il pulsante agisce.

**Seleziona tutte** spunta le righe che passano i filtri, anche quelle nelle pagine
successive, non solo la pagina visibile:
- con una selezione già avviata, aggiunge le righe filtrate di quel tipo;
- con la selezione vuota e i filtri che mostrano un tipo solo, le prende tutte;
- con la selezione vuota e i filtri che mostrano entrambi i tipi, non spunta niente e un
  avviso chiede di filtrare per anno o per stato.

**Un disdetto non è rinnovabile finché l'operatore non lo riattiva** (deciso durante
l'implementazione). La decisione 7 di `abbonamenti_annulla_accettazione_spec.md` lo ammetteva
insieme ad attivi e scaduti, senza un motivo proprio: così un cliente che aveva chiuso il
contratto poteva finire in una coda di rinnovi. Ora la disdetta non è più uno stato finale:
dalla scheda si riattiva (`disdetto → attivo` in `cambiaStato()`), e la riattivazione cambia
solo lo stato. Le visite annullate dalla disdetta restano annullate. Un disdetto riattivato
con la data di fine passata risulta scaduto, quindi rinnovabile.

Il caso misto è raro nell'uso: le proposte sono dell'anno dopo e le rinnovabili dell'anno in
corso, e il filtro dell'anno le separa già.

### 4. L'ordine è quello di selezione

I form si aprono nell'ordine in cui le righe sono state selezionate:
- spuntando a mano, l'ordine dei clic;
- con **Seleziona tutte**, l'ordine in cui la tabella è ordinata in quel momento. Ordinando
  per cliente prima di selezionare, i form arrivano in ordine alfabetico.

Una riga tolta dalla selezione esce dalla coda; rispuntata, va in fondo.

### 5. La coda viaggia nell'indirizzo, non in sessione

L'elenco degli abbonamenti ancora da fare passa di form in form nella query string del
rinnovo:

```
abbonamenti/46/rinnova?coda=51,38,12&fatti=2&saltati=40
```

- `coda`: gli id ancora da aprire, nell'ordine, escluso quello del form corrente;
- `fatti`: quante proposte sono state create finora;
- `saltati`: gli id saltati finora.

Il form li riporta in campi nascosti. Pro rispetto alla sessione:
- **due schede non si pestano i piedi**: ogni coda vive nel suo indirizzo;
- **un errore di validazione non perde la coda**: `store()` torna indietro con
  `redirect()->back()`, cioè allo stesso indirizzo con la sua query string;
- niente da pulire quando l'operatore abbandona a metà.

Con 150 id l'indirizzo resta sotto il chilobyte.

### 6. Tre pulsanti nel form, in coda

Solo quando il form è aperto da una coda; il rinnovo singolo resta com'è.
- **Salva e prossimo** (primario; sull'ultimo diventa **Salva e termina**): crea la proposta
  e apre il form del successivo.
- **Salta** (secondario): non crea niente, aggiunge l'id ai saltati e apre il successivo. È
  un link, non serve un POST.
- **Interrompi** (ritorno): prende il posto di Annulla, porta al riepilogo e all'elenco.

In alto, un'indicazione «Rinnovo 3 di 12». La posizione si ricava da `fatti + saltati + 1`,
il totale aggiungendo la lunghezza di `coda`.

### 7. Riepilogo finale con i nomi dei saltati

Alla fine della coda, o dopo Interrompi, si torna all'elenco con un messaggio: «Create 10
proposte. Saltati 2: Rossi Mario, Piscina Bianchi». I nomi si leggono dal database a partire
dagli id dei saltati. Interrompendo, il messaggio dice anche quanti ne restavano.

L'elenco ricorda i filtri in `sessionStorage`, quindi si ritrova come lo si era lasciato.

### 8. Un abbonamento non più rinnovabile si salta da solo

Fra la selezione e l'apertura del form un abbonamento può smettere di essere rinnovabile,
per esempio perché è stato rinnovato da un'altra scheda. In coda, `rinnova()` non mostra
l'errore come nel singolo: lo aggiunge ai saltati e passa al successivo. Nel riepilogo, per
ogni saltato che oggi non è rinnovabile, il motivo viene da `motivoNonRinnovabile()`, lo
stesso metodo che decide.

### 9. Riprendere il giorno dopo non richiede niente

Un abbonamento rinnovato non è più rinnovabile e perde la sua casella. Interrotta la coda,
il giorno dopo si riapplicano gli stessi filtri, si seleziona tutto e restano solo quelli
non ancora fatti. I saltati invece ricompaiono, perché sono ancora rinnovabili.

### 10. Corretto: la selezione multipla ignorava le altre pagine

DataTables stacca dalla pagina le righe che non sono nella pagina visibile o non passano i
filtri. Il codice di oggi cerca le caselle con `document.querySelectorAll`, quindi:
- **Seleziona tutte** spuntava solo le proposte della pagina visibile;
- **Accetta selezionati** e **Scarica proposte** inviavano solo le caselle della pagina
  visibile al momento del clic: quelle spuntate su altre pagine restavano spuntate ma non
  partivano.

La selezione diventa un elenco ordinato tenuto dal JavaScript (punto 4). Al clic sui pulsanti
il form riceve gli id da quell'elenco come campi nascosti, non dalle caselle presenti nella
pagina. Seleziona tutte usa le righe della DataTable filtrate (`rows({ search: 'applied',
order: 'applied' })`), non il DOM.

## Alternative scartate

- **«Rinnova selezionati» senza form**, che crea tutte le proposte in un colpo: veloce, ma
  nessuno vede i dati prima del salvataggio. Rimandato al flag del rinnovo automatico.
- **Selezione mista, ogni pulsante sulle righe sue**: vedi punto 3.
- **Modalità «Rinnovo in blocco»** da attivare con un pulsante, che fa comparire le caselle
  sulle rinnovabili: ancora più chiara, ma un clic in più ogni volta, e la selezione di un
  tipo solo basta.
- **Coda in sessione**: più corta da scrivere, ma due schede aperte si sovrascrivono la coda
  e va ripulita quando l'operatore abbandona.
- **Non farlo e usare il rinnovo singolo**: 150 form con ritorno all'elenco ogni volta.
  Scartato da Daniela.

## Riepilogo modifiche file per file

1. `app/Views/abbonamenti/index.php`
   - casella di selezione anche sulle righe rinnovabili, con un attributo che dice il tipo
     (proposta o rinnovo);
   - pulsante **Rinnova selezionati (n)** nel `card-tools`;
   - script: selezione come elenco ordinato e di un tipo solo, con le caselle dell'altro
     tipo disattivate; Seleziona tutte sulle righe filtrate, con l'avviso sul caso misto;
     abilitazione dei pulsanti del tipo scelto e conteggio; invio degli id come campi nascosti
     (punto 10), avvio della coda con il primo id e il resto in `coda`.
2. `app/Controllers/AbbonamentiController.php`
   - `rinnova()`: legge e ripulisce `coda`, `fatti`, `saltati` (solo interi); in coda, un
     abbonamento non rinnovabile si salta da solo (punto 8); passa alla view i dati della
     coda;
   - `store()`: se il POST porta la coda, dopo il salvataggio va al successivo, oppure al
     riepilogo se la coda è finita;
   - nuovo `fineRinnovo()`: compone il riepilogo con i nomi dei saltati e i motivi, lo mette
     in flashdata e torna all'elenco.
3. `app/Config/Routes.php`: rotta GET `abbonamenti/rinnovo-fine` nel gruppo abbonamenti.
4. `app/Views/abbonamenti/nuovo.php`: in coda, l'indicazione «Rinnovo n di m», i campi
   nascosti della coda e i tre pulsanti (punto 6) al posto di Salva e Annulla.
5. `app/Views/help/abbonamenti.php`: come si rinnova in blocco.
6. `docs/backlog.md`: tolto il punto del rinnovo multiplo, aggiunto il flag del rinnovo
   automatico.

Nessuna migration.

## Fuori scope

- **Rinnovo automatico senza form**, con un flag sull'abbonamento che dica se si può
  rinnovare senza controllo. Se ne riparla dopo il primo giro di rinnovi, sapendo quanti
  sono stati approvati senza modifiche.
- **Tabella delle condizioni di pagamento**, in `docs/backlog.md`.
- **Proposta Word delle piscine**, in `docs/backlog.md`. Le proposte create dal rinnovo si
  scaricano in blocco come oggi, per i tipi che hanno un modello.

## Da confermare

- Nessun punto aperto al momento della scrittura.
