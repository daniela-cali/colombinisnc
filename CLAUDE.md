# Progetto Colombini SNC

## Preferenze generali
- Rispondere sempre in italiano, anche dopo compattazioni del contesto.
- Se dei file sono stati dimenticati nell'ultimo commit, usare `git commit --amend --no-edit` invece di un nuovo commit separato (commit atomici e puliti).
- Le preferenze e regole di progetto vanno sempre in questo file `CLAUDE.md` (non nel sistema di memoria), così possono essere pushate e condivise.
- **Review del codice**: creare i file direttamente con Write/Edit e lasciare che l'utente approvi i diff nell'IDE. Non mostrare l'intero file o blocchi lunghi di codice in chat — la spiegazione descrive le modifiche a parole, non ripete il codice verbatim.
- **Spiegazioni passo per passo**: prima di ogni modifica, spiegare passo per passo e riga per riga cosa si sta per fare e perché, come farebbe un insegnante — cosa cambia, perché si sceglie quell'approccio, quali effetti produce. Solo dopo usare Write/Edit. Eccezione: per modifiche di una sola riga o correzioni ovvie basta una frase di contesto.
- **Commit solo dopo test**: non proporre mai il commit finché l'utente non conferma di aver testato le modifiche. Aspettare esplicita conferma prima di eseguire `git add` / `git commit`.
- **CSS sempre in `public/css/custom.css`**: mai scrivere `<style>` inline nelle view né aggiungere attributi `style=` per regole riutilizzabili. Tutte le personalizzazioni CSS vanno in `custom.css`. Eccezione: le sezioni con molte regole specifiche (es. calendario) usano un file dedicato `public/css/<sezione>.css` caricato via `section('styles')` nella view.
- **Nomenclatura controller**: ogni classe controller termina sempre con il suffisso `Controller`

- **Rotte raggruppate**: in `Routes.php` usare sempre `$routes->group()` per raggruppare le rotte per sezione. Mantiene il file ordinato e leggibile. (es. `DashboardController`, `GeneraleController`, `UtentiController`). Vale anche per i controller in sottocartelle.

- **Branch Git**: non aprire un branch per ogni piccola modifica. Suggerire attivamente quando NON serve un branch (es. modifiche contenute su una o due view/controller). Usare un branch solo per feature significative o rischiose.
- **Guida passo per passo — ordine dei file**: quando si guida l'utente nell'implementazione passo per passo di cose codice già scritto, partire sempre dalla **view** prima del controller e del model. La view definisce quali variabili servono, così controller e model vengono scritti sapendo già cosa devono produrre. Per le parti da creare ex novo partire dalla migration, dal model, poi controller e poi view.

## Brainstorming prima di implementare
Prima di iniziare qualsiasi feature nuova o non banale, proporre sempre un brainstorming onesto sui pro e contro — senza dare per scontato che si proceda. L'utente vuole valutare se vale la pena e confrontare approcci alternativi prima di investire tempo. Non cercare file né scrivere codice finché non si è concordato l'approccio.

## Spec scritta prima di implementare (feature non banali)
Una volta concordato l'approccio nel brainstorming, per le feature non banali scrivere uno spec in `docs/spec/<nome>_spec.md` **prima** di scrivere codice — segue la traccia degli spec già esistenti nella cartella (es. `abbonamenti_next_visita_spec.md`): contesto/problema, soluzione con le decisioni chiave e il *perché*, eventuali alternative scartate, riepilogo puntuale delle modifiche file per file, sezione esplicita "fuori scope". Serve a tenere traccia dei ragionamenti fatti insieme, non solo del risultato finale. Non serve per fix di una riga o modifiche ovvie — solo per feature con più decisioni di design da ricordare.

## Modo di lavorare
Regole nate da correzioni esplicite nel corso del progetto. Ognuna ha avuto un caso concreto dietro.

**Bug e difetti**
- **Un bug trovato si corregge subito**, anche se è collaterale al lavoro in corso e sta in un file che il task non tocca. Non va annotato "per dopo". Si chiede prima solo se la correzione è a sua volta un lavoro grosso o ha decisioni di design aperte. È l'eccezione alla regola sul branch qui sotto.
- **Sanare la classe di difetto, non il caso segnalato.** Le incoerenze visive emergono usando l'app, una alla volta. Alla prima segnalazione cercare la causa in tutto il progetto, presentare il censimento con i numeri e correggere l'intera classe, dicendo quali file si toccano fuori dalla richiesta.
- **Accentrare invece di ripetere.** Se la stessa modifica va fatta su più di tre o quattro file, dirlo e proporre un punto unico: il criterio è «se cambia una cosa si modifica un punto solo». Il punto unico va agganciato dove non si può dimenticare: un partial già incluso, un metodo base. Una convenzione da ricordare a ogni uso non risolve il problema.
- **Spec contro codice.** Prima di applicare un passo di uno spec che riscrive qualcosa di diffuso, contare le occorrenze delle due forme. Se la forma da eliminare è maggioritaria, fermarsi e riportare i numeri: probabilmente sbaglia lo spec. Una domanda come «non va bene così?» va trattata come un dato da verificare, non come una preferenza.
- **Niente guard difensivi copiati per simmetria.** Quando una modifica si replica in due file simili, verificare in ciascuno se la condizione può davvero verificarsi. Un controllo che serve in un solo file resta solo lì.
- **Un vincolo oggettivo si blocca lato server** (es. tecnico assente: «se uno non c'è non c'è»). L'avviso "puoi procedere comunque" è per i vincoli morbidi, come una scadenza superata.
- **`dd()` commentati** sono il normale metodo di debug: vanno elencati nella pulizia prima del commit, mai trattati come possibile causa di un bug.
- **Bug solo su mobile**, senza devtools: dopo uno o due tentativi falliti, smettere di indovinare. Iniettare un pannello di log `position:fixed` in pagina e chiedere uno screenshot, poi rimuoverlo prima del commit.

