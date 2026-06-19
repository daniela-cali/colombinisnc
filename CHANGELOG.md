# Changelog — Colombini SNC Gestionale

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
