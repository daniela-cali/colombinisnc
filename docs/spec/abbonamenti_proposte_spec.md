# Spec: Abbonamenti — Stato "proposta" e accettazione (fase 1 di 2)

> **Sostituisce la versione precedente di questo documento** (7 luglio 2026), che prevedeva una tabella `abbonamento_proposte` separata con copia dati alla conferma. Da allora sono cambiate due cose rilevanti: è nata `abbonamenti_periodi` (un abbonamento può avere più periodi con frequenze diverse) ed esiste già un flusso di rinnovo diretto (`AbbonamentiController::rinnova()`). Riscritta per riusare la tabella `abbonamenti` esistente invece di duplicarla.
>
> **Fase 2** (spec separata, da scrivere quando il template è pronto): generazione/rigenerazione del documento con PhpWord a partire dal `.docx` che Daniela ha già pronto. Fuori scope qui.

## Contesto

Oggi ogni abbonamento nasce già `attivo`: alla creazione (form nuovo o rinnovo) il sistema genera subito in batch tutti gli interventi dell'anno (`AbbonamentiModel::generaInterventi()`, chiamato da `AbbonamentiController::store()`). Nella pratica, però, un abbonamento proposto a un cliente — nuovo o rinnovo — non è ancora un contratto finché il cliente non lo accetta. Oggi questo passaggio avviene a mano, fuori dal gestionale (si propone il prezzo, si aspetta conferma, solo allora si crea davvero l'abbonamento). L'obiettivo è portare questo iter dentro il gestionale: ogni abbonamento nasce come proposta, e solo un'azione esplicita di accettazione lo rende un contratto attivo con interventi generati.

## Decisioni chiave

### 1. Nessuna tabella nuova — riuso di `abbonamenti`

La proposta *è* l'abbonamento, semplicemente non ancora accettato. Stessa tabella, stesso model, stesso controller, stesse viste — evita di duplicare CRUD e periodi in una struttura parallela, e soprattutto evita il passaggio di copia dati proposta→abbonamento della versione precedente della spec (fonte di disallineamenti se le due strutture divergono nel tempo).

### 2. Due nuovi valori di stato

```php
// nel model — valori aggiornati: proposta, attivo, sospeso, scaduto, rifiutata, disdetto
const STATO_PROPOSTA  = 'proposta';  // nuovo — stato di partenza
const STATO_RIFIUTATA = 'rifiutata'; // nuovo — terminale, il cliente non ha accettato
```

Macchina a stati aggiornata (`attivo`/`sospeso`/`scaduto`/`disdetto` restano invariati tra loro):

```
proposta → attivo     (accettazione — unico punto che genera interventi)
proposta → rifiutata  (terminale, nessun effetto: gli interventi non esistono ancora)
rifiutata  → proposta : ripensamento o modifica elementi della proposta
attivo   → sospeso / disdetto      (invariato)
sospeso  → attivo / disdetto       (invariato)
```

Migration leggera (nessuna modifica di tipo, `stato` è già `VARCHAR(20)`): aggiorna solo il commento colonna con l'elenco valori aggiornato e il default da `'attivo'` a `'proposta'`, per coerenza documentale con la convenzione CLAUDE.md (i valori ammessi vanno nel commento). Il default DB conta poco in pratica perché il controller imposta sempre lo stato esplicitamente, ma mantiene la colonna autodocumentata.

### 3. Creazione: `store()` e `rinnova()` nascono sempre `proposta`

- `store()`: rimuove la chiamata a `generaInterventi()` dalla creazione. Lo stato non arriva più (e non deve arrivare) da `$_POST` — va forzato lato server a `STATO_PROPOSTA`, stesso principio già in uso per `created_by`/`updated_by` ("campi impostati lato server" in CLAUDE.md).
- `rinnova()`: l'array `$precompilato` passa da `'stato' => AbbonamentiModel::STATO_ATTIVO` a `STATO_PROPOSTA`. Il rinnovo, come la creazione nuova, richiede sempre accettazione esplicita — anche per i clienti consolidati che di norma accettano "implicitamente", perché l'obiettivo è avere un iter automatizzato uniforme, non un'eccezione silenziosa che bypassa lo storico.

### 4. Accettazione singola

Nuovo metodo `AbbonamentiController::accetta(int $id)`, separato da `cambiaStato()` (non una transizione in più nel suo array `$transizioni`): valida che lo stato attuale sia `proposta`, dentro una transazione aggiorna `stato = attivo` e chiama `generaInterventi()`, poi redirect con messaggio (`"Proposta accettata, N interventi generati."`). Tenerlo separato da `cambiaStato()` rende esplicito che questa transizione ha un effetto collaterale che nessun'altra ha (generazione massiva di righe), invece di nascondere un `if` speciale dentro un metodo pensato per cambi di stato "leggeri".

### 5. Accettazione multipla (mass-accept)

