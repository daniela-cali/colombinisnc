# 2. Architettura

## 2.1 Stack

| Componente | Versione | Ruolo |
|---|---|---|
| PHP | 8.2+ | linguaggio |
| CodeIgniter | 4.7 | framework MVC |
| CodeIgniter Shield | 1.3 | autenticazione, gruppi e permessi |
| MySQL | 8.x | database (le CTE ricorsive richiedono la 8) |
| dompdf | 3.1 | generazione dei PDF |
| AdminLTE | 4.0.2 | tema di amministrazione |
| Bootstrap | 5.3.8 | framework CSS, incluso in AdminLTE |

Sul lato browser: FullCalendar 6 per il calendario, Leaflet per le mappe, DataTables 2
per le liste, Tom Select per gli autocomplete, jQuery (richiesto da DataTables),
Font Awesome e Bootstrap Icons per le icone.

Il sistema gira su un singolo server: circa dieci utenti in contemporanea, nessuna
esigenza di scalabilità orizzontale. Il target di produzione è Nginx su VPS con il dominio
`colombini.metesoftware.it`.

> **I domini in gioco sono due.** `colombini.metesoftware.it` è l'indirizzo del gestionale,
> su una VPS dedicata, ed è il valore di `app.baseURL` in produzione. `colombini-snc.it` è
> il sito dell'azienda, ospitato altrove, e non va usato come indirizzo del gestionale.

## 2.2 Nessun bundler, di proposito

Non c'è Vite né Webpack. Le dipendenze frontend si installano con npm e vengono copiate
in `public/assets/vendor/` dal comando Spark `php spark assets:publish`
(`app/Commands/AssetsPublish.php`), che legge un manifest e copia le cartelle `dist/`
dei pacchetti.

La scelta è deliberata: per un progetto di questa dimensione la configurazione di un
bundler costerebbe più di quanto renda. Il flusso è quindi:

```bash
npm install                  # dopo ogni modifica delle dipendenze
php spark assets:publish     # copia i file in public/assets/vendor/
```

In produzione i file pubblicati sono già committati nel repository, quindi il comando non
serve al deploy. AdminLTE 4 non dipende da jQuery — è una riscrittura su Bootstrap 5 —
ma jQuery resta comunque installato perché DataTables lo richiede.

## 2.3 Organizzazione del codice

La struttura segue quella standard di CodeIgniter 4, con i controller raggruppati in
sottocartelle che rispecchiano le sezioni del menu.

```
app/
  Cells/         componenti di vista riusabili (campanella avvisi, modal promemoria)
  Commands/      comandi Spark (assets:publish, batch:abbonamenti-scaduti)
  Config/        configurazione: Routes, AuthGroups, Filters, Autoload
  Controllers/
    Anagrafiche/   ClientiController, PersonaleController
    Impostazioni/  GeneraleController, TipiInterventoController,
                   CategorieArticoliController, UtentiController, ImportClientiController
    Magazzino/     ArticoliController
    Operativo/     InterventiController, CalendarioController,
                   ViaggioController, MaterialiController
    AbbonamentiController, CantieriController, DashboardController,
    PromemoriaController, ProfiloController
  Database/Migrations/   una migration per modifica di schema
  Helpers/       acl, changelog, colore, custom_log
  Libraries/     ClientiAdhocImporter (import CSV)
  Models/        un model per tabella, più la logica di dominio
  Views/
    help/        una guida contestuale per sezione
    layouts/     admin.php (layout principale), auth.php
public/
  css/           custom.css più un file per le sezioni con molte regole
  js/            calendario.js, search-bar.js, mappa-posizione.js, geocoding.js, ...
  assets/vendor/ dipendenze npm pubblicate
docs/            analisi, schema DB, specifiche, questo manuale
tools/manuale/   generatore del manuale
```

Ogni classe controller termina con il suffisso `Controller`, comprese quelle nelle
sottocartelle.

## 2.4 Rotte

`app/Config/Routes.php` raggruppa sempre le rotte per sezione con `$routes->group()`.
I gruppi principali corrispondono alle voci di menu: `impostazioni`, `anagrafiche`
(con i sottogruppi `personale` e `clienti`), `abbonamenti`, `cantieri`, `operativo`
(interventi, calendario, viaggi, materiali), `magazzino`, `promemoria`, `profilo`.

Dentro un gruppo, le rotte in sola lettura e quelle che scrivono possono avere protezioni
diverse: il pattern usato è un sottogruppo con prefisso vuoto e un filtro di permesso.

```php
$routes->group('cantieri', function ($routes) {
    // lettura: basta essere autenticati
    $routes->get('/',      'CantieriController::index');
    $routes->get('(:num)', 'CantieriController::show/$1');

    // scrittura: serve il permesso
    $routes->group('', ['filter' => 'permission:cantieri.manage'], function ($routes) {
        $routes->post('store',         'CantieriController::store');
        $routes->post('(:num)/delete', 'CantieriController::delete/$1');
    });
});
```

