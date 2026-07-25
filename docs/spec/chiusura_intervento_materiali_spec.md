# Spec — Checklist materiali itemizzata e aggiunta materiali per la prossima visita in chiusura intervento

> Punto **6.R** degli appunti riunione 10/07/2026 (`docs/spec/idee.txt`). Da leggere insieme a `docs/spec/abbonamenti_next_visita_spec.md`, di cui riusa interamente il sistema "materiali sospesi" e la riassegnazione automatica — qui si interviene solo sulla UX di chiusura intervento, non sulla logica di riassegnazione.

## 1. Contesto e problema di partenza

Il modal "Chiudi intervento" (`app/Views/operativo/interventi/show.php`, righe 292-336) oggi pone, se l'intervento ha materiali associati, un'unica domanda binaria: "Hai consegnato i materiali al cliente?" con due bottoni Sì/No. La risposta è **in blocco**: Sì marca *tutti* i materiali dell'intervento come consegnati, No li libera *tutti* come sospesi (con tentativo di riassegnazione automatica alla prossima visita dell'abbonamento, già implementato).

Due limiti emersi testando il flusso reale:

1. **Nessuna granularità**: se un intervento ha 3 materiali e 2 sono stati consegnati ma 1 no, oggi non è rappresentabile — bisogna forzare tutto o niente.
2. **Nessun modo di segnalare materiali mancanti scoperti durante la visita**: un tecnico che si accorge sul posto che serve portare qualcosa alla prossima visita deve uscire dal flusso di chiusura, andare sulla scheda cliente o sulla pagina edit dell'intervento, e usare il mini-form lì presente. Macchinoso durante un giro di visite da mobile.

## 2. Soluzione

### 2.1 Checklist di consegna itemizzata

Il blocco "Hai consegnato i materiali?" del modal diventa un elenco, una riga per ogni materiale in stato `da_portare` di quell'intervento (stessa fonte dati già passata alla view: `$materiali` da `InterventiMaterialiModel::perIntervento()`), con un checkbox per riga:

```
☑ 2× Cloro granulare
☑ 1× Test kit pH
☐ 1× Guarnizione filtro
```

- Checkbox **pre-selezionato** (default: consegnato) — nella maggioranza dei casi il tecnico porta tutto, deve deselezionare solo le eccezioni. Coerente col comportamento attuale dove "Sì consegnati" è il caso comune.
- Ogni checkbox ha `name="consegnato[]" value="<id materiale>"`.
- Un solo bottone di submit ("Chiudi intervento"), non più due bottoni Sì/No.
- Se l'intervento non ha materiali, la sezione non appare (come oggi).
- **Checkbox master "Seleziona/deseleziona tutto"** sopra l'elenco, solo se ci sono 2+ materiali (con 1 solo materiale è ridondante). Puro JS lato client (nessun campo POST proprio): al click imposta `checked` su tutte le righe. Serve soprattutto per smarcare tutto in un colpo quando il tecnico non ha consegnato nulla — il default è già "tutto consegnato", quindi il caso d'uso principale è l'azzeramento rapido, non la selezione.

**Comportamento server (`InterventiController::chiudi()`)**: si calcola l'insieme completo degli id `da_portare` per l'intervento (`idsDaPortarePerIntervento()`, già esistente) e il sottoinsieme presente in `consegnato[]`. La differenza è l'insieme dei "non consegnati". Si applicano poi, separatamente:

- **Consegnati** → nuovo metodo model `consegnaSelezionati(array $ids, int $interventoId)`: marca `stato = consegnato` per quegli id (filtrati anche per `intervento_id` come controllo di appartenenza, contro un POST manomesso).
- **Non consegnati** → nuovo metodo model `liberaSelezionati(array $ids, int $interventoId, string $codice)`: stessa logica di `liberaPerIntervento()` esistente (torna sospeso, nota `[Da {codice}]`) ma ristretta agli id passati invece che a tutto l'intervento.
- Il tentativo di riassegnazione automatica alla prossima visita (query next-by-scadenza, §2 di `abbonamenti_next_visita_spec.md`) si esegue sugli id "non consegnati" così calcolati, esattamente come oggi si eseguiva su tutti gli id liberati.

Il metodo `consegnaPerIntervento()` esistente viene sostituito da `consegnaSelezionati()` e rimosso (usato solo in `chiudi()`). **`liberaPerIntervento()` invece resta**: verificato in fase di implementazione che è usato anche da `InterventiController::annulla()` e `::delete()`, dove serve ancora la semantica "libera tutti i materiali dell'intervento in blocco" (annullare/eliminare un intervento non ha un concetto di consegna parziale) — solo `chiudi()` passa alla logica itemizzata con `liberaSelezionati()`.

### 2.2 Step "materiali per la prossima visita"