Nuovo endpoint `AbbonamentiController::accettaMultiplo()`: riceve un array di id (checkbox selezionate nell'index). Per ciascun id: **una transazione per abbonamento**, non un'unica transazione su tutti — un problema su un singolo abbonamento non deve bloccare l'accettazione degli altri, e i lock restano brevi proprio nello scenario che scrive di più (decine di abbonamenti a inizio anno). Risultato riepilogato in un unico flash message ("N accettati" + eventuale "M non riusciti" se qualcosa fallisce).

### 6. Rifiuto

Nuovo metodo `AbbonamentiController::rifiuta(int $id)`: valida transizione solo da `proposta`, imposta `stato = rifiutata`. Nessun effetto su interventi (non esistono ancora per definizione). La riga resta nel DB come storico — coerente con la convenzione CLAUDE.md di non usare flag di cancellazione: `rifiutata` è uno stato di business legittimo, non un modo per nascondere un record.

### 7. Performance: `insertBatch()` al posto di `insert()` in loop

`generaInterventi()` oggi fa un `insert()` per ogni singola scadenza calcolata dentro il `foreach`. Con l'accettazione multipla a inizio anno (decine di abbonamenti, ciascuno con fino a ~52 occorrenze per le frequenze settimanali) il volume di query singole cresce rapidamente. Si accumula l'array di righe nel loop e si chiama `$interventiModel->insertBatch($righe)` una sola volta a fine metodo (per abbonamento) — stessi campi/valori di oggi, cambia solo il numero di query.

Da verificare in fase di implementazione: `insertBatch()` in CI4 applica comunque i callback `$beforeInsert` per singola riga, quindi `created_by`/`updated_by` degli interventi generati dovrebbero valorizzarsi correttamente — va comunque controllato che il `normalizza()` di `InterventiModel` non assuma un contesto da insert singolo.

### 8. Index: filtro "Proposte" + azioni

- Nuova pillola filtro accanto ad Attivi/Sospesi/Scaduti/Disdetti/Tutti: "Proposte" (con badge conteggio, stesso pattern delle altre).
- Filtro attivo di default all'apertura pagina: da confermare in fase di test — "Proposte" ha senso come coda di lavoro da smaltire (coerente con l'obiettivo della feature), ma potrebbe risultare spiazzante rispetto all'abitudine attuale ("Attivi" di default). Decidiamo guardandolo a schermo, non è un dettaglio che vale la pena fissare solo sulla carta.
- Colonna azioni: bottoni Accetta/Rifiuta sulle righe in stato `proposta` (al posto del bottone Rinnova, che lì non ha senso).
- Checkbox di selezione multipla (significative solo sulle righe `proposta`) + bottone "Accetta selezionati" nel `card-header`, abilitato solo quando almeno una riga è selezionata.

### 9. `STATI_LABEL` / `STATI_BADGE`

```php
'proposta'  => 'Proposta',   // badge distinto, es. bg-info — è lo stato che richiede un'azione
'rifiutata' => 'Rifiutata',  // es. bg-danger, ma testo diverso da "Disdetto" per distinguere
                              // "non è mai partito" da "è stato attivo e poi cancellato"
```

### 10. Campi aggiuntivi per il futuro documento (raccolti già in fase 1)

Analizzato il modello `.docx` reale (`docs/spec/proposta_abbonamento_modello.docx`, variante piscine — la variante addolcitori non esiste ancora, non blocca questa fase). **Non c'è nessuna tabella a righe variabili** (niente `cloneBlock()` per più impianti/voci): il contenuto che sembrava "a righe" è in realtà un elenco puntato, uguale di norma per tutti gli abbonamenti dello stesso tipo di intervento (es. le 8 operazioni standard di manutenzione piscina), ma da poter correggere caso per caso. Il resto del documento (cliente, frequenza, periodo, prezzo) è già coperto dai campi esistenti di `abbonamenti`/`abbonamenti_periodi`. Mancano solo tre campi, tutti da raccogliere in fase 1 anche se la generazione vera e propria resta fase 2:

