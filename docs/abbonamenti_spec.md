# Modulo Abbonamenti — Specifica v0.14.0

> Questo documento sostituisce la versione precedente (vecchio flusso "silenzioso").
> Tutto ciò che era nella versione precedente è obsoleto.

---

## Concetto

Un **abbonamento** è il contratto ricorrente con un cliente (es. manutenzione piscina mensile,
addolcitore quindicinale). È il punto di partenza: dall'abbonamento si generano in batch tutti
gli interventi previsti per il periodo. L'intervento è l'esecuzione, non il contrario.

Un cliente può avere più abbonamenti attivi contemporaneamente (es. uno per piscine, uno per
addolcitori).

---

## Schema database

### Tabella `abbonamenti`

```sql
CREATE TABLE abbonamenti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    cliente_id INT UNSIGNED NOT NULL
        COMMENT 'FK → clienti.id',

    tipo_intervento_id INT UNSIGNED NOT NULL
        COMMENT 'FK → tipi_intervento.id — stesso tipo per tutti gli interventi generati',

    abbonamento_precedente_id INT UNSIGNED NULL
        COMMENT 'FK → abbonamenti.id — punta al rinnovo precedente; permette di ricostruire la catena storica con CTE ricorsiva',

    frequenza VARCHAR(20) NOT NULL
        COMMENT 'settimanale | quindicinale | mensile | bimestrale | trimestrale | semestrale | annuale',

    data_inizio DATE NOT NULL
        COMMENT 'tipicamente 01/01 — primo giorno di validità',

    data_fine DATE NOT NULL
        COMMENT 'tipicamente 31/12 — ultimo giorno di validità; se < CURDATE() e stato = attivo → visualizzato come scaduto',

    durata_mesi INT UNSIGNED NULL
        COMMENT 'calcolato automaticamente dal model al salvataggio — non passare dal controller',

    prezzo DECIMAL(10,2) NULL
        COMMENT 'totale abbonamento per il periodo intero, non prezzo per singola visita',

    stato VARCHAR(20) NOT NULL DEFAULT 'attivo'
        COMMENT 'attivo | sospeso | scaduto | disdetto — scaduto è calcolato a runtime, non scritto da cron',

    note TEXT NULL,

    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,

    FOREIGN KEY (cliente_id)                REFERENCES clienti(id),
    FOREIGN KEY (tipo_intervento_id)        REFERENCES tipi_intervento(id),
    FOREIGN KEY (abbonamento_precedente_id) REFERENCES abbonamenti(id)
);
```

### Modifica tabella `interventi`

Aggiungere la FK diretta (sostituisce la vecchia ipotesi di pivot `abbonamenti_interventi`):

```sql
ALTER TABLE interventi
    ADD COLUMN abbonamento_id INT UNSIGNED NULL
        COMMENT 'FK → abbonamenti.id — NULL se intervento standalone; NOT NULL se generato da un abbonamento',
    ADD FOREIGN KEY (abbonamento_id) REFERENCES abbonamenti(id);
```

Un intervento appartiene a un solo abbonamento (one-to-many). La pivot non serve.

### Modifica colonna `priorita` in `interventi`

```sql
-- rinomina del valore 'programmato' → 'abbonamento'
ALTER TABLE interventi MODIFY COLUMN priorita VARCHAR(20) NOT NULL DEFAULT 'normale'
    COMMENT 'abbonamento | normale | urgente';

UPDATE interventi SET priorita = 'abbonamento' WHERE priorita = 'programmato';
```

---

## AbbonamentiModel — costanti

```php
// valori frequenza
const FREQUENZA_SETTIMANALE  = 'settimanale';   //  ~52 interventi/anno
const FREQUENZA_QUINDICINALE = 'quindicinale';  //  ~26 interventi/anno
const FREQUENZA_MENSILE      = 'mensile';        //  12 interventi/anno
const FREQUENZA_BIMESTRALE   = 'bimestrale';     //   6 interventi/anno
const FREQUENZA_TRIMESTRALE  = 'trimestrale';    //   4 interventi/anno
const FREQUENZA_SEMESTRALE   = 'semestrale';     //   2 interventi/anno
const FREQUENZA_ANNUALE      = 'annuale';        //   1 intervento/anno

const FREQUENZE_LABEL = [
    'settimanale'  => 'Settimanale',
    'quindicinale' => 'Quindicinale',
    'mensile'      => 'Mensile',
    'bimestrale'   => 'Bimestrale',
    'trimestrale'  => 'Trimestrale',
    'semestrale'   => 'Semestrale',
    'annuale'      => 'Annuale',
];

// valori stato
const STATO_ATTIVO   = 'attivo';   // in corso
const STATO_SOSPESO  = 'sospeso';  // temporaneamente sospeso — interventi figli → sospeso
const STATO_SCADUTO  = 'scaduto';  // termine naturale (data_fine < oggi) — calcolato a runtime
const STATO_DISDETTO = 'disdetto'; // cliente ha disdetto — terminale

const STATI_LABEL = [
    'attivo'   => 'Attivo',
    'sospeso'  => 'Sospeso',
    'scaduto'  => 'Scaduto',
    'disdetto' => 'Disdetto',
];
```

---

## InterventiModel — modifiche in v0.14.0

### Rinomina priorità

