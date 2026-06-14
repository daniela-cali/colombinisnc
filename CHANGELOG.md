# Changelog — Colombini SNC Gestionale

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
- [DEV] Gruppi utente: `admin`, `staff`, `tecnici`, `clienti`
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
