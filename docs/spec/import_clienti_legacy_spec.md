# Spec — Import Clienti Legacy (`clienti_adhoc`)

**Versione:** 1.0 — approvata, pronta per l'implementazione
**Data:** 16/08/2026
**Modulo di riferimento:** Impostazioni → Import Clienti (promozione verso Anagrafica Clienti)

---

## 1. Contesto e obiettivo

L'anagrafica clienti storica dell'azienda vive nel gestionale **Ad Hoc (Zucchetti)**, su MSSQL. Nel
vecchio progetto ne erano stati importati **2590 record**.

Il problema non è tecnico ma di usabilità: riversare 2590 clienti dentro `clienti` renderebbe
inutilizzabile ogni tendina, ogni ricerca e ogni elenco del gestionale, quando i clienti realmente
attivi sono presumibilmente qualche centinaio. La maggior parte di quei record è storia: clienti di
vent'anni fa che non chiameranno mai più.

**Obiettivo**: migrare progressivamente i clienti legacy senza inserimento manuale massivo e senza
inquinare l'anagrafica di produzione.

La soluzione è una **tabella di parcheggio** `clienti_adhoc`: il CSV atterra lì, la tabella non
partecipa a nessun flusso operativo del gestionale, e da lì l'operatore **promuove un cliente alla
volta** — quando quel cliente si rifà vivo davvero — passando dal normale form "Nuovo Cliente", dove
rivede e corregge i dati prima che entrino in produzione.

Segue il principio già adottato nel progetto: **replicare prima di automatizzare**. L'operatore
mantiene il controllo pieno sul dato che entra in anagrafica.

### Perché non l'import diretto

Era l'approccio del vecchio progetto (upsert diretto in `clienti` sul campo `codice`) ed era stato
previsto anche dallo schema di questo: il commento nella migration di `clienti` dice *"codice
univoco: dal software contabile per i clienti importati, INT-xxx per i nuovi"*. È stato scartato per
il volume — 2590 record in anagrafica sono un costo permanente su ogni schermata, pagato per un
beneficio (avere il cliente già pronto) che si realizza poche centinaia di volte.

Una via di mezzo valutata e scartata: importare tutto in `clienti` con `attivo = 0`. Ottiene un
effetto simile senza tabelle nuove, ma sporca comunque la tabella di produzione con record mai
verificati da nessuno, e rende `attivo` ambiguo (oggi significa "cliente sospeso", diventerebbe anche
"cliente mai controllato").

### Cosa si riusa dal vecchio progetto

`D:\Programmazione\Progetti\colombini\app\Services\ClientiImportService.php` e le view
`app/Views/clienti/import*.php` contengono un wizard CSV in 3 passi già collaudato. Si riportano:

- rilevamento automatico del separatore (`;` vs `,`) e conversione encoding ISO-8859-1 → UTF-8;
- il passo di **mappatura colonne**, con pre-selezione automatica quando il nome della colonna CSV
  coincide con il campo di destinazione, e il JS che rimuove dalle tendine i campi già assegnati
  (impedisce di mappare due colonne sullo stesso campo);
- il salvataggio del mapping in `setting('Import.clienti_mapping')`, per ri-proporlo agli import
  successivi.

Le view vanno riscritte per **AdminLTE 4 / Bootstrap 5**: le originali usano `float-left`,
`custom-file`, `form-group`, `description-block` e Font Awesome, tutta roba di Bootstrap 4.

---

## 2. Qualità dei dati legacy — decisione sulla normalizzazione

Il vecchio servizio applicava euristiche di pulizia in fase di import. Sui dati reali hanno prodotto
sporcizia, verificata interrogando il DB del vecchio progetto:

| Sintomo | Esempio reale | Causa |
|---|---|---|
| Codice fiscale dentro il nome | `REMBADO` \| `ADRIANO RMBDRN63C10G605Z` | split di `ragsoc` sul primo spazio |
| Doppio cognome spezzato male | `MASSAI` \| `MINUTOLO FRANCESCA` | idem — il cognome vero è "MASSAI MINUTOLO" |
| Nome straniero diviso a caso | `BENNET` \| `CYNTHIA KAI LARSEN` | idem |
| Persona fisica marcata `societa` | `PESCE LUCIANO` come `societa` | `normalizeTipo()` fa fallback su `societa` quando `ANPERFIS` è vuoto |
| Nazione non normalizzata | `IT` invece di `ITALIA` | nessuna conversione (qui `NAZIONI_PREDEFINITE` vale `['ITALIA', 'FRANCIA']`) |