Dopo la chiusura (submit del modal sopra), si presenta **sempre** — anche se l'intervento non aveva materiali pregressi — una seconda domanda: *"Ci sono materiali da portare alla prossima visita?"*.

**Decisione UX**: non un unico form combinato con la chiusura, ma un **secondo modal**, aperto automaticamente subito dopo il redirect di chiusura, riusando il meccanismo di coda già esistente (`window.enqueueModal`, definito in `app/Views/layouts/admin.php`, oggi usato per la sequenza changelog → promemoria). Motivazione: `chiudi()` resta un'azione isolata e semplice; l'aggiunta di un nuovo materiale sospeso riusa **letteralmente** il sistema già collaudato (`MaterialiController::store()` con `intervento_id` vuoto → materiale nasce sospeso sul cliente, esattamente come già succede oggi dalla scheda cliente), senza reimplementare nulla né toccare la transazione di chiusura.

Flusso:

1. `chiudi()` imposta, oltre al messaggio di successo, la flashdata `mostra_step_materiali = true` e reindirizza a `operativo/interventi/{id}`.
2. `InterventiController::show()` legge quella flashdata e la passa alla view come `$mostraStepMateriali`. Nello script della pagina, se vera, si chiama `enqueueModal('modal-materiali-prossima-visita', ...)`.
3. Il modal contiene il mini-form TomSelect già esistente in `edit.php` (articolo/descrizione libera + quantità + note), **estratto in un partial condiviso** (`app/Views/operativo/interventi/_form_materiale.php`, markup + inizializzazione TomSelect) incluso sia da `edit.php` sia dal nuovo modal — evita di duplicare markup e JS.
4. Nel partial, dentro questo modal, `intervento_id` resta vuoto (il materiale nasce sospeso sul cliente, non legato a questo intervento già chiuso) e `from` punta all'URL corrente della show (`operativo/interventi/{id}`), così dopo il salvataggio si torna sulla scheda dell'intervento appena chiuso invece che su edit o sulla scheda cliente.
5. Un bottone "No, ho finito" chiude semplicemente il modal (`data-bs-dismiss`, nessun submit).
5.bis. **Elenco dei sospesi già presenti**: sopra il mini-form, il modal mostra l'elenco dei materiali già sospesi per quel cliente (`InterventiMaterialiModel::sospesiPerCliente()`), altrimenti il tecnico non avrebbe alcun riscontro di cosa ha già scritto in questo giro (emerso testando: la scelta "un submit per materiale" del §3 non mostra di per sé nessuna lista, a differenza del mini-form di `edit.php` che ha la tabella sopra). Esclusi esplicitamente i materiali la cui nota inizia con `[Da <codice di questo intervento>]` — sono quelli appena tornati sospesi un attimo prima dalla checklist di consegna (§2.1): mostrarli di nuovo qui creerebbe l'impressione di un errore ("non li avevo appena tolti?"). Restano visibili invece eventuali sospesi di origine diversa/precedente per lo stesso cliente, informazione comunque utile.
6. **Aggiungere più di un materiale**: se il tecnico salva un materiale e vuole aggiungerne un secondo, il form include un campo hidden `riapri_step_materiali=1`. `MaterialiController::store()`, se lo riceve, imposta di nuovo la flashdata `mostra_step_materiali` prima del redirect a `from` — il modal si riapre pronto per un secondo inserimento. Se il tecnico non riapre nulla (chiude il modal), la sequenza finisce lì.

**Modifica necessaria a `MaterialiController::store()`**: oggi il redirect dopo l'insert è cablato (`edit` dell'intervento se `intervento_id` presente, altrimenti scheda cliente) e non considera `from`. Va allineato al "Sistema di ritorno from" già documentato in `CLAUDE.md` (leggere `from` da POST, validare con `str_starts_with($from, base_url())`, usarlo come destinazione se presente) — stesso pattern già usato altrove nel progetto, qui esteso a un controller che finora non lo implementava.

## 2.3 Fallback mobile per il mini-form materiali (`_form_materiale.php`)

Testando da telefono (Chrome iOS) è emerso un bug reale: il dropdown custom di TomSelect non riceve correttamente lo scroll touch quando la lista supera l'altezza visibile — diagnosticato dal vivo (log touch temporaneo in pagina, niente devtools remoti su Chrome iOS) fino a isolare che il tocco arriva correttamente sull'opzione ma il box interno non scrolla mai (`scrollTop` resta a 0), mentre la pagina sotto sì. Confermato essere un problema noto e mai risolto in modo definitivo anche in Select2 (libreria analoga), non qualcosa di specifico a questo progetto. Provati senza successo, in ordine: `overscroll-behavior: contain`, `-webkit-overflow-scrolling: touch`, `touch-action: pan-y`, patch del bug noto di TomSelect su `focus()`/`preventScroll` (github.com/orchidjs/tom-select/issues/729, causa reale ma non quella di questo sintomo).