L'ultima riga del file è `service('auth')->routes($routes)`, che registra le rotte di
Shield. Le due rotte di login vengono dichiarate **prima** di quella chiamata proprio per
sovrascrivere quelle predefinite e applicarvi il filtro `noauth` (vedi 2.10).

Convenzione degli URL, uniforme in tutto il progetto: `/` per l'elenco, `nuovo` e `store`
per la creazione, `(:num)` per la scheda in sola lettura, `(:num)/edit` e `(:num)/update`
per la modifica, `(:num)/delete` per l'eliminazione. Le sotto-risorse usano un segmento
proprio: `(:num)/posizione`, `note/aggiungi`, `assenze/(:num)/elimina`.

## 2.5 Come si dividono i compiti fra controller e model

La regola è netta e vale in tutto il progetto: **le normalizzazioni dei dati stanno nel
model, non nel controller**. Cast, conversione delle stringhe vuote in `NULL`, maiuscole,
valori di default, autore della modifica: tutto dentro un metodo `normalizza()` agganciato
ai callback `$beforeInsert` e `$beforeUpdate`.

Il controller resta sottile:

```php
$model->insert($this->request->getPost());
// oppure, per i campi che devono essere decisi dal server:
$model->insert(array_merge($this->request->getPost(), ['stato' => 'proposta']));
```

Non esistono metodi `campiDaRequest()` né array di campi scritti uno per uno nei
controller. Il vantaggio pratico è che ogni scrittura verso una tabella passa dallo stesso
punto, da qualunque parte dell'applicazione arrivi.

Nei metodi dei model si usa sempre il query builder del model (`$this->select(...)`) e
non `$this->db->table(...)`: così i timestamp e i callback del model restano attivi.
Le query grezze (`$db->query()`) sono ammesse solo dove il query builder non arriva —
in pratica solo `ClientiModel::relazioniBloccanti()`, che interroga `information_schema`,
e il contatore atomico dei codici intervento.

## 2.6 View e layout

Le view estendono `app/Views/layouts/admin.php`, che si occupa di sidebar, navbar,
tema chiaro/scuro, messaggi flash, campanella degli avvisi e coda dei modal automatici.

Ogni view si apre con un blocco PHP che dichiara le variabili ricevute dal controller:

```php
<?php
/**
 * @var array  $cliente
 * @var array  $interventi
 * @var string $from
 */
$this->extend('layouts/admin');
?>
```

Non è formalità: elimina i falsi positivi dell'analisi statica e documenta il contratto
fra controller e view, che altrimenti resterebbe implicito.

**I messaggi flash `success`, `error` e `warning` li gestisce già il layout.** Ripeterli
nella singola view produce il messaggio doppio. Nella view va incluso solo `errors`
(plurale), la lista degli errori di validazione, che il layout non tratta.

**Il CSS sta in `public/css/custom.css`.** Niente `<style>` nelle view e niente attributi
`style=` per regole riutilizzabili. Le sezioni con molte regole proprie hanno un file
dedicato (`calendario.css`, `dashboard-tecnico.css`) caricato dalla view con
`section('styles')`.

Lo stesso vale per il JavaScript: quando cresce, esce dalla view. Il calendario ha circa
650 righe in `public/js/calendario.js`, con i dati che arrivano da PHP raccolti in un
unico oggetto `window.CalendarioConfig` invece che interpolati in mezzo allo script.
Gli script condivisi sono dichiarativi, configurati da attributi `data-*` nel markup:
`search-bar.js` (filtri a pillole e a tendina), `currency-input.js` (formattazione
valuta), `mappa-posizione.js` (mappa Leaflet con correzione del pin), `geocoding.js`.

## 2.7 Il sistema di ritorno "from"

Molti form sono raggiungibili da più punti: la scheda cliente, l'elenco, la scheda
cantiere. Il parametro `from` riporta l'utente da dove era partito dopo il salvataggio.

Il flusso è sempre lo stesso: il link di apertura passa `?from=URL%23ancora`; il
controller lo legge con `getGet('from')` e lo passa alla view; la view lo inserisce come
campo nascosto in **ogni** form della pagina, subito dopo `csrf_field()`; il bottone
Annulla lo usa come destinazione. Dopo la scrittura il controller lo rilegge dal POST e
lo valida:

```php
$from = $this->request->getPost('from');
$dest = ($from && str_starts_with($from, base_url())) ? $from : 'fallback/url';
return redirect()->to($dest)->with('success', '...');
```

Il controllo con `str_starts_with()` non è cosmetico: senza, il parametro diventerebbe un
open redirect verso un dominio esterno.

