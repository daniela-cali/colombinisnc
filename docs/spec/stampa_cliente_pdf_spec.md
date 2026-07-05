# Spec — Stampa PDF scheda cliente

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale del progetto (Colombini SNC Gestionale). Questo documento copre solo la funzionalità descritta qui sotto.

## 1. Contesto e stato di partenza

"Stampe di tutte le schede" (interventi, clienti, abbonamenti, cantieri) è in backlog dalle note di roadmap. Si parte dalla scheda **Cliente** come pilota: se il pattern funziona bene, si estende alle altre schede in iterazioni successive (fuori scope qui).

`dompdf` è già una dipendenza del progetto (`composer.json`, `^3.1`) ed è già usato in `ViaggioController::pdf()` per il foglio di viaggio giornaliero — quel metodo definisce il pattern da riusare:

- Un metodo dedicato `pdf()` nel controller (accanto a `show()`), niente logica di generazione nel model.
- Una view HTML/CSS separata (non quella usata a video), con stili inline nel file stesso (dompdf ha supporto CSS limitato — niente flexbox/grid, solo tabelle e stili di base).
- `Dompdf\Options` con `defaultFont = Helvetica` e `isRemoteEnabled = false` (nessuna chiamata di rete durante la generazione).
- `$dompdf->stream($nomefile, ['Attachment' => false])` — il PDF si apre nel browser, non forza il download.

## 2. Obiettivo

Il PDF cliente **non è un dump 1:1** della scheda a video (`show.php`). È pensato come **documento operativo essenziale** — cosa serve sapere e cosa resta da fare per questo cliente — per essere stampato su carta o consultato/allegato da PC. Decisione presa dopo uno scarabocchio di mockup dell'utente (vedi sezione 3): niente storico completo, niente mappa, solo le informazioni che servono per intervenire.

## 3. Contenuto e decisioni chiave

Ordine dall'alto in basso, per sezioni separate da un filetto orizzontale (come nello scarabocchio):

### 3.1 Anagrafica
Riquadro in alto: denominazione (ragione sociale o nome+cognome), indirizzo (via, cap, città, provincia), zona, telefono/email/P.IVA/codice fiscale **solo se presenti** (stesso criterio già usato in `show.php`), note cliente se presenti.

- **Niente mappa**: la mappa Leaflet a video è interattiva (tile scaricate dal browser al volo) e non si presta a una resa statica via dompdf senza introdurre una dipendenza da un servizio di static-map esterno raggiungibile ad ogni generazione — scartato esplicitamente dopo brainstorming.
- Al posto della mappa: un **link testuale "Apri in Google Maps"** accanto all'indirizzo, visibile solo se `lat`/`lng` sono presenti (stesso URL già costruito in `show.php`: `https://www.google.com/maps/dir/?api=1&destination=lat,lng`). Utile quando il PDF è aperto da PC/tablet, inerte se stampato su carta — nessun problema, resta comunque un link valido nel testo.

### 3.2 Materiali da portare
Elenco dei materiali sospesi del cliente — stesso dataset di `InterventiMaterialiModel::sospesiPerCliente()`, già caricato in `show()`. Sezione omessa se vuota.

### 3.3 Interventi — due gruppi, niente storico concluso
A differenza della scheda a video (che mostra tutto lo storico in una DataTable filtrabile), nel PDF la sezione Interventi è divisa in **due gruppi**, separati da un filetto interno:

1. **Da pianificare** (`stato = InterventiModel::STATO_DA_PIANIFICARE`) — in cima, sono l'informazione più urgente. Le visite ricorrenti generate da un abbonamento (`priorita = InterventiModel::PRIORITA_ABBONAMENTO`) sono ulteriormente limitate al **mese corrente** (confronto su `data_scadenza`) — altrimenti un abbonamento settimanale riempirebbe il PDF di righe per mesi. Le richieste manuali (`priorita` normale/urgente) restano sempre visibili, indipendentemente dalla data.
2. **Pianificati** (`stato IN (STATO_PIANIFICATO, STATO_IN_CORSO)`) — tutto ciò che ha già una data fissata o è in corso di lavorazione.

