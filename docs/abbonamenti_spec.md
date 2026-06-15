# Modulo Abbonamenti — Specifica per Claude Code

## Contesto

Nel gestionale CI4, gli **interventi di tipo "programmato"** innescano silenziosamente la creazione di un abbonamento. L'utente vede solo una modale semplice (data inizio, data fine, frequenza); il sistema crea automaticamente le righe nelle tabelle `abbonamenti` e `abbonamenti_interventi`.

---

## Schema Database

```sql
CREATE TABLE abbonamenti (
    id                        INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id                INT NOT NULL,
    abbonamento_precedente_id INT NULL,          -- catena storica per reportistica
    data_inizio               DATE NOT NULL,
    data_fine                 DATE NULL,
    frequenza                 VARCHAR(20) NOT NULL,
    prezzo                    DECIMAL(10,2) NULL, -- totale abbonamento (non per visita)
    durata_mesi               INT NULL,           -- calcolato automaticamente dal Model
    stato                     VARCHAR(20) NOT NULL DEFAULT 'attivo',
    note                      TEXT NULL,
    created_at                DATETIME,
    updated_at                DATETIME,

    FOREIGN KEY (cliente_id)                REFERENCES clienti(id),
    FOREIGN KEY (abbonamento_precedente_id) REFERENCES abbonamenti(id)
);

CREATE TABLE abbonamenti_interventi (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    abbonamento_id  INT NOT NULL,
    intervento_id   INT NOT NULL,
    created_at      DATETIME,

    FOREIGN KEY (abbonamento_id) REFERENCES abbonamenti(id),
    FOREIGN KEY (intervento_id)  REFERENCES interventi(id)
);
```

---

## AbbonamentiModel

### Costanti

```php
const FREQUENZE = [
    'settimanale'  => 'Settimanale',
    'quindicinale' => 'Quindicinale',
    'mensile'      => 'Mensile',
    'bimestrale'   => 'Bimestrale',
    'trimestrale'  => 'Trimestrale',
    'semestrale'   => 'Semestrale',
    'annuale'      => 'Annuale',
];

const STATI = [
    'attivo'  => 'Attivo',
    'sospeso' => 'Sospeso',
    'scaduto' => 'Scaduto',
];
```

### Calcolo durata (metodo privato)

```php
private function calcolaDurataMesi(string $dataInizio, string $dataFine): int
{
    $inizio = new \DateTime($dataInizio);
    $fine   = new \DateTime($dataFine);
    $diff   = $inizio->diff($fine);

    return (int) ($diff->y * 12) + $diff->m;
}
```

### Override insert() e update()

`durata_mesi` viene calcolato automaticamente — il controller non deve occuparsene.

```php
public function insert($data = null, bool $returnID = true): bool|int|string
{
    if (isset($data['data_inizio'], $data['data_fine'])) {
        $data['durata_mesi'] = $this->calcolaDurataMesi(
            $data['data_inizio'],
            $data['data_fine']
        );
    }

    return parent::insert($data, $returnID);
}

public function update($id = null, $data = null): bool
{
    if (isset($data['data_inizio'], $data['data_fine'])) {
        $data['durata_mesi'] = $this->calcolaDurataMesi(
            $data['data_inizio'],
            $data['data_fine']
        );
    }

    return parent::update($id, $data);
}
```

---

## Flusso di creazione (Controller Interventi)

Quando si salva un intervento di tipo `programmato`:

1. Salvare l'intervento normalmente
2. Creare la riga in `abbonamenti` con i dati della modale
3. Creare la riga pivot in `abbonamenti_interventi` collegando i due ID

```php
// Pseudocodice nel controller
if ($data['tipo'] === 'programmato') {
    $abbonamentoId = $this->abbonamentiModel->insert([
        'cliente_id'  => $data['cliente_id'],
        'data_inizio' => $data['data_inizio'],
        'data_fine'   => $data['data_fine'],
        'frequenza'   => $data['frequenza'],
        'prezzo'      => $data['prezzo'] ?? null,
        'stato'       => 'attivo',
    ]);

    $this->abbonamentiInterventiModel->insert([
        'abbonamento_id' => $abbonamentoId,
        'intervento_id'  => $interventoId,
    ]);
}
```

---

## Note progettuali

- `prezzo` è il **totale dell'abbonamento**, non il prezzo per singola visita
- `durata_mesi` è **calcolato dal Model** via override di `insert()`/`update()` — non passarlo mai dal controller
- `abbonamento_precedente_id` serve per ricostruire la **catena storica** con CTE ricorsiva (MySQL 8+) quando cambiano i prezzi
- Le frequenze e gli stati sono **costanti nel Model**, non enum a DB né fake enum
- La creazione dell'abbonamento è **silenziosa**: l'utente interagisce solo con la modale intervento programmato