Se il `from` contiene un'ancora e la pagina di destinazione usa i tab Bootstrap, la view
di destinazione attiva anche il tab corrispondente leggendo `location.hash`.

## 2.8 Helper, view cell e comandi

Quattro helper in `app/Helpers/`:

- **`acl_helper`** — `is_solo_tecnico()`, la funzione che distingue il tecnico puro da chi
  ha anche un ruolo di gestione. Caricato globalmente da `Autoload.php`.
- **`changelog_helper`** — legge `CHANGELOG.md` e lo converte in HTML, filtrando le righe
  `[DEV]` per chi non è sviluppatore.
- **`colore_helper`** — `colore_testo()` sceglie con la formula YIQ se scrivere in nero o
  in bianco sopra un colore di sfondo arbitrario. Serve per i colori profilo dei
  dipendenti, che l'utente sceglie liberamente.
- **`custom_log_helper`** — `custom_log($contesto, $messaggio)` scrive in
  `writable/custom_log/<contesto>/AAAAMMGG.log`, un file al giorno per contesto. Nato per
  il batch degli abbonamenti scaduti ma scritto per essere generico.

Due view cell in `app/Cells/`: `AvvisiCell` (la campanella in navbar, predisposta per
aggregare notifiche future oltre ai promemoria) e `PromemoriaOggiCell` (il modal dei
promemoria di giornata).

Due comandi Spark: `assets:publish` e `batch:abbonamenti-scaduti` (capitolo 6.7).

## 2.9 Ambiente di sviluppo

Il server di sviluppo è quello integrato di PHP:

```bash
php -S 0.0.0.0:8081 -t public
```

Con `0.0.0.0` l'applicazione è raggiungibile anche dallo smartphone sulla stessa rete
Wi-Fi, indispensabile per provare le schermate mobili. Per questo `app.baseURL` nel file
`.env` punta all'indirizzo LAN del PC e non a `localhost`: `base_url()` genera link
assoluti basandosi su quel valore fisso, non sull'host della richiesta. Se l'indirizzo IP
cambia, va aggiornato.

`dd()` funziona: CodeIgniter include una copia di Kint nel framework, attiva
automaticamente in ambiente `development`.

## 2.10 Due trappole dell'ambiente, già risolte

Vale la pena conoscerle: sono costate tempo e potrebbero ripresentarsi.

**L'hot-reload della Debug Toolbar blocca `php -S`.** La rotta `__hot-reload` dello
scaffolding standard apre una connessione SSE che il browser tiene aperta finché la scheda
resta aperta. Il server integrato di PHP serve **una richiesta alla volta per l'intero
processo**: una sola di quelle connessioni appese blocca tutto, da qualunque scheda o
browser, senza alcun errore nei log. Il sintomo è il sito che "si pianta" dal nulla mentre
il processo PHP risulta vivo. La rotta è stata rimossa da `app/Config/Events.php`. Se un
domani servisse riattivarla, serve un server che gestisca richieste concorrenti — mai
`php -S`.

**Il doppio login lanciava un'eccezione di Shield.** Una scheda con il form di login
lasciata aperta mentre la sessione era già autenticata mandava il POST direttamente a
`loginAction()`, che rispondeva con `LogicException: The user has User Info in Session`.
Il filtro `NoAuth` era applicato solo al `GET login`, mentre il `POST login` era quello
registrato da Shield. Ora `Routes.php` sovrascrive entrambi.

## 2.11 Convenzioni di lavoro

Sono raccolte in `CLAUDE.md` alla radice del repository, che è la fonte autorevole. Le
principali:

- **Niente `ENUM`.** I flag booleani sono `TINYINT`; gli stati con più valori sono
  `VARCHAR` con le costanti nel model e un commento che elenca i valori ammessi. Così
  aggiungere un valore non richiede un `ALTER TABLE` (capitolo 3.1).
- **Docblock su ogni metodo** di controller e model, che spieghi cosa fa e perché, senza
  ripetere i tipi già dichiarati nella firma.
- **Un file di guida per sezione** in `app/Views/help/`, non uno per singola pagina.
- **Commit numerati**: `v0.4.0 — Descrizione breve`, con l'em dash come separatore.
- **`CHANGELOG.md` e `ANALISI.md` §7.1 si aggiornano nella stessa commit** della modifica
  che documentano; lo stesso vale per `docs/schema.html` rispetto alle migration.
- **Niente valori PHP dentro gli attributi JavaScript inline.** Un `$var['chiave']` scritto
  dentro un `onclick="..."` funziona a runtime ma manda in confusione l'analizzatore
  dell'editor, perché gli apici della chiave collidono con i due livelli di virgolette che
  la racchiudono. Si estrae prima il valore in una variabile semplice.
