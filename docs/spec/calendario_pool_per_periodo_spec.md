# Spec — Pool "da pianificare" agganciato al periodo visibile del calendario

> Secondo bug di fondo emerso discutendo lo spec `chiusura_intervento_materiali_spec.md` (punto 6.R): il pool mostra oggi *tutto il mese corrente* in blocco, il che permette di pianificare più occorrenze dello stesso abbonamento fuori dal loro ordine cronologico reale, creando una finestra in cui un materiale sospeso nato dopo non può più essere intercettato dal punto di pianificazione. Va risolto restringendo cosa il pool mostra, non aggiungendo meccanismi compensativi a valle.

## 1. Il problema

`InterventiModel::poolDaPianificare()` (righe 257-283) mostra le occorrenze regolari da abbonamento (`abbonamento_id` valorizzato, `extra = 0`) con un solo filtro: `data_scadenza <= LAST_DAY(CURDATE())`. Questo include **tutte** le occorrenze del mese corrente, non solo quelle imminenti — dal giorno 1 del mese sono già visibili anche le occorrenze del giorno 28.

Conseguenza pratica: un abbonamento settimanale con 4 occorrenze a luglio (07/07, 14/07, 21/07, 28/07) le mostra tutte e 4 insieme dal 1° luglio. Il dispatcher può pianificarle tutte in un colpo solo, in qualunque ordine. Se un materiale sospeso nasce *dopo* (es. chiudendo la visita del 07/07 e scoprendo che manca del materiale) quando la visita del 14/07 è già `pianificato` da giorni, qualunque meccanismo legato "al momento in cui si pianifica" non ha più occasione di scattare per quell'occorrenza specifica.

## 2. Soluzione: il pool segue il periodo visibile sul calendario, non il mese fisso

Il calendario (`calendario.js`, FullCalendar) ha già `initialView: timeGridWeek` (desktop) / `timeGridDay` (mobile) e naviga con i controlli `prev`/`next` già esistenti. Il pool va agganciato allo stesso range visibile, invece di essere calcolato una volta sola lato server sul mese corrente.

### Comportamento

- Le occorrenze regolari da abbonamento compaiono nel pool solo se `data_scadenza` è **entro la fine del periodo attualmente visibile sul calendario** (non più "entro fine mese" fisso).
- Navigando con `prev`/`next` (o cambiando vista), il pool si aggiorna di conseguenza: si vedono solo le occorrenze della settimana (o giorno, su mobile) in cui ci si è spostati.
- **Interventi normali (`abbonamento_id IS NULL`) e visite extra (`extra = 1`) restano sempre visibili nel pool, indipendentemente dal periodo visualizzato** — non sono legati a una cadenza di abbonamento, sono già filtrati per stato `da_pianificare` e basta, esattamente come oggi. Non hanno motivo di sparire solo perché si guarda una settimana diversa.
- Le occorrenze da abbonamento già in ritardo (scadute nel passato, mai pianificate) restano **sempre visibili** qualunque periodo si stia guardando: il filtro resta un *upper bound* soltanto (`data_scadenza <= fine periodo visibile`), esattamente come oggi è `<= LAST_DAY(CURDATE())` senza limite inferiore — cambia solo *quale* data usare come tetto superiore, non la logica del confronto. Andare avanti nel tempo con `next` non nasconde mai un arretrato, allarga solo in avanti quali occorrenze *future* diventano visibili.

### Perché questa soluzione e non un filtro fisso più stretto

Si era considerato in alternativa un filtro fisso "solo questa settimana" (calcolato lato server in PHP con `DateTime('monday this week')`), ma legarlo al periodo **effettivamente visibile sul calendario** è più corretto e generale: funziona automaticamente sia in vista Settimana sia in vista Giorno (mobile) senza bisogno di due logiche diverse, e resta coerente se in futuro si aggiungesse una vista Mese (il pool si allargherebbe di conseguenza, senza bisogno di ridiscutere la regola).

## 3. Modifiche tecniche

### 3.1 Model

`InterventiModel::poolDaPianificare()` accetta un parametro esplicito invece del valore fisso `LAST_DAY(CURDATE())`:

```php
public function poolDaPianificare(string $finePeriodo): array
{
    return $this->select(...)
        ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
        ->where('interventi.stato', self::STATO_DA_PIANIFICARE)
        ->groupStart()
            ->where('interventi.abbonamento_id IS NULL', null, false)
            ->orWhere('interventi.extra', 1)
            ->orGroupStart()
                ->where('interventi.abbonamento_id IS NOT NULL', null, false)
                ->where('interventi.extra', 0)
                ->where('interventi.data_scadenza <=', $finePeriodo)
            ->groupEnd()
        ->groupEnd()
        ->orderBy('interventi.urgenza', 'DESC')
        ->orderBy('interventi.created_at', 'ASC')
        ->findAll();
}
```

Passare `$finePeriodo` come parametro legato (query builder) invece della stringa raw `LAST_DAY(CURDATE())` è anche l'occasione per eliminare l'unica espressione SQL raw rimasta in questo metodo, coerente con la convenzione del progetto di usare sempre il Query Builder.

