# Spec — Restrizioni server-side per il ruolo "tecnico"

> Concordata il 01.08.2026, ripresa dal branch mobile UX. Segue lo stesso
> problema già chiuso per personale/impostazioni in
> `rotte_protette_personale_spec.md` (10.R, v0.24.23), ma esteso a diversi
> moduli in più.

## Contesto / problema

Verificato leggendo `app/Config/Routes.php` e `app/Config/AuthGroups.php`:
oggi esistono solo due permessi Shield (`personale.manage`,
`impostazioni.manage`). Tutte le altre rotte — Clienti, Abbonamenti,
Cantieri, Interventi, Materiali, Magazzino/Articoli — sono protette **solo**
dal filtro di sessione generico (utente autenticato), nessun controllo di
ruolo.

Il menu (`admin.php`, helper `is_solo_tecnico()`) nascondeva ai tecnici puri
(gruppo `tecnico` e nessun altro gruppo di gestione) le voci Personale,
Abbonamenti, Cantieri, Magazzino, Impostazioni — ma era **solo estetica**: un
tecnico che apre direttamente l'URL (o lo intuisce/salva da un link
condiviso) poteva comunque:

- modificare/cambiare stato di un abbonamento (`abbonamenti/(:num)/update`,
  `/stato`, `/rinnova`);
- modificare, cambiare stato o **eliminare** un cantiere
  (`cantieri/(:num)/update`, `/stato`, `/delete`);
- modificare o **eliminare** un articolo di magazzino, prezzi inclusi
  (`magazzino/articoli/(:num)/update`, `/delete`);
- modificare, annullare o **eliminare** un intervento **di qualsiasi
  tecnico**, non solo i propri (`operativo/interventi/(:num)/update`,
  `/annulla`, `/delete`);
- **eliminare** un cliente (`anagrafiche/clienti/(:num)/delete`) — qui la
  voce di menu è invece visibile a tutti, quindi il gap non è "nascosto ma
  raggiungibile": è proprio senza alcun controllo, nemmeno visivo.

## Soluzione concordata

Decisioni prese punto per punto con l'utente:

| Modulo | Policy per il tecnico |
|---|---|
| Abbonamenti | Sola lettura (index/show): può consultare, non creare/modificare/cambiare stato/rinnovare |
| Cantieri | Sola lettura (index/show/pdf): può consultare, non creare/modificare/cambiare stato/eliminare |
| Magazzino/Articoli | Può creare e modificare articoli (a differenza degli altri moduli, deciso in fase di implementazione — vedi sotto); solo l'eliminazione resta riservata a ufficio/admin/developer |
| Clienti | Nessuna restrizione tranne l'eliminazione, che resta riservata a ufficio/admin/developer |
| Interventi | `inizia`/`chiudi`/`annulla`/`update` consentiti **solo sui propri** (tecnico_id = il proprio); `delete` **mai**, riservato a ufficio/admin/developer indipendentemente dalla proprietà |

### Due meccanismi diversi, per due tipi diversi di restrizione

È importante distinguerli bene, perché richiedono implementazioni diverse:

1. **Restrizione di modulo** (Abbonamenti/Cantieri/Magazzino: tutto o
   niente, uguale per tutti i tecnici) → **permesso Shield sulla rotta**,
   stesso meccanismo già usato per `personale.manage`/`impostazioni.manage`:
   un filtro `permission:xxx.manage` su un gruppo di rotte.
2. **Restrizione di proprietà** (Interventi: dipende dal singolo record,
   "è il mio o no?") → un permesso Shield da solo non basta, perché Shield
   assegna permessi per gruppo, non per record. Serve un **controllo nel
   controller**, dopo aver caricato l'intervento dal DB, che confronta
   `tecnico_id` con l'utente loggato.

### Permessi Shield nuovi

In `AuthGroups.php`, stesso pattern di `personale.manage`/
`impostazioni.manage` (assegnati a `admin`/`developer`/`ufficio`, mai a
`tecnico`):

```php
public array $permissions = [
    'personale.manage'    => 'Può gestire anagrafica personale e account',
    'impostazioni.manage' => 'Può accedere alle impostazioni applicative',
    'abbonamenti.manage'  => 'Può creare, modificare e cambiare stato agli abbonamenti',
    'cantieri.manage'     => 'Può creare, modificare, cambiare stato ed eliminare i cantieri',
    'clienti.elimina'     => 'Può eliminare un cliente',
    'interventi.elimina'  => 'Può eliminare un intervento (distinto da annulla)',
    'magazzino.elimina'   => 'Può eliminare gli articoli di magazzino',
];
```

`clienti.elimina`, `interventi.elimina` e `magazzino.elimina` sono
**volutamente più stretti** di
un generico `.manage`: per Clienti, Interventi e Magazzino solo
l'eliminazione va bloccata (le altre azioni restano aperte, per Interventi
con l'aggiunta della verifica di proprietà — vedi sotto), quindi un nome che
descrive esattamente cosa gate-a evita ambiguità future.

