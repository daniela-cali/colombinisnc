# 4. Anagrafiche

## 4.1 Clienti

`Anagrafiche\ClientiController` · `ClientiModel` · rotte `anagrafiche/clienti/*`

L'anagrafica clienti è il punto di partenza di quasi tutto: interventi, abbonamenti e
cantieri nascono da qui, e la scheda cliente è la pagina più consultata del gestionale.

### Elenco

Una DataTable con ricerca testuale, ordinamento multi-colonna e colonne collassabili su
schermi stretti. L'ordinamento iniziale è alfabetico per denominazione — cosa che richiede
di dichiararlo **due volte**: `orderBy()` nel model e `order: [[1, 'asc']]` in DataTables,
perché altrimenti il valore predefinito della libreria (ordina per la prima colonna)
sovrascrive silenziosamente l'ordine restituito dal server.

Il badge del numero interventi conta solo il lavoro effettivamente aperto:
`abbonamento_id IS NULL` e stato diverso da completato o annullato. Senza il primo filtro,
un abbonamento che genera cinquanta visite in una volta gonfierebbe il contatore fino a
renderlo inutile.

### Scheda cliente

Non è organizzata a tab ma come una **pagina verticale a sezioni**, con una barra di
navigazione laterale che compare sopra i 1200px di larghezza e segue la sezione attiva
tramite `IntersectionObserver`. Le sezioni sono Anagrafica, Posizione, Materiali da
portare, Interventi, Abbonamenti e Cantieri.

Le tre tabelle interne (Interventi, Abbonamenti, Cantieri) condividono lo stesso
meccanismo di filtro: una colonna nascosta con lo stato grezzo, i bottoni a pillola con
`data-filtro`, e la configurazione in JSON nell'attributo `data-pill-filtri` del
contenitore. Il listener sta in `public/js/search-bar.js`, uno solo per tutta
l'applicazione — prima era logica duplicata per ogni tabella.

Gli interventi collegati a un cantiere compaiono **anche** in questa lista, con il badge
del cantiere di provenienza. Erano stati esclusi per non affollare la pagina, ma questo li
faceva sparire non appena uscivano dallo stato "da pianificare", cioè l'unico contato nel
badge della sezione Cantieri: si perdevano di vista del tutto.

### Codice cliente

Assegnato automaticamente da `ClientiModel::generaCodice()` nella forma `INT-001`,
`INT-002`… La numerazione cerca il massimo fra i soli codici che iniziano per `INT-`, così
i codici numerici dei clienti importati dal gestionale contabile convivono senza
interferire. Il significato dei due formati è al capitolo 3.3: **non vanno uniformati**.

### Geocodifica e zona

L'indirizzo viene risolto in coordinate tramite **Nominatim** (OpenStreetMap), chiamato dal
browser da `public/js/geocoding.js`. Il salvataggio delle coordinate scatena, dentro
`ClientiModel::normalizza()`, due calcoli:

1. **`distanza_sede`** — distanza in linea d'aria dalla sede aziendale con la formula
   dell'emisenoverso, ricalcolata a ogni salvataggio ma solo se anche la sede ha
   coordinate.
2. **`zona`** — assegnata confrontando la longitudine del cliente con le due soglie
   configurate in Impostazioni (`Azienda.zona_lng_ovest` e `zona_lng_est`). Sotto la prima
   è Ventimiglia, sopra la seconda è Savona, in mezzo è Ceriale. **L'assegnazione
   automatica avviene solo se la zona non è già impostata**: una scelta manuale
   dell'utente non viene mai sovrascritta.

La zona guida il raggruppamento del pool nel Calendario e del foglio di viaggio. Il
sistema a tre zone fisse è una semplificazione consapevole e già segnalata come da
rivedere: nella pratica le zone si suddividono ulteriormente. L'evoluzione prevista è una
tabella `zone` con `nome`, `lng_min`, `lng_max` e `ordine`, con lo stesso meccanismo di
assegnazione ma un numero di zone libero.

### Posizione sulla mappa

La sezione Posizione mostra una mappa Leaflet con il punto del cliente e un pallino rosso
fisso, non modificabile, per la sede aziendale. Il link "Apri in Google Maps" è costruito
dalle coordinate.

**"Correggi posizione" è sempre disponibile**, non solo quando la geocodifica automatica
fallisce: serve anche a spostare un pin impreciso, per esempio quando Nominatim ha
restituito il centro del paese invece dell'indirizzo esatto. Si clicca sulla mappa o si
trascina il pin, poi si conferma con un POST dedicato
(`ClientiController::aggiornaPosizione()`).

