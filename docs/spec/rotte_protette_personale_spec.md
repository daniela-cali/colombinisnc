# Spec — Rotte protette per gestione personale e privilege escalation dal profilo

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale del progetto (Colombini SNC Gestionale). Questo documento copre solo la funzionalità descritta qui sotto.

## 1. Contesto e problema (idee.txt punto 10.R)

Domanda aperta in `docs/spec/idee.txt`: *"I tecnici dal loro profilo non si possono assegnare altri diritti e non possono creare un nuovo dipendente — rotte protette?"*

Verifica fatta sul codice attuale: **nessuna rotta è protetta lato server**. `app/Config/Filters.php` applica solo il filtro globale `session` (verifica il login, non il gruppo). Shield registra già gli alias `group` e `permission` (`GroupFilter`/`PermissionFilter`), ma non sono usati in nessuna route del progetto.

Conseguenza concreta: `ProfiloController::index()` reindirizza l'utente loggato a `anagrafiche/personale/{id}/edit` — cioè **lo stesso form/controller** che l'admin usa per gestire qualunque dipendente. La view (`app/Views/anagrafiche/personale/edit.php`, righe 116-134) mostra i checkbox `gruppi[]` con tutti i gruppi disponibili (admin, developer, ufficio, tecnico), precompilati ma liberamente modificabili. `PersonaleController::update()` (righe 219-229) legge `getPost('gruppi')` e applica `addGroup`/`removeGroup` senza controllare chi sta chiamando.

Due problemi distinti, non uno:

1. **Privilege escalation**: un tecnico che apre "Profilo" può selezionare "Amministratore"/"Sviluppatore" e salvare.
2. **IDOR**: la rotta `anagrafiche/personale/(:num)/edit` non verifica che l'`id` nell'URL corrisponda al dipendente collegato all'utente loggato — un tecnico può cambiare l'ID e modificare/eliminare la scheda di un altro dipendente, admin incluso.

Bonus, non affrontato qui: la matrice permessi Shield in `AuthGroups.php` (`users.create`, `users.edit`, ecc.) è configurata ma non viene mai controllata da nessun controller — è morta, non un vero enforcement. Non la usiamo in questa spec: usiamo direttamente i **gruppi** (`inGroup()`/filtro `group:`), coerente con `PersonaleController::soloGestoriAssenze()` già esistente, che fa lo stesso controllo per le assenze.

## 2. Cosa esiste già e va solo esteso a livello server

`app/Helpers/acl_helper.php::is_solo_tecnico()` (righe 13-23) determina se l'utente è **solo** tecnico (gruppo `tecnico` e nessuno tra `admin`/`developer`/`ufficio`). `app/Views/layouts/admin.php` (righe 147, 185) usa questo helper per **nascondere** dal menu le voci "Personale" e tutta la sezione "Amministrazione" (Impostazioni) a chi è solo tecnico.

Quindi l'intento del progetto era già chiaro lato UI: queste sezioni sono per staff (admin/developer/ufficio), non per i tecnici. Manca solo l'enforcement lato server — se un tecnico digita l'URL direttamente, oggi ci entra comunque.

## 3. Decisioni di design

**3.1 — Permessi granulari (non gruppi diretti) sulle rotte, non controlli duplicati nei controller.**
Invece di un filtro `group:...` diretto, si introducono due permessi Shield nuovi in `AuthGroups.php`:

- `personale.manage` — gestione anagrafica personale (creazione/modifica/eliminazione dipendenti, assegnazione gruppi).
- `impostazioni.manage` — accesso alla sezione Impostazioni.

Assegnati nella `$matrix` a `admin`, `developer` e `ufficio` (stessi tre gruppi già ammessi oggi dal menu tramite `is_solo_tecnico()` — nessun cambiamento di comportamento percepito). `tecnico` e `cliente` restano senza questi permessi.

Perché permessi e non un filtro `group:admin,developer,ufficio` diretto: con i gruppi il controllo è tutto-o-niente e serve a ricordarsi a mano l'elenco dei gruppi ovunque venga ripetuto; con i permessi la lista dei gruppi autorizzati vive in un solo posto (`AuthGroups::$matrix`) e in futuro si può dare `personale.manage` a un solo gruppo specifico, o creare permessi più fini (`personale.delete`, `personale.assign-admin`, ecc.) senza toccare le rotte — si aggiornerebbe solo la matrice. Per ora la granularità richiesta è "staff sì/no", quindi due soli permessi bastano; non si introducono permessi più fini di questi perché non c'è ancora un caso d'uso concreto che li richieda (vedi §6).