**Decisione**: nessuna euristica di pulizia in fase di import. `clienti_adhoc` conserva il dato
grezzo così come arriva dal CSV.

Il motivo è che il vincolo di partenza è cambiato: l'export non viene più prodotto dalla funzione di
esportazione del gestionale Ad Hoc (che sputava un CSV di pessima qualità) ma da una **query mirata
scritta a mano sul database**. La pulizia — split cognome/nome, tipo, `IT` → `ITALIA`, filtro sui
clienti realmente interessanti — si fa quindi alla sorgente, dove c'è il contesto per farla bene.
Duplicarla in PHP a valle significherebbe solo reintrodurre gli stessi errori.

Il secondo presidio è la promozione uno-alla-volta: qualunque residuo di sporcizia passa comunque
sotto gli occhi dell'operatore nel form, prima di entrare in `clienti`.

---

## 3. Struttura tabella `clienti_adhoc`

Tabella di appoggio. Non è referenziata da nessuna altra tabella del gestionale; l'unico legame è la
FK verso `clienti` valorizzata dopo la promozione.

**Campi anagrafici** — sottoinsieme importabile di `clienti`, tutti `VARCHAR` nullable. Nessun
vincolo di integrità: è dato grezzo, la validazione avviene alla promozione.

`codice`, `tipo`, `ragsoc`, `cognome`, `nome`, `piva`, `cfisc`, `indirizzo`, `citta`, `cap`,
`provincia`, `nazione`, `telefono`, `email`, `note`

**Campi di stato migrazione:**

| Campo | Tipo | Note |
|---|---|---|
| `importato` | `TINYINT` default `0` | flag booleano, da convenzione CLAUDE.md (niente `ENUM`) |
| `cliente_id` | `INT UNSIGNED NULL`, FK → `clienti.id` (`ON DELETE SET NULL`) | valorizzato alla promozione |
| `imported_at` | `DATETIME NULL` | valorizzato alla promozione |

Più i campi standard di ogni tabella: `created_by`, `updated_by`, `created_at`, `updated_at`.

Indici su `ragsoc` (campo di ricerca principale) e su `importato` (filtro presente in quasi ogni
query).

> Scostamento dalla bozza 0.1: il campo `creato_il` è sostituito dallo standard `created_at`, per non
> avere due campi con lo stesso significato; `data_importazione` è rinominato `imported_at`, per
> seguire la convenzione dei timestamp già in uso nel progetto (`created_at`, `updated_at`,
> `geocoded_at`) — i flag di dominio restano invece in italiano, come `attivo` e `importato`.

**Perché sia `importato` sia `cliente_id`**: nel caso normale il flag è deducibile
(`cliente_id IS NOT NULL`), ma la FK è `ON DELETE SET NULL`. Se il cliente promosso viene cancellato
dall'anagrafica, `cliente_id` si azzera: senza il flag quella riga tornerebbe nell'elenco "da
migrare" come se non fosse mai stata valutata. Con il flag resta leggibile come *"migrato, cliente
poi eliminato"*, che è un'informazione diversa da *"mai toccato"*.

---

## 4. Mapping campi

Il mapping dell'export Ad Hoc, recuperato dalla tabella `settings` del vecchio progetto
(`class = 'Import'`, `key = 'clienti_mapping'`). I nomi di colonna sono quelli dell'anagrafica conti
di Ad Hoc:

| Colonna Ad Hoc | Campo destinazione |
|---|---|
| `ANCODICE` | `codice` |
| `ANDESCRI` | `ragsoc` |
| `ANPERFIS` | `tipo` |
| `ANCODFIS` | `cfisc` |
| `ANPARIVA` | `piva` |
| `ANINDIRI` | `indirizzo` |
| `ANLOCALI` | `citta` |
| `AN___CAP` | `cap` |
| `ANPROVIN` | `provincia` |
| `ANNAZION` | `nazione` |
| `ANTELEFO` | `telefono` |