- **`tipi_intervento.operazioni_standard`** (TEXT, nullable) — il testo di default per quel tipo (es. le 8 righe piscina). Editabile nel form esistente Impostazioni → Tipi di intervento, stesso pattern delle altre migration mono-campo già presenti su questa tabella (`AddAbbonabileToTipiIntervento`, `AddHaPuliziaFondoToTipiIntervento`, ecc.) → nuova migration `AddOperazioniStandardToTipiIntervento`.
- **`abbonamenti.operazioni_incluse`** (TEXT, nullable) — il testo che finirà davvero nel documento di *questo* abbonamento. Un `<textarea>` libero, precompilato dal valore standard del tipo selezionato con lo **stesso meccanismo già in uso** per la descrizione intervento (`operativo/interventi/nuovo.php`: mappa `tipoId → testo` in JS, popolata al `change` di `tipo_intervento_id`, solo se il campo è ancora vuoto — mai sovrascrive un testo già presente). Restando testo libero, si possono togliere o aggiungere righe liberamente per il singolo abbonamento, senza nessuna struttura a checkbox — scartata perché aggiungerebbe un catalogo/tabella di voci riutilizzabili senza un bisogno reale confermato, quando il testo libero copre già il caso d'uso ("di solito fisso, a volte va corretto per quel cliente").
- **`abbonamenti.modalita_pagamento`** (VARCHAR(255), nullable) — testo libero, non lookup: le modalità cambiano spesso e sono specifiche del singolo accordo (es. "a metà servizio, Agosto 2026"). Niente FK verso una futura tabella `pagamenti` per un eventuale incrocio con la fatturazione: la tabella non esiste nemmeno sulla carta, indovinarne oggi la forma (un pagamento per abbonamento? più pagamenti parziali, come nell'esempio reale?) rischia di essere sbagliata comunque — la migration andrebbe rifatta quando la fatturazione avrà una spec propria, senza che il campo di oggi risparmi lavoro futuro.

## Alternative scartate

- **Tabella `abbonamento_proposte` separata** (versione precedente della spec): duplicherebbe CRUD/model/view già esistenti e richiederebbe un passaggio di copia dati alla conferma, con rischio di disallineamento tra le due strutture — a fronte di campi che nella pratica coincidono quasi del tutto con quelli di un abbonamento vero.
- **Un'unica transazione globale per il mass-accept**: un errore su un solo abbonamento annullerebbe l'accettazione di tutti gli altri selezionati, e la transazione resterebbe aperta più a lungo proprio nello scenario con più scritture (inizio anno). Isolare per abbonamento e riportare un riepilogo di successi/fallimenti è più robusto.
- **Riutilizzare `disdetto` invece di un nuovo `rifiutata`**: semanticamente diverso — "disdetto" oggi significa "è stato un contratto attivo, poi cancellato" (con interventi da annullare); una proposta rifiutata non è mai stata un contratto e non ha interventi da toccare. Confonderli renderebbe ambigui i filtri/report futuri.

## Riepilogo modifiche file per file

1. `app/Database/Migrations/...AddStatoPropostaRifiutataAbbonamenti.php` (nuova) — aggiorna commento e default della colonna `stato`; aggiunge `operazioni_incluse` (TEXT, nullable) e `modalita_pagamento` (VARCHAR(255), nullable) su `abbonamenti`.
2. `app/Database/Migrations/...AddOperazioniStandardToTipiIntervento.php` (nuova) — aggiunge `operazioni_standard` (TEXT, nullable) su `tipi_intervento`, stesso pattern delle altre migration mono-campo già presenti su questa tabella.
3. `app/Models/AbbonamentiModel.php` — nuove costanti `STATO_PROPOSTA`/`STATO_RIFIUTATA`, `STATI_LABEL`/`STATI_BADGE` aggiornate, `$allowedFields` con `operazioni_incluse`/`modalita_pagamento`, `generaInterventi()` passa a `insertBatch()`.
4. `app/Models/TipiInterventoModel.php` — `$allowedFields` con `operazioni_standard`.
5. `app/Controllers/AbbonamentiController.php` — `store()` non chiama più `generaInterventi()` e forza `stato = proposta`; `rinnova()` forza `stato = proposta` nel precompilato; nuovi metodi `accetta()`, `accettaMultiplo()`, `rifiuta()`; `cambiaStato()` invariato per le transizioni esistenti.
6. `app/Config/Routes.php` — nuove rotte nel gruppo `abbonamenti` per accetta/accetta-multiplo/rifiuta.
7. `app/Views/abbonamenti/index.php` — pillola filtro "Proposte", checkbox multiselezione + bottone "Accetta selezionati", bottoni Accetta/Rifiuta per riga.
8. `app/Views/abbonamenti/show.php` — bottoni Accetta/Rifiuta quando `stato === proposta`; visualizzazione `operazioni_incluse`/`modalita_pagamento`.
9. `app/Views/abbonamenti/nuovo.php` ed `edit.php` — campo `<textarea>` per `operazioni_incluse` (precompilato via JS al cambio `tipo_intervento_id`, stesso meccanismo di `descrizione` in `operativo/interventi/nuovo.php`) e campo testo per `modalita_pagamento`.
10. `app/Views/impostazioni/tipi_intervento/...` (form esistente) — nuovo campo `operazioni_standard`.
11. `docs/ANALISI.md` §7.1 e `CHANGELOG.md` — a chiusura, come da convenzione.

## Fuori scope

- Generazione/rigenerazione documento Word (PhpWord) — fase 2, spec separata quando il template è pronto. Include anche la creazione del modello `.docx` per gli addolcitori (non esiste ancora).
- Catalogo strutturato di operazioni con selezione a checkbox — scartato in favore del testo libero precompilato (vedi decisione 10).
- FK verso una futura tabella `pagamenti`/fatturazione — rimandato a quando quel modulo avrà una spec propria (vedi decisione 10).
- Validazioni aggiuntive sui dati di una proposta (date, periodi) — restano obbligatorie come oggi anche in stato `proposta`; una "proposta vaga" senza date definite non rientra in questa fase.
- Notifiche automatiche (email/promemoria) per proposte rimaste in sospeso a lungo — resta controllo manuale sul filtro "Proposte" nell'index, come già avviene per gli altri filtri di stato.
- Modifica del comportamento di `cambiaStato()` per le transizioni `attivo`/`sospeso`/`disdetto` già esistenti — restano identiche.