**Design**
- **Brainstorming con domande aperte.** In un design ancora fluido non usare `AskUserQuestion`: chiedere a parole e lasciare esporre l'idea propria, che spesso non sta fra le opzioni. Lo strumento va bene per formalizzare una scelta fra alternative già discusse.
- **Elenco scritto a mano o query sullo schema.** Quando un controllo dipende da un insieme di tabelle destinato a crescere (es. FK verso `clienti.id`), preferire `information_schema` a un elenco scritto a mano, che diventa incompleto in silenzio.
- **Tracciamento per analisi future**: se nessuna funzionalità dipende dal dato a breve, bastano poche colonne timestamp sulla tabella esistente, non una tabella di log generica.
- **Codice versionato non è "debug"**: migration, view e classi nel repo si descrivono per la loro funzione. "Debug" o "temporaneo" vanno solo su ciò che verrà davvero rimosso a breve.
- **Prima di introdurre una struttura dati nuova** (oggetto di config, formato JSON, firma di endpoint) mostrare uno snippet isolato di quella sola forma e farla confermare. Non vale per il file intero, che si rivede come diff.

**Git e comandi**
- **Si lavora da soli**: amend e force push su main sono ordinari. Niente avvertenze su storia condivisa o altre copie del repo; `--force-with-lease` resta il default tecnico, senza spiegarlo ogni volta.
- **Un branch alla volta.** Un refactor o una migliorìa applicabile anche altrove si propone a parole e si annota in `docs/backlog.md`, ma non si implementa nel branch corrente. I bug sono l'eccezione, vedi sopra.
- **Commit intermedi su un branch con più parti**: sono checkpoint senza versione, senza `CHANGELOG.md` e senza §7.1 di `ANALISI.md`. Questi si aggiornano solo nel commit finale.
- **Feature con file intrecciati**: se le modifiche pendenti di più sotto-feature condividono gli stessi file, niente commit intermedi ricavati con patch parziali. Si fa un commit unico a feature finita, testando lungo il percorso.
- **Le scritture sul database le lancia l'utente.** `php spark migrate`, `db:seed` e simili si propongono nella forma `! <comando>`, dicendo cosa aspettarsi. Le letture restano normali.
- **Controllo finale dopo un'iterazione manuale**: se l'utente ha appena sistemato a mano un file, i problemi trovati in quel file si segnalano e si lasciano testare prima di correggerli. Fix, changelog e commit non vanno incatenati nello stesso turno.
- **Amministrazione di sistema** (cron, rotazione e copia off-site dei backup, SSH, pool PHP) è materia del sistemista. Non proporla come prossimo passo; se serve, scrivere le istruzioni per lui.

## Roadmap — non proporre la v1.0.0
La v1.0.0 (release finale: test, deploy su colombini.metesoftware.it, ottimizzazione percorsi OpenRouteService) è prevista per **gennaio 2027**, non è imminente. La data non è tecnica ma operativa: si cambia gestionale all'inizio dell'anno contabile, quando gli abbonamenti ripartono, non negli ultimi mesi dell'anno con il lavoro in corso.

Non proporla come prossimo passo a inizio sessione. Al momento si aggiungono le funzionalità che vengono in mente via via, senza un ordine rigido pianificato — chiedere all'utente cosa vuole affrontare piuttosto che assumere si proceda verso v1.0.0.

**Il database di produzione è destinato ai dati veri.** Il 26/08/2026 è stato svuotato e ricostruito da zero, e a inizio ottobre è ancora vuoto; il caricamento dell'anagrafica avverrà lì, progressivamente, fino al go-live. Non è un ambiente demo sacrificabile: le operazioni distruttive su quel database vanno trattate di conseguenza.

I punti aperti, le idee rimandate e le rifiniture stanno in `docs/backlog.md`.

## Stack tecnologico
- **Sviluppo e produzione girano sullo stesso server e sugli stessi motori**: **MariaDB 10.11** (LTS, Debian 12) e **PHP 8.4**. Nessun manifest dichiara il motore del database, quindi va ricordato qui. Fino a ottobre 2026 lo sviluppo girava su MySQL 8 in locale: il codice non usa niente di specifico di un dialetto, e le colonne generate (`GENERATED ALWAYS AS ... STORED`, su `clienti.denominazione`) hanno la stessa sintassi nei due.
- **Non cambiare la collation.** `app/Config/Database.php` fissa `DBCollat` a `utf8mb4_general_ci`, che coincide con il `collation_server` di MariaDB. È anche ciò che ha reso portabile il dump da MySQL 8, il cui default `utf8mb4_0900_ai_ci` in MariaDB **non esiste**.
- Versioni di PHP, CodeIgniter e Shield: `composer.json`. Versioni di AdminLTE, Bootstrap e degli altri pacchetti frontend: `package.json`.