**Soluzione**: su viewport <768px (`window.matchMedia('(max-width: 767.98px)')`), il `<select>` resta nativo — nessuna inizializzazione di TomSelect — perché lo scroll di un picker nativo lo gestisce il sistema operativo, non la pagina. Per non perdere la possibilità di materiali fuori catalogo, il select include un'opzione extra `__libero__` ("Descrizione libera…") che rivela un campo di testo dedicato (`#input-descrizione-libera`) quando selezionata. Su desktop l'opzione `__libero__` viene rimossa dal DOM prima di inizializzare TomSelect (che gestisce nativamente la creazione di testo libero digitando) — nessun cambiamento per l'esperienza desktop esistente. Si perde solo la ricerca-mentre-scrivi su mobile, non la categorizzazione (gli `<optgroup>` restano) né la possibilità di testo libero.

## 3. Alternative scartate

- **Un unico submit che chiude l'intervento e salva anche il nuovo materiale in una sola transazione**: scartata su indicazione dell'utente. Più "atomica" ma richiede estendere `chiudi()` per gestire anche l'inserimento di righe materiali (validazione TomSelect, gestione errori parziali), a fronte di un guadagno marginale — l'aggiunta manuale di materiali non è mai stata un'operazione atomica col resto nemmeno oggi (in `edit.php` è già un form/submit separato).
- **Più righe insieme in un solo submit (form dinamico multi-riga)**: scartata su indicazione dell'utente. Si può aggiungere in futuro senza rompere nulla (il partial `_form_materiale.php` resterebbe comunque il building block), ma non è necessaria ora: il pattern "un submit per materiale, ripetibile" è già quello in uso in `edit.php`.
- **Mantenere il sì/no in blocco e aggiungere solo lo step 2**: scartata perché non risolve il problema di tracciabilità reale segnalato testando il modal (impossibile distinguere quali materiali specifici sono stati consegnati).

## 4. Riepilogo modifiche file per file

1. **`app/Models/InterventiMaterialiModel.php`**: aggiungere `consegnaSelezionati(array $ids, int $interventoId)` e `liberaSelezionati(array $ids, int $interventoId, string $codice)`; rimuovere `consegnaPerIntervento()` e `liberaPerIntervento()` se non più usati altrove.
2. **`app/Controllers/Operativo/InterventiController.php`**:
   - `chiudi()`: sostituire la logica blanket (`materiali_consegnati` 0/1) con la logica itemizzata su `consegnato[]` descritta al §2.1; impostare flashdata `mostra_step_materiali`.
   - `show()`: passare `articoliPerCat` (già caricato in `edit()` con `(new ArticoliModel())->perCategoria()`) e `mostraStepMateriali` (da flashdata) alla view.
3. **`app/Views/operativo/interventi/show.php`**:
   - Modal chiudi: sostituire il blocco sì/no con la checklist per riga (§2.1).
   - Nuovo modal `#modal-materiali-prossima-visita` che include il partial `_form_materiale.php` con `intervento_id` vuoto, `from` = URL corrente, campo hidden `riapri_step_materiali`.
   - Script: se `$mostraStepMateriali`, chiamare `enqueueModal('modal-materiali-prossima-visita', ...)`.
4. **`app/Views/operativo/interventi/edit.php`**: estrarre il mini-form esistente (righe 287-326) e l'inizializzazione TomSelect (riga ~424) nel nuovo partial `_form_materiale.php`; includerlo al posto del markup inline.
5. **`app/Views/operativo/interventi/_form_materiale.php`** (nuovo): partial riusabile, parametrizzato su `intervento_id` (opzionale), `cliente_id`, `from`, `articoliPerCat`.
6. **`app/Controllers/Operativo/MaterialiController.php`**: `store()` — leggere `from` da POST e usarlo come destinazione del redirect se valido (pattern "Sistema di ritorno from"); se riceve `riapri_step_materiali=1`, impostare di nuovo la flashdata `mostra_step_materiali` prima del redirect.

## 5. Fuori scope

- Form dinamico multi-riga per aggiungere più materiali in un solo submit (vedi §3 — rimandato, non bloccante per questa versione).
- Firma cliente o generazione PDF di chiusura intervento (non richiesti da questo punto della riunione).
- Notifiche/alert per materiali rimasti sospesi troppo a lungo senza essere agganciati a nessuna visita futura (idea emersa in fase di brainstorming ma non nell'appunto originale — eventuale punto separato futuro).
- Gestione scorte/giacenze di magazzino: `articoli` resta un catalogo anagrafico senza tracking di stock, non toccato da questo spec.
- Estendere il meccanismo `enqueueModal` oltre l'uso già generico attuale: nessuna modifica necessaria, è già riusabile così com'è.
