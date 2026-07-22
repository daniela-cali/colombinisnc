# Spec — Eliminare le scadenze duplicate/sovrapposte nella generazione batch abbonamenti

> Bug di fondo emerso discutendo lo spec `chiusura_intervento_materiali_spec.md` (punto 6.R): la query next-by-scadenza usata per la riassegnazione automatica dei materiali sospesi presuppone che, per un dato abbonamento, non esistano due occorrenze con la stessa `data_scadenza`. Oggi questo non è garantito. Va risolto **prima** di continuare con altre feature che si appoggiano a quella garanzia, per non dover gestire eccezioni evitabili.

## 1. Il bug

`AbbonamentiModel::generaInterventi()` itera sui periodi dell'abbonamento (`abbonamenti_periodi`) e per ciascuno chiama `calcolaScadenzePeriodi()` **in modo indipendente dagli altri periodi**. Ogni chiamata forza sempre l'ultimo elemento generato a coincidere con `data_fine` del periodo (per costruzione, righe 273/292/324/347 di `AbbonamentiModel.php`).

Quando un periodo continua naturalmente il precedente **condividendo lo stesso giorno di confine** (`data_inizio` del periodo N+1 = `data_fine` del periodo N), e quella data cade già su un punto di allineamento naturale del calendario per la nuova frequenza, l'algoritmo del periodo N+1 **rigenera la stessa data come propria prima scadenza**.

> **Correzione post-fix**: la prima stesura di questa spec definiva questo giorno di confine condiviso "il caso normale e voluto" di continuazione tra periodi. Non è così — verificato empiricamente (vedi §4) che periodi **non sovrapposti** (`data_inizio` del periodo N+1 = `data_fine` del periodo N + 1 giorno) coprono l'intero calendario senza buchi e **senza generare nessun duplicato**, perché non condividono alcun giorno. È anzi la convenzione che il form ora impone attivamente (vedi `abbonamenti_periodi_copertura_spec.md`): la prima riga eredita la Data inizio dell'abbonamento, il bottone "Aggiungi periodo" propone sempre il giorno successivo alla fine dell'ultimo periodo, e il salvataggio viene bloccato se il primo/ultimo periodo non combaciano con l'inizio/fine dell'abbonamento. Il fix descritto qui sotto resta comunque corretto e utile come rete di sicurezza per l'eventualità di un giorno condiviso (es. dati inseriti manualmente a DB, o import futuri).

### Esempio concreto verificato

