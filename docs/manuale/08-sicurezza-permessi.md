# 8. Sicurezza e permessi

## 8.1 Autenticazione

L'autenticazione è affidata a **CodeIgniter Shield**, scelto per non riscrivere sessioni,
hashing e gruppi. Il filtro globale `session` protegge tutte le rotte: senza login non si
raggiunge nulla. La protezione CSRF è quella integrata nel framework, applicata a tutti i
form tramite `csrf_field()`.

Shield ha la funzione "ricordami" attiva con durata di 30 giorni. Non è un dettaglio
marginale per questo progetto: il login frequente è la prima fonte di frustrazione per
utenti poco tecnici che aprono il gestionale dal telefono in cantiere.

Il filtro `NoAuth` reindirizza alla dashboard chi è già autenticato e apre la pagina di
login. È applicato **sia** al `GET` sia al `POST` — vedi il caso descritto al capitolo 2.10.

## 8.2 Gruppi e permessi

Gruppi e permessi **non stanno nel database**: sono dichiarati in
`app/Config/AuthGroups.php`. Il gruppo assegnato per impostazione predefinita a un nuovo
utente è `ufficio`.

I sette permessi esistenti:

| Permesso | Cosa protegge |
|---|---|
| `personale.manage` | anagrafica del personale e gestione degli account |
| `impostazioni.manage` | l'intera sezione Impostazioni |
| `abbonamenti.manage` | creazione, modifica e cambio di stato degli abbonamenti |
| `cantieri.manage` | creazione, modifica, cambio di stato ed eliminazione dei cantieri |
| `clienti.elimina` | eliminazione di un cliente |
| `interventi.elimina` | eliminazione di un intervento (distinta dall'annullamento) |
| `magazzino.elimina` | eliminazione di un articolo |

La matrice che li assegna ai gruppi:

| Gruppo | Permessi |
|---|---|
| `admin`, `developer` | tutti e sette |
| `ufficio` | tutti tranne `impostazioni.manage` |
| `tecnico`, `cliente` | nessuno |

I permessi si applicano alle rotte con il filtro `permission:<nome>`, e i due insiemi sono
raccolti in due costanti (`PERMESSI_ADMIN` e `PERMESSI_UFFICIO`) invece di essere ripetuti.

**Perché permessi e non gruppi direttamente sulle rotte.** Un filtro
`group:admin,developer,ufficio` funzionerebbe, ma l'elenco dei gruppi andrebbe ripetuto e
tenuto allineato in ogni punto in cui compare. Con i permessi quell'elenco vive solo nella
matrice: dare `personale.manage` a un gruppo in più, o spezzarlo in permessi più fini,
significa toccare un solo file senza rivedere le rotte.

I permessi dello scaffolding di Shield (`admin.access`, `users.create`, `beta.access`…)
sono stati **rimossi**, non lasciati inutilizzati accanto ai nuovi: nessuna rotta li
richiedeva, quindi restare avrebbero solo dato l'impressione di una protezione che non
c'era.

## 8.3 Due tipi diversi di restrizione

La distinzione è importante perché richiede implementazioni diverse.

**Restrizione di modulo** — "tutto o niente, uguale per tutti i tecnici". Si risolve con un
permesso Shield sul gruppo di rotte, isolando le rotte che scrivono da quelle che leggono:

```php
$routes->group('abbonamenti', function ($routes) {
    $routes->get('/',      'AbbonamentiController::index');   // lettura: aperta
    $routes->get('(:num)', 'AbbonamentiController::show/$1');

    $routes->group('', ['filter' => 'permission:abbonamenti.manage'], function ($routes) {
        $routes->post('store', 'AbbonamentiController::store'); // scrittura: protetta
        // ...
    });
});
```

**Restrizione di proprietà** — "è il mio o no?". Un permesso non basta, perché Shield
assegna i permessi per gruppo e non per singolo record. Serve un controllo nel controller,
dopo aver caricato il record e prima di modificarlo:

```php
private function accessoConsentito(?int $interventoTecnicoId): bool
{
    if (! is_solo_tecnico()) return true;   // chi gestisce non ha restrizioni

    $tecnicoLoggato = (new PersonaleModel)->perUtente(auth()->user()->id)['id'] ?? null;
    // ... confronto fra il tecnico dell'intervento e il proprio
}
```

Viene richiamato da `inizia()`, `chiudi()`, `annulla()` e `update()` di
`InterventiController`. Un intervento non ancora assegnato risulta bloccato per qualunque
tecnico puro, ed è corretto: non ha senso segnare "inizio lavoro" su un intervento che non
ti è stato assegnato.

> Il parametro è `?int` e non `int`: dichiarato come `int` produceva un `TypeError` a
> runtime ogni volta che un tecnico apriva un intervento senza tecnico assegnato — corretto
> in v0.25.1.

**La coerenza dell'interfaccia va curata a parte.** Un bottone visibile che risponde con un
errore è funzionalmente corretto ma confonde: la scheda intervento nasconde le azioni quando
il tecnico non può agire su quel record, e mostra il bottone di eliminazione solo a chi ha
il permesso.

### La policy per il ruolo tecnico

| Modulo | Cosa può fare un tecnico puro |
|---|---|
| Abbonamenti | sola lettura |
| Cantieri | sola lettura, stampa PDF compresa |
| Magazzino | creare e modificare articoli; **non** eliminarli |
| Clienti | tutto tranne l'eliminazione |
| Interventi | avviare, completare, annullare e modificare **solo i propri**; mai eliminare |

Le sezioni in sola lettura **compaiono nel menu** anche per i tecnici puri: se possono
consultarle, ha senso che le trovino invece di doverne conoscere l'URL. Resta nascosta solo
Impostazioni, dove non hanno nemmeno accesso in lettura.

Alcune scelte meritano la motivazione. Bloccare del tutto Abbonamenti e Cantieri è stato
scartato: un tecnico sul posto può aver bisogno di controllare l'indirizzo di un cantiere o
le condizioni di un contratto. Un unico permesso generico `operativo.manage` è stato
scartato perché moduli diversi hanno titolari concettualmente diversi, e permessi separati
lasciano aperta la possibilità di un ruolo intermedio. La verifica di proprietà **anche per
l'eliminazione** ("il tecnico può eliminare i propri") è stata scartata: l'eliminazione è
distruttiva e senza recupero facile, diversamente dall'annullamento che lascia comunque il
record.

## 8.4 I due problemi di sicurezza già chiusi

Vale la pena conoscerli, perché sono lo stesso errore in due punti diversi: **la
protezione esisteva solo nel menu**.

### Privilege escalation e IDOR sul profilo (v0.24.23)

Prima di quella versione nessuna rotta era protetta lato server: il filtro globale
verificava il login, non il gruppo. La voce "Personale" era nascosta ai tecnici dal menu, ma
digitando l'URL ci si entrava comunque.

Due problemi distinti, non uno:

1. **Privilege escalation** — "Il mio profilo" reindirizzava allo stesso form che
   l'amministrazione usa per qualunque dipendente, caselle dei gruppi comprese. Un tecnico
   poteva selezionare "Amministratore" e salvare.
2. **IDOR** — la rotta `anagrafiche/personale/(:num)/edit` non verificava che l'identificativo
   nell'URL corrispondesse al dipendente dell'utente collegato: cambiando il numero si
   modificava, o eliminava, la scheda di chiunque.

La correzione ha introdotto i due permessi `personale.manage` e `impostazioni.manage` sui
gruppi di rotte, e ha separato il profilo personale in una pagina propria che risolve
sempre il dipendente lato server da `user_id()`, senza accettare alcun identificativo da URL
o POST. Il secondo problema è quindi chiuso **per costruzione**, non da un controllo che si
potrebbe dimenticare.

### Le altre sezioni scoperte (v0.24.31)

L'audit successivo ha trovato lo stesso schema altrove: un tecnico poteva modificare o
eliminare abbonamenti, cantieri, articoli di magazzino e interventi di chiunque, oltre a
eliminare clienti — quest'ultimo caso senza nemmeno la protezione visiva, perché la voce di
menu era visibile a tutti. Da qui i cinque permessi rimanenti e il controllo di proprietà
descritti sopra.

## 8.5 Cosa resta scoperto

Va detto esplicitamente, perché sono scelte consapevoli e non dimenticanze:

- **Calendario e foglio di viaggio** sono raggiungibili per intero anche dai tecnici puri.
  Un tecnico può quindi trascinare o ripianificare interventi di colleghi e generare il
  foglio di viaggio altrui. Non è stato toccato perché cambierebbe un flusso oggi
  funzionante senza una richiesta esplicita — da riprendere se risulta un problema
  nell'uso quotidiano.
- **Materiali** (`operativo/materiali/*`) non hanno controllo di proprietà: sono legati a un
  intervento quando hanno `intervento_id`, ma anche a un cliente quando sono sospesi, e
  estendere la stessa logica richiederebbe distinguere i due casi.
- **Promemoria** restano aperti a qualunque utente autenticato.
- **Non esiste alcuna suite di test automatici** per queste rotte: la verifica è manuale,
  entrando come tecnico e provando gli URL diretti.

## 8.6 Dati personali

`clienti` e `clienti_adhoc` contengono dati anagrafici e fiscali di persone reali — nomi,
indirizzi, telefoni, codici fiscali e partite IVA — di circa 2600 soggetti fra anagrafica
attiva e archivio storico. Valgono le normali cautele: accesso solo autenticato, nessuna
esposizione pubblica, backup cifrati.

È prevista una revisione della sicurezza del server con il sistemista prima della messa in
produzione. Il piano di disponibilità prevede backup giornaliero del database su una
seconda VPS.

## 8.7 Prima della messa in produzione

**Il database di sviluppo va svuotato completamente.** Tutti i dati attuali — clienti,
interventi, materiali, abbonamenti — sono dati di prova: nessun record va migrato
dall'ambiente di sviluppo a quello di produzione.

Questo ha una conseguenza pratica di cui il codice tiene già conto in più punti: non serve
alcuna migration di pulizia per i dati sporchi prodotti da bug corretti in seguito, perché
quei dati non arriveranno mai in produzione.
