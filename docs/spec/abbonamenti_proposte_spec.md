# Spec: Proposte Abbonamento e Generazione Documento

> Documento di decisioni architetturali, generato da discussione, come input per sessioni Claude Code. Da integrare/armonizzare con `docs/abbonamenti_spec.md` (da riscrivere) prima della milestone v0.14.0.

## Contesto

Il sistema attuale (fuori gestionale) genera documenti Word per preventivi, schede impianti e abbonamenti scrivendoli/assemblandoli a mano, con risultati disorganizzati (file salvati per anno in cartelle, nessuna versione digitale strutturata). L'obiettivo è digitalizzare il flusso mantenendo la logica operativa reale, non imporre struttura nuova.

## Un solo flusso, un'unica accettazione

Tutti gli abbonamenti (cliente nuovo o rinnovo) passano dalla stessa sequenza: **proposta → documento firmato/accettato restituito → accettazione registrata dall'operatore → abbonamento creato**. Non c'è una modalità "automatica" diversa per i rinnovi: è sempre l'operatore in ufficio a confermare nel gestionale che il preventivo è tornato accettato (firmato dal cliente, o comunque validato secondo la prassi in uso), indipendentemente dal fatto che si tratti di un cliente nuovo o di un rinnovo.

- Si genera la **proposta** (prezzo, tipologia, frequenza, descrizione impianti) e il relativo documento.
- Finché non arriva conferma, la proposta resta `inviata` — nessun dato scritto su `abbonamenti`, nessun intervento generato.
- Quando l'operatore riceve il preventivo firmato/accettato (anche per rinnovi impliciti tipo condomini, gestiti comunque con un'unica azione di conferma), preme **"Accetta e crea abbonamento"**: nasce l'`abbonamento` vero, già `attivo`, e scatta il batch di generazione interventi per tutto l'anno — comportamento invariato rispetto a oggi.
- Per i rinnovi, l'abbonamento creato viene collegato al precedente tramite il campo self-referencing esistente (`abbonamento_precedente_id`).

**Gestione dei ritardatari**: è compito dell'operatore, non del sistema, verificare periodicamente (es. a inizio anno) quali proposte risultano ancora `inviata` e sollecitare/confermare manualmente. Non serve automazione dedicata: un controllo manuale su una lista/filtro delle proposte non ancora accettate è sufficiente.

## Nuova tabella: `abbonamento_proposte`

Rappresenta esclusivamente la fase pre-vendita, prima dell'accettazione. Non genera mai interventi.

```
id
cliente_id            FK -> clienti
tipologia              ENUM('piscina', 'addolcitore', 'altro')
descrizione_impianti   MEDIUMTEXT, nullable  -- inserito manualmente finché non esiste scheda impianti collegabile
prezzo_proposto        DECIMAL
frequenza_id           FK (o struttura già esistente in gestione frequenze/abbonamenti)
pdf_path               VARCHAR, nullable     -- popolato alla generazione documento
stato                  ENUM('inviata', 'accettata', 'rifiutata')
creato_il              TIMESTAMP
```

### Campo aggiuntivo su `abbonamenti`

```
proposta_id   FK -> abbonamento_proposte, nullable
```

- Nullable perché gli abbonamenti esistenti (dati demo/storici, creati prima di questo flusso) non hanno una proposta associata — non serve costruire proposte retroattive, semplicemente `proposta_id = NULL` per quei record.
- Serve solo per audit/tracciabilità, nessuna logica applicativa dipende da esso.

## Flusso "Accetta proposta"

1. Utente apre la proposta con `stato = 'inviata'`.
2. Click su **"Accetta e crea abbonamento"** — sempre confermato manualmente dall'operatore quando riceve il preventivo firmato/accettato (nuovo cliente o rinnovo, stessa azione).
3. Il sistema copia i dati rilevanti (prezzo, tipologia, frequenza) nella nuova riga `abbonamenti` (che nasce `attivo`).
4. Scatta il batch esistente: generazione di tutti gli interventi dell'anno (`genere = 'abbonamento'`).
5. `abbonamento_proposte.stato` passa a `'accettata'`.

Se rifiutata: si aggiorna `stato = 'rifiutata'` (o si elimina la riga). Nessun impatto su `abbonamenti`/`interventi`, perché non sono mai stati toccati.

## Generazione documento (PDF)

**Principio guida: il documento è sempre un output derivato, mai la fonte di verità.**

- Non si prevede editing manuale del file finale (né `.docx` né `.pdf`) — ogni modifica ai dati (prezzo, frequenza, ecc.) avviene nei campi del gestionale, poi si rigenera il documento da zero con un'azione esplicita **"Rigenera documento"**, che sovrascrive il precedente.
- Questo evita la necessità di upload manuale di PDF modificati e mantiene un'unica fonte di verità (il database).
- Meccanismo di generazione: **PhpWord con `TemplateProcessor`**.
  - Si predispone un file modello `.docx` (creato normalmente in Word/LibreOffice) con segnaposto tipo `${cliente_nome}`, `${prezzo_proposto}`, `${tipologia}`, ecc.
  - Per le sezioni a righe variabili (es. tabella righe standard di manutenzione), si usa il meccanismo di blocchi clonabili di PhpWord (`cloneBlock()` su un blocco `${righe}...${/righe}` nel template), con loop PHP che popola una riga per voce.
  - Se serve output finale in PDF (non `.docx` modificabile), aggiungere step di conversione (es. LibreOffice headless) dopo la generazione via PhpWord.
- Eccezioni/ritocchi non previsti dal template restano un caso limite gestito manualmente fuori sistema (coerente con il principio "i casi limite li gestisce l'umano", non il codice).

## Cose da definire in seguito (non bloccanti per l'avvio)

- Se il documento finale sarà `.docx` o `.pdf` (dipende da conferma su necessità di conversione server-side).
- Struttura definitiva del template Word (sezioni, tabella righe standard, sezione impianti) — dipende dal nuovo modello che Daniela ristrutturerà a partire da quello attuale.
- Se/quando aggiungere una vista dedicata "proposte in sospeso" nel gestionale — non necessaria da subito: un filtro/ricerca sulle proposte con `stato = 'inviata'` nell'indice esistente potrebbe già bastare per il controllo manuale a inizio anno.