Se il cliente non ha ancora coordinate proprie la mappa si centra comunque su qualcosa di
utile, con tre livelli di ripiego: posizione del cliente, altrimenti la città indicata
risolta al volo via Nominatim (senza salvare nulla), altrimenti la sede aziendale.

Lo script Leaflet vive in `public/js/mappa-posizione.js` con un contenitore generico
`#mappa-posizione` e riceve tutto tramite attributi `data-*`: lo usano identico sia la
scheda cliente sia la scheda cantiere.

Due bug risolti che vale la pena non reintrodurre. Il primo: `L.Icon.Default._getIconUrl()`
antepone sempre un percorso rilevato dal CSS, anche davanti a un URL già assoluto,
producendo un indirizzo doppio e un 404 sulle icone dei marker. Il secondo: lo zoom con la
rotella è disattivato finché non si clicca sulla mappa (un velo con l'invito a cliccare lo
segnala), altrimenti scorrendo la pagina si zoomava la mappa per sbaglio.

### Stampa PDF

Il bottone Stampa PDF produce un **documento operativo essenziale**, non un dump della
scheda: anagrafica, materiali sospesi, interventi da pianificare e pianificati, abbonamento
attivo, cantieri aperti o sospesi. Restano fuori completati, annullati e sospesi, perché il
documento risponde alla domanda *"cosa resta da fare per questo cliente"*, non a *"cosa è
successo"*.

Le visite da abbonamento nel gruppo "da pianificare" sono limitate al mese corrente:
altrimenti un abbonamento settimanale riempirebbe pagine di righe. Le richieste manuali
restano sempre visibili.

Niente mappa nel PDF: renderla staticamente richiederebbe un servizio esterno da contattare
a ogni generazione, mentre `isRemoteEnabled` resta `false` per scelta. Al suo posto c'è il
link testuale a Google Maps, inerte sulla carta ma valido se il PDF si legge da schermo.
Il logo aziendale viene incorporato come data URI in base64, per la stessa ragione.

### Eliminazione

Bloccata dalle chiavi `RESTRICT` finché esistono interventi, cantieri o abbonamenti
collegati. L'utente vede un messaggio che elenca cosa lo blocca e in che quantità, non
l'eccezione del database: il meccanismo è descritto al capitolo 3.9.

L'eliminazione richiede inoltre il permesso `clienti.elimina`, che i tecnici non hanno.

## 4.2 Personale

`Anagrafiche\PersonaleController` · `PersonaleModel` · rotte `anagrafiche/personale/*`

CRUD dei dipendenti, con la scelta del colore che li identifica sul calendario e la
gestione dell'account Shield collegato (email, password, gruppi di appartenenza).

L'intera sezione è protetta dal permesso **`personale.manage`**, assegnato a admin,
developer e ufficio. Prima della v0.24.23 era protetta solo dal menu, che nascondeva la
voce ai tecnici: un tecnico che digitava l'URL entrava comunque, e da lì poteva assegnarsi
il gruppo `admin`. Il capitolo 8.4 racconta il problema per esteso.

**Dentro la sezione i diritti non sono però tutti uguali** (v0.28.0). Il blocco *Account di
accesso* — email, ruoli, password — richiede `personale.account`, che hanno solo admin e
developer: chi non ce l'ha modifica l'anagrafica e le assenze, e non vede nemmeno le caselle
dei gruppi. Anche **creare** un dipendente richiede quel permesso, perché creare una persona
significa creare le sue credenziali. Il controller non si fida della view: senza il permesso
non passa proprio dal model, quindi inviare i campi a mano non serve a niente.

L'**eliminazione** richiede `personale.elimina` ed è consentita solo sulle schede senza nessun
intervento e nessuna assenza, cioè in pratica per rimediare a un inserimento sbagliato:
`interventi.tecnico_id` ha `ON DELETE SET NULL` e `assenze.personale_id` `ON DELETE CASCADE`,
quindi eliminare chi ha lavorato azzererebbe il tecnico su tutto il suo storico e ne
cancellerebbe il diario delle assenze, dopo una sola conferma del browser. Per escludere dal
gestionale chi ha lavorato si **sospende l'account** da Impostazioni → Utenti (capitolo 7.4).

### Il mio profilo

`ProfiloController` · rotte `profilo/*`

Pagina separata, accessibile a chiunque sia autenticato. Mostra i propri dati anagrafici e
le proprie credenziali, **senza la sezione gruppi** — rimossa dalla view, non nascosta —
e senza il bottone di eliminazione. Dalla v0.28.0 la garanzia non è più solo nella view: il
controller passa al model un elenco di gruppi assegnabili **vuoto**, e il model tocca solo i
gruppi compresi in quell'elenco, quindi i ruoli restano intatti anche se la richiesta prova a
includerli.