Undici colonne: l'export originale non portava né email né note (nell'anagrafica legacy il campo
email era vuoto su tutti e 2590 i record).

Questo mapping è un **punto di partenza, non un vincolo**: il nuovo export sarà prodotto con una
query mirata, quindi i nomi di colonna possono essere scelti liberamente. Se coincidono con i nomi
dei campi di destinazione, il passo di mappatura si pre-compila da solo e diventa una semplice
conferma.

---

## 5. Flusso — Fase 1

### 5.1 Caricamento

1. Impostazioni → **Import Clienti**. La landing mostra i contatori di avanzamento (totale in
   parcheggio / già promossi / ancora da migrare) e il form di upload.
2. Upload del CSV → il sistema legge gli header e passa alla **mappatura colonne**, pre-selezionata
   dal mapping salvato o dalla coincidenza dei nomi.
3. Conferma → i record entrano in `clienti_adhoc`. Il mapping viene salvato per il giro successivo.
4. Schermata di **risultato**: elaborati / inseriti / saltati.

Le righe senza `codice` vengono saltate e conteggiate. In caso di `codice` ripetuto nello stesso CSV
vince l'ultima riga (deduplica in memoria prima della scrittura).

**Il ri-caricamento è previsto e sicuro.** `codice` ha un vincolo `UNIQUE` e la scrittura usa
`upsertBatch()`: ricaricare lo stesso export aggiorna le righe di parcheggio invece di duplicarle.
È il caso normale, non l'eccezione — l'export nasce da una query scritta a mano, che verrà affinata
più volte. Le colonne di stato (`importato`, `cliente_id`, `imported_at`) non fanno parte del payload
di import, quindi un cliente già promosso resta promosso anche dopo un ricaricamento.

### 5.2 Promozione a cliente

1. Dall'elenco dei record parcheggiati (DataTable con ricerca full-text, filtrato di default sui non
   ancora importati) l'operatore trova il cliente e preme **Crea cliente**.
2. Si apre il normale form "Nuovo Cliente" **precompilato** con i dati del record parcheggiato, con
   un banner che segnala la provenienza. Tutti i campi restano modificabili.
3. Le sigle nazione di Ad Hoc (`IT`, `FR`) vengono tradotte nei valori del gestionale
   (`ITALIA`, `FRANCIA`, vedi `ClientiAdhocModel::NAZIONI_ADHOC`) **in questo momento**, non
   durante l'import: il parcheggio resta fedele all'export, e la tendina Nazione del form
   seleziona comunque la voce giusta invece di ricadere su "Altra…".
4. La geocodifica non richiede nulla di nuovo: `geocoding.js` è già attivo sul form e risolve
   l'indirizzo precompilato. È un vantaggio concreto della promozione uno-alla-volta — un import
   massivo avrebbe lasciato 2600 clienti senza coordinate, richiedendo una geocodifica batch che qui
   non esiste.
5. Al salvataggio: nasce il cliente in `clienti` (con `dt_import` valorizzato) e il record in
   `clienti_adhoc` viene marcato `importato = 1`, `cliente_id`, `imported_at`.
6. Il record sparisce dall'elenco e i contatori si aggiornano.

### 5.3 Il codice Ad Hoc va conservato

Decisione centrale, da non reinterpretare in futuro: **`clienti.codice` porta un'informazione di
dominio, non è un semplice identificativo.**

- codice **numerico** (`2121`, `945`) = cliente presente nel gestionale contabile Ad Hoc. È
  l'`ANCODICE` originale, conservato tale e quale alla promozione.
- codice **`INT-xxx`** = cliente interno, creato solo dentro questo gestionale e non presente in
  contabilità.

Il prefisso `INT-` è quindi la marca dei clienti **interni, non presenti in contabilità** — una
distinzione che serve poter isolare con una query. Dare `INT-xxx` anche ai clienti promossi
cancellerebbe il segnale: `ClientiController::store()` usa `generaCodice()` solo quando non sta
promuovendo.

È sicuro: `generaCodice()` cerca il massimo con `LIKE 'INT-%'`, quindi i codici numerici legacy
convivono senza alterare la numerazione progressiva. Il criterio per una futura query "clienti
interni" è `codice LIKE 'INT-%'`.

