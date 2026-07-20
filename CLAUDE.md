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

## Roadmap — non proporre la v1.0.0
La v1.0.0 (release finale: test, deploy su colombini-snc.it, ottimizzazione percorsi OpenRouteService) è prevista per **metà settembre 2026**, non è imminente. Non proporla come prossimo passo a inizio sessione. Al momento si aggiungono le funzionalità che vengono in mente via via, senza un ordine rigido pianificato — chiedere all'utente cosa vuole affrontare piuttosto che assumere si proceda verso v1.0.0.

## Stack tecnologico
- **PHP**: 8.2+
- **CodeIgniter**: 4.7.3
- **MySQL**: 8.x
- **AdminLTE**: 4.0.2 con Bootstrap 5.3.8
- **Frontend**: nessun bundler (Vite/webpack) — dipendenze gestite via npm + comando `assets:publish`

## Asset frontend — gestione dipendenze
Le dipendenze frontend si installano via **npm** e vengono copiate in `public/assets/vendor/` tramite il comando Spark `php spark assets:publish`.

Stack npm del progetto:
```
admin-lte@^4.0       → AdminLTE 4 (CSS + JS)
bootstrap@^5.3       → Bootstrap 5 (solo JS — il CSS è incluso in AdminLTE)
@popperjs/core       → richiesto da Bootstrap per dropdown e tooltip
overlayscrollbars    → richiesto da AdminLTE 4
bootstrap-icons      → icone
```

Il comando `app/Commands/AssetsPublish.php` legge un manifest e copia i file `dist/` da `node_modules/` verso `public/assets/vendor/`. Va eseguito dopo ogni `npm install` o aggiornamento pacchetti. In produzione i file sono committati in git e il comando non serve.

AdminLTE 4 non ha jQuery come dipendenza — è un rewrite su Bootstrap 5 puro.

## Go-live in produzione
Il database di sviluppo dovrà essere **completamente svuotato** prima del go-live. Tutti i dati attuali sono dati di test — clienti, interventi, materiali. Non migrare nessun record dal dev al prod.

## Note ambiente e troubleshooting
Questa sezione raccoglie note tecniche sull'ambiente di sviluppo e soluzioni a problemi ricorrenti. Non sono regole di progetto ma vanno tenute qui perché il file viene pushato ed è disponibile su qualsiasi macchina.

**Ripristino diff VSCode (Claude Code)**
Se il diff delle modifiche smette di apparire nell'IDE VSCode:
1. Verificare che `~/.claude/settings.json` abbia `"defaultMode": "default"` (non `"acceptEdits"`)
2. Eseguire `Ctrl+Shift+P` → **Developer: Reload Window** per ricaricare l'estensione
3. Se non basta, aprire una nuova sessione di Claude Code

**ID utente loggato: usare `user_id()`, non `session()->get('user_id')`**
Shield salva i dati dell'utente in sessione sotto la chiave `'user'` (array con `id`, email, ecc. — vedi `Config\Auth::$sessionConfig['field']`), non sotto una chiave piatta `'user_id'`. `session()->get('user_id')` restituisce quindi sempre `null`, silenziosamente (nessun errore). Per ottenere l'ID dell'utente loggato usare l'helper Shield **`user_id()`** (o `auth()->id()`), già autoloadato. Bug noto: `PromemoriaModel::normalizza()` usa ancora il pattern sbagliato → `created_by`/`updated_by` dei promemoria sono sempre `NULL`.

**`dd()` funziona regolarmente**
CodeIgniter 4 include una copia vendorizzata di Kint dentro il framework (`vendor/codeigniter4/framework/system/ThirdParty/Kint/`), attivata automaticamente quando `CI_DEBUG` è `true` (ambiente `development`) — non serve installare `kint-php/kint` separatamente. Verificato con `dd()` diretto: dump completo e corretto. Se in una sessione di debug sembra non fare nulla, sospettare prima il contesto (output engoiato da un ob_start() esterno, contenuto che appare ma passa inosservato più in alto/basso nella pagina) prima di concludere che Kint sia assente.

**Accesso da smartphone in LAN (dev server)**
Per testare l'app dal telefono sulla stessa rete Wi-Fi del PC di sviluppo:
1. `cd d:\Programmazione\Progetti\colombinisnc`
2. `php -S 0.0.0.0:8081 -t public` (in ascolto su tutte le interfacce, non solo `localhost`)
3. Dal telefono: `http://<IP-LAN-PC>:8081`

`app.baseURL` in `.env` è attualmente impostato su `http://192.168.1.133:8081` (IP LAN del PC di sviluppo, non `localhost:8081`) — necessario perché `base_url()` genera i link assoluti in base a quel valore fisso, non in base all'host da cui arriva la richiesta. Funziona identico anche da desktop (basta aprire lo stesso IP invece di `localhost`), quindi si può lasciare così come configurazione permanente di sviluppo.

Se l'IP del PC cambia (riconnessione Wi-Fi, rinnovo DHCP): controllare il nuovo indirizzo con `ipconfig` (voce "Indirizzo IPv4" della scheda Wi-Fi) e aggiornare `app.baseURL` di conseguenza. Per fermare il server: `Ctrl+C` nel terminale in cui gira.

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

Nel model: `$useTimestamps = true` per `created_at`/`updated_at`. I campi `created_by` e `updated_by` vengono popolati automaticamente dal callback `normalizza()` leggendo l'helper Shield **`user_id()`** (non `session()->get('user_id')` — vedi nota in "Note ambiente e troubleshooting"). `updated_by` va impostato in `$beforeUpdate`; `created_by` in `$beforeInsert` (e non va mai sovrascritto negli update).

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

## AdminLTE 4 — Layout card
Le convenzioni per card-header, card-footer e card-tools vanno documentate qui man mano che si scopre il comportamento reale di AdminLTE 4 con Bootstrap 5. Non usare i pattern del vecchio progetto (`float-left`/`float-right`, `clearfix`) — erano workaround di Bootstrap 4.

## Bottoni nelle card — colore ereditato
Usare variabili CSS custom per fare in modo che i bottoni dentro `.card-tools` ereditino automaticamente il colore della card, senza aggiungere classi di colore (`btn-primary` ecc.) nella view.

Il pattern: ogni classe di card definisce `--card-accent` e `--card-accent-text`; la regola CSS su `.card-tools .btn` legge quelle variabili. I bottoni nelle card colorate si colorano da soli — nella view basta `btn btn-sm`.

Le classi specifiche di AdminLTE 4 vanno documentate qui man mano che vengono scoperte. Non copiare i selettori del vecchio progetto (erano Bootstrap 4 / AdminLTE 3).

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

## Dominio aziendale
Il dominio aziendale è **colombini-snc.it**.
Usare per baseURL in produzione e qualsiasi riferimento al dominio. Non usare indirizzi email su questo dominio — non esistono caselle attive.
