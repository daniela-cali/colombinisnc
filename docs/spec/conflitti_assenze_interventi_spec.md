# Spec — Avviso conflitti tra assenze e interventi già pianificati

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale del progetto (Colombini SNC Gestionale). Questo documento copre solo la funzionalità descritta qui sotto.

## 1. Contesto e stato di partenza

Il blocco "non si può assegnare un tecnico assente a un intervento" (in lavorazione parallela, non ancora rilasciato) copre solo le assegnazioni **attive**: quando tecnico e/o data pianificata vengono impostati o cambiati (nuovo intervento, drag dal pool, drag di un evento già pianificato, modifica che tocca tecnico o data), `InterventiController` blocca il salvataggio se il tecnico scelto è assente in quella data.

**Il caso scoperto testando quel blocco**: se l'assenza viene inserita **dopo** che l'intervento è già pianificato (es. malattia improvvisa di un tecnico che aveva già interventi assegnati nei giorni successivi), non c'è alcun controllo — l'intervento resta silenziosamente in conflitto finché qualcuno non se ne accorge manualmente aprendo la scheda o il calendario. Non è un bug del blocco esistente: è un caso che il blocco, per design, non copre (si attiva solo su modifiche attive a tecnico/data, non retroattivamente).

## 2. Obiettivo

Avvisare chi inserisce un'assenza che quella scelta mette in conflitto interventi già pianificati, **e** rendere visibile lo stato di *tutti* i conflitti correnti a chiunque (admin/ufficio) apra la dashboard — non solo a chi ha inserito l'assenza in quel momento. Da ogni conflitto elencato, un link diretto alla modifica dell'intervento per riassegnarlo subito.

## 3. Decisione di design: nessuna tabella nuova, query live