**Nota su `codice_esterno`.** La colonna esiste (aggiunta il giorno dopo `clienti`) ed è etichettata
nel form come *"Codice nel software di contabilità esterno"*: era la rete di sicurezza per lo
scenario in cui l'import non si fosse mai fatto, cioè poter almeno annotare a mano il riferimento
contabile. Non è il posto del codice Ad Hoc e non viene valorizzata dalla promozione: resta a
disposizione dell'operatore. Il commento nella migration di `clienti` — *"codice univoco: dal
software contabile per i clienti importati, INT-xxx per i nuovi"* — è corretto e attuale.

Unico limite noto: `clienti.codice` è `VARCHAR(15)` mentre il parcheggio ammette 30 caratteri. Un
codice Ad Hoc più lungo di 15 farebbe fallire il salvataggio della promozione. Sui dati reali i
codici sono numerici di 3-4 cifre, quindi il caso è teorico; se dovesse presentarsi, la scelta è
allargare `clienti.codice`, non troncare.

---

## 6. Ciclo di vita della tabella

**Archivio permanente.** Non viene eliminata al termine della migrazione.

Costa pochissimo (una tabella di poche migliaia di righe, fuori da ogni percorso operativo) e
conserva due informazioni che altrimenti si perderebbero: quali clienti storici esistevano e quali
di quelli sono stati effettivamente ripresi, con la data.

---

## 7. Sicurezza

Tutte le rotte stanno dentro il gruppo `impostazioni`, già protetto dal filtro
`permission:impostazioni.manage` — quindi accessibili solo ad admin e developer, mai ai tecnici.

`clienti_adhoc` contiene dati anagrafici e fiscali di clienti reali: valgono le stesse cautele delle
altre tabelle con dati personali. È già prevista una revisione della sicurezza del VPS in una
sessione dedicata con il sistemista.

---

## 8. Punti aperti della bozza 0.1 — chiusi

- **Durata della fase di migrazione** — irrilevante ai fini della progettazione, una volta deciso che
  la tabella è un archivio permanente (§6). Non serve stimarla.
- **Gestione dei duplicati / corrispondenze multiple** — misurati sui dati reali: **6 gruppi di
  ragione sociale duplicata (12 record) e 5 gruppi cognome+nome (11 record) su 2590**, sotto l'1%.
  Non serve nessuna interfaccia di disambiguazione: l'elenco li mostra tutti e l'operatore sceglie a
  vista.
- **Normalizzazione stringa per il matching** — non serve lato applicativo. La ricerca full-text di
  DataTables lavora già su tutte le colonne, e la pulizia si fa alla sorgente nella query di export
  (§2).

---

## 9. Fuori scope

- **Autocomplete nel form "Nuovo Cliente"** (§3 della bozza 0.1) — cercare il record parcheggiato
  mentre si digita la ragione sociale, invece di partire dall'elenco. È la **fase 2**: riusa la
  stessa rotta di precompilazione della fase 1 e si valuta solo dopo aver usato sul campo l'elenco,
  che è più semplice, non tocca il percorso caldo di creazione cliente e serve comunque per vedere
  l'avanzamento della migrazione.
- **DataTables server-side** — l'elenco è client-side come tutte le altre liste del progetto. Con
  ~2600 righe è pesante ma gestibile, e si assottiglia man mano che la migrazione avanza. Se
  diventasse un problema si valuta allora: oggi nel progetto non esiste alcuna infrastruttura
  server-side, introdurla per questa schermata sarebbe sproporzionato.
- **Geocodifica batch** — non serve (§5.2), la geocodifica avviene nel form a ogni promozione.
- **Import di altre entità** (articoli di magazzino, interventi storici) — non previsto. Il
  controller è volutamente specifico dei clienti; se un giorno servisse altro si valuterà come
  generalizzare.
- **Sincronizzazione ricorrente con Ad Hoc** — l'import resta una migrazione una tantum, non un
  ponte permanente fra i due gestionali. Ri-caricare l'export **aggiorna** il parcheggio
  (`upsertBatch` su `codice`, vedi §5.1) ma non propaga nulla verso i clienti già promossi: una volta
  che un cliente è in `clienti`, la sua fonte di verità è il gestionale, non più Ad Hoc.