### 3.2 Controller — caricamento iniziale

`CalendarioController::index()` calcola la fine del periodo iniziale (fine settimana corrente su desktop, fine giornata corrente su mobile — o più semplicemente: dato che il calendario sa già quale vista iniziale usa, si calcola lato server la fine settimana ISO corrente in entrambi i casi, dato che la vista Giorno è comunque contenuta nella settimana) e la passa a `poolDaPianificare()` invece del vecchio `LAST_DAY(CURDATE())` implicito.

### 3.3 Nuovo endpoint AJAX

Nuovo metodo `CalendarioController::poolPeriodo()`, che riceve `fine` (data ISO) via query string, richiama `poolDaPianificare($fine)` e restituisce **HTML già renderizzato** (non JSON — vedi correzione sotto).

Nuova rotta in `Routes.php`, nel gruppo già esistente delle rotte calendario.

### 3.4 JavaScript

> **Corretto in corso d'opera rispetto alla stesura iniziale**: qui sotto si era ipotizzato JSON + template JS (stesso pattern dei materiali sospesi in `nuovo.php`). Guardando il markup reale del pool (raggruppamento zone/sottogruppi con `id` di collapse annidati, lookup tipo intervento, badge priorità/urgenza, materiali per card) è emerso che non è un caso semplice come i sospesi (lì solo checkbox): riscrivere tutta quella logica di presentazione in JS avrebbe significato duplicarla e tenerla allineata a mano in due linguaggi. Scelto invece: il server rende lo stesso HTML che genera già oggi, il JS lo incolla con `innerHTML` — un'unica "ricetta" per la card, in PHP, richiamata da entrambi i punti d'ingresso. Vedi `docs/spec/calendario_pool_per_periodo_spec.md` §3.3 aggiornato e la spiegazione data in chat sul perché (event delegation di FullCalendar Draggable e dei toggle Bootstrap: agganciati al contenitore, non alle singole card, quindi continuano a funzionare anche su HTML sostituito via `innerHTML` senza reinizializzazione).
>
> Effetto collaterale accettato: lo stato aperto/chiuso dei collapse di zona/sottogruppo si resetta al default ad ogni refresh del pool (la sostituzione `innerHTML` ricrea gli elementi da zero) — giudicato accettabile perché il contenuto sottostante cambia comunque a ogni navigazione.
>
> **Bug reale scoperto testando**: `info.endStr` di FullCalendar non è una data pura ma include orario/fuso (es. `"2026-07-27T00:00:00+02:00"`), esattamente come già gestito altrove in `calendario.js` per il fetch eventi (`fetchInfo.endStr.substring(0, 10)`). Il primo tentativo ci appendeva sopra un secondo `'T00:00:00'`, producendo una stringa non interpretabile da `new Date()` (`Invalid Date` → `NaN-NaN-NaN` finito nella query SQL). Corretto estraendo prima i 10 caratteri della data, poi sottraendo un giorno per compensare l'estremo esclusivo di FullCalendar.

In `calendario.js`, il callback `datesSet` (oggi fa solo `montaInputVaiAData()`) chiama anche la nuova funzione `aggiornaPoolPeriodo(info.endStr)`, che calcola la fine periodo (correggendo l'estremo esclusivo) e sostituisce `#pool-container`/`#pool-count` con la risposta HTML del server.

## 4. Riepilogo modifiche file per file

1. **`app/Models/InterventiModel.php`**: `poolDaPianificare()` accetta `string $finePeriodo`, sostituendo `LAST_DAY(CURDATE())` raw con parametro legato.
2. **`app/Controllers/Operativo/CalendarioController.php`**: nuovo metodo privato `datiPool()` (raggruppamento zone/sottogruppi, condiviso); `index()` lo usa con la fine settimana ISO corrente come periodo iniziale; nuovo metodo `poolPeriodo()` per il fetch AJAX, restituisce la view parziale renderizzata.
3. **`app/Config/Routes.php`**: nuova rotta `pool-periodo` nel gruppo calendario esistente.
4. **`public/js/calendario.js`**: `datesSet` richiama `aggiornaPoolPeriodo()`, che sostituisce pool + contatore con l'HTML ricevuto.
5. **`app/Views/operativo/calendario/_pool.php`** (nuovo): markup delle card del pool, estratto da `index.php` — unica "ricetta" richiamata sia dal caricamento iniziale sia dall'endpoint AJAX. `index.php` ora richiama questa view parziale al posto del markup inline; `$prioritaInfo` (badge priorità) spostato dentro il partial, dato che serviva solo lì.

## 5. Fuori scope

- Vista Mese del calendario: non esiste ancora, questa spec non la introduce. Se verrà aggiunta in futuro, il meccanismo qui descritto si estende da solo (il pool seguirebbe comunque il periodo visibile).
- Un eventuale indicatore/contatore "materiali sospesi in attesa" sulla scheda cliente: discusso durante il brainstorming del punto 6.R ma reso meno urgente proprio da questa fix — resta comunque una possibilità futura, non necessaria ora.