**Nota emersa in implementazione**: inizialmente il piano per Magazzino era
"sola lettura", come per Abbonamenti/Cantieri. In corso d'opera l'utente ha
deciso diversamente: i tecnici possono creare e modificare articoli di
magazzino liberamente (nessun modulo di gestione è "sensibile" quanto
contratti/cantieri), resta riservata solo l'eliminazione — coerente con la
policy già scelta per Clienti e Interventi ("mai per i tecnici, anche se
l'hanno creato loro", scartata l'alternativa più granulare "elimina solo se
creato da te" per restare su un'unica regola semplice in tutto il
gestionale, invece di una regola diversa per ogni modulo).

### Rotte: split tra lettura (aperta) e scrittura (permesso)

Per Abbonamenti e Cantieri, dentro il gruppo esistente, isolare le
rotte mutanti (POST + le GET che comunque cambiano stato, es. `rinnova`) in
un sotto-gruppo con prefisso vuoto e il filtro di permesso — pattern
standard CI4, coerente con "raggruppare le rotte per sezione" di CLAUDE.md.
Per Magazzino, isolare solo `delete` (stesso schema di Clienti/Interventi,
vedi sotto):

```php
$routes->group('cantieri', function ($routes) {
    // Lettura: nessuna restrizione oltre l'essere autenticati
    $routes->get('/',          'CantieriController::index');
    $routes->get('(:num)',     'CantieriController::show/$1');
    $routes->get('(:num)/pdf', 'CantieriController::pdf/$1');

    // Scrittura: solo chi ha cantieri.manage
    $routes->group('', ['filter' => 'permission:cantieri.manage'], function ($routes) {
        $routes->get('nuovo',             'CantieriController::nuovo');
        $routes->post('store',            'CantieriController::store');
        $routes->get('(:num)/edit',       'CantieriController::edit/$1');
        $routes->post('(:num)/update',    'CantieriController::update/$1');
        $routes->post('(:num)/stato',     'CantieriController::cambiaStato/$1');
        $routes->post('(:num)/delete',    'CantieriController::delete/$1');
        $routes->post('(:num)/posizione', 'CantieriController::aggiornaPosizione/$1');
        $routes->post('note/aggiungi',       'CantieriController::aggiungiNota');
        $routes->post('note/(:num)/elimina', 'CantieriController::eliminaNota/$1');
    });
});
```

Stessa struttura per `abbonamenti` (lettura: `/`, `(:num)`; scrittura:
`nuovo`, `store`, `edit`, `update`, `stato`, `rinnova`).

Per **Clienti** e **Magazzino/Articoli**, solo la rotta `delete` va isolata
(tutto il resto resta aperto, decisione esplicita):

```php
$routes->group('clienti', function ($routes) {
    // ... tutte le rotte esistenti invariate, tranne delete ...
    $routes->group('', ['filter' => 'permission:clienti.elimina'], function ($routes) {
        $routes->post('(:num)/delete', 'Anagrafiche\ClientiController::delete/$1');
    });
});
```

Per **Interventi**, solo `delete` va isolata allo stesso modo (permesso
`interventi.elimina`); `inizia`/`chiudi`/`annulla`/`update` restano aperte a
livello di rotta ma ottengono il controllo di proprietà nel controller (vedi
sotto) — non possono essere un filtro di rotta perché dipendono dal singolo
record, non dal ruolo.

### Controllo di proprietà per Interventi

In `InterventiController`, un metodo privato riusabile:

```php
/**
 * Un tecnico "puro" può agire solo sui propri interventi assegnati.
 * Chi ha un ruolo di gestione (ufficio/admin/developer) non ha restrizioni.
 */
private function accessoConsentito(array $intervento): bool
{
    if (! is_solo_tecnico()) {
        return true;
    }

    $personale = (new PersonaleModel())->perUtente(user_id());

    return $personale !== null && (int) $intervento['tecnico_id'] === (int) $personale['id'];
}
```

Da richiamare in `inizia()`, `chiudi()`, `annulla()`, `update()` **dopo**
aver caricato l'intervento dal DB e **prima** di eseguire la modifica:

```php
if (! $this->accessoConsentito($intervento)) {
    return redirect()->to('operativo/interventi/' . $id)
        ->with('error', 'Non hai i permessi per modificare questo intervento.');
}
```

Un intervento non ancora assegnato (`tecnico_id` NULL) risulta bloccato per
qualunque tecnico puro — corretto: non ha senso che un tecnico segni
"Inizio lavoro" su un intervento che non gli è stato assegnato.

### Coerenza con la UI (view)

Oggi `interventi/show.php` mostra i bottoni azione (Inizio lavoro, Completa,
Annulla, Modifica, Elimina) **senza alcuna condizione di proprietà** — un
tecnico che apre l'intervento di un collega li vede comunque, e cliccandoli
otterrebbe ora un redirect con errore invece dell'azione: funzionalmente
corretto ma un'esperienza confusa (bottone visibile che "non funziona").

Va aggiunta la stessa condizione di `accessoConsentito()` (esposta alla view
come variabile, es. `$puoAgire`) attorno al blocco Inizio lavoro/Completa/
Annulla; il bottone Elimina va condizionato al permesso
`auth()->user()->can('interventi.elimina')` (prima volta che si usa `->can()`
in una view di questo progetto — l'unica alternativa fin qui era
`is_solo_tecnico()` per nascondere voci di menu).