La scheda dipendente è inoltre **facoltativa**: un account che non ne ha — oggi nessuno, domani
quelli del portale clienti — vede e salva comunque email e password, saltando il blocco
anagrafico. È ciò che rende il Profilo la sede naturale del self-service dei clienti senza
doverne costruire una separata.

Il punto chiave è che `ProfiloController::update()` non riceve né si fida di alcun `id`:
risolve sempre il proprio dipendente lato server con `PersonaleModel::perUtente(user_id())`.
Non c'è un identificativo da manomettere nell'URL o nel form, quindi la vulnerabilità è
chiusa per costruzione e non da un controllo che si potrebbe dimenticare.

## 4.3 Assenze

Ferie, malattie e permessi si registrano dalla scheda del dipendente, in una sezione
dedicata riservata a ufficio, admin e developer. Compaiono sul calendario come eventi
arancioni nella fascia "tutto il giorno", dove però non sono modificabili: si gestiscono
solo dalla scheda personale.

Due sovrapposizioni fra assenze dello stesso dipendente **non bloccano** il salvataggio ma
generano un avviso: il caso reale è la malattia che cade durante le ferie.

### Il conflitto fra assenze e interventi

Assegnare un intervento a un tecnico assente in quella data è invece **bloccato davvero
lato server**, non solo segnalato: vale in creazione, in modifica e nel trascinamento sul
calendario. `InterventiController::erroreAssenzaTecnico()` centralizza il controllo e viene
riusato da `store()`, `update()`, `pianifica()` e `CalendarioController::sposta()`. Nei
form un avviso dal vivo disabilita il salvataggio prima ancora del tentativo.

In modifica il blocco scatta solo se tecnico o data vengono **attivamente cambiati**: un
conflitto già presente all'apertura del form — nato da un'assenza inserita dopo — non deve
impedire di correggere altri campi.

Resta il caso complementare, che il blocco per costruzione non copre: **l'assenza inserita
dopo la pianificazione**, cioè la malattia improvvisa di un tecnico che aveva già interventi
assegnati. Due punti di visibilità, stesso meccanismo sotto:

1. chi inserisce l'assenza riceve subito l'elenco degli interventi che quella scelta mette
   in conflitto;
2. la dashboard di admin e ufficio ha una card **"Interventi in conflitto"** che mostra lo
   stato corrente di *tutti* i conflitti, non solo quello appena creato — così se ne
   accorge anche chi entra il giorno dopo. Il link di ogni riga porta direttamente al form
   di modifica, non alla scheda: l'azione richiesta è riassegnare, e ogni clic intermedio
   sarebbe sprecato.

**Nessuna tabella per i conflitti.** Nascono e spariscono da soli in base allo stato
corrente di `interventi` e `assenze`: si risolvono riassegnando il tecnico, spostando la
data o cancellando l'assenza. Un flag salvato andrebbe tenuto sincronizzato a mano a ogni
cambiamento, con il rischio di disallinearsi; `InterventiModel::inConflitto()` è
una query che è sempre vera adesso.

Dalla v0.28.0 quella card ospita un **secondo motivo** oltre all'assenza: il tecnico assegnato
il cui **account è stato sospeso**, che non entrerà più nel gestionale e non compare più fra gli
assegnabili. È lo stesso problema — un fatto registrato dopo la pianificazione la invalida — e
quindi lo stesso posto. Un'etichetta colorata distingue i due casi, e un intervento che ricade
in entrambi compare una volta sola come sospensione: l'assenza finisce a fine ferie, la
sospensione no. `inConflitto()` unisce le due query e deduplica; il motivo è aggiunto in PHP e
non selezionato come letterale SQL, dove il Query Builder lo tratterebbe da nome di colonna.

## 4.4 Import dei clienti storici

`Impostazioni\ImportClientiController` · `ClientiAdhocModel` · `ClientiAdhocImporter`
Rotte `impostazioni/import-clienti/*` · spec `import_clienti_legacy_spec.md`

### Il problema

L'anagrafica storica vive nel gestionale contabile **Ad Hoc (Zucchetti)** e conta circa
**2590 record**. Il problema non è tecnico ma di usabilità: riversarli in `clienti`
renderebbe inutilizzabile ogni tendina, ogni ricerca e ogni elenco, quando i clienti
realmente attivi sono qualche centinaio. La maggior parte di quei record è storia — clienti
di vent'anni fa che non chiameranno più.