Le rotte usano il filtro `permission:...` (alias già registrato da Shield tramite `Registrar.php`, stesso meccanismo di `group:...` ma verifica `$user->can($permesso)` invece di `$user->inGroup(...)`).

I permessi Shield scaffoldati esistenti (`admin.access`, `admin.settings`, `users.manage-admins`, `users.create`, `users.edit`, `users.delete`) vengono **rimossi**, non lasciati morti accanto ai nuovi: verificato che non compaiono in nessun controller/filtro/view di `app/`, quindi tenerli sarebbe solo un elenco di permessi "finti" che confonderebbe chi legge la config in futuro (sembrerebbero attivi, ma `can()` verifica solo la matrice — nessuna rotta li richiede). Stesso discorso per `beta.access`, presente solo nella matrice e mai in `$permissions`.

Non si aggiungono controlli `can()`/`inGroup()` manuali dentro `PersonaleController` o `UtentiController` — sarebbe un guard difensivo duplicato per simmetria con quello già fatto dal filtro di route, che il progetto preferisce evitare (vedi nota già in memoria su questo punto). Il filtro sulla route è l'unico punto di enforcement per queste sezioni, esattamente come già avviene per `noauth` sul login.

**3.2 — Il profilo personale diventa una view/rotta separata, non più un redirect al form admin.**
`ProfiloController::index()` oggi reindirizza a `anagrafiche/personale/{id}/edit`. Con il filtro del punto 3.1, un tecnico non potrebbe più nemmeno raggiungere quella rotta — il redirect diventerebbe un vicolo cieco (`group_denied` → dashboard con errore). Serve quindi una vista/form dedicata al "mio profilo", accessibile a **tutti** gli utenti loggati (nessun filtro di gruppo sulla route `profilo/*`), che:

- mostra solo i dati anagrafici propri (nome, cognome, telefono, colore) ed email/password del proprio account;
- **non mostra affatto** la sezione gruppi (non nascosta via CSS: proprio rimossa dalla view e mai letta dal controller);
- non ha il bottone "Elimina".

**3.3 — Nessun parametro `id` in URL o POST per il profilo: l'owner è sempre risolto lato server.**
`ProfiloController::update()` (nuovo metodo) non riceve né fida di un `id`: risolve sempre il proprio dipendente con `PersonaleModel::perUtente(user_id())`, esattamente come fa oggi `index()`. Questo elimina l'IDOR per costruzione su questo flusso — non c'è un ID da manomettere nell'URL o nel form. Combinato con 3.1 (l'admin form resta dietro al filtro di gruppo), il dipendente di un altro utente non è più raggiungibile da un tecnico né passando per "Profilo" né digitando `anagrafiche/personale/{altro-id}/edit`.

`PersonaleController::edit()`/`update()` restano invariati: continuano a gestire anche i gruppi, ma sono usati solo da admin/developer/ufficio grazie al filtro di route — nessuna modifica di codice a questo controller.

## 4. Modifiche da implementare

1. **`app/Config/Routes.php`**
   - Il gruppo `anagrafiche/personale` (righe 49-59) riceve `['filter' => 'permission:personale.manage']` come secondo parametro di `$routes->group(...)`.
   - Il gruppo `impostazioni` (righe 16-45) riceve `['filter' => 'permission:impostazioni.manage']`.
   - Il gruppo `profilo` (righe 8-11) resta senza filtro di permesso (solo il filtro globale `session`, già applicato a tutte le rotte). Aggiunta rotta `$routes->post('aggiorna', 'ProfiloController::update');`.

2. **`app/Controllers/ProfiloController.php`**
   - `index()`: non reindirizza più. Recupera `$persona = (new PersonaleModel())->perUtente(user_id())`; se assente, redirect a `/` con errore (comportamento invariato in questo caso). Altrimenti passa alla view `profilo/index` gli stessi dati che oggi passa `PersonaleController::edit()` **tranne** `gruppi`/`gruppi_correnti`: `persona`, `user`, `email`, `pastelli`, `colori_usati`.
   - Nuovo metodo `update()`: stesse regole di validazione di `PersonaleController::update()` (nome, cognome, telefono, colore, email se account presente, password opzionale) **meno** la regola `gruppi`. Risolve il proprio `$persona` via `perUtente(user_id())` (mai da input esterno). Aggiorna `PersonaleModel` ed eventualmente email/password sull'entity Shield, stesso pattern già presente in `PersonaleController::update()` (righe 208-235) ma senza toccare `getGroups()`/`addGroup()`/`removeGroup()` in alcun modo. Redirect a `profilo` con flashdata `success`.
   - `versioneVista()`: invariato.