```php
// prima
const PRIORITA_PROGRAMMATO = 'programmato';

// dopo
const PRIORITA_ABBONAMENTO = 'abbonamento'; // intervento generato da un abbonamento
const PRIORITA_NORMALE     = 'normale';
const PRIORITA_URGENTE     = 'urgente';
```

### Nuovo stato `sospeso`

```php
// valori: da_pianificare, pianificato, in_corso, completato, annullato, sospeso
const STATO_SOSPESO = 'sospeso'; // in pausa per abbonamento sospeso — potenzialmente recuperabile
```

### allowedFields

Aggiungere `abbonamento_id` alla lista.

---

## Generazione batch interventi

Al salvataggio di un nuovo abbonamento, il controller genera tutti gli interventi del periodo
**in una singola transazione** (abbonamento + tutti gli interventi insieme o rollback).

### Calcolo date di scadenza per periodo

La `data_scadenza` di ogni intervento è la **fine del periodo di competenza**:

| Frequenza    | Logica data_scadenza                                          |
|--------------|---------------------------------------------------------------|
| mensile      | ultimo giorno del mese (31/01, 28/02, 31/03…)                |
| bimestrale   | ultimo giorno del 2° mese del blocco (28/02, 30/04, 30/06…)  |
| trimestrale  | ultimo giorno del 3° mese del blocco (31/03, 30/06, 30/09…)  |
| semestrale   | ultimo giorno del 6° mese del blocco (30/06, 31/12)          |
| annuale      | data_fine dell'abbonamento (31/12)                            |
| settimanale  | data_inizio + n×7 giorni                                      |
| quindicinale | data_inizio + n×14 giorni                                     |

### Attributi degli interventi generati

```php
[
    'cliente_id'         => $abbonamento['cliente_id'],
    'abbonamento_id'     => $abbonamentoId,
    'tipo_intervento_id' => $abbonamento['tipo_intervento_id'],
    'priorita'           => InterventiModel::PRIORITA_ABBONAMENTO,
    'stato'              => InterventiModel::STATO_DA_PIANIFICARE,
    'data_pianificata'   => null,
    'data_scadenza'      => $fineDelPeriodo,  // vedi tabella sopra
    'durata_stimata'     => null,             // prende default dal tipo_intervento nel calendario
]
```

Il `codice` viene generato automaticamente dal callback `normalizza()` già esistente.

---

## Comportamento cambio stato abbonamento

### `attivo` → `sospeso`

Interventi figli con `data_scadenza > oggi` e stato `da_pianificare` → `sospeso`.

### `sospeso` → `attivo`

Modale di conferma: **"Vuoi ripristinare gli interventi sospesi?"**
- **Sì** → interventi `sospeso` con `data_scadenza > oggi` → `da_pianificare` (tornano nel pool)
- **No** → interventi `sospeso` → `annullato`

### `attivo` o `sospeso` → `disdetto`

Interventi figli con stato `da_pianificare` o `sospeso` → `annullato`. Terminale, nessun ripristino.

### Fine naturale (`data_fine < oggi`)

Stato `scaduto` calcolato a runtime nelle query, non scritto su DB.
Nessun effetto sugli interventi figli (fine naturale: dovrebbero essere già completati o annullati dal dispatcher).

---

## Rinnovo

Il bottone **Rinnova** è disponibile su ogni abbonamento che:
1. Non è `disdetto`
2. Non ha già un successore (nessun record con `abbonamento_precedente_id = questo.id`)

Il rinnovo apre il form precompilato con i dati dell'abbonamento corrente
(tipo, frequenza, prezzo, note) e le date spostate di un anno. Tutti i campi sono modificabili.

Al salvataggio: nuovo abbonamento con `abbonamento_precedente_id = vecchio.id` + generazione
batch interventi per il nuovo periodo.

---

## Interfaccia

### Index globale (sidebar "Abbonamenti")

Tabella con colonne: Cliente, Tipo, Frequenza, Periodo (da → a), Stato, Rinnova.
Filtri rapidi per stato. Bottone "Nuovo abbonamento".
Il bottone Rinnova appare solo se le condizioni sopra sono soddisfatte.

### Scheda cliente — tab "Abbonamenti"

Mini tabella con gli abbonamenti del cliente (storico completo, ordinato per data_inizio DESC).
Colonne: Tipo, Frequenza, Periodo, Prezzo, Stato, Rinnova.
Bottone "Nuovo abbonamento" (precompilato con cliente_id).

### Scheda abbonamento (show)

- Dati chiave: cliente, tipo, frequenza, periodo, prezzo, stato calcolato
- Bottoni: Modifica dati, Cambia stato, Rinnova
- Tabella interventi figli: codice, data_scadenza, stato, tecnico assegnato, data_pianificata
  (ordinati per data_scadenza ASC)

---

## Note implementative

- `durata_mesi` calcolato nel callback `normalizza()` del model — non passare dal controller
- Generazione interventi in transazione — rollback se qualsiasi insert fallisce
- La priorità `abbonamento` degli interventi figli **non è modificabile** dalla scheda intervento
  quando `abbonamento_id IS NOT NULL` (il campo è di proprietà dell'abbonamento)
- La tabella `abbonamenti_interventi` (vecchio spec) non va creata
- `scaduto` non viene scritto a DB in questa versione — calcolato a runtime con CASE WHEN