Periodo 1: mensile, 01/01→31/05. Genera `31/01, 28/02, 31/03, 30/04, 31/05` (l'ultima forzata a `data_fine`).

Periodo 2: quindicinale, `data_inizio = 31/05` (continuazione diretta). Nel ramo quindicinale (`AbbonamentiModel.php` righe 294-324): `day = 31 > 15` → il cursore iniziale viene impostato a "ultimo giorno del mese di `data_inizio`" = **31/05 stesso**, perché `data_inizio` è già l'ultimo giorno di maggio. Il loop lo genera subito come prima scadenza del periodo 2.

Risultato: due righe `interventi` con lo stesso `abbonamento_id`, `data_scadenza = 2026-05-31`, nessun vincolo DB o applicativo lo impedisce.

### Perché è un problema reale, non solo teorico

`InterventiController::chiudi()` rileva questo caso tramite `InterventiModel::prossimiPerAbbonamento($abbonamentoId, $dataScadenza, limit: 2)`: se la query restituisce le 2 righe duplicate, l'ambiguità viene rilevata e si fa fallback ai materiali sospesi gestiti manualmente. Il sintomo è quindi mascherato (nessun crash, nessuna assegnazione sbagliata), ma è comunque un difetto della generazione: la garanzia "un abbonamento ha una sola prossima visita" che sorregge la riassegnazione automatica non è rispettata, e ogni feature futura che si appoggia a quella garanzia deve reintrodurre gestione dell'ambiguità solo per compensare questo bug.

## 2. Soluzione

Non serve toccare l'algoritmo di ciascuna frequenza (`calcolaScadenzePeriodi()` resta invariato per ogni singolo periodo preso isolatamente). Il problema è solo al **confine tra periodi consecutivi**, quindi si risolve nell'orchestrazione in `generaInterventi()`: tenere traccia dell'ultima scadenza già inserita e scartare qualunque scadenza del periodo successivo che non sia strettamente maggiore.

```php
public function generaInterventi(int $abbonamentoId, array $abbonamento): int
{
    $periodi = (new AbbonamentiPeriodiModel())->perAbbonamento($abbonamentoId);

    if (empty($periodi)) {
        return 0;
    }

    $interventiModel = new InterventiModel();
    $count = 0;
    $ultimaScadenza = null; // garantisce sequenza strettamente crescente tra periodi diversi

    foreach ($periodi as $periodo) {
        $scadenze = $this->calcolaScadenzePeriodi(
            $periodo['data_inizio'],
            $periodo['data_fine'],
            $periodo['frequenza']
        );

        foreach ($scadenze as $scadenza) {
            if ($ultimaScadenza !== null && $scadenza <= $ultimaScadenza) {
                continue; // duplicato/sovrapposizione al confine tra periodi: scartato
            }

            $interventiModel->insert([
                'cliente_id'         => $abbonamento['cliente_id'],
                'abbonamento_id'     => $abbonamentoId,
                'tipo_intervento_id' => $abbonamento['tipo_intervento_id'],
                'priorita'           => InterventiModel::PRIORITA_ABBONAMENTO,
                'stato'              => InterventiModel::STATO_DA_PIANIFICARE,
                'data_pianificata'   => null,
                'data_scadenza'      => $scadenza,
                'pulizia_fondo'      => (int) ($periodo['con_pulizia_fondo'] ?? 0),
                'descrizione'        => 'Visita in abbonamento [#' . $abbonamentoId ."]",
            ]);
            $count++;
            $ultimaScadenza = $scadenza;
        }
    }

    return $count;
}
```

Confronto di stringhe `Y-m-d` (`<=`) è sufficiente e corretto per l'ordinamento cronologico, senza bisogno di istanziare `DateTime` per il confronto.

### Perché questa soluzione e non altre

- **Non serve validare a monte i periodi per vietare la coincidenza dei confini**: non è comunque un errore da bloccare (l'utente potrebbe inserirla, es. abitudine da un altro gestionale), e il fix qui sotto la gestisce correttamente scartando il duplicato. La convenzione senza sovrapposizione è comunque quella che il form promuove attivamente lato UI (vedi correzione in apertura e `abbonamenti_periodi_copertura_spec.md`), ma non è imposta come unico input valido a livello di generazione.
- **Non serve un vincolo univoco DB** su `(abbonamento_id, data_scadenza)`: aggiungerebbe un livello di protezione ma non risolverebbe la generazione stessa (l'insert del duplicato fallirebbe con errore SQL invece che essere silenziosamente evitato) e complicherebbe eventuali casi legittimi futuri (es. visite extra con scadenza coincidente, già previste e gestite a parte dalla query next-by-scadenza).
- **Scartare invece di segnalare un errore**: il duplicato in questi casi è sempre "la stessa identica data generata due volte per continuità tra periodi", non un errore di configurazione dell'utente da bloccare — scartare silenziosamente il secondo è corretto e non perde nessuna occorrenza reale (la data è la stessa, l'intervento sarebbe stato lo stesso giorno).

## 3. Riepilogo modifiche

1. **`app/Models/AbbonamentiModel.php`**, metodo `generaInterventi()`: aggiungere il tracking `$ultimaScadenza` e lo skip dei duplicati/sovrapposizioni, come sopra.
2. Nessuna migration, nessuna modifica a `calcolaScadenzePeriodi()`, nessuna modifica a `AbbonamentiController`.

## 4. Verifica

Da testare ricreando lo scenario esatto (periodo mensile che finisce a fine mese seguito da periodo quindicinale che inizia lo stesso giorno) e verificando che venga generata una sola occorrenza con quella `data_scadenza`, non due. Utile anche un controllo una tantum sugli abbonamenti già esistenti in ambiente di sviluppo, per capire se il bug ha già prodotto duplicati da ripulire manualmente (dato che l'ambiente dev verrà comunque svuotato prima del go-live, non è necessaria una migration di pulizia dati).

## 5. Fuori scope

- Vincolo di unicità a livello DB su `(abbonamento_id, data_scadenza)` — valutabile in futuro come protezione aggiuntiva, non necessario per chiudere questo bug.
- Validazione di contiguità/sovrapposizione tra periodi in `AbbonamentiController` — non è il problema qui (la contiguità è voluta), non toccare.