Restano **esclusi** completati, annullati e sospesi (per abbonamento in pausa): decisione esplicita per tenere il documento breve e operativo ("cosa resta da fare"), non un archivio storico. Ciascun gruppo omesso se vuoto; l'intera sezione Interventi omessa solo se entrambi i gruppi sono vuoti.

### 3.4 Abbonamento dell'anno in corso
**Solo se esiste** un abbonamento con `stato_calcolato = AbbonamentiModel::STATO_ATTIVO` (coerente con "solo attivi" deciso per i cantieri, vedi sotto) — sezione omessa interamente se non c'è nessun abbonamento attivo, non un "nessun abbonamento" esplicito.

Contenuto per ciascun abbonamento: **tipo**, poi a seconda del numero di periodi (`AbbonamentiPeriodiModel::perAbbonamento($abbonamentoId)`, esiste già):
- **Un solo periodo** (caso comune): riga "Frequenza" (`AbbonamentiModel::FREQUENZE_LABEL`) + riga "Periodo" (`data_inizio` – `data_fine` dell'abbonamento, formato `d/m/Y`) — a differenza di `show.php` che in questo caso mostrava solo la frequenza, senza le date.
- **Periodi multipli**: invece del generico "Multipla" di `show.php`, si elenca **ogni periodo** con il proprio intervallo di date e la propria frequenza (`abbonamenti_periodi.frequenza`), più un'annotazione "(+ pulizia fondo)" se `con_pulizia_fondo` è impostato sul periodo — più informativo per un documento operativo, dove sapere quale frequenza vale in quale intervallo è utile.

Nota: in teoria potrebbero esserci più abbonamenti attivi contemporaneamente (es. tipi di intervento diversi) — in quel caso si elencano tutti quelli attivi, non solo il primo.

### 3.5 Cantieri — solo se presenti
**Solo** cantieri con `stato` in `[CantieriModel::STATO_APERTO, CantieriModel::STATO_SOSPESO]` (esclusi i chiusi) — sezione omessa interamente se non ce ne sono. Per ciascun cantiere, **due riquadri affiancati**:

- **Sinistra — ultime 3 note** (testo + data ciascuna) — da `CantieriNoteModel::perCantiere($cantiereId)` (esiste già, ordina per `data_nota DESC`), troncato a 3 con `array_slice()` nel controller.
- **Destra — ultimi 3 interventi** (data + descrizione/tipo breve ciascuno) — da `InterventiModel::perCantiere($cantiereId)` (esiste già, ordina per `data_pianificata DESC`), troncato a 3 allo stesso modo.

Nessun nuovo metodo nel model per nessuno dei due elenchi. Il layout a due colonne si ottiene con una tabella HTML a due celle (`<table><tr><td>...</td><td>...</td></tr></table>`) — dompdf non supporta flexbox/grid ma le tabelle sì, è già il pattern usato in `operativo/viaggio/pdf.php`.

## 4. Stile grafico e header di pagina

Non si riusa lo stile bianco/nero di `operativo/viaggio/pdf.php` (bordi, zero colore) — si riprende invece la palette del **vecchio progetto** (`D:\Programmazione\Progetti\colombini\app\Views\{viaggi\pdf_viaggio.php, viaggi\pdf_giornata.php, interventi\pdf_rapportino.php}`), più curata e già rifinita su più iterazioni:

- Accento blu `#2980b9` per titoli/bordi, testo `#1f2937`, etichette grigie `#6b7280`, separatori `#e5e7eb`/`#f3f4f6`.
- Intestazione a due colonne: logo (o nome azienda in testo) + indirizzo a sinistra; titolo documento (bold, blu, allineato a destra) + sottotitolo a destra.
- Sezioni con header maiuscoletto grigio e riga sotto (classe `h2` del vecchio progetto) — corrisponde esattamente al "filetto orizzontale" disegnato nello scarabocchio.
- Tabelle etichetta/valore (classe `dettagli`) per l'anagrafica, badge colorati con classi tipo `s-<stato>`/`<priorita>` per stato interventi.

**Dati azienda per l'intestazione**: si usano i campi `Azienda.sede_*` (`sede_nome`, `sede_indirizzo`, `sede_cap`, `sede_citta`, `sede_telefono`, `sede_logo_path`), letti dalla pagina "Parametri Generali" (`impostazioni/parametri`) — è l'unico gruppo di impostazioni azienda realmente compilato nel progetto. Il logo va incorporato come **data URI base64** (letto da `FCPATH . setting('Azienda.sede_logo_path')`), non referenziato come URL — necessario perché `Options::isRemoteEnabled` resta `false` (stesso pattern già usato per il logo nel vecchio progetto, vedi `Interventi.php` riga ~677-682 del vecchio repo).

**Nota emersa durante questa sessione**: esisteva un secondo gruppo di impostazioni azienda (`Azienda.ragione_sociale`/`partita_iva`/`codice_fiscale`/`logo_path`, metodo `GeneraleController::salva()`) risultato **codice morto** — nessuna view lo referenziava. Rimosso in questa stessa sessione (vedi `CHANGELOG.md`), non più disponibile.

## 5. Riepilogo modifiche da implementare

1. **`app/Config/Routes.php`**: nuova rotta GET nel gruppo `clienti` già esistente:
   ```php
   $routes->get('(:num)/pdf', 'Anagrafiche\ClientiController::pdf/$1');
   ```
2. **`app/Controllers/Anagrafiche/ClientiController.php`**: nuovo metodo `pdf(int $id)`, stesso schema di `ViaggioController::pdf()` — carica il cliente (redirect con errore se non trovato, come gli altri metodi del controller), filtra interventi (due gruppi), abbonamenti e cantieri secondo le regole di §3, per ogni cantiere carica e tronca a 3 sia le note (`CantieriNoteModel::perCantiere()`) sia gli interventi (`InterventiModel::perCantiere()`), costruisce `Dompdf` con le stesse `Options` di `ViaggioController`, `setPaper('A4', 'portrait')` (documento verticale, a differenza del foglio viaggio che è landscape), stream inline.
3. **Nuova view `app/Views/anagrafiche/clienti/pdf_scheda_cliente.php`**: HTML/CSS autonomo (no `extend layouts/admin`), palette e classi CSS riprese dal vecchio progetto (§4: accento blu, header `h2` con riga sotto, tabelle `dettagli`, badge), sezioni nell'ordine di §3; riquadri note/interventi cantiere affiancati via tabella a due colonne.
4. **`app/Views/anagrafiche/clienti/show.php`**: nuovo bottone "Stampa PDF" nell'header della scheda (accanto a "Modifica"), link diretto a `anagrafiche/clienti/{id}/pdf` (si apre in una nuova tab/finestra, essendo GET e senza JS coinvolto).
5. Nessuna modifica ai model: tutti i metodi necessari (`perCliente`, `perCantiere` di `InterventiModel`, `perCantiere` di `CantieriNoteModel`, `sospesiPerCliente`) esistono già; il filtro per stato e il troncamento a 3 elementi si fanno in PHP nel controller sui dataset già caricati (stessa quantità di query di `show()` più le note cantiere, nessun'altra query aggiuntiva).

## 6. Esplicitamente fuori scope per ora

- Logo aziendale nell'header del PDF.
- Static map / immagine della posizione (valutato e scartato, vedi §3.1).
- Estensione ad altre schede (Intervento, Abbonamento, Cantiere) — si valuta con un'iterazione dedicata dopo aver verificato il pattern sulla scheda Cliente.
- Storico completo interventi/abbonamenti/cantieri nel PDF — il documento resta volutamente operativo, non un archivio.