I conflitti non vengono persistiti da nessuna parte: nascono e spariscono da soli in base allo stato corrente di `interventi` e `assenze` (si risolvono riassegnando il tecnico, cambiando la data, o cancellando l'assenza). Una tabella "notifiche" o un flag salvato andrebbe tenuto sincronizzato manualmente ad ogni cambiamento, con rischio di disallineamento — scartata a favore di una query che è sempre vera "adesso". Coerente con la decisione già presa per `ClientiModel::relazioniBloccanti()` (query live su `information_schema` invece di un elenco scritto a mano) e con la scelta di non costruire una tabella `notifiche` generica finché non ci sono requisiti concreti oltre ai promemoria.

Due punti di visualizzazione, stesso meccanismo sottostante:

1. **Avviso immediato** al salvataggio dell'assenza (flashdata `warning`, stesso stile di `AssenzeModel::sovrapposizioni()` già esistente per le sovrapposizioni tra assenze) — elenca gli interventi che *quella* assenza appena inserita mette in conflitto.
2. **Card in dashboard** (admin/ufficio, stesso stile di "Assenti oggi"/"Abbonamenti in scadenza") — mostra sempre lo stato corrente di *tutti* i conflitti esistenti, non solo quello appena creato. Chi entra il giorno dopo la vede comunque, anche se non era presente al momento dell'inserimento.

## 4. Query

**Avviso immediato**: nessun metodo nuovo — riusa `InterventiModel::agendaTecnicoPeriodo($personaleId, $dataInizio, $dataFine)`, già esistente per l'agenda mobile del tecnico. Restituisce esattamente gli interventi pianificati/in corso di quel tecnico nella finestra di date, che è precisamente l'insieme da controllare subito dopo l'inserimento di una nuova assenza.

**Card dashboard**: nuovo metodo `InterventiModel::inConflittoConAssenze(): array` — JOIN diretto tra `interventi` e `assenze` sul tecnico, con la data pianificata compresa nell'intervallo dell'assenza:

```php
public function inConflittoConAssenze(): array
{
    return $this->select("interventi.id, interventi.data_pianificata,
           clienti.denominazione AS cliente_denominazione,
            TRIM(CONCAT_WS(' ', personale.cognome, personale.nome)) AS tecnico,
            assenze.tipo AS assenza_tipo")
        ->join('clienti', 'clienti.id = interventi.cliente_id')
        ->join('personale', 'personale.id = interventi.tecnico_id')
        ->join('assenze', "assenze.personale_id = interventi.tecnico_id
            AND DATE(interventi.data_pianificata) >= assenze.data_inizio
            AND DATE(interventi.data_pianificata) <= assenze.data_fine")
        ->whereIn('interventi.stato', [self::STATO_PIANIFICATO, self::STATO_IN_CORSO])
        ->orderBy('interventi.data_pianificata', 'ASC')
        ->findAll();
}
```

Non serve un metodo diverso da `agendaTecnicoPeriodo()` per il caso 1: qui invece serve davvero un metodo nuovo perché la finestra di date non è fissa — ogni tecnico ha la propria assenza con il proprio intervallo, quindi va incrociata via JOIN invece che passata come parametro.

## 5. Flusso avviso immediato

`PersonaleController::aggiungiAssenza()` (unico punto di scrittura di una nuova assenza — non esiste un `modificaAssenza()`, solo aggiunta ed eliminazione): dopo l'insert, oltre al controllo già esistente `sovrapposizioni()`, aggiungere la chiamata a `agendaTecnicoPeriodo($personaleId, $dataInizio, $dataFine)`. Se restituisce risultati, comporre un messaggio che elenca cliente + data di ciascun intervento in conflitto. Il layout gestisce un solo flashdata `warning` (stringa unica): se **sia** la sovrapposizione tra assenze **sia** il conflitto con interventi si verificano insieme, i due messaggi vanno concatenati in un'unica stringa invece di scriversi a vicenda.

`eliminaAssenza()` non necessita di alcun controllo: cancellare un'assenza può solo risolvere conflitti, mai crearne.

## 6. Card dashboard

Nuova card "Interventi in conflitto" nella riga liste di `dashboard/index.php`, visibile ad admin/ufficio (stessa condizione delle altre card operative), stile `card-outline` con badge rosso se il conteggio è > 0, verde se 0 — stesso pattern di "Urgenti non pianificati"/"Assenze di oggi". Ogni riga: cliente, tecnico, tipo di assenza, data pianificata — **il link della riga porta direttamente a `operativo/interventi/{id}/edit`** (non a `show`), così l'admin/ufficio riassegna subito il tecnico nel form di modifica già esistente, zero click intermedi. Nessun nuovo info-box nella riga contatori in alto — il volume atteso di questi conflitti è basso (caso eccezionale, non un flusso quotidiano come interventi/urgenti/assenze), una card nella riga liste è sufficiente senza appesantire la vista d'insieme.

`DashboardController::caricaDatiUfficio()`: nuova riga `$data['conflitti'] = model(InterventiModel::class)->inConflittoConAssenze();`.

## 7. Riepilogo modifiche da implementare

1. **`app/Models/InterventiModel.php`**: nuovo metodo `inConflittoConAssenze()` (§4).
2. **`app/Controllers/Anagrafiche/PersonaleController.php`**: `aggiungiAssenza()` — aggiunta chiamata a `agendaTecnicoPeriodo()` e composizione del messaggio di avviso (§5). Nessuna modifica a `eliminaAssenza()`.
3. **`app/Controllers/DashboardController.php`**: `caricaDatiUfficio()` — nuova entry `conflitti` in `$data` (§6); inizializzazione a `[]` nell'array `$data` di `index()`.
4. **`app/Views/dashboard/index.php`**: nuova card "Interventi in conflitto" nella riga liste, link diretto a `edit` (§6), nuovo `@var array $conflitti` nel docblock.
5. **`AssenzeModel`**: nessuna modifica.

## 8. Esplicitamente fuori scope per ora

- Nessuna cronologia/audit di chi ha risolto un conflitto o quando — se un domani servisse un log (es. per email automatiche), va ripensato da capo con una tabella dedicata.
- Nessuna notifica push/email — solo flashdata a video e card dashboard.
- Nessuna riassegnazione inline dalla card (dropdown tecnico senza aprire il form): il link porta al form di modifica già esistente, non si costruisce un'azione rapida separata.
