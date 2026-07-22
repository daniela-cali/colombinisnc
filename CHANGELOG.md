# Changelog — Colombini SNC Gestionale

## [0.24.24] - 2026-07-22

### Abbonamenti: fix scadenze duplicate e copertura periodi garantita nel form

- [APP] Form periodi di frequenza: la prima riga eredita in automatico la Data inizio dell'abbonamento; il bottone "Aggiungi periodo" propone il giorno successivo alla fine dell'ultimo periodo esistente; il salvataggio viene bloccato con un avviso se i periodi non coprono l'intero arco dell'abbonamento (buco all'inizio o alla fine)
- [DEV] `AbbonamentiModel::generaInterventi()`: la generazione batch ora scarta le scadenze duplicate/sovrapposte al confine tra due periodi consecutivi (bug verificato: un periodo che continua il precedente sullo stesso giorno poteva rigenerare la stessa data come prima scadenza propria)
- [DEV] Nuovo metodo `AbbonamentiController::periodiCoprono()`, richiamato da `store()` e `update()`: verifica server-side che il primo/ultimo periodo combacino con la Data inizio/fine dell'abbonamento, a garanzia della stessa regola imposta lato form

## [0.24.23] - 2026-07-22

### Rotte protette per personale/impostazioni: chiusa possibile auto-assegnazione di diritti (10.R)

- [APP] "Il mio profilo" è ora una pagina dedicata, separata dalla scheda dipendente gestita dall'amministrazione: mostra solo i propri dati anagrafici e le credenziali di accesso, senza le opzioni riservate allo staff
- [DEV] Introdotti i permessi Shield `personale.manage` e `impostazioni.manage` (assegnati a admin/developer/ufficio in `AuthGroups.php`), al posto dei permessi scaffoldati da Shield e mai utilizzati (`admin.access`, `admin.settings`, `users.manage-admins`, `users.create/edit/delete`, `beta.access`)
- [DEV] Le rotte `anagrafiche/personale` e `impostazioni` sono ora protette lato server dal filtro `permission:...`: prima erano raggiungibili da qualunque utente autenticato tramite URL diretto (erano solo nascoste dal menu per chi è "solo tecnico")
- [DEV] `ProfiloController` non reindirizza più al form condiviso con l'amministrazione (`anagrafiche/personale/{id}/edit`): nuovo metodo `update()` che risolve sempre il proprio dipendente da `user_id()` lato server, mai da un id ricevuto in POST/URL

## [0.24.22] - 2026-07-21

### Calendario: barra "Attenzione" con scadenze in ritardo, appuntamenti mancati e interventi fermi

- [APP] La barra "Scadenze aperte" del calendario diventa "Attenzione": tre pill collassabili — **Non completato** (appuntamento passato mai chiuso), **In ritardo** (scadenza superata), **Fermo** (da pianificare, inserito da più di 7 giorni) — ciascuna con conteggio e tooltip che spiega il criterio
- [APP] Click su un intervento nella pill: se è da pianificare, apre il pool e lo evidenzia con uno scroll+flash; se è già pianificato, porta il calendario alla sua data e lo evidenzia allo stesso modo — nessuna navigazione fuori pagina
- [APP] Doppio click su un intervento (tap su mobile) apre direttamente la sua scheda
- [DEV] `InterventiModel::scadenzeAperte()` sostituito da `scadenzeInRitardo()`: nuova query con motivo/giorni calcolati in PHP; gli interventi da abbonamento generati in blocco a inizio anno sono esclusi dal criterio "fermo" finché la loro scadenza non rientra nel mese corrente (altrimenti risulterebbero sempre fermi per costruzione)
- [DEV] `CalendarioController` raggruppa le scadenze per motivo (`scadenzePerMotivo`) prima di passarle alla view
- [DEV] `calendario.js`: nuovo click-handler con distinzione singolo/doppio click (desktop) e tap diretto (mobile); nuova classe `.cal-flash` (animazione pulse) riusata su pool-card ed eventi FullCalendar; tooltip delle pill inizializzati esplicitamente (usano `data-bs-toggle="collapse"`, non intercettati dal loop globale)

## [0.24.21] - 2026-07-20

### Calendario: pool collassato di default, JS in file esterno, fix crash sul drag

- [APP] Il pannello "Da pianificare" si apre ora con il secondo livello (tipi di intervento) già chiuso, mostrando solo le zone — meno scroll da subito, si espande con un click
- [DEV] Fix: trascinare un evento sul calendario generava un `TypeError` in console (bug del Tooltip di Bootstrap in conflitto con l'elemento "mirror" che FullCalendar crea durante il drag) — i tooltip ora vengono saltati sull'elemento mirror (`info.isMirror`)
- [DEV] Tutto il JavaScript della pagina Calendario (~650 righe inline) spostato in `public/js/calendario.js`; i dati passati da PHP (URL, CSRF, tecnici, assenze, flag) sono ora raccolti in un unico oggetto `window.CalendarioConfig` invece di interpolazioni PHP sparse nello script

## [0.24.20] - 2026-07-20

### Fix: "Vai a data" del calendario non funzionava su mobile (iOS Safari/Chrome)

- [APP] Cliccando/toccando il titolo del calendario il datepicker nativo si apre correttamente su tutti i browser testati (desktop, Safari iOS, Chrome iOS) — prima su mobile non succedeva nulla, e su Safari iOS compariva anche un pill grigio indesiderato con la data
- [DEV] L'input `type="date"` invisibile non viene più tenuto a parte e riposizionato via `getBoundingClientRect()` al click: è ora incollato nel DOM dentro il contenitore del titolo (`.fc-toolbar-chunk`) e dimensionato con CSS puro (`position:absolute; inset:0`), così resta sempre allineato ad ogni riflusso del layout senza ricalcoli manuali
- [DEV] `showPicker()` chiamata da un listener attaccato direttamente sull'input (non più delegato da un elemento ancestor): necessario perché Chrome/Firefox per iOS rifiutavano la chiamata con `NotAllowedError`, non riconoscendo un click delegato come gesto utente diretto
- [DEV] Rimosso `aria-hidden="true"` dall'input: in conflitto con la sua natura ora realmente interattiva (focus reale al click), Chrome lo segnalava in console

## [0.24.19] - 2026-07-19

### Foglio di viaggio: layout a card, filtro tecnico e PDF ristilizzato

- [APP] Pagina Foglio di viaggio (9.R): contenuto racchiuso in un'unica card coerente con lo stile del resto del gestionale (Calendario, Clienti, ecc.), invece di blocchi "nudi" fuori pattern
- [APP] Nuova riga di pill per filtrare gli interventi per tecnico (stesso stile del filtro in Calendario): selezionando un tecnico spariscono le righe e le zone senza suoi interventi
- [APP] Il bottone PDF segue il filtro tecnico attivo: genera il foglio solo per il tecnico selezionato (indicato nel sottotitolo e nel nome del file)
- [APP] PDF ristilizzato con lo stesso pattern grafico delle stampe Cliente/Cantiere: header con logo aziendale, badge colorati per priorità (Urgente/Normale/Abbonamento), righe urgenti evidenziate
- [DEV] `InterventiModel::perGiornata()`: nuovo parametro opzionale `$tecnicoId` per filtrare a livello query; selezionato anche `tecnico_id`
- [DEV] `ViaggioController`: `index()` carica l'elenco tecnici (`PersonaleModel::elencoPerGruppi()`); `pdf()` legge il parametro GET `tecnico_id` e propaga il filtro

## [0.24.18] - 2026-07-19

### Vai a data: click sul titolo del calendario apre il datepicker nativo

- [APP] Cliccando il titolo del calendario (es. "16 – 22 luglio 2026") si apre il datepicker nativo del browser/dispositivo per saltare direttamente a una data, su mobile e su desktop
- [DEV] Input `type="date"` invisibile in `calendario/index.php`, aperto con `showPicker()` (fallback `click()` sui browser che non lo supportano) e riposizionato dinamicamente sopra al titolo con `getBoundingClientRect()` a ogni click; nuove regole in `calendario.css` (`.cal-date-jump`, cursore sul titolo)

## [0.24.17] - 2026-07-19

### Fix: modal Novità/Changelog vuoto sulle versioni solo [DEV]

- [APP] Quando una versione contiene solo modifiche tecniche interne (nessuna riga `[APP]`), il modal Novità e il Changelog ora mostrano un avviso ("Solo modifiche tecniche interne in questa versione, nessuna novità per l'utente") invece di restare vuoti
- [DEV] `changelog_helper.php::changelog_to_html()`: aggiunto il ramo `else` mancante per `$appItems` vuoto

## [0.24.16] - 2026-07-19

### Fix: modal Novità e Promemoria di oggi sovrapposti all'accesso

- [DEV] I modal auto-aperti all'accesso (Novità di versione, Promemoria di oggi) ora si mostrano in sequenza invece che sovrapposti: nuova coda JS `enqueueModal` in `layouts/admin.php`, usata anche da `Cells/promemoria_oggi.php`

## [0.24.15] - 2026-07-19

### Orario suggerito nel modal di pianificazione (drag dal pool)

- [APP] Assegnando un tecnico nel modal "Pianifica" (drag di un intervento dal pool sul calendario), l'orario si precompila con un suggerimento: subito dopo la fine dell'ultimo intervento già pianificato di quel tecnico in quella data, o l'inizio giornata configurato in Impostazioni se non ne ha — solo un default comodo, resta liberamente modificabile
- [DEV] Nuovo endpoint `GET operativo/calendario/orario-suggerito` (`CalendarioController::orarioSuggerito()`)
- [DEV] `InterventiModel::durataMinuti()`: formula centralizzata (durata stimata, altrimenti default del tipo, minimo 60') già usata da `eventiCalendario()`, ora riusata anche qui invece di restare duplicata
- [DEV] `InterventiModel::agendaGiornoTecnico()`: nuovo metodo, agenda di un tecnico in una singola data con la durata di ogni intervento
- [DEV] Fix: il default `oraInizio` del modal Pianifica era fisso a `'08:00'` invece di leggere `Azienda.orario_inizio` dalle Impostazioni

## [0.24.14] - 2026-07-19

### Spunta "completato" sul calendario + fix icone tipo intervento

- [APP] Calendario: gli interventi completati mostrano ora una spunta verde nell'angolo in alto a sinistra dell'evento
- [DEV] Fix: le icone dei tipi di intervento nel calendario (evento, pool, modal dettaglio) usavano il prefisso CSS sbagliato (Bootstrap Icons `bi` invece di Font Awesome `fas`, libreria corretta per `tipi_intervento.icona`) e non venivano mai renderizzate — corretto nei tre punti coinvolti e nel fallback lato server (`CalendarioController::eventi()`)
- [DEV] Refactor `eventContent` (JS calendario): HTML dell'evento costruito con template literal invece di concatenazione di stringhe; stili spostati da inline a classi dedicate in `calendario.css` (`.evt-body`, `.evt-title`, `.evt-sub`, `.evt-btn-rimuovi`, `.evt-badge-completato`)
- [DEV] `InterventiModel::poolDaPianificare()`: selezionata anche la colonna `stato`

## [0.24.13] - 2026-07-19

### Blocco tecnico assente su interventi + notifica conflitti retroattivi

- [APP] Assegnare un tecnico assente a un intervento (creazione, modifica, drag sul calendario) è ora bloccato: un alert nel form segnala il conflitto e disabilita il salvataggio finché non si cambia tecnico o data
- [APP] Nuova card dashboard "Interventi in conflitto": segnala gli interventi già pianificati che restano scoperti quando un'assenza viene inserita dopo (es. malattia improvvisa), con link diretto alla modifica per riassegnare
- [APP] Aggiungendo un'assenza che copre interventi già pianificati sul dipendente, l'avviso nella scheda personale lo segnala subito
- [APP] Visita extra da abbonamento: campo data pianificata ora visibile e facoltativo (prima nascosto per errore rispetto allo spec originale); descrizione precompilata automaticamente con cliente e tipo intervento
- [APP] Scheda abbonamento: badge "Extra" nella tabella interventi collegati, per distinguere le visite extra dalle occorrenze regolari generate dal piano
- [DEV] `AssenzeModel::mappaPerDipendente()`/`copreData()`: nuovi metodi per il controllo di conflitto lato client (JS) e lato server
- [DEV] `InterventiController::erroreAssenzaTecnico()`: helper condiviso da `store()`, `update()` e `pianifica()` — blocca sempre in creazione, solo se tecnico/data cambiano in modifica
- [DEV] `CalendarioController::sposta()`: blocca lo spostamento di un evento su un tecnico assente nel nuovo giorno
- [DEV] `InterventiModel::inConflittoConAssenze()`: query live (nessuna tabella nuova) per la card dashboard
- [DEV] `PersonaleController::aggiungiAssenza()`: dopo l'inserimento, verifica anche gli interventi già pianificati messi in conflitto, oltre alle sovrapposizioni tra assenze già esistenti
- [DEV] `InterventiModel::perAbbonamento()`: selezionata anche la colonna `extra`

## [0.24.12] - 2026-07-15

### Fix visibilità changelog: solo developer vede le righe [DEV]

- [APP] Solo modifiche tecniche interne in questa versione, nessuna novità per l'utente
- [DEV] Layout `admin.php`: rimosso `admin` dal controllo che determina la visibilità delle righe `[DEV]` nel modal Novità e nel Changelog — ora solo il ruolo `developer` le vede

## [0.24.11] - 2026-07-15

### Sottogruppi generico/cantiere/abbonamento nel pool calendario

- [APP] Pool "da pianificare" nel calendario: dentro ogni zona, gli interventi sono ora raggruppati anche per tipo — Generici, Cantieri, Abbonamenti — ciascuno con la propria intestazione pieghevole e conteggio
- [DEV] `InterventiModel::poolDaPianificare()`: selezionati anche `cantiere_id`/`abbonamento_id`, usati per classificare ogni intervento nel sottogruppo giusto
- [DEV] `CalendarioController::index()`: `$poolPerZona` ora è `zona => blocchi[]` (uno per sottogruppo non vuoto, con label/icona/interventi), ordine fisso Generici→Cantieri→Abbonamenti; `$totaliPerZona` precalcolato per il badge di ogni zona; `$pool` non è più passato alla view (non più usato lì, sostituito da `$totaleDaPianificare`/`$poolPerZona`)
- [DEV] `operativo/calendario/index.php`: nuovo livello di accordion Bootstrap annidato per i sottogruppi, dentro quello di zona già esistente

## [0.24.10] - 2026-07-11

### Ritocchi calendario e form interventi

- [APP] Pool "da pianificare" nel calendario: ordinamento semplificato a due criteri — urgenza, poi data di inserimento (prima includeva anche scadenza e distanza dalla sede)
- [APP] Nuovo campo "Creato il" nel dettaglio di un intervento aperto dal calendario (eventi pianificati e pool) e nell'header della scheda intervento
- [APP] Descrizione dell'intervento precompilata automaticamente col nome del tipo scelto ("Intervento: <nome>"), sempre editabile — riduce la digitazione per i casi più semplici
- [APP] Impostando la data pianificata su un intervento ancora "da pianificare", lo stato passa automaticamente a "pianificato"
- [APP] Il testo dei pill/eventi colorati per dipendente (filtro calendario, eventi, badge colore nella scheda personale) ora è sempre leggibile: nero o bianco scelto automaticamente in base al colore di sfondo, non più bianco fisso
- [DEV] `InterventiModel::poolDaPianificare()`/`eventiCalendario()`: selezionato anche `created_at`, usato per il nuovo ordinamento e per il campo "Creato il"
- [DEV] Nuovo helper `colore_helper.php` con `colore_testo()` (formula YIQ) per calcolare il colore di testo leggibile su uno sfondo dinamico
- [DEV] `nuovo.php`/`edit.php`: nuovi listener JS sul cambio di `tipo_intervento_id` (precompila descrizione) e `data_pianificata` (aggiorna stato)

## [0.24.9] - 2026-07-10

### Riordino voci di menu

- [APP] Voce "Calendario" spostata in cima al menu laterale, sopra "Anagrafiche"

## [0.24.8] - 2026-07-10

### Dark mode: altri fix di leggibilità + tabella Cantieri con DataTable

- [APP] Il testo `.text-muted` dentro badge/elementi con sfondo chiaro fisso (`.bg-light`, es. le date nei badge "Scadenze aperte" del calendario) ora resta leggibile in dark mode — prima diventava chiaro su sfondo chiaro
- [APP] Le intestazioni `table-light` delle tabelle (Cantieri, Viaggio, Abbonamenti, Interventi nella scheda cliente) sono coerenti anche in dark mode, niente più banda bianca in una pagina scura
- [APP] Scheda cliente: nuova tabella Cantieri con ricerca, paginazione e filtri Aperti (default)/Sospesi/Chiusi/Tutti, come già presente per Interventi e Abbonamenti
- [DEV] `custom.css`: nuove regole `[data-bs-theme="dark"] .bg-light .text-muted` e `[data-bs-theme="dark"] .table-light`
- [DEV] `clienti/show.php`: aggiunta `class="table-light"` ai `<thead>` mancanti; nuova tabella `tbl-cantieri` con DataTable + `pill-filtri.js`, stesso pattern di `tbl-interventi`/`tbl-abbonamenti` (colonna nascosta con stato raw, click automatico sul pill di default)
- [DEV] `cantieri/index.php`: icone Aperti/Chiusi allineate (`bi-unlock`/`bi-lock`) a quelle dei nuovi filtri pill nella scheda cliente

## [0.24.7] - 2026-07-10

### Fix leggibilità righe urgenti in dark mode

- [APP] Nelle liste Interventi e Viaggio, le righe degli interventi urgenti (`table-danger`) ora restano leggibili anche in dark mode — prima il testo delle colonne Cliente/Tecnico risultava chiaro su sfondo rosa chiaro, quasi invisibile
- [DEV] `custom.css`: nuova regola `[data-bs-theme="dark"] .table-danger` con variabili Bootstrap ridefinite (sfondo rosso scuro, testo chiaro) — Bootstrap/AdminLTE nel bundle vendorizzato definisce `.table-danger` solo per la modalità chiara, senza variante dark

## [0.24.6] - 2026-07-08

### Fix zoom da rotella sulla mappa nella scheda cliente

- [APP] Scorrendo la pagina con il mouse sopra la mappa della scheda cliente, la pagina ora scorre normalmente invece di zoomare la mappa per errore. Lo zoom da rotella si attiva solo cliccando prima sulla mappa (velo con messaggio "Clicca per attivare lo zoom con la rotella"), e si disattiva di nuovo appena il mouse esce dalla mappa
- [DEV] `L.map('mappaCliente', { scrollWheelZoom: false })` + overlay assoluto (`.mappa-wrapper`/`.mappa-overlay-zoom` in custom.css) con listener `click`/`mouseleave` per abilitare/disabilitare `map.scrollWheelZoom`
- [DEV] Fix stacking context: `#mappaCliente` necessitava di uno `z-index` esplicito oltre al `position: relative` (impostato da Leaflet) — senza, i pannelli interni di Leaflet (tile, controlli zoom) competevano direttamente con l'overlay invece di restarne sempre sotto

## [0.24.5] - 2026-07-08

### Fix visibilità interventi da cantiere nella scheda cliente

- [APP] Gli interventi collegati a un cantiere ora compaiono anche nella lista "Interventi" della scheda cliente (con badge "Cantiere: nome" cliccabile) — prima sparivano dalla scheda cliente appena uscivano dallo stato "da pianificare", perché esclusi dalla lista e non contati nel badge Cantieri
- [DEV] `InterventiModel::perCliente()`: rimosso il filtro `cantiere_id IS NULL`, aggiunto join su `cantieri` per il titolo
- [DEV] `ClientiController::pdf()`: filtro equivalente reintrodotto solo lì, per evitare che gli interventi da cantiere compaiano due volte nel PDF (lista piatta + blocco del proprio cantiere, che li elenca già)

## [0.24.4] - 2026-07-08

### Filtri pill e ricerca DataTable per Abbonamenti nella scheda cliente

- [APP] Sezione Abbonamenti della scheda cliente: bottoni filtro (Attivi/Sospesi/Scaduti/Disdetti/Tutti) e ricerca full text, come già presenti per Interventi
- [DEV] Nuovo file condiviso `public/js/pill-filtri.js`: un unico listener delegato su `document` sostituisce la logica di filtro pill duplicata per tabella — ogni contenitore dichiara la propria configurazione in JSON (`data-pill-filtri`), i bottoni portano solo `data-filtro`
- [DEV] Migrata anche la sezione Interventi della scheda cliente al nuovo sistema condiviso, rimossa la vecchia logica JS inline duplicata

## [0.24.3] - 2026-07-07

### Fix ordinamento iniziale lista clienti

- [APP] La lista clienti si apre ora ordinata alfabeticamente per denominazione come previsto, invece che per codice
- [DEV] `ClientiModel::elencoCompleto()`: `orderBy()` referenzia ora l'alias SQL `denominazione` invece di ripetere l'intera espressione `CASE WHEN...`, che il Query Builder alterava con l'escaping automatico degli identificatori
- [DEV] DataTables (`clienti/index.php`): aggiunto `order: [[1, 'asc']]` esplicito — senza, il default di DataTables (ordina per la prima colonna) sovrascriveva silenziosamente l'ordine restituito dal backend

## [0.24.2] - 2026-07-06

### Dettaglio intervento anche dal pool di pianificazione

- [APP] Cliccando una card nel pool "Da pianificare" si apre ora lo stesso modal di dettaglio già usato per gli eventi pianificati nel calendario, con i bottoni "Modifica" e "Apri scheda"
- [APP] Il modal dettaglio intervento (sia dal pool che dal calendario) mostra ora anche i **materiali da portare**, quando presenti
- [DEV] `CalendarioController::index()` ed `eventi()` raggruppano i materiali "da portare" per intervento riusando `InterventiMaterialiModel::daPortarePerInterventi()`, stesso pattern già in uso in `ViaggioController`
- [DEV] Fix bordo colorato del modal: era applicato solo a `#modal-header` (alto quanto il titolo), creando uno scalino nel bordo sinistro dove l'header incontra il body; spostato su `#modal-content` per coprire tutta l'altezza senza interruzioni

## [0.24.1] - 2026-07-06

### Blocco cancellazione cliente e view di consultazione

- [APP] Cancellare un cliente con interventi/cantieri/abbonamenti ancora collegati ora mostra un messaggio chiaro (es. "22 in interventi, 1 in abbonamenti") invece dell'errore grezzo del database
- [DEV] `ClientiModel::relazioniBloccanti()`: scopre dinamicamente da `information_schema` quali tabelle hanno FK RESTRICT/NO ACTION su `clienti.id`, invece di un elenco scritto a mano — si aggiorna da solo quando in futuro si aggiungono nuove tabelle collegate ai clienti (es. impianti, preventivi)
- [DEV] Nuove view di sola lettura per le query manuali a DB: `v_abbonamenti_clienti`, `v_abbonamenti_clienti_interventi`, `v_interventi_clienti`

## [0.24.0] - 2026-07-06

### Stampe PDF: scheda Cliente e Cantiere

- [APP] Nuovo bottone **Stampa PDF** nella scheda Cliente: documento operativo essenziale (anagrafica, materiali sospesi, interventi da pianificare/pianificati, abbonamento attivo, cantieri aperti/sospesi) — niente storico completo, pensato per essere stampato o consultato prima di un intervento
- [APP] Nuovo bottone **Stampa PDF** nella scheda Cantiere: riepilogo completo, non solo l'essenziale — anagrafica cliente e dati cantiere completi affiancati, **diario integrale** (tutte le note, non solo le ultime), **tutti gli interventi collegati** (ogni stato, storico incluso) con i relativi materiali portati/da portare
- [DEV] `ClientiController::pdf()` e `CantieriController::pdf()`, stesso pattern già in uso per il foglio di viaggio: view HTML/CSS dedicata (dompdf, niente flexbox/grid), `Options::isRemoteEnabled = false`, stream inline senza forzare il download
- [DEV] Palette grafica delle stampe ripresa dal vecchio progetto (accento blu, tabelle etichetta/valore, badge di stato) invece dello stile spoglio del foglio di viaggio esistente
- [DEV] Rimosso codice morto: secondo gruppo di impostazioni azienda (`Azienda.ragione_sociale`/`partita_iva`/`logo_path`, `GeneraleController::salva()`) mai raggiungibile da nessuna view, superato dalla pagina "Parametri Generali" (`Azienda.sede_*`) usata per i dati d'intestazione dei PDF
- [DEV] Nuove spec `docs/spec/stampa_cliente_pdf_spec.md` e `docs/spec/stampa_cantiere_pdf_spec.md`
- *Nota: stampe di Intervento e Abbonamento rimandate, da pianificare senza scadenza precisa*

## [0.23.1] - 2026-07-05

### Piccoli ritocchi

- [APP] Scheda intervento: la **data pianificata** mostra ora anche l'**ora** (`d/m/Y H:i`), prima solo il giorno — coerente con la lista interventi
- [DEV] Unificato su un solo pattern SQL (`CASE WHEN tipo = 'persona_fisica'`) il calcolo della denominazione cliente in `InterventiModel::agendaGiorno()`, `urgentiDaPianificare()`, `agendaTecnicoPeriodo()` e `AbbonamentiModel::inScadenza()` — prima usavano `COALESCE(NULLIF(ragsoc, ''), ...)`, equivalente ma diverso dal pattern già in uso ovunque altrove nel progetto

## [0.23.0] - 2026-07-05

### Mappa in scheda cliente

- [APP] Nuova sezione **Posizione** nella scheda cliente: mappa Leaflet con il punto geografico del cliente e link diretto per aprirlo in Google Maps
- [APP] Pulsante **Correggi posizione** sempre disponibile (non solo quando la geocodifica automatica fallisce): clicca sulla mappa o trascina il pin per spostarlo, poi conferma con **Salva posizione** — utile anche per correggere un punto impreciso (es. centro città invece dell'indirizzo esatto)
- [APP] Se il cliente non ha ancora una posizione precisa, la mappa si centra comunque su un riferimento utile: la **città** indicata (se presente) o, in mancanza, la **sede aziendale** — solo per orientarsi, senza salvare nulla finché non si posiziona il pin manualmente
- [APP] **Pallino rosso fisso della sede aziendale**, sempre visibile sulla mappa (quando le coordinate sede sono impostate nei parametri), non modificabile
- [APP] Campo **Nazione** (nuovo/modifica cliente): ora una tendina con **Italia** e **Francia** già pronte, più l'opzione **Altra…** per i casi eccezionali
- [APP] Guida della sezione Clienti aggiornata con le nuove funzionalità
- [DEV] Nuova rotta/metodo `ClientiController::aggiornaPosizione()`, sotto-risorsa nello stesso schema di `aggiungiAssenza()`/`aggiungiNota()`; nessuna modifica al model, i campi `lat`/`lng`/`geocoded_at`/`geocodifica_fallita` erano già presenti
- [DEV] Nuova costante `ClientiModel::NAZIONI_PREDEFINITE` — punto unico da aggiornare per aggiungere altre nazioni alla tendina
- [DEV] Fix bug Leaflet: `L.Icon.Default._getIconUrl()` anteponeva sempre un `imagePath` auto-rilevato dal CSS anche davanti a un URL già assoluto, causando un URL doppio e 404 sulle icone marker — corretto sia nella nuova mappa che nel bug preesistente identico in `dashboard/tecnico.php`
- [DEV] Fix bug: il listener `dragend` veniva collegato solo ai marker creati già `draggable: true`, quindi trascinare il pin esistente (view-mode, non draggable alla creazione) non aggiornava le coordinate da salvare
- [DEV] Nuova spec `docs/spec/mappa_cliente_spec.md`

## [0.22.0] - 2026-07-05

### Assenze personale

- [APP] Nuova sezione **Assenze** nella scheda dipendente (Anagrafiche → Personale): registrazione di ferie, malattia, permesso o altro con data di inizio/fine (giornata intera) e note facoltative. Gestione riservata a ufficio, admin e developer
- [APP] Se una nuova assenza si sovrappone a un'altra già registrata per lo stesso dipendente, il salvataggio procede comunque con un **avviso** (non è un blocco) — utile ad es. per una malattia durante le ferie
- [APP] **Calendario**: le assenze compaiono come eventi arancioni nella riga "tutto il giorno" (viste Giorno/Settimana) o come barra in vista Mese; non sono modificabili da lì, solo dalla scheda Personale
- [APP] **Calendario — modal Pianifica**: avviso non bloccante se si assegna un intervento a un tecnico che risulta assente in quella data
- [APP] **Dashboard** (admin/ufficio): nuovo info-box "Assenti oggi" e card con l'elenco di chi è assente oggi, con link alla scheda Personale; icona e colore dedicati (arancione, coerenti con l'evento calendario) per distinguerla da "Abbonamenti in scadenza"
- [APP] **Dashboard**: le 5 info-box e le 4 card di riepilogo sono ora affiancate su un'unica riga sui monitor larghi (`row-cols` responsive), nello stesso ordine su entrambe le righe (Urgenti → Assenti oggi → Promemoria → Abbonamenti)
- [DEV] Nuova tabella `assenze` (FK `personale_id` con `ON DELETE CASCADE`, non `users` — coerente con `interventi.tecnico_id` che punta a `personale`); `AssenzeModel` con costanti tipo/label/badge, `perPersonale()`, `sovrapposizioni()`, `perCalendario()`, `daOggiInPoi()`, `oggi()`
- [DEV] `PersonaleController::aggiungiAssenza()`/`eliminaAssenza()` sul modello delle note di cantieri/interventi (sotto-risorsa del controller genitore, non un controller dedicato)
- [DEV] Nuovo flashdata `warning` (alert giallo) nel layout `admin.php`, usato per le sovrapposizioni
- [DEV] Calendario: `allDaySlot` abilitato (prima disattivato) per poter mostrare eventi senza orario preciso nelle viste Giorno/Settimana
- [DEV] Nuove classi `.card-assenza`/`.bg-assenza` in `custom.css` (arancione `#e8590c`), stesso pattern già usato per `.card-promemoria`/`.bg-promemoria`

## [0.21.7] - 2026-07-04

### Fix created_by/updated_by in tutti i model

- [DEV] Tutti i model con `normalizza()` (`PromemoriaModel`, `PersonaleModel`, `AbbonamentiModel`, `AbbonamentiPeriodiModel`, `ArticoliModel`, `CantieriModel`, `CantieriNoteModel`, `CategorieArticoliModel`, `ClientiModel`, `InterventiModel`, `InterventiNoteModel`, `InterventiMaterialiModel`, `TipiInterventoModel`) usavano `session()->get('user_id')`, che in questo progetto restituisce sempre `null` (Shield salva l'utente sotto la chiave di sessione `'user'`, non `'user_id'`) — sostituito ovunque con l'helper Shield `user_id()`. `created_by`/`updated_by` ora si popolano correttamente in tutto il gestionale, non solo sui promemoria

## [0.21.6] - 2026-07-04

### Promemoria di oggi — modal informativa

- [APP] Ad ogni accesso al gestionale, se ci sono **promemoria previsti per oggi** compare una modal informativa in alto, bloccante finché non si clicca **"Ho letto"** — stile Google Calendar
- [APP] Il click su "Ho letto" segna il promemoria come visto **per l'utente e per la giornata**: la conferma è salvata a DB (non nel browser), quindi resta valida anche cambiando dispositivo o browser
- [APP] Campanella e dashboard mostrano una fascia **"Oggi"** (al posto di "Questa settimana"), sempre visibile per l'intera giornata anche a orario già passato; il badge rosso conta solo i promemoria di oggi
- [APP] I promemoria già letti restano visibili in campanella e dashboard (fino a fine giornata) con una **spunta verde**, invece di sparire
- [APP] Creando un promemoria senza indicare l'orario di fine, viene impostato automaticamente **inizio + 1 ora** (come Google Calendar)
- [DEV] Nuova tabella `promemoria_dismiss` (FK `promemoria_id` e `utente_id`, chiave unica sulla coppia) per il tracciamento "visto" per utente
- [DEV] Nuovo Cell `PromemoriaOggiCell` dedicato alla modal, separato da `AvvisiCell` (campanella) per non mischiare comportamento bloccante e informativo
- [DEV] `PromemoriaModel::inArrivo()` semplificato a confronto sulla sola data (nessun filtro sull'orario); join con `promemoria_dismiss` per il flag `letto` per utente; nuova query `oggiNonVisti()` per la modal

## [0.21.5] - 2026-07-04

### Colore profilo — selezione a tavolozza

- [APP] La scelta del **colore profilo** del dipendente (nuovo/modifica) avviene ora su una **tavolozza di pastelli**: si clicca il cerchio desiderato, senza più codici esadecimali né slider
- [APP] I colori **già assegnati** ad altri dipendenti appaiono **sbarrati** e non selezionabili, così è subito chiaro quali tinte sono libere
- [APP] Nuovo dipendente: viene **preselezionato automaticamente** il primo colore libero della tavolozza
- [APP] **Scheda dipendente**: il colore profilo è mostrato come pallino colorato accanto al codice, non più solo come testo esadecimale
- [DEV] Partial riutilizzabile `_colore_picker.php` (radio nascosti, nessun JavaScript) condiviso tra le view nuovo/modifica; rimosso il vecchio picker con slider hue e relativo script
- [DEV] Indicatore "già assegnato" realizzato in CSS con doppio `linear-gradient` (barra diagonale + alone bianco), senza immagini né icone
- [DEV] Aggiunta la specifica tecnica ed economica per l'integrazione del **centralino Voxloud** (`docs/spec/centralino.md`)

## [0.21.4] - 2026-07-02

### Doppio click sulle righe per aprire le schede

- [APP] **Doppio click su una riga** di elenco apre la relativa scheda: Interventi, Clienti, Abbonamenti, Cantieri, Personale e le tabelle Interventi/Abbonamenti/Cantieri dentro la scheda cliente. Il doppio click su un link esistente lascia agire quel link; con **Ctrl/Cmd** la scheda si apre in una nuova scheda del browser
- [APP] Le tabelle **Abbonamenti** e **Cantieri** nella scheda cliente hanno ora una colonna **Rif. #ID** cliccabile, così la scheda si apre anche con un singolo click
- [DEV] Nuovo script condiviso `public/js/row-dblclick.js`: handler `dblclick` delegato su `document` che naviga verso il link `.js-row-open` della riga (nessuna duplicazione di URL, sopravvive ai redraw di DataTables). Caricato globalmente dal layout
- [DEV] Regola `custom.css` `tr:has(a.js-row-open) { cursor: pointer }` per l'affordance visiva

## [0.21.3] - 2026-07-02

### Cantieri — lista come abbonamenti + suggerimento ordinamento

- [APP] **Lista cantieri** rinnovata: colonna **riferimento #ID**, ricerca full-text, ordinamento su tutte le colonne e filtri rapidi per stato (Aperti/Sospesi/Chiusi/Tutti, apertura su Aperti)
- [APP] Sotto il tipo di ogni cantiere compare un'**anteprima delle note** di testata (descrizione del lavoro), troncata
- [APP] Icona informativa accanto ai titoli delle liste (Interventi, Clienti, Abbonamenti, Articoli, Cantieri) che spiega l'**ordinamento su più colonne** (Shift+clic)
- [DEV] Vista cantieri convertita a DataTable con filtri pill sullo stato (colonna nascosta), riusando il pattern di abbonamenti
- [DEV] Commento chiarificatore sulle righe `orderMulti: true` (già default di DataTables)

## [0.21.2] - 2026-07-01

### Abbonamenti — lista e descrizioni

- [APP] Gli interventi generati da un abbonamento ricevono ora una **descrizione automatica** ("Visita in abbonamento #N")
- [APP] **Lista abbonamenti** rinnovata: filtri rapidi per stato (Attivi/Sospesi/Scaduti/Disdetti/Tutti, apertura su Attivi), colonna **riferimento #ID**, ricerca full-text e ordinamento su tutte le colonne
- [DEV] Descrizione aggiunta in `AbbonamentiModel::generaInterventi()`
- [DEV] Vista abbonamenti convertita a DataTable con filtri pill sullo stato calcolato (colonna nascosta)
- [DEV] Fix globale in `custom.css`: la freccia di ordinamento DataTables resta a destra anche sulle colonne numeric/date (che il rilevamento automatico allineava a destra con freccia a sinistra)

## [0.21.1] - 2026-07-01

### Query spostate nei model

- [DEV] Tutte le query rimaste nei controller spostate nei rispettivi model: `InterventiModel` (`agendaGiorno`, `urgentiDaPianificare`, `agendaTecnicoPeriodo`, `perGiornata`, `eventiCalendario`, `perAbbonamento`, `prossimiPerAbbonamento`, `contaPerTipo`), `InterventiMaterialiModel` (`daPortarePerInterventi`, `contaPerArticolo`), `AbbonamentiModel` (`inScadenza`)
- [DEV] Controller ripuliti: Dashboard, Viaggio, Calendario, Abbonamenti, Interventi, Articoli, TipiIntervento — ora contengono solo chiamate ai metodi del model
- [DEV] Rimossi gli ultimi accessi diretti al DB dai controller (`db_connect()->table()` / `db()->table()` in ArticoliController e TipiInterventoController)
- [DEV] Dashboard interventi di oggi: una sola query con `count()`/`array_slice` al posto di due (conteggio + lista)

## [0.21.0] - 2026-07-01

### Promemoria e avvisi

- [APP] **Promemoria**: nuovi eventi aziendali ad-hoc con data e ora (es. "Cliente arriva/chiede di aprire"), gestibili dall'ufficio direttamente dal **Calendario** (pulsante "+ Promemoria", con creazione/modifica/eliminazione). Compaiono come eventi viola sul calendario; i tecnici li vedono in sola lettura
- [APP] **Campanella avvisi** in alto a destra: elenca i promemoria in arrivo divisi in "Questa settimana" e "Prossimi giorni", con un contatore di quelli imminenti; ogni voce apre il calendario sul giorno del promemoria
- [APP] **Dashboard** riorganizzata: riga di contatori sintetici (interventi oggi, urgenti, promemoria, abbonamenti), card con l'elenco degli **interventi di oggi** (con tecnico assegnato) e card dei **promemoria in arrivo** a due fasce
- [APP] **Nuovo intervento**: il campo "Data pianificata" consente ora di indicare anche l'**ora** (prima solo la data)
- [APP] Le **persone fisiche** senza ragione sociale mostrano correttamente **nome e cognome** nelle liste della dashboard (prima potevano apparire senza denominazione)
- [DEV] Tabella `promemoria` (migration), `PromemoriaModel` (`inArrivo`, `inArrivoRaggruppati`, `perCalendario`), `PromemoriaController` (store/update/delete con guard gestori)
- [DEV] Campanella come **View Cell** `AvvisiCell`, predisposta ad aggregare anche le future notifiche; dashboard e campanella condividono `inArrivoRaggruppati()`
- [DEV] `CalendarioController::index` accetta `?data=` (validata) per aprire il calendario su un giorno specifico (`initialDate`)
- [DEV] Fix `data_pianificata`: input `datetime-local` nel form di creazione, allineato alla validazione `valid_date[Y-m-d\TH:i]` e alla normalizzazione del model
- [DEV] `NULLIF(clienti.ragsoc, '')` nelle query dashboard per il fallback su cognome+nome

## [0.20.1] - 2026-06-30

### Guida — sotto-sezioni

- [APP] **Guida** aggiunta anche per **Tipi intervento**, **Categorie articoli** e **Utenti** (Impostazioni) e per il **Foglio di viaggio**
- [DEV] `help_sezione` agganciato all'`index()` di `TipiInterventoController`, `CategorieArticoliController`, `UtentiController::utentiApp()` e `ViaggioController::index()`

## [0.20.0] - 2026-06-30

### Guida contestuale

- [APP] **Guida di sezione**: nuovo pulsante `?` in alto a destra che apre la guida della sezione in cui ti trovi, in una finestra dedicata (a tutto schermo su smartphone). Il pulsante compare solo nelle sezioni che hanno una guida. Sezioni documentate: **Dashboard** (ufficio e tecnico), **Personale**, **Clienti**, **Calendario**, **Interventi**, **Abbonamenti**, **Cantieri**, **Articoli**, **Impostazioni**
- [APP] **Changelog e Novità**: il testo in **grassetto** viene ora mostrato correttamente (prima compariva la sintassi `**...**`)
- [APP] **Calendario**: il pannello "Da pianificare", quando compresso, si riduce a un piccolo pulsante-icona invece di restare una barra larga, liberando spazio per il calendario
- [DEV] Sistema help: il layout legge `$help_sezione` (passato dal solo `index()` di ogni controller) e include `app/Views/help/<sezione>.php` se esiste; convertitore Markdown inline (`changelog_inline()`) per grassetto/corsivo/`code` nelle voci di changelog
- [DEV] Calendario: la larghezza inline del pool (resize) viene azzerata in stato compresso per non sovrascrivere il `width: auto` del CSS, e ripristinata alla riespansione

## [0.19.0] - 2026-06-28

### Adattamento mobile

- [APP] **Dashboard tecnico** dedicata e ottimizzata per smartphone: agenda dei prossimi 3 giorni (Oggi / Domani / Dopodomani) con orario, cliente, indirizzo e materiali da portare; pulsante mappa che mostra la posizione del cliente (OpenStreetMap) con collegamento a Google Maps per la navigazione; sezione urgenti da pianificare. I tecnici "puri" vengono indirizzati direttamente a questa vista
- [APP] **Sidebar ridotta per i tecnici**: chi ha solo il ruolo tecnico vede unicamente Dashboard, Clienti, Calendario e Interventi
- [APP] **Liste responsive**: le tabelle di Clienti, Interventi e Articoli su smartphone collassano le colonne meno importanti in una riga espandibile (tocca il `+`), restando leggibili senza sforare lo schermo
- [APP] **Filtri** delle liste (scheda cliente e interventi) adattati a mobile: vanno a capo ordinatamente invece di sforare
- [APP] **Calendario** su mobile: vista Giorno con barra essenziale (frecce + data); il pannello "Da pianificare" è comprimibile a icona su desktop e nascosto su mobile (si pianifica aprendo l'intervento)
- [APP] **Calendario**: tooltip esplicativo sulla fascia "Scadenze aperte" che chiarisce quali interventi mostra
- [APP] **Foglio di viaggio**: tabella scrollabile orizzontalmente su mobile
- [DEV] Estensione **DataTables Responsive** (`datatables.net-responsive-bs5`) con priorità colonne configurate per lista; asset DataTables centralizzati in partial condivisi (`partials/datatables_styles`, `partials/datatables_scripts`)
- [DEV] Helper `acl` con `is_solo_tecnico()` per la logica di visibilità basata sui gruppi, riusato in `DashboardController` e nel layout
- [DEV] `DashboardController::agendaTecnico()` costruisce l'agenda a 3 giorni con materiali e coordinate in query aggregate

## [0.18.1] - 2026-06-27

### Leaflet — dipendenza frontend

- [DEV] Aggiunta dipendenza **Leaflet** (`leaflet` npm) per mappe interattive con OpenStreetMap; file pubblicati in `public/assets/vendor/leaflet/` tramite `assets:publish`

## [0.18.0] - 2026-06-27

### Dashboard role-aware

- [APP] **Dashboard principale** adattata al ruolo: admin e ufficio vedono una card con il conteggio degli interventi pianificati oggi (link a calendario e foglio di viaggio) e una card con la lista degli urgenti non ancora pianificati
- [APP] Gruppo **ufficio**: sezione aggiuntiva con la tabella degli abbonamenti in scadenza nei prossimi 30 giorni, ordinati per data con badge giorni rimasti (rosso ≤7gg, giallo ≤15gg, grigio oltre)
- [APP] Gruppo **tecnico**: sezione personale con i propri interventi di oggi (ora, cliente, indirizzo) e i propri urgenti non pianificati; chi è sia admin che tecnico vede entrambe le sezioni
- [DEV] `DashboardController`: metodi privati `caricaDatiUfficio()` e `caricaDatiTecnico()` separano la logica per ruolo; gli abbonamenti in scadenza vengono caricati solo per il gruppo ufficio
- [DEV] Fix: denominazione cliente calcolata ovunque con `COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome)))` — la colonna `denominazione` non esiste nella tabella `clienti`
- [DEV] Fix: validazione `data_pianificata` in `InterventiController` aggiornata a `valid_date[Y-m-d\TH:i]`; `InterventiModel::normalizza()` converte il separatore `T` di `datetime-local` in spazio prima della persistenza su MySQL

## [0.17.0] - 2026-06-27

### Cantieri

- [APP] Nuova sezione **Cantieri**: raggruppa più interventi legati a un unico progetto per un cliente (nuova costruzione o ristrutturazione). Ogni cantiere ha titolo, tipo, stato (aperto / sospeso / chiuso), date e note generali
- [APP] **Diario del cantiere**: note datate in ordine cronologico, aggiungibili ed eliminabili direttamente dalla scheda cantiere — stessa UX del diario interventi
- [APP] Scheda cantiere: header con azioni cambio-stato (riapri / sospendi / chiudi) e bottone elimina (bloccato se ci sono interventi collegati); sezione interventi collegati; sezione diario
- [APP] **Tipo intervento predefinito** sul cantiere: campo facoltativo che pre-seleziona il tipo nel form "Nuovo intervento" aperto dalla scheda cantiere (modificabile prima del salvataggio)
- [APP] Scheda cliente: nuova sezione **Cantieri** (sotto Abbonamenti) con indice sottile — titolo, tipo, periodo, contatore "⚠ N da pianificare", anteprima ultima nota, stato, bottone Apri. La sezione Interventi della scheda cliente ora esclude gli interventi agganciati a un cantiere (compaiono solo sotto il loro cantiere)
- [APP] Scheda intervento e form modifica intervento: se l'intervento è collegato a un cantiere, viene mostrato un banner con link alla scheda cantiere
- [APP] Form "Nuovo intervento": se aperto dalla scheda cantiere, mostra un banner informativo e aggancia automaticamente il cantiere
- [APP] Voce **Cantieri** nel menu laterale (icona bi-bricks, tra Abbonamenti e Calendario)
- [APP] Calendario — modal pianifica: se la data scelta supera la `data_scadenza` dell'intervento, compare un avviso inline nel modal (giallo per normali, rosso per urgenti). Il bottone "Pianifica" resta sempre attivo — l'avviso informa senza bloccare
- [DEV] Nuova tabella `cantieri`: `cliente_id` FK RESTRICT, `titolo`, `tipo` VARCHAR (nuova_costruzione / ristrutturazione), `tipo_intervento_id` FK nullable SET NULL (default pre-compilazione), `stato` VARCHAR (aperto / sospeso / chiuso), `data_inizio`, `data_fine_prevista`, `note`, campi standard
- [DEV] Nuova tabella `cantieri_note`: `cantiere_id` FK CASCADE, `data_nota`, `testo`, campi standard. Stessa struttura di `interventi_note`
- [DEV] Colonna `cantiere_id INT UNSIGNED NULL FK → cantieri.id RESTRICT` su `interventi` (gemella di `abbonamento_id`)
- [DEV] Colonna `tipo_intervento_id INT UNSIGNED NULL FK → tipi_intervento.id SET NULL` su `cantieri` (default per i nuovi interventi)
- [DEV] `CantieriModel`: costanti TIPO_/STATO_, `normalizza()` (created_by/updated_by, nullificazione date e tipo_intervento_id vuoti), `perCliente()` con subquery contatore da-pianificare e ultima nota, `conCliente()`, `elencoCompleto()`
- [DEV] `CantieriNoteModel`: gemello di `InterventiNoteModel`
- [DEV] `InterventiModel::perCliente()`: aggiunto filtro `cantiere_id IS NULL` (opzione A — gli interventi di cantiere si vedono solo sotto il cantiere, non nella lista piatta della scheda cliente)
- [DEV] `InterventiController::nuovo()`: legge `?cantiere_id`, carica cantiere, pre-compila `cliente_id` e `tipo_intervento_id` dal default del cantiere
- [DEV] `InterventiController::show()` e `edit()`: caricano il cantiere collegato e lo passano alle view
- [DEV] Calendario `index.php` — card pool: aggiunti `data-scadenza` e `data-urgenza`; modal pianifica: avviso inline scadenza superata (client-side, senza round-trip server)

## [0.16.1] - 2026-06-26

### Sezioni interventi per area (piscine / addolcitori / generici)

- [APP] La lista interventi è ora divisa per area dal menu laterale: **Generici**, **Piscine**, **Addolcitori** (sottovoci treeview sotto "Interventi"). Ogni sezione mostra solo gli interventi della propria categoria — i Generici includono anche quelli senza tipo assegnato. Nasce dal caso reale delle aperture piscine dai quaderni cartacei: il titolare ragiona per area ed evita l'affollamento di un'unica lista
- [APP] I tipi di intervento hanno una **categoria** (Generici / Piscine / Addolcitori), scelta dalla select "Sezione" nella gestione tipi e mostrata in colonna
- [APP] **Fase apertura/chiusura** sugli interventi piscina: nel form intervento la select "Fase" (ordinaria / apertura / chiusura) compare solo per i tipi della sezione Piscine e imposta i flag corrispondenti
- [APP] Pill di filtro **Aperture** e **Chiusure** nella sezione Piscine; pill **Abbonamenti** nelle sezioni Piscine e Addolcitori (la pill "Da pianificare" esclude gli abbonamenti puri, che restano nella loro pill, per non allungare la lista con voci fuori periodo)
- [APP] Badge **Apertura** / **Chiusura** (azzurri, con icona) per riconoscere a colpo d'occhio questi interventi nella lista, nella scheda intervento e nella tabella interventi della scheda cliente
- [APP] Scheda cliente: aggiunto anche il badge **Extra** nella tabella interventi (prima era solo un filtro nascosto); la sezione **Interventi** è stata spostata sopra **Abbonamenti** (più consultata), con la nav laterale riallineata
- [APP] Fix lista interventi: cliccare un campo di ricerca di colonna non riordina più la colonna per sbaglio
- [APP] Migliorata la spaziatura dei titoli di sezione nella scheda cliente (padding sinistro, allineati al contenuto)
- [DEV] Migration: campo `categoria` su `tipi_intervento` (VARCHAR, default `generale`; seed `piscine`/`addolcitori`) + `apertura` / `chiusura` TINYINT(1) su `interventi`. Costanti `CATEGORIE_LABEL` in `TipiInterventoModel` (categoria generale ribattezzata `Generici` per non collidere col menu padre "Interventi"); guardia di mutua esclusione apertura/chiusura in `normalizza()`
- [DEV] `InterventiModel::elencoCompleto(?string $categoria)`: filtra per categoria (i generici includono `tipo_intervento_id IS NULL`); `InterventiController::index()` valida `?sezione` su `CATEGORIE_LABEL` (default generici). Lista parametrica con view unica via query string, senza nuove rotte né view duplicate
- [DEV] Menu treeview in `layouts/admin.php` generato da `CATEGORIE_LABEL` (icone tools/water/droplet); fix ordinamento via `stopPropagation` sul click degli input di ricerca; CSS sottovoce treeview attiva in teal + bordo gruppo

## [0.16.0] - 2026-06-26

### Diario interventi

- [APP] Diario di note datate per ogni intervento: ogni voce ha data, testo e autore, così gli aggiornamenti progressivi (es. "15.06 vuotata → 20.06 in riempimento → 24.06 avviato") non si sovrascrivono più nel campo note unico. Caso d'uso: aperture piscine e lavori multi-visita
- [APP] Le note si aggiungono ed eliminano dalla modifica intervento (accanto ai materiali, così note e materiali si trascrivono insieme); nella scheda read-only il diario è visibile in sola lettura
- [DEV] Nuova tabella `interventi_note` (`intervento_id` FK CASCADE, `data_nota`, `testo`, campi standard); model `InterventiNoteModel` con `perIntervento()` (note recenti + autore via join su `personale`)
- [DEV] `InterventiController::aggiungiNota()` / `eliminaNota()` con sistema `from`; rotte POST `operativo/interventi/note/aggiungi` e `note/(:num)/elimina`

## [0.15.2] - 2026-06-25

### Fix aggiunta ricerca input con multi-search Datatables

- [APP] Filtri di secondo livello con multisearch Datatables, al momento solo in input semplice (no select)
- [DEV] Fix header separati per nome colonna e input di ricerca usando appendChild sul <tr> della table header

## [0.15.1] - 2026-06-25

### Fix filtro pills lista interventi + badge Extra

- [APP] Filtro pills interventi: tutte le pills ora funzionano correttamente — colonna filtro origine valorizzata sempre con `abbonamento` o `singolo` (prima stringa vuota per i non-abbonamento rendeva il filtro regex inaffidabile); aggiunto `searchable: true` alle colonne nascoste (DataTables 2.x disabilita la ricerca su colonne invisibili per default)
- [APP] Badge "Extra" nella lista interventi: visibile nella colonna Tipo/Priorità per le visite extra fuori piano
- [DEV] Fix `$prioritaBadge` in index interventi: chiave `programmato` → `abbonamento`

## [0.15.0] - 2026-06-24

### Abbonamenti — prossima visita, visite extra, pulizia fondo

- [APP] Next-by-scadenza: alla chiusura di un intervento con materiali "non portati", il sistema cerca automaticamente il prossimo intervento aperto dello stesso abbonamento (per `data_scadenza`) e riassegna i materiali direttamente su di esso invece di lasciarli sospesi sul cliente; se non esiste un prossimo univoco il comportamento precedente è preservato
- [APP] Visite extra: dalla scheda abbonamento si può creare un intervento aggiuntivo fuori piano (`extra = 1`); tipo intervento e priorità pre-compilati in readonly; sincronizzazione bidirezionale `data_pianificata` ↔ `data_scadenza` via JS; bottone visibile solo su abbonamenti attivi
- [APP] Flag pulizia fondo: ogni intervento generato eredita `pulizia_fondo` dal periodo; l'operatore può modificarlo al momento della chiusura (checkbox visibile solo per tipi con `ha_pulizia_fondo = 1`)
- [DEV] `InterventiModel::prossimoPerAbbonamento()`: restituisce il prossimo intervento aperto dell'abbonamento con `data_scadenza` successiva a quella corrente
- [DEV] `InterventiMaterialiModel::idsDaPortarePerIntervento()` + `assegnaAdIntervento()`: cattura e riassegna i materiali da portare al prossimo intervento
- [DEV] `AbbonamentiModel::generaInterventi()`: popola `pulizia_fondo` da `con_pulizia_fondo` del periodo alla generazione del batch
- [DEV] Migration `AddExtraAndPuliziaFondoToInterventi`: aggiunge `extra TINYINT(1) DEFAULT 0` e `pulizia_fondo TINYINT(1) DEFAULT 0` su `interventi`

## [0.14.1] - 2026-06-23

### Fix

- [DEV] DataTables 2.x: frecce di ordinamento centrate per le colonne Interventi e Zona in lista clienti — DataTables usa `flex-direction:row-reverse` su `div.dt-column-header` per le colonne numeriche; override a `row` via CSS per i `<th class="text-center">`

## [0.14.0] - 2026-06-23

### Abbonamenti

- [APP] Disdetta abbonamento: gli interventi figli ancora in `da_pianificare` vengono marcati `annullato` in transazione — nessun intervento "orfano" dopo la disdetta
- [APP] Lista clienti — badge "Interventi": conta solo gli interventi manuali aperti (`abbonamento_id IS NULL` e stato non completato/annullato); non più gonfiato dagli interventi da abbonamento
- [APP] Lista clienti — tooltip sulla colonna "Interventi": icona info spiega cosa conta il badge (inizializzazione Bootstrap tooltip nello script)
- [DEV] Fix `InterventiModel::normalizza()`: cambio da `!isset($data['id'])` a `!array_key_exists('id', $data)` — evita generazione duplicata del codice in bulk UPDATE dove `id` è presente ma `null`
- [DEV] `AbbonamentiController::cambiaStato()`: cambio stato abbonamento e operazione sugli interventi collegati avvolti in transazione `$db->transStart()`/`transComplete()` — rollback automatico se uno dei due fallisce

## [0.13.0] - 2026-06-21

### Viaggi — Foglio di viaggio giornaliero

- [APP] Vista "Viaggi": elenco di tutti gli interventi pianificati per il giorno selezionato, raggruppati per zona geografica con barre colorate (stessa palette dell'index clienti e del pool calendario)
- [APP] Navigazione per data: frecce precedente/successiva, selettore data e bottone "Oggi"
- [APP] Materiali "da portare" mostrati come lista puntata nella cella tipo/descrizione — quantità sempre visibile (es. "2×"), nessuna riga separata
- [APP] Bottone "PDF": genera un foglio di viaggio A4 landscape con dompdf; intestazione azienda, intestazione zona colorata, colonna Firma vuota; materiali come lista con bordo giallo a sinistra
- [APP] Bottone "Stampa" per stampa diretta della vista web (`@media print`)
- [DEV] `ViaggioController`: metodi `index()`, `pdf()` e `fetchGiornata()` privata condivisa tra i due; `dataValida()` per sanitizzazione formato data
- [DEV] dompdf 3.1.5 via Composer; view `pdf.php` HTML puro A4 landscape; margini via `padding` su `body` (la regola `@page` non viene applicata da dompdf)
- [DEV] `InterventiMaterialiModel::normalizza()`: al `$beforeInsert`, se `articolo_id` è presente e `descrizione` è vuota, copia automaticamente `articoli.descrizione` — elimina il JOIN su `articoli` da tutte le query sui materiali
- [DEV] Seeder `BackfillMaterialiDescrizione`: `UPDATE … JOIN` una-tantum per popolare `descrizione` sui record già esistenti (`php spark db:seed BackfillMaterialiDescrizione`)
- [DEV] Rotte `operativo/viaggi` (GET) e `operativo/viaggi/pdf` (GET) in gruppo; `operativo/calendario/genera-viaggio` (POST) reindirizza alla vista viaggi
- [DEV] `public/css/viaggio.css`: stili tabella e regole `@media print`

## [0.12.0] - 2026-06-21

### Calendario interventi

- [APP] Calendario interventi: griglia FullCalendar con viste giorno/settimana/mese; eventi colorati per tecnico assegnato
- [APP] Pool "Da pianificare": sidebar affiancata alla griglia con interventi non pianificati, raggruppati per zona geografica (barra colorata giallo/verde/blu corrispondente all'index clienti); sezioni collassabili per zona
- [APP] Drag & drop dal pool al calendario: apre modal di pianificazione con selezione tecnico e orario; salva `data_pianificata` e porta lo stato a `pianificato`
- [APP] Drag & drop degli eventi sul calendario: sposta data/ora dell'intervento con persistenza immediata
- [APP] Bottone × sugli eventi: annulla la pianificazione e riporta l'intervento nel pool (`da_pianificare`)
- [APP] Click sull'evento: modal dettaglio con tecnico, tipo, stato, descrizione, link a scheda intervento e modifica
- [APP] Filtro per tecnico: pill buttons nella barra sopra il calendario
- [APP] Elenco interventi: nuovi filtri "Da pianificare" (default), "Pianificati", "Completati", "Annullati" — rimosso il filtro generico "Aperti"
- [APP] Form modifica intervento: campo data pianificata include ora (datetime-local)
- [APP] Descrizione intervento obbligatoria (minimo 3 caratteri)
- [APP] Scheda cliente — colonna "Data pianificata": mostra anche l'orario
- [DEV] `CalendarioController`: `index()`, `eventi()` JSON per FullCalendar, `sposta()`, `generaViaggio()` placeholder v0.13.0
- [DEV] `InterventiController`: `pianifica()` e `annullaPianificazione()` via AJAX POST
- [DEV] FullCalendar 6 installato via npm; bundle global + locale IT in `public/assets/vendor/fullcalendar/`
- [DEV] `public/css/calendario.css`: layout flex sidebar+griglia, resize handle, pool card, dark mode FullCalendar via `--fc-*` CSS custom properties
- [DEV] Rotte `operativo/calendario` (GET/POST) e `operativo/interventi/(:num)/pianifica|annulla-pianificazione` (POST)
- [DEV] Validazione `data_pianificata` aggiornata a `valid_date[Y-m-d\TH:i]`
- [DEV] DataTables scheda cliente: `type:'string'` su colonne data per disabilitare auto-rilevamento tipo e ripristinare allineamento corretto
- [DEV] ANALISI.md: capitolo v0.15.0 Cantieri; nota architettura zone configurabili

## [0.11.3] - 2026-06-20

### Sospesi nel nuovo intervento + Annulla intervento + Descrizione + migliorie UX

- [APP] Form "Nuovo intervento": se il cliente ha materiali sospesi, appare una sezione con checkbox pre-selezionati — i sospesi selezionati vengono spostati sull'intervento al salvataggio
- [APP] Campo "Descrizione" aggiunto agli interventi (VARCHAR 255): visibile nel form di creazione/modifica, nella scheda intervento e nella tabella interventi della scheda cliente
- [APP] Bottone "Annulla intervento" nella scheda show: apre modale con conferma; i materiali da portare tornano automaticamente tra i sospesi del cliente con nota `[Da IV-XXXX]`
- [APP] Eliminazione intervento spostata dalla pagina edit alla pagina show (visibile solo quando annullato)
- [APP] Tipo intervento ora obbligatorio nel form (era facoltativo)
- [APP] Elenco interventi: filtri rapidi Aperti / Completati / Annullati / Tutti (default: Aperti)
- [APP] Scheda cliente — sospesi: quantità mostrata prima della descrizione articolo
- [APP] Scheda cliente — layout info-grid adattivo al contenuto (nessun allargamento forzato delle colonne)
- [DEV] Fix: `cliente_id` mancante nell'insert materiali da nuovo intervento causava FK error #1216
- [DEV] `InterventiController::annulla()`: nuovo metodo POST; guardia blocca edit di interventi annullati con redirect a show
- [DEV] Rotta `operativo/interventi/(:num)/annulla` (POST)
- [DEV] Endpoint AJAX `anagrafiche/clienti/(:num)/sospesi` (GET) — restituisce sospesi del cliente come JSON
- [DEV] `generaCodice()`: contatore atomico in `settings` con `SELECT FOR UPDATE` in transazione — nessun duplicato possibile con utenti concorrenti; sostituisce lettura da `information_schema` (inaffidabile su InnoDB)
- [DEV] Migration: colonna `descrizione` in `interventi`; seed `progressivo_interventi` in `settings`
- [DEV] "annullato" rimosso dal select stato nel form edit — impostabile solo tramite il bottone dedicato

## [0.11.2] - 2026-06-19

### Chiudi intervento + fix dark mode

- [APP] Pulsante "Chiudi intervento" nella scheda read-only di un intervento: apre modal con conferma; se ci sono materiali chiede se sono stati consegnati (Sì / No)
- [APP] Materiali non consegnati alla chiusura: tornano automaticamente tra i sospesi del cliente con nota `[Da INT-XXX]` per conservare traccia dell'intervento di origine
- [DEV] `InterventiController::chiudi()`: nuovo metodo POST con guardia di sicurezza su stati già chiusi
- [DEV] `InterventiMaterialiModel`: nuovi metodi `consegnaPerIntervento()` e `liberaPerIntervento()`
- [DEV] Rotta `operativo/interventi/(:num)/chiudi` (POST)
- [DEV] Rimosso `table-light` da tutti i `<thead>` del progetto — fix compatibilità dark mode Bootstrap 5
- [DEV] ID database visibile al passaggio del mouse su ogni riga/elemento nelle view di lista e dettaglio (debug)
- [DEV] Anchor `#pane-interventi` → `#sec-interventi` nel back link della scheda intervento

## [0.11.1] - 2026-06-19

### Redesign scheda cliente

- [APP] Scheda cliente: layout verticale scrollabile a sezioni (Anagrafica · Materiali da portare · Interventi) — sostituisce il precedente layout a tab Bootstrap
- [APP] Header compatto con back link, denominazione, badge attivo/inattivo e azioni (Modifica · Nuovo intervento)
- [APP] Nav anchor laterale sticky (visibile da ≥1400px) con highlight automatico della sezione visibile via IntersectionObserver
- [APP] Nuova pagina storico materiali `/clienti/{id}/materiali`: tutti i materiali del cliente (sospesi + legati a interventi) con group header per gruppo, codice intervento, data, badge stato e link all'edit; colonne Qtà prima di descrizione per simulare indentazione visiva
- [APP] Pulsante "Storico materiali" nella card sospesi della scheda cliente
- [DEV] `ClientiController::materiali()`: nuovo metodo; rotta `anagrafiche/clienti/(:num)/materiali`
- [DEV] `InterventiMaterialiModel::perCliente()`: aggiunto `i.stato AS stato_intervento` al select per la pagina storico
- [DEV] `MaterialiController`: anchor redirect aggiornato da `#pane-materiali` a `#sec-materiali`
- [DEV] `custom.css`: classi layout scheda cliente (`.section-anchor`, `.section-title`, `.info-grid`, `.info-item`, `.sospeso-row`, `.page-nav`); classi tabella storico (`.mat-group`, `.mat-group-sospesi`, `.mat-group-intervento`, `.mat-spacer` con `border-style: hidden` per sopprimere bordi adiacenti in border-collapse)

## [0.11.0] - 2026-06-18

### Materiali sospesi

- [APP] Scheda cliente — tab Materiali: sezione "Materiali da portare" con elenco sospesi (descrizione, quantità, note) e pulsante elimina
- [APP] Mini-form aggiunta rapida materiale sospeso nella scheda cliente: Tom Select (articolo da catalogo o testo libero), quantità, note
- [APP] Tom Select materiali: testo digitato forzato in maiuscolo mentre si scrive (CSS `text-transform`) e al salvataggio item libero
- [DEV] Migrazione `AddClienteIdToInterventiMateriali`: colonna `cliente_id` NOT NULL con FK CASCADE su `interventi_materiali`; `intervento_id` reso nullable (NULL = materiale sospeso, non ancora legato a un intervento)
- [DEV] `InterventiMaterialiModel`: `cliente_id` in `allowedFields`; nuovo metodo `sospesiPerCliente()`; `perCliente()` filtra su `intervento_id IS NOT NULL`; `normalizza()` usa `empty()` per nullificare `intervento_id`, `articolo_id` e `note`
- [DEV] `MaterialiController`: redirect differenziato dopo store/delete — se c'è `intervento_id` torna all'edit intervento, altrimenti alla scheda cliente `#pane-materiali`
- [DEV] `ClientiController::show()`: aggiunge `sospesi` e `articoliPerCat` alla view
- [DEV] `docs/schema.html`: schema DB completo creato (tutte le tabelle, relazioni, log modifiche per versione)

## [0.10.0] - 2026-06-18

### Materiali interventi e scheda cliente

- [APP] Campo priorità negli interventi: `programmato`, `normale`, `urgente` (sostituisce il vecchio campo genere)
- [APP] Stato materiale: `da portare` (default) / `consegnato` — visibile nella scheda intervento e nella scheda cliente
- [APP] Selezione materiali con Tom Select: autocomplete da catalogo articoli + testo libero in campo unico
- [APP] Form nuovo intervento: aggiunta materiali direttamente in fase di creazione, prima del salvataggio
- [APP] Form edit intervento: materiali inline sotto il form (eliminata la tab separata)
- [APP] Pagina show intervento: vista read-only con dati, materiali, note materiale e stato
- [APP] Scheda cliente — tab Materiali: elenco materiali raggruppati per intervento (DataTables rowGroup), con stato e link alla show intervento
- [DEV] `genere` rinominato `priorita` nella tabella `interventi`; migrazione con rimappatura `sopralluogo`/`commerciale` → `normale`
- [DEV] Migrazione `AddStatoToInterventiMateriali`: colonna `stato VARCHAR(20) DEFAULT 'da_portare'`
- [DEV] `InterventiMaterialiModel`: campo `articolo_id` nullificato se stringa vuota (fix FK constraint); costanti `STATO_DA_PORTARE`/`STATO_CONSEGNATO`; metodi `perIntervento()` e `perCliente()` con JOIN articoli e COALESCE descrizione
- [DEV] `MaterialiController`: `from` propagato nei redirect dopo store/delete per mantenere il contesto di navigazione
- [DEV] Tom Select dark mode: fix colore testo nell'input mentre si digita

## [0.9.0] - 2026-06-15

### Magazzino base

- [APP] Categorie articoli: CRUD mini in Impostazioni (lista + form inline + modal modifica), ordine configurabile con suggerimento automatico del prossimo numero
- [APP] Articoli: CRUD completo (codice obbligatorio, descrizione, categoria, unità di misura, costo acquisto, prezzo vendita, giacenza, attivo)
- [APP] Codice e descrizione articolo salvati sempre in maiuscolo
- [APP] Giacenza mostrata come intero nell'elenco (il DB mantiene DECIMAL per usi futuri)
- [APP] Voce "Articoli" aggiunta alla sidebar nella sezione Magazzino
- [APP] Card "Categorie Articoli" aggiunta alla pagina Impostazioni
- [DEV] Migrazione `categorie_articoli`: id, nome, ordine, created_by, updated_by, timestamp
- [DEV] Migrazione `articoli`: id, codice, descrizione, categoria_id (FK nullable), unità di misura, costo, vendita, giacenza, attivo, created_by, updated_by, timestamp
- [DEV] Migrazione `AddArticoloIdToInterventiMateriali`: `articolo_id` nullable con FK verso `articoli` (base per v0.10.0)
- [DEV] `CategorieArticoliModel`, `ArticoliModel`: callbacks `normalizza()`, metodi `tutteOrdinate()`, `elencoAttivi()`, `elencoCompleto()`, `perCategoria()`
- [DEV] `ArticoliController` (namespace `Magazzino`) e `CategorieArticoliController` (namespace `Impostazioni`) con sistema `from` completo
- [DEV] Eliminazione articolo bloccata se usato in `interventi_materiali`; eliminazione categoria bloccata se ha articoli collegati
- [DEV] CLAUDE.md: aggiunta sezione "Sistema di ritorno from" con flusso completo e pattern anti-open-redirect

## [0.8.0] - 2026-06-15

### Interventi

- [APP] CRUD interventi: lista, creazione e modifica con cliente, tecnico, genere, tipo, stato, data pianificata, data scadenza, durata stimata, urgenza, note
- [APP] Generi intervento: `programmato`, `normale`, `sopralluogo`, `commerciale`
- [APP] Tipi intervento configurabili: entità separata (`tipi_intervento`) con nome, icona FontAwesome e durata default — select nel form, durata auto-compilata al cambio selezione
- [APP] Stati intervento: `da_pianificare` (default), `pianificato`, `in_corso`, `completato`, `annullato`
- [APP] Materiali consegnati: aggiunta e rimozione materiali dalla scheda modifica intervento
- [APP] Creazione intervento da scheda cliente con cliente pre-selezionato; dopo salvataggio ritorno automatico al tab Interventi
- [APP] Scheda cliente — tab Interventi: DataTables con filtri rapidi (Aperti / Completati / Annullati / Tutti), badge urgenza, link diretto all'intervento
- [APP] Lista clienti: badge colorato con numero interventi associati (verde <5 · giallo 5–10 · rosso >10)
- [APP] Scheda cliente e personale: pagina `show` read-only separata da `edit`; azioni (Annulla · Elimina · Salva) nell'header della card
- [APP] Data pianificata: solo data nei form manuali; orario visibile nell'elenco per interventi pianificati da calendario (v0.9.0)
- [DEV] Migrazioni: `tipi_intervento`, `interventi`, `interventi_materiali`; `impianto_id` nullable come FK placeholder per v0.11.0
- [DEV] `InterventiModel`: costanti `GENERI_LABEL` / `STATI_LABEL`, metodi `elencoCompleto()`, `perCliente()`, `generaCodice()`
- [DEV] `TipiInterventoModel`, `InterventiMaterialiModel`: nuovi model con metodi dedicati
- [DEV] `InterventiController` in sottocartella `Operativo/`; rotte raggruppate in `$routes->group('interventi')`
- [DEV] `ClientiModel::elencoCompleto()`: subquery `num_interventi` per il badge lista
- [DEV] Sistema `from`: parametro GET/POST che porta il redirect post-azione all'URL di origine con fragment anchor; JS in scheda cliente attiva il tab corrispondente all'hash

## [0.7.0] - 2026-06-14

### Anagrafica clienti

- [APP] CRUD clienti: ragione sociale / nome+cognome, tipo (società/persona fisica), indirizzo operativo, CAP, città, provincia, nazione, telefono, email, P.IVA, C.F., note, contatti liberi
- [APP] Geocodifica automatica indirizzo via Nominatim (stesso pattern della sede aziendale)
- [APP] Distanza sede calcolata in linea d'aria (haversine) ad ogni salvataggio con coordinate valide
- [APP] Auto-assegnazione zona da longitudine: soglie Ventimiglia/Ceriale/Savona configurabili in Parametri; zona manuale ha la precedenza
- [APP] Scheda cliente a tab: Anagrafica (attiva), Interventi e Materiali (placeholder v0.8.0)
- [APP] Lista clienti con DataTables: ricerca testuale, ordinamento multi-colonna (Shift+click), paginazione
- [APP] Tipo cliente mostrato con icona (edificio/persona), zona con badge colorato — visivamente separati
- [APP] Codice contabilità (`codice_esterno`): collegamento con software esterno, affiancato alla denominazione nel form con tooltip descrittivo
- [APP] Denominazione e città sempre in maiuscolo al salvataggio
- [APP] Parametri — card "Zone geografiche clienti": soglie di longitudine configurabili per le tre zone operative
- [DEV] Migrazione `clienti`: nuova tabella con FK a `personale` e `users`, campi geocodifica, zona, distanza
- [DEV] Migrazione `AddCodiceEsternoToClienti`: colonna `codice_esterno VARCHAR(50)` aggiunta dopo `codice`
- [DEV] `ClientiModel`: callbacks `normalizza()` per created_by/updated_by, haversine, auto-zona, maiuscolo su denominazione e città
- [DEV] `geocoding.js`: script generico per geocodifica lato client via Nominatim, configurabile con attributi `data-*`
- [DEV] `ClientiController` in sottocartella `Anagrafiche/`; rotte raggruppate in `$routes->group('clienti')`
- [DEV] jQuery e DataTables (datatables.net + datatables.net-bs5) aggiunti via npm e pubblicati in `public/assets/vendor/`
- [DEV] Layout admin: sezione `styles` nell'`<head>` per CSS page-specific; tooltip Bootstrap inizializzati globalmente
- [DEV] Fix form delete annidato nel form update: risolto con attributo HTML `form="id"` sul bottone di submit

## [0.6.0] - 2026-06-14

### Profilo e changelog

- [APP] Modal novità: all'avvio mostra le modifiche dell'ultima versione rispetto all'ultima visita dell'utente
- [APP] Voce "Changelog" nel dropdown navbar: mostra la storia completa delle versioni
- [APP] Changelog filtrato per ruolo: admin e developer vedono anche le righe `[DEV]`, gli altri solo `[APP]`
- [APP] Voce "Profilo" nel dropdown utente collegata alla scheda dipendente dell'utente loggato
- [APP] Pannello utente nella sidebar: nome, ruolo e link diretto al profilo
- [APP] Restyling palette: navbar blu medio, sidebar-brand scura con separatore teal, voci active aggiornate al nuovo schema colori
- [APP] Numero versione corrente visualizzato nel footer
- [DEV] Migrazione: colonna `ultima_versione_vista` su tabella `users`
- [DEV] `UserModel`: override `initialize()` per aggiungere `ultima_versione_vista` agli `allowedFields`
- [DEV] `Auth.php`: usa `App\Models\UserModel` invece del modello di Shield
- [DEV] Helper `changelog_helper`: `changelog_to_html()` e `changelog_data()` per parsing server-side del `CHANGELOG.md`, filtro per ruolo
- [DEV] `ProfiloController`: `index()` redirige alla scheda dipendente; `versioneVista()` aggiorna la versione via AJAX
- [DEV] `BaseController`: caricamento automatico dell'helper `changelog`

## [0.5.0] - 2026-06-14

### Parametri generali

- [APP] Pagina Parametri Generali: dati sede (nome, indirizzo, CAP, città, telefono, sito, lat/lng)
- [APP] Geocodifica automatica dell'indirizzo sede via Nominatim/OpenStreetMap (nessuna API key)
- [APP] Orari aziendali configurabili: apertura/chiusura giornata e pausa pranzo
- [APP] Durate standard interventi per tipo (sale, filtri, piscine, addolcitori, acquedotti, commerciale) con visualizzazione live ore/minuti
- [APP] Upload logo aziendale con anteprima; logo mostrato nella sidebar con fallback testo
- [DEV] Package `codeigniter4/settings` (DatabaseHandler): settings key/value in tabella `settings`
- [DEV] `GeneraleController`: metodi `parametri()`, `salvaParametri()`, `cambiaLogo()`
- [DEV] Fix form logo separato dal form principale tramite attributo HTML `form="id"` (evita form annidati)
- [DEV] Palette CSS: variabile `--clr-top-bar` usata su navbar, sidebar-brand e voce attiva menu per cambio colore centralizzato

## [0.4.0] - 2026-06-13

### Anagrafica personale

- [APP] CRUD personale completo: lista, creazione, modifica, eliminazione dipendente
- [APP] Creazione account Shield contestuale al dipendente (username, email, password, gruppi)
- [APP] Color picker profilo: slider hue continuo + swatches predefiniti; colori già assegnati mostrati come disabilitati
- [APP] Voce attiva evidenziata nella sidebar in base all'URL corrente
- [APP] Toggle mostra/nascondi password su tutti i campi di tipo password
- [DEV] Migrazione `personale`: nuova tabella con FK a `users`, rimozione campi anagrafica da `users`
- [DEV] `PersonaleModel`: callbacks `normalizza()` per `created_by`/`updated_by`, metodi `elencoCompleto()`, `coloriUsati()`
- [DEV] Costante `PASTELLI` in `PersonaleController` come unica sorgente della palette colori
- [DEV] Docblock `@var` in tutte le view per eliminare falsi positivi Intelephense
- [DEV] Fix CSS: `--bs-primary`, `btn-primary`, `card-primary`, stato attivo sidebar
- [DEV] Toggle password riscritto in vanilla JS (AdminLTE 4 non ha jQuery)
- [DEV] Fix form delete annidato in form update (bug: il browser ignorava il form interno)
- [DEV] ANALISI.md: clienti spostati in v0.5.0, versioni successive scalate

## [0.3.0] - 2026-06-12

### Impostazioni — index e navbar

- [APP] Navbar mostra nome/cognome dell'utente loggato (fallback su username)
- [APP] Link "Profilo" e "Esci" funzionanti nel dropdown utente
- [APP] Pagina indice impostazioni con card a icone centrate
- [DEV] Fix icone Bootstrap Icons nelle card (doppio attributo `class` rimosso)
- [DEV] Layout card impostazioni con utility Bootstrap flex (compatibile con AdminLTE 4)
- [DEV] CSS: `settings-icon` ridimensionato a 2.5rem, bordo sidebar-brand a 1px teal

## [0.2.0] - 2026-06-12

### Autenticazione (Shield)

- [APP] Login con username e password, logout, "Ricordami"
- [APP] Pagina di login con layout dedicato (gradiente blu/teal, card centrata)
- [DEV] Installazione e configurazione CodeIgniter Shield
- [DEV] Migrazioni Shield: tabelle `users`, `auth_identities`, `auth_logins` e correlate
- [DEV] Configurazione login per username (override validazione Shield che usa email di default)
- [DEV] Gruppi utente: `admin`, `ufficio`, `developer`, `tecnico`, `clienti`
- [DEV] Filter `session` globale con esclusione rotte auth (protezione tutte le rotte)
- [DEV] Filter `noauth` su `/login` (redirect dashboard se già autenticati)
- [DEV] Seeder `AdminSeeder` per creazione utente admin iniziale
- [DEV] Fix attivazione utente nel seeder (`active = 1`)
- [DEV] Override `Validation::$login` in `app/Config/Validation.php`

## [0.1.0] - 2026-06-10

### Inizializzazione progetto

- [DEV] Setup CodeIgniter 4.7.3
- [DEV] Integrazione AdminLTE 4.0.2 con Bootstrap 5.3.8
- [DEV] Pipeline asset: npm + comando Spark `assets:publish`
- [DEV] Layout admin con sidebar, navbar, dark mode, flashdata
- [DEV] Localizzazione italiana completa
- [DEV] Dashboard base