## Asset frontend — gestione dipendenze
Le dipendenze frontend si installano via **npm** (l'elenco aggiornato è in `package.json`) e vengono copiate in `public/assets/vendor/` tramite il comando Spark `php spark assets:publish`. Non c'è nessun bundler.

Il comando `app/Commands/AssetsPublish.php` legge un manifest e copia i file `dist/` da `node_modules/` verso `public/assets/vendor/`. Va eseguito dopo ogni `npm install` o aggiornamento pacchetti. In produzione i file sono committati in git e il comando non serve.

**I file statici si includono con `asset_url()`, mai con `base_url()`.** Vale per `js/`, `css/` e `assets/vendor/` nei tag `<script>` e `<link>`: `asset_url('js/search-bar.js')` aggiunge `?v=<data di modifica del file>`, così il browser riscarica un file appena cambia invece di usare la copia in cache (`app/Helpers/asset_helper.php`, autoloadato). Resta `base_url()` solo dove l'indirizzo è una cartella a cui il JS attacca un nome di file, come l'`iconBase` di Leaflet.

**jQuery non c'è.** AdminLTE 4 è un rewrite su Bootstrap 5 puro, e da DataTables 3 non serve più nemmeno lì: è stato tolto con l'aggiornamento. Non reintrodurlo. Le DataTable si creano con `new DataTable(...)` (dentro `initTabella()`), una tabella già creata si recupera con `new DataTable.Api('#id')`, e gli script delle view partono con `document.addEventListener('DOMContentLoaded', ...)` invece di `$(function () {...})`.

## Go-live in produzione
Non migrare nessun record dal database di sviluppo a quello di produzione: clienti, interventi, materiali, abbonamenti sono dati di test. L'unica eccezione nel contenuto, non nella regola, è `clienti_adhoc`: è l'anagrafica reale importata da Ad Hoc, quindi il database di sviluppo non è del tutto sacrificabile. In produzione l'import si rifà da capo dall'interfaccia.

**Il 2027 parte dalle proposte, non dai rinnovi.** Gli abbonamenti reali del 2027 si caricano come proposte con "Nuovo abbonamento"; il 2026 non si carica. Al primo avvio non esiste nessun abbonamento da cui rinnovare: un vincolo messo su `rinnova()` non deve toccare la creazione da zero di un abbonamento con date future.

## Codice cliente — numerico o `CLI-`
`clienti.codice` porta un'informazione: un codice **numerico** è l'`ANCODICE` del gestionale contabile Ad Hoc, conservato alla promozione da `clienti_adhoc`; un codice **`CLI-xxxx`** (da `NumeratoriModel`) indica un cliente interno, non presente in contabilità. **Non normalizzare mai** tutti i codici a `CLI-`, né spostare il codice Ad Hoc in `codice_esterno`.

Fino alla v0.30.0 il prefisso era `INT-`: la migration `ContatoreCodiciClienti` l'ha cambiato perché `INT` indicava anche gli interventi. Un `INT-` su un cliente è un residuo, non la forma corretta.

`codice_esterno` non è un doppione: `codice` dice *come* il cliente è entrato (storico, non modificabile da nessuna UI), `codice_esterno` dice *se oggi* è in contabilità ed è aggiornabile dalla scheda. `normalizza()` lo converte da `''` a `NULL`, così `codice_esterno IS NULL` funziona come criterio.

## ID utente loggato — usare `user_id()`, non `session()->get('user_id')`
Shield salva i dati dell'utente in sessione sotto la chiave `'user'` (array con `id`, email, ecc. — vedi `Config\Auth::$sessionConfig['field']`), non sotto una chiave piatta `'user_id'`. `session()->get('user_id')` restituisce quindi sempre `null`, silenziosamente (nessun errore). Per ottenere l'ID dell'utente loggato usare l'helper Shield **`user_id()`** (o `auth()->id()`), già autoloadato.

## Ambiente di sviluppo e troubleshooting
Si sviluppa sul server, in `/var/www/colombini-dev`, come utente `colombini-dev` (senza `sudo`), via VS Code Remote-SSH; il sito di sviluppo è `https://colombini-dev.metesoftware.it`. Le note pratiche — cosa si può fare da qui e cosa no, log, database, password, `dd()`/Kint, diff VS Code, doppio login Shield — stanno nella skill `ambiente-dev` (`.claude/skills/ambiente-dev/SKILL.md`), che si carica da sola quando serve. Il perché dell'assetto è in `docs/spec/ambiente_dev_server_spec.md`.

## Vecchio progetto — guardarlo prima di progettare
Il gestionale precedente è in `/var/www/colombini-old`, in sola lettura, ed è anch'esso **CodeIgniter 4**. Il suo sito (`colombini-old.metesoftware.it`) è ancora online, dietro password, per vedere come funzionava una feature. Prima di progettare da zero una feature di logica di business (pianificazione, calcoli, flussi UI), proporre di guardare come era risolta lì: spesso c'è un pattern riusabile, o il motivo per cui non si fece in un certo modo. Esempio: per le sovrapposizioni orarie dei tecnici, lì c'era un suggerimento d'orario e non un blocco, ed è stato ripreso così.

Le **stampe PDF** riprendono lo stile dei suoi template dompdf (`app/Views/viaggi/pdf_viaggio.php`, `pdf_giornata.php`, `app/Views/interventi/pdf_rapportino.php`), già applicato in `anagrafiche/clienti/pdf_scheda_cliente.php`: leggere il template corrispondente prima di scrivere la view. Il logo va incorporato come data URI base64, perché `isRemoteEnabled` resta `false`.

## Documenti Word — un motore, modelli nel repository
I documenti in Word (oggi la proposta di abbonamento, domani i preventivi) passano tutti da
`app/Libraries/DocumentoWord.php`, sopra il `TemplateProcessor` di PhpWord: segnaposto
`${nome}` ed elenchi a blocchi `${blocco}` / `- ${voce}` / `${/blocco}`, ogni marcatore in un
paragrafo suo e con un nome diverso dalla riga che contiene. Il motore fa l'escape dei valori
(PhpWord non lo fa nei blocchi), si ferma se resta un segnaposto non compilato e non lascia
file sul server: il documento si genera e si scarica. Cosa mettere nei segnaposto lo decide una
classe di dominio, come `PropostaAbbonamento`.

I modelli stanno in `app/Templates/word/`. **I segnaposto non si scrivono a mano in Word**: il
controllo ortografico e le revisioni li spezzano in pezzi invisibili e PhpWord non li trova
più, senza errori. Stile e testi fissi si cambiano in Word; per i segnaposto il modello si
prepara via XML, come spiegato in `docs/spec/abbonamenti_proposte_word_spec.md`, decisione 10.

## Sistema di ritorno "from"
Quando un form (edit o nuovo) può essere aperto da contesti diversi (lista, scheda cliente, ecc.), si usa il parametro `from` per tornare alla pagina di origine dopo salvataggio o eliminazione.

**Flusso:**
1. Il link di apertura passa `?from=URL%23anchor` come query string (GET).
2. Il controller legge `$this->request->getGet('from')` e lo passa alla view.
3. La view lo inserisce come `<input type="hidden" name="from" value="...">` in **ogni form** della pagina (update, delete, e form principale del nuovo). L'input va dopo `csrf_field()`, condizionato a `if ($from)`.
4. Il bottone Annulla usa `$from ?: base_url('sezione/default')`.
5. Il controller dopo store/update/delete legge `$this->request->getPost('from')` e valida:
   ```php
   $from = $this->request->getPost('from');
   $dest = ($from && str_starts_with($from, base_url())) ? $from : 'fallback/url';
   return redirect()->to($dest)->with('success', '...');
   ```
   Il controllo `str_starts_with($from, base_url())` impedisce open redirect su domini esterni.

**Tab Bootstrap al ritorno:** se il `from` contiene un hash (`#pane-interventi`), il browser scrollerà all'anchor. Per attivare anche il tab Bootstrap aggiungere nella view di destinazione:
```js
const hash = location.hash;
if (hash) {
    const trigger = document.querySelector('[data-bs-target="' + hash + '"]');
    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
}
```

## Controller CRUD — dati da request
Le normalizzazioni dei dati (casting, null per stringhe vuote, uppercase, default) appartengono al **model**, non al controller. Usare i callback CI4 `$beforeInsert` / `$beforeUpdate` con un metodo `normalizza()`.

Il controller si limita a:
```php
$model->insert($this->request->getPost());
// oppure, per campi impostati lato server:
$model->insert(array_merge($this->request->getPost(), ['stato' => 1]));
```

Non creare metodi helper `campiDaRequest()` né array espliciti campo per campo nel controller.

## Validazione — le etichette dei campi non si scrivono nei controller

I messaggi di CI4 contengono `{field}`, che il framework sostituisce con la `label` della regola
se c'è e altrimenti con **il nome grezzo della colonna**: senza etichette l'utente leggeva
«Il campo "tipo_intervento_id" è obbligatorio».

Le etichette non vanno aggiunte regola per regola. `BaseController::validate()` è sovrascritto e
fa passare ogni array di regole da `regole_con_etichette()`
(`app/Helpers/validazione_helper.php`), quindi **le regole si continuano a scrivere come sempre**
e un controller nuovo eredita il comportamento senza saperlo:

```php
'tipo_intervento_id' => 'required|is_natural_no_zero',   // → «Il campo "Tipo di intervento"…»
```

`etichetta_campo()` cerca prima in una mappa corta, che copre solo i casi che una regola
meccanica sbaglierebbe (`piva`, `cfisc`, `citta`, `lat`, `password_confirm`), e altrimenti
ripiega su una trasformazione automatica: toglie il suffisso `_id`, cambia gli underscore in
spazi, mette l'iniziale maiuscola. **La mappa non va tenuta allineata al database**: serve solo
per le eccezioni, e il ripiego garantisce che un campo nuovo non mostri mai il nome della
colonna. Aggiungere una voce solo quando il risultato automatico è sbagliato o brutto.

Una regola già scritta in forma array conserva i suoi `errors`: riceve solo la `label` in più.

## Flashdata e layout
Il layout `app/Views/layouts/admin.php` gestisce già `success`, `error` e `warning` per tutte le pagine. Non duplicarli nelle singole view — causa visualizzazione doppia. Nelle view includere solo `errors` (plurale) per la lista errori di validazione, che il layout non gestisce.

## Campi con valori limitati nelle migrazioni
Non usare il tipo `ENUM` di MySQL. Seguire queste convenzioni:

- **Flag booleani** (`attivo`, `visibile`) → `TINYINT` con default `0` o `1`. `attivo = 0` significa disabilitato temporaneamente; non usare un flag `eliminato` — si usa hard delete con controllo applicativo preventivo (verificare record collegati prima di cancellare, mostrare messaggio chiaro all'utente).
- **Stati con più valori** (`bozza/attivo/sospeso/scaduto`) → `VARCHAR` con costanti nel model e un commento che elenca i valori ammessi:

```php
// valori: bozza, attivo, sospeso, scaduto, disdetto
'stato' => [
    'type'       => 'VARCHAR',
    'constraint' => 30,
    'default'    => 'bozza',
],
```

```php
// nel model
const STATO_BOZZA   = 'bozza';
const STATO_ATTIVO  = 'attivo';
const STATO_SOSPESO = 'sospeso';
```

Questo evita `ALTER TABLE` ogni volta che si aggiunge un valore: basta aggiornare la costante nel model e la validazione CI4.

- **Valori configurabili a runtime** → tabella di lookup con foreign key (solo se l'utente deve poterli modificare senza deploy)

## Campi standard in ogni tabella
Ogni migrazione include sempre questi campi:

```php
$this->forge->addField([
    // ... campi specifici della tabella ...
    'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
    'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
]);
$this->forge->addField('created_at DATETIME NULL');
$this->forge->addField('updated_at DATETIME NULL');
```

Nel model: `$useTimestamps = true` per `created_at`/`updated_at`. I campi `created_by` e `updated_by` vengono popolati automaticamente dal callback `normalizza()` leggendo l'helper Shield **`user_id()`** (non `session()->get('user_id')` — vedi la sezione "ID utente loggato"). `updated_by` va impostato in `$beforeUpdate`; `created_by` in `$beforeInsert` (e non va mai sovrascritto negli update).

## Codici progressivi — sempre da NumeratoriModel
Ogni codice progressivo (`CLI-0001`, `INT-0042`, `PIS-0007`) si ottiene da
`NumeratoriModel::prossimo($classe, $prefisso)`. Nessun model compone codici per conto suo, né
li ricava da `MAX(codice)` o dall'`AUTO_INCREMENT`: il primo regredisce dopo una cancellazione
e sbaglia il massimo quando i codici hanno lunghezze diverse, il secondo è aggiornato da InnoDB
in modo asincrono.

Un model che genera codici dichiara due costanti e delega:

```php
const CLASSE_NUMERATORE = 'Clienti';
const PREFISSO_CODICE   = 'CLI';

public function generaCodice(string $prefisso = self::PREFISSO_CODICE): string
{
    return (new NumeratoriModel())->prossimo(self::CLASSE_NUMERATORE, $prefisso);
}
```

I prefissi non si scrivono a mano nel codice: vanno in costanti del model, così la pagina
Impostazioni → Numeratori può descrivere ogni serie senza indovinare da dove nasce.

## Query nei model — usare $this, non $this->db->table()
Nei metodi di un model, usare sempre `$this` (il Query Builder del model) invece di `$this->db->table(...)`. Questo garantisce che timestamp e altri comportamenti del model vengano applicati automaticamente.

```php
// ✓ corretto
public function perCliente(int $clienteId): array
{
    return $this->select('clienti_impianti.*, i.nome, i.marca')
        ->join('impianti i', 'i.id = clienti_impianti.impianto_id')
        ->where('clienti_impianti.cliente_id', $clienteId)
        ->findAll();
}

// ✗ evitare
public function perCliente(int $clienteId): array
{
    return $this->db->table('clienti_impianti ci')
        ->select('ci.*, i.nome')
        ->join('impianti i', 'i.id = ci.impianto_id')
        ->get()->getResultArray();
}
```

## Query database — CodeIgniter 4
Usare sempre il **Query Builder** di CI4 (`$db->table(...)` o il model builder). Evitare query raw (`$db->query(...)`) anche per JOIN complessi: usare la stringa di condizione nel terzo parametro di `->join()` con `$db->escape()` per i valori dinamici.

Le query raw non hanno protezione automatica contro SQL injection e rendono il codice meno leggibile e coerente. L'unica eccezione ammessa è una query talmente complessa da non essere esprimibile con il Query Builder — in un gestionale di questo tipo non dovrebbe mai succedere.

```php
// JOIN con condizioni multiple — Query Builder
$db->table('users u')
   ->join('tecnici_competenze tc',
          'tc.tecnico_id = u.id AND tc.tipo_intervento_id = ' . $tipoId . ' AND tc.livello >= 1',
          'inner')
   ->join('interventi i',
          'i.tecnico_id = u.id AND DATE(i.data_pianificata) = ' . $db->escape($data),
          'left');
```

## Denominazione cliente nelle query — sempre `cliente_denominazione`
`clienti.denominazione` è una colonna generata (`GENERATED ALWAYS AS ... STORED`, vedi migrazione `AddDenominazioneToClienti`): ragione sociale per le società, cognome+nome per le persone fisiche. Quando una query la seleziona — sia con un JOIN da un'altra tabella (cantieri, interventi, abbonamenti) sia dentro `ClientiModel` stesso — va **sempre** aliasata come `cliente_denominazione`, mai lasciata come `denominazione` nudo:

```php
// ✓ ovunque, anche dentro ClientiModel
->select("clienti.*, clienti.denominazione AS cliente_denominazione, ...")
```

In `ClientiModel::elencoCompleto()`/`trovaConDettagli()` questo produce un doppione apparente (`clienti.*` porta comunque con sé anche il campo grezzo `denominazione`) — è un effetto collaterale innocuo di `SELECT *`, non un errore: il codice applicativo (controller, view) non deve mai leggere quella chiave grezza, solo `cliente_denominazione`. Un solo nome in tutto il codice evita confusione tra query che joinano clienti da entità diverse.

## Commenti sui metodi PHP
Aggiungere sempre un docblock sopra ogni metodo di controller o model che spieghi **cosa fa e perché**. Includere solo ciò che aggiunge valore rispetto alla firma: descrizione, eventuale `@throws`. Non ripetere `@param` e `@return` se il tipo è già dichiarato nella firma.

## Docblock nelle view
Ogni view inizia con un blocco `<?php ... ?>` che dichiara le variabili iniettate dal controller tramite `@var`, seguito da `$this->extend()`. Il resto del file usa la sintassi template normale `<?= ... ?>`.

```php
<?php
/**
 * @var array                                  $persona
 * @var string                                 $email
 * @var array                                  $gruppi
 * @var \CodeIgniter\Shield\Entities\User|null $user
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>...<?= $this->endSection() ?>
```

Dichiarare tutte le variabili passate dalla chiamata `view(...)` nel controller. Per i tipi usare le stesse regole dei docblock PHP: tipo preciso (`string`, `array`, FQN per oggetti, `|null` se nullable). Questo elimina i falsi positivi di Intelephense e documenta implicitamente la firma della view.

```php
/**
 * Restituisce il tecnico meno occupato per il tipo dato,
 * escludendo chi supera la soglia giornaliera.
 *
 * @throws RuntimeException se nessun tecnico è disponibile
 */
public function tecnicoDisponibile(int $tipoId, string $data): ?array
```

## Valori PHP dentro attributi HTML con JS inline (onclick, onsubmit, ecc.)
Non scrivere mai un accesso ad array PHP (`$var['chiave']`) direttamente dentro un attributo `onXXX="..."` (es. `onsubmit`, `onclick`). L'attributo è delimitato da apici doppi e spesso contiene già una stringa JS delimitata da apici singoli (es. `confirm('...')`): qualunque apice si scelga per la chiave dell'array (singolo o doppio) finisce per collidere con uno dei due livelli di apici che lo racchiudono.

Il problema è invisibile a runtime — PHP valuta `$var['chiave']` prima che il browser veda l'HTML, quindi gli apici della sintassi PHP non compaiono mai nell'output — ma confonde il parser HTML/JS dell'editor: causa falsi errori del linter o styling errato (es. testo in corsivo) sul codice successivo nel file.

Soluzione: estrarre sempre il valore in una variabile PHP semplice prima dell'attributo, e usare solo quella dentro l'attributo — nessuna parentesi quadra, nessun apice aggiuntivo nella zona sensibile.

```php
<?php $codiceIntervento = $intervento['codice']; ?>
<form onsubmit="return confirm('Eliminare <?= esc($codiceIntervento) ?>?')">
```

## AdminLTE 4 — Layout card
Le convenzioni per card-header, card-footer e card-tools vanno documentate qui man mano che si scopre il comportamento reale di AdminLTE 4 con Bootstrap 5. Non usare i pattern del vecchio progetto (`float-left`/`float-right`, `clearfix`) — erano workaround di Bootstrap 4.

## Azioni nelle card — dove stanno e che aspetto hanno

> Nota: questa sezione descriveva fino alla v0.33.1 un sistema `--card-accent` per cui i
> bottoni ereditavano il colore della card e nella view bastava `btn btn-sm`. **Quel sistema
> non è mai esistito**: in `custom.css` non c'erano né la regola `.card-tools .btn` né le
> variabili. I bottoni scritti `btn btn-sm` erano semplicemente privi di variante di colore,
> quindi trasparenti — sembravano funzionare nelle intestazioni per caso. Ogni pulsante deve
> avere una variante esplicita. Dettagli in `docs/spec/ui_azioni_coerenti_spec.md`.

**La regola di collocazione:**

- **In alto, nel `card-tools`**: gli strumenti che portano altrove — Modifica, Stampa PDF,
  Nuovo X (anche dentro una scheda, quando crea un altro record). Più guida e tooltip.
- **In basso, nel `card-footer`**: le azioni che decidono qualcosa e chiudono il discorso lì —
  Salva, Annulla, Elimina, e i cambi di stato delle schede. Il link di ritorno fa parte del
  gruppo e sta con loro.
- I **filtri** stanno nel `card-body` sopra la tabella, non sono azioni su un record.

**Lo stile, sempre lo stesso:**

| Azione | Classi |
|---|---|
| Modifica | `btn btn-sm btn-outline-primary` + `bi-pencil` |
| Stampa PDF | `btn btn-sm btn-outline-secondary` + `bi-file-earmark-pdf` |
| Nuovo X | `btn btn-sm btn-primary` + `bi-plus-lg`, etichetta completa ("Nuovo cantiere") |
| Salva | `btn btn-sm btn-primary` |
| Annulla / ritorno | `btn btn-sm btn-outline-secondary` |
| Elimina | `btn btn-sm btn-outline-danger` |

Il pieno è riservato all'azione principale della pagina (Nuovo X negli elenchi, Salva nei
form); gli strumenti sono contornati. Sempre `btn-sm`.

**Il footer usa `.card-azioni`** più una classe semantica per pulsante — `.azione-primaria`,
`.azione-secondaria`, `.azione-ritorno`, `.azione-distruttiva` — che decide ordine e posizione
via `order`, così non dipendono da come è scritto il markup. Nessuna utility di allineamento
nella view: niente `d-flex`, `justify-content-between`, `ms-auto`. **La classe semantica va sul
figlio diretto** di `.card-azioni`: l'`<a>`, il `<button>`, oppure il `<form>` che lo racchiude
quando l'azione è un POST — è quello l'elemento flex che riceve l'`order`.

**Nel `card-tools`, su mobile l'etichetta sparisce e resta l'icona**, con `title` come tooltip:

```php
<a href="..." class="btn btn-sm btn-outline-primary" title="Modifica">
    <i class="bi bi-pencil"></i><span class="d-none d-sm-inline ms-1">Modifica</span>
</a>
```

Lo spazio va sullo `span` (`ms-1`) e non sull'icona (`me-1`): con l'etichetta nascosta un
margine a destra scentrerebbe l'icona nel bersaglio da 44px.

Le altre classi specifiche di AdminLTE 4 vanno documentate qui man mano che vengono scoperte.
Non copiare i selettori del vecchio progetto (erano Bootstrap 4 / AdminLTE 3).

## DataTables — l'init è condiviso, la view passa solo le differenze

Nessuna view configura una DataTable da sola. `public/js/datatable-init.js` espone
`initTabella(selettore, opzioni)` e tiene i default comuni: `responsive`, `orderMulti`,
`lengthMenu: [10, 25, 50, 100, -1]`, `pageLength: 25` e tutte le traduzioni. È agganciato in
`app/Views/partials/datatables_scripts.php`, quindi **la view non deve caricare niente**: basta
l'include degli asset che già fa.

Nella view resta solo ciò che è specifico di quella tabella — `columnDefs`, `order`, il
`pageLength` quando differisce dal default, e i messaggi che nominano l'entità:

```js
var table = initTabella('#tabella-cantieri', {
    order: [[0, 'desc']],
    columnDefs: [ ... ],
    language: { emptyTable: 'Nessun cantiere registrato.' }
});
```

Il `pageLength` passato deve essere un valore presente nel `lengthMenu`, altrimenti la tendina
mostra un numero che non contiene.

**La fusione dei default è a tre livelli, e non è un dettaglio**: `Object.assign` è
superficiale, quindi `language` va fuso chiave per chiave e dentro di esso anche `paginate` e
`lengthLabels`. Passare `language: { emptyTable: '...' }` con una fusione piatta cancellerebbe
l'intero blocco delle traduzioni e la tabella tornerebbe in inglese — senza errori, quindi
difficile da notare. Chi tocca `datatable-init.js` deve tenerlo presente.

## DataTables 2 — l'etichetta di "Tutti" non sta nel `lengthMenu`
Per offrire la scelta del numero di righe si usa `lengthMenu: [25, 50, 100, -1]`, dove `-1` significa "nessuna paginazione". L'etichetta di quella voce **non** si imposta passando due array paralleli (`[[25, -1], [25, 'Tutti']]`): era il modo di DataTables 1.x, e in 2.x per i valori noti viene scavalcata dal default inglese `lengthLabels: {"-1": "All"}` che sta nel bundle.

La traduzione va dentro `language`:

```js
language: {
    lengthMenu:   'Mostra _MENU_ righe',
    lengthLabels: { '-1': 'Tutti' }
}
```

Altre stranezze di DataTables 2 vanno documentate qui man mano che si scoprono: il progetto non carica nessun file di lingua esterno, ogni view dichiara il proprio oggetto `language` inline.

## DataTables — l'ordinamento guarda il testo della cella, non il dato
DataTables ordina una colonna sul **testo** che la cella contiene. Quando la cella non mostra il valore grezzo, l'ordinamento è sbagliato o non fa proprio niente, e il difetto è silenzioso:

- **cella con sola icona** (`<i class="bi ...">`): il testo è vuoto per tutte le righe, quindi ordinare quella colonna non cambia nulla
- **badge con etichetta**: ordina alfabeticamente sull'etichetta (Annullato, Completato, Da pianificare…), non secondo il ciclo di vita
- **data formattata `d/m/Y`**: ordina per giorno, poi mese, poi anno

Il rimedio è sempre `data-order` sulla `<td>`, con il valore vero: la data ISO, il rango numerico dello stato, `1`/`0` per un flag. Per gli stati si dichiara una mappa accanto a `$statoBadge` nella view (`$statoOrdine = ['da_pianificare' => 1, ...]`), così l'ordinamento segue il flusso del lavoro.

Un valore nullo va pensato, non lasciato vuoto: una stringa vuota in ordinamento crescente precede qualunque data. In `operativo/interventi/index.php` la scadenza assente diventa `'9999-' . $i['created_at']`, che spinge quelle righe in fondo e fra loro le ordina per data di creazione.

## Filtri delle tabelle — partial, colonne nascoste e `search-bar.js`
I filtri degli elenchi non si scrivono a mano: né il markup né il JavaScript. Il meccanismo è
condiviso da tutte le tabelle del gestionale (interventi, cantieri, abbonamenti, scheda cliente)
e si compone di tre pezzi:

1. la view aggiunge una **colonna nascosta** (`visible: false`, `searchable: true`) con il valore
   grezzo su cui filtrare;
2. la tendina si stampa con il partial `app/Views/partials/filtro_tendina.php`, reso con
   `view('partials/filtro_tendina', [...])` — **non** con `$this->include()`, che condivide i dati
   della view chiamante invece di accettare i propri;
3. `public/js/search-bar.js` fa il resto: applica i filtri, aggiorna l'etichetta del bottone e
   ricorda la scelta.

Ogni voce si dichiara **una volta sola**, con dentro sia l'etichetta sia cosa cercare: il partial
separa le chiavi `col`/`q`/`regex` (che diventano il JSON di `data-pill-filtri`) dal resto
(`label`, `icona`, `default`, `sotto`, che diventano la riga del menu). Una voce senza `col`
azzera le colonne del gruppo: è il classico "Tutti".

```php
<?= view('partials/filtro_tendina', [
    'tabella'   => 'tabella-interventi',
    'etichetta' => 'Stato',
    'classe'    => 'btn-outline-primary',
    'voci'      => [
        'tutti'    => ['label' => 'Tutti (' . count($interventi) . ')'],
        'aperti'   => ['label' => 'Aperti', 'icona' => 'bi-play-circle', 'default' => true,
                       'col' => 9, 'q' => '^(da_pianificare|pianificato|in_corso)$', 'regex' => true],
        'in_corso' => ['label' => 'In corso', 'sotto' => true,
                       'col' => 9, 'q' => '^in_corso$', 'regex' => true],
    ],
]) ?>
```

Dopo aver creato la DataTable, la view chiama `filtriIniziali('id-tabella')`: applica i filtri
ricordati dalla sessione e, dove non ce ne sono, quelli marcati `'default' => true`.

**I filtri scelti si ricordano in `sessionStorage`**, cioè finché la scheda del browser resta
aperta: si torna alla lista dopo aver aperto una scheda o salvato un form e la si ritrova come la
si era filtrata, mentre il giorno dopo si riparte dai default. La chiave comprende la pagina con
la sua query string, così le tre sezioni degli interventi (`?sezione=piscine`, `generale`,
`addolcitori`) ricordano ciascuna la propria combinazione. Scartato mettere i filtri
nell'indirizzo: l'URL resta pulito. Quando in una pagina ci sono più tendine con la stessa
etichetta su tabelle diverse — la scheda cliente ne ha tre chiamate "Stato" — va passato un
`gruppo` esplicito, altrimenti si sovrascrivono la memoria a vicenda.

Ogni tendina è un gruppo a sé e azzera solo le proprie colonne, quindi i filtri di gruppi diversi
**si combinano**. Una colonna nascosta può contenere più parole per riga (`oggi settimana mese`,
`2025 2026`): in quel caso il filtro cerca la singola parola con `\b`, non l'intero contenuto con
`^...$`.

Conseguenza da tenere a mente: le colonne nascoste sono `searchable`, quindi anche la ricerca
globale trova quei valori — scrivendo `scaduto` o `apertura` nel campo Cerca escono le righe
corrispondenti.

## View Help
Un file di help per sezione (non per view singola): `app/Views/help/<sezione>.php`. Descrive il flusso completo della sezione — come creare, modificare, le regole di cancellazione, ecc. Il controller passa `$help_sezione = 'clienti'`; il layout carica il file corrispondente e mostra il bottone guida solo se esiste. Se una sezione non ha ancora un file help, il bottone non appare.

## Messaggi di commit
Ogni commit inizia con il numero di versione: `v0.4.0 — Descrizione breve`. Usare l'em dash (—) come separatore. Il messaggio descrive cosa cambia, non come.

## Changelog
Prima di ogni commit aggiornare `CHANGELOG.md` seguendo il pattern markdown esistente e includerlo nella stessa commit.

Ogni voce è taggata con il tipo:
- `[APP]` — funzionalità o modifiche visibili all'utente finale
- `[DEV]` — modifiche tecniche (refactor, migrazioni, dipendenze, fix interni)

Il sistema confronta `CHANGELOG.md` con il campo `users.ultima_versione_vista` per mostrare le novità all'avvio. Solo gli utenti con ruolo `developer` vedono tutte le righe (`[APP]` + `[DEV]`); gli altri ruoli, incluso `admin`, vedono solo le righe `[APP]`.

## Roadmap — sezione 7.1 di ANALISI.md
La pianificazione delle versioni è in `docs/ANALISI.md` sezione **7.1 Milestone e fasi**. Non esiste un file ROADMAP.md separato.
Aggiornare la sezione 7.1 (milestone completate e nuove) prima di ogni commit che chiude una versione, e includerla nella stessa commit del CHANGELOG.

## Documentazione tecnica (docs/)
La cartella `docs/` nella root contiene documentazione tecnica in HTML, versionata insieme al codice e consultabile direttamente da browser o GitHub.

Contiene almeno:
- Schema del database (tabelle, campi, relazioni) aggiornato ad ogni migrazione
- Log sintetico delle modifiche DB (cosa è cambiato e perché, versione per versione)

I file HTML nella cartella `docs/` vanno aggiornati nella stessa commit della migrazione corrispondente. Visibili solo agli utenti con ruolo `developer`.

## Domini — sono due, non confonderli
Il dominio **aziendale** è **colombini-snc.it**: è il sito dell'azienda, non l'indirizzo del gestionale.

Il **gestionale** è ospitato su una VPS separata, all'indirizzo **colombini.metesoftware.it**. È questo il valore da usare per `app.baseURL` in produzione e in ogni riferimento al deploy.