3. **`app/Views/profilo/index.php`** (nuovo file)
   - Adattamento di `app/Views/anagrafiche/personale/edit.php`: stessa struttura di card/form, action su `profilo/aggiorna`.
   - Breadcrumb semplificato (Home → Il mio profilo, niente link a "Personale" dato che il tecnico non può aprirlo).
   - Nessun bottone "Elimina".
   - Nessuna sezione "Gruppi" — rimossa del tutto, non solo nascosta.
   - Riusa il partial `anagrafiche/personale/_colore_picker` per la scelta colore (nessun dato sensibile: solo hex già in uso da altri dipendenti).

4. **`app/Config/AuthGroups.php`**
   - `$permissions` (righe 74-81): sostituire l'intero blocco di permessi Shield scaffoldati e mai usati (`admin.access`, `admin.settings`, `users.manage-admins`, `users.create`, `users.edit`, `users.delete`) con le due sole voci nuove: `'personale.manage' => 'Può gestire anagrafica personale e account'`, `'impostazioni.manage' => 'Può accedere alle impostazioni applicative'`. Verificato che nessuna di quelle voci scaffoldate è referenziata altrove in `app/` (né controller né filtri né view) — sono sicure da rimuovere, non solo da lasciare inutilizzate.
   - `$matrix` (righe 91-111): ogni gruppo elenca solo i permessi che gli servono davvero, niente `beta.access` residuo:
     ```php
     public array $matrix = [
         'admin'     => ['personale.manage', 'impostazioni.manage'],
         'developer' => ['personale.manage', 'impostazioni.manage'],
         'ufficio'   => ['personale.manage', 'impostazioni.manage'],
         'tecnico'   => [],
         'cliente'   => [],
     ];
     ```

5. **`app/Controllers/Anagrafiche/PersonaleController.php`**: nessuna modifica.
6. **`app/Helpers/acl_helper.php`**: nessuna modifica — `is_solo_tecnico()` resta per l'uso già esistente (nascondere voci di menu, incluse anche Abbonamenti/Cantieri/Magazzino che restano fuori da questa spec); ora è ridondante rispetto al filtro di route su personale/impostazioni ma coerente con esso.

## 5. Comportamento atteso dopo la modifica

- Un tecnico che digita `anagrafiche/personale`, `anagrafiche/personale/nuovo`, `.../{id}/edit`, `impostazioni`, `impostazioni/utenti-app/...` viene rediretto a `/` con l'errore standard Shield "non hai privilegi sufficienti" (`permission_denied`, configurato su `/` in `app/Config/Auth.php` riga 81 — nessuna modifica necessaria lì).
- Un tecnico che apre "Profilo" vede e modifica solo i propri dati anagrafici ed email/password, senza alcuna possibilità di toccare i gruppi o la scheda di qualcun altro.
- Admin/developer/ufficio: nessun cambiamento percepito, continuano a usare `anagrafiche/personale/*` e `impostazioni/*` esattamente come oggi — hanno tutti e due i nuovi permessi.

## 6. Esplicitamente fuori scope

- **Permessi più granulari** (es. `personale.delete` solo admin, un permesso separato per assegnare gruppi admin/developer): per ora `personale.manage`/`impostazioni.manage` sono binari (ce l'hai o no), coprendo l'intera sezione. Non c'è ancora un caso d'uso concreto per differenziare admin/developer/ufficio tra loro — se emerge, si aggiungono permessi più fini aggiornando solo `AuthGroups.php` e i filtri di route interessati, senza toccare i controller.
- **Audit delle altre sezioni** (`magazzino`, `cantieri`, `abbonamenti`, `operativo/*`): probabilmente hanno lo stesso problema di fondo (nessuna rotta protetta), ma non è il perimetro di questo punto — da valutare come task separato se si vuole un audit generale.
- **IDOR su altre entità** (clienti, interventi, ecc.): non toccato qui.
- **Test automatici**: nessuna suite di test esiste nel progetto per queste rotte; verifica manuale post-implementazione (login come tecnico, provare le URL dirette).
- **Test automatici**: nessuna suite di test esiste nel progetto per queste rotte; verifica manuale post-implementazione (login come tecnico, provare le URL dirette).