Due alternative valutate e scartate. L'**import diretto** in `clienti` (l'approccio del
vecchio progetto) paga un costo permanente su ogni schermata per un beneficio che si
realizza poche centinaia di volte. L'import **con `attivo = 0`** evita tabelle nuove ma
sporca comunque la tabella di produzione con record mai verificati, e rende ambiguo il
campo `attivo`, che oggi significa "cliente sospeso" e diventerebbe anche "mai
controllato".

### La soluzione: parcheggio e promozione

Il CSV atterra in `clienti_adhoc`, che non partecipa a nessun flusso operativo. Da lì
l'operatore **promuove un cliente alla volta** — quando quel cliente si rifà vivo davvero —
passando dal normale form di inserimento, precompilato e interamente modificabile.

```
CSV Ad Hoc → mappatura colonne → clienti_adhoc → [promozione] → clienti
```

**Caricamento.** Si carica il CSV, il sistema ne legge le intestazioni e propone la
mappatura verso i campi di destinazione, preselezionata dalla mappatura salvata l'ultima
volta (in `settings`, chiave `Import.clienti_mapping`) o dalla coincidenza dei nomi. Alla
conferma i record entrano in parcheggio e viene mostrato il riepilogo di elaborati,
inseriti e saltati. Le righe senza `codice` vengono saltate e contate; in caso di codice
ripetuto nello stesso file vince l'ultima riga.

**Il ri-caricamento è previsto, non è un incidente.** L'export nasce da una query scritta a
mano che verrà affinata più volte: `codice` ha un vincolo `UNIQUE` e la scrittura usa
`upsertBatch()` con `onConstraint`, quindi ricaricare lo stesso file aggiorna i dati
anagrafici invece di duplicarli. Le colonne di stato della migrazione non fanno parte del
payload, quindi un cliente già promosso resta promosso.

**Promozione.** Dall'elenco "Clienti da migrare" — una DataTable con ricerca su tutte le
colonne, filtrata di default sui non ancora importati — il bottone "Crea cliente" apre il
form precompilato con un banner che segnala la provenienza. Al salvataggio nasce il
cliente, il record in parcheggio viene marcato, e i contatori di avanzamento si aggiornano.

Due dettagli che rendono la promozione uno-alla-volta un vantaggio e non un ripiego: la
geocodifica funziona da sola, perché è già attiva su quel form (un import massivo avrebbe
lasciato 2600 clienti senza coordinate, richiedendo una geocodifica batch che così non
serve); e le sigle nazione di Ad Hoc (`IT`, `FR`) vengono tradotte in `ITALIA` e `FRANCIA`
**in questo momento**, non durante l'import, così il parcheggio resta fedele all'export ma
la tendina arriva già sulla voce giusta.

### Nessuna pulizia automatica del dato

Il servizio del vecchio progetto applicava euristiche di normalizzazione in fase di import.
Sui dati reali producevano sporcizia, misurata interrogando il vecchio database:

| Sintomo | Esempio reale | Causa |
|---|---|---|
| Codice fiscale finito nel nome | `REMBADO` / `ADRIANO RMBDRN63C10G605Z` | divisione della ragione sociale sul primo spazio |
| Doppio cognome spezzato male | `MASSAI` / `MINUTOLO FRANCESCA` | idem: il cognome vero è "MASSAI MINUTOLO" |
| Persona fisica marcata società | `PESCE LUCIANO` come `societa` | ripiego automatico quando il flag di origine è vuoto |

La decisione è quindi di **non applicare alcuna euristica**: `clienti_adhoc` conserva il
dato grezzo. Il motivo è che il vincolo di partenza è cambiato — l'export non è più
prodotto dalla funzione di esportazione di Ad Hoc, ma da una query mirata scritta a mano
sul database. La pulizia si fa alla sorgente, dove c'è il contesto per farla bene;
duplicarla in PHP a valle reintrodurrebbe gli stessi errori. Il secondo presidio è la
revisione umana a ogni promozione.

I duplicati sono stati misurati e si sono rivelati un non problema: sei gruppi di ragione
sociale ripetuta e cinque di cognome più nome su 2590 record, sotto l'1%. L'elenco li
mostra tutti e l'operatore sceglie a vista, senza bisogno di un'interfaccia di
disambiguazione.

### Ciclo di vita

`clienti_adhoc` è un **archivio permanente**, non viene eliminata a migrazione conclusa.
Costa pochissimo e conserva due informazioni altrimenti perdute: quali clienti storici
esistevano e quali di quelli sono stati effettivamente ripresi, con la data.

L'import resta una **migrazione una tantum, non un ponte permanente** fra i due gestionali:
ri-caricare l'export aggiorna il parcheggio ma non propaga nulla verso i clienti già
promossi. Una volta che un cliente è in `clienti`, la sua fonte di verità è questo
gestionale.