## Deviazioni emerse in fase di implementazione

- **Ufficio perde l'accesso a Impostazioni**: decisione presa dall'utente
  durante l'implementazione, non discussa nel brainstorming iniziale di
  questa spec. Prima di questa spec `ufficio` aveva `impostazioni.manage`
  fin da `rotte_protette_personale_spec.md` (10.R, v0.24.23, deciso
  esplicitamente insieme in quella sessione). Con questa spec la matrice
  distingue `PERMESSI_ADMIN` (con `impostazioni.*`) da `PERMESSI_UFFICIO`
  (senza) — solo admin/developer possono più accedere a Impostazioni,
  ufficio no. Non riguarda i tecnici, è un restringimento separato per lo
  staff ufficio.

## Alternative scartate

- **Bloccare interamente Abbonamenti/Cantieri/Magazzino ai tecnici** (stesso
  trattamento di Personale/Impostazioni): scartata dall'utente — servono in
  sola lettura, es. un tecnico può aver bisogno di controllare l'indirizzo
  di un cantiere o le condizioni di un abbonamento sul posto.
- **Un unico permesso generico `operativo.manage`** invece di permessi
  separati per modulo: scartato — moduli diversi (abbonamenti, cantieri,
  magazzino) hanno owner concettualmente diversi (ufficio commerciale,
  cantieristica, magazzino); permessi separati permettono in futuro di
  assegnarli anche a un utente `ufficio` solo su un sottoinsieme, se mai
  servisse un ruolo intermedio.
- **Verifica di proprietà anche per `delete`** (tecnico può eliminare i
  propri interventi): scartata dall'utente — l'eliminazione è distruttiva e
  senza recupero facile (diversa da "annulla", che lascia comunque il record
  con stato `annullato`), va tenuta riservata a chi gestisce l'anagrafica
  interventi.

## Modifiche, file per file

1. **`app/Config/AuthGroups.php`** — 5 nuovi permessi (vedi sopra) e
   relativa matrice (`admin`/`developer`/`ufficio` li hanno tutti,
   `tecnico` nessuno).
2. **`app/Config/Routes.php`** — split lettura/scrittura per i gruppi
   `abbonamenti`, `cantieri`, `magazzino/articoli` (sotto-gruppo con
   `permission:xxx.manage`); isolata la sola rotta `delete` per `clienti`
   (`permission:clienti.elimina`) e per `operativo/interventi`
   (`permission:interventi.elimina`).
3. **`app/Controllers/Operativo/InterventiController.php`** — nuovo metodo
   privato `accessoConsentito(array $intervento): bool`; richiamato in
   `inizia()`, `chiudi()`, `annulla()`, `update()` prima di eseguire la
   mutazione.
4. **`app/Views/operativo/interventi/show.php`** — footer azioni: Inizio
   lavoro/Completa/Annulla condizionati a `$puoAgire` (passata dal
   controller, stesso valore di `accessoConsentito()`); Elimina condizionato
   a `auth()->user()->can('interventi.elimina')`.

## Fuori scope

- **Materiali** (`operativo/materiali/store`, `/delete`): non toccati in
  questa spec. Sono legati a un intervento (quando hanno `intervento_id`) ma
  anche a materiali "sospesi" per un cliente senza intervento — estendere la
  stessa logica di proprietà richiederebbe distinguere i due casi, da
  valutare a parte se emerge un problema reale.
- **Calendario/Viaggio** (`operativo/calendario/sposta`,
  `/genera-viaggio`, `operativo/viaggi/*`): la voce di menu "Calendario" è
  oggi visibile e raggiungibile per intero anche dai tecnici puri (non è
  dentro il blocco `$_soloTecnico` di `admin.php`, a differenza di
  Abbonamenti/Cantieri/Magazzino) — un tecnico può quindi trascinare/
  ripianificare interventi di colleghi o generare il foglio di viaggio
  altrui. Non toccato qui perché non è il punto sollevato dall'utente
  ("cancellare") e cambia un flusso oggi funzionante senza una richiesta
  esplicita — da riprendere con un brainstorming dedicato se risulta un
  problema reale nell'uso quotidiano.
- **Promemoria** (`promemoria/store`, `/update`, `/delete`, `/dismiss`):
  non toccati, non menzionati nella discussione — restano aperti a
  qualunque utente autenticato come oggi.
- **Menu**: Abbonamenti, Cantieri e Magazzino/Articoli sono ora visibili nel
  menu anche ai tecnici puri (tolto il blocco `$_soloTecnico` che le
  nascondeva) — coerente con la policy di sola lettura decisa in questa
  spec: se un tecnico può consultarle, ha senso che le trovi in menu invece
  di doverne conoscere l'URL. Resta nascosta solo "Impostazioni" (sezione
  "Amministrazione"), dato che lì il tecnico non ha nemmeno accesso in
  lettura. Nessuna modifica di codice necessaria oltre a questa (già fatta).
