# Spec — Gestione "prossima visita" su Abbonamenti, Visite Extra e Pulizia Fondo

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale del progetto (Colombini SNC Gestionale). Questo documento copre solo le funzionalità descritte qui sotto, non sostituisce l'analisi generale degli abbonamenti.

## 1. Contesto e problema di partenza

Gli abbonamenti generano in batch, alla creazione, tutte le occorrenze (`interventi`) dell'anno, anche con scadenze multiple e frequenze diverse nello stesso anno (es. mensile da gennaio a maggio, quindicinale da maggio a giugno, settimanale da giugno a settembre). Le occorrenze nascono con `data_pianificata = NULL` (vengono pianificate manualmente dal dispatcher sul calendario in un secondo momento) ma con `data_scadenza` già valorizzata e univoca per abbonamento.

Quando un tecnico, durante una visita, segna del materiale da portare alla visita successiva (funzionalità "materiali sospesi", **già esistente e funzionante** sul cliente), il sistema deve identificare automaticamente qual è "la prossima visita" di quello stesso abbonamento per assegnarci il materiale, invece di lasciare sempre la gestione manuale.

## 2. Soluzione: query "next-by-scadenza"

Non serve nessuna colonna di sequenza né struttura a lista collegata. La colonna `data_scadenza`, già esistente e già univoca all'interno di un abbonamento nel caso normale, è sufficiente come criterio d'ordinamento.

### Query di riferimento

```sql
SELECT *
FROM interventi
WHERE abbonamento_id = ?
  AND priorita = 'abbonamento'
  AND data_scadenza > ?  -- data_scadenza dell'intervento appena chiuso
ORDER BY data_scadenza ASC
LIMIT 1
```

Nota: il filtro `priorita = 'abbonamento'` include sia le occorrenze regolari generate dal batch sia le visite extra (vedi §4), perché le visite extra useranno lo stesso valore di `priorita`. Questo è intenzionale: una visita extra deve poter "intercettare" i materiali sospesi se cade cronologicamente prima della prossima occorrenza regolare (es. visita extra di trattamento shock prima della prossima manutenzione quindicinale).

### Comportamento alla chiusura di un intervento

1. Se l'intervento chiuso ha materiali marcati come "da portare alla prossima visita":
   - Eseguire la query next-by-scadenza.
   - **Se restituisce esattamente un risultato**: assegnare i materiali a quell'intervento.
   - **Se restituisce zero risultati o più di un risultato** (ambiguità, es. due interventi con stessa `data_scadenza` per cause eccezionali): non tentare di indovinare. Salvare i materiali nel campo `materiali_sospesi` già esistente sul cliente, con l'alert visivo già implementato. Gestione manuale da parte del dispatcher in seguito.

2. Questo comportamento è lo stesso identico sia per la chiusura di un'occorrenza regolare sia per la chiusura di una visita extra — nessuna logica condizionale diversa tra i due casi.

### Casi limite esplicitamente NON gestiti automaticamente (per scelta)

- Sovrapposizioni di `data_scadenza` all'interno dello stesso abbonamento: sono considerate eccezioni rare; in caso di ambiguità si ricade nel flusso "materiali sospesi sul cliente" già esistente, gestito manualmente.
- Non si tenta nessuna euristica aggiuntiva per "indovinare" il next in caso di ambiguità: meglio rendere il caso visibile all'operatore che rischiare un'assegnazione sbagliata silenziosa.

## 3. Nuovi campi su `interventi`

| Campo | Tipo | Default | Note |
|---|---|---|---|
| `extra` | `BOOLEAN` / `TINYINT(1)` | `false` | Marca un intervento come visita extra (vedi §4). Usato per fatturazione/reportistica separata. Non influisce sulla query next-by-scadenza. |
| `pulizia_fondo` | `BOOLEAN` / `TINYINT(1)` | dipende (vedi §5) | Indica se in quella visita è stata/va fatta la pulizia del fondo. Liberamente modificabile in chiusura intervento. |

Nessun'altra modifica di schema richiesta. Non sono necessarie nuove tabelle né pivot.

## 4. Visite extra

### Definizione

Una visita extra è un intervento che:
- ha `abbonamento_id` valorizzato con l'abbonamento del cliente (stesso FK già esistente su `interventi`, riusato)
- ha `priorita = 'abbonamento'` (stesso valore delle occorrenze regolari — **non** un valore `priorita` a parte, proprio per partecipare al circuito next-by-scadenza e materiali sospesi senza bisogno di filtri aggiuntivi)
- ha `extra = true`
- nasce con `data_pianificata` e `data_scadenza` coincidenti, impostate manualmente al momento della creazione (a differenza delle occorrenze batch, che nascono con `data_pianificata = NULL`)
- materiale da portare (se previsto) deciso manualmente al momento della creazione, dall'operatore

### Entry point UI (da implementare ora)

- Bottone "Nuova visita extra" nella scheda cliente / scheda abbonamento.
- Form di creazione intervento esistente, con select a tendina per scegliere l'abbonamento (l'utente non deve mai conoscere o inserire manualmente l'`abbonamento_id`).
- Alla creazione, precompilare `abbonamento_id` (in base alla scheda da cui si parte, se applicabile) e `genere = 'abbonamento'`, `extra = true` automaticamente; l'operatore inserisce data, materiali, note come per un intervento normale.

### Fatturazione

`extra = true` serve come marcatore per la reportistica/fatturazione separata. La logica di fatturazione stessa non è oggetto di questo spec.

## 5. Pulizia fondo

### Principio

`pulizia_fondo` è un singolo campo booleano sull'intervento, non duplicato. Si comporta così:

1. **Alla generazione batch** (occorrenze regolari): il valore di default viene letto dal periodo dell'abbonamento attivo per quella `data_scadenza` (i periodi già supportano la proprietà "pulizia fondo sì/no" come configurazione, es. periodo settimanale giugno-settembre con pulizia fondo incluso).
2. **Alla creazione di una visita extra**: default `false`, modificabile manualmente dall'operatore in fase di creazione.
3. **Alla chiusura di qualunque intervento** (regolare o extra): il flag `pulizia_fondo` è liberamente modificabile dal tecnico/dispatcher, indipendentemente dal valore di default. Questo copre sia il caso "prevista dal periodo ma non fatta quel giorno" sia il caso "non prevista ma fatta extra".

### Determinare se una pulizia fondo è stata "extra" (fatturabile a parte)

Non si salva uno stato separato. Si calcola a posteriori confrontando, per un dato intervento:
- `interventi.pulizia_fondo` (valore effettivo registrato alla chiusura)
- il valore previsto dal periodo dell'abbonamento attivo in quella `data_scadenza`

Se `pulizia_fondo = true` sull'intervento ma il periodo corrispondente non la prevedeva, si tratta di pulizia fondo extra (fatturabile a parte, come le visite extra). Questo calcolo va fatto in fase di report/fatturazione, non salvato come colonna ridondante.

### Esplicitamente fuori scope per ora

- Saltare la pulizia fondo su un'occorrenza che la prevedrebbe di default è già tecnicamente supportato dallo stesso flag (semplicemente lo si imposta a `false` in chiusura), ma non è il caso primario per cui questa funzionalità nasce. Nessun lavoro aggiuntivo necessario: è un effetto collaterale gratuito della soluzione.

## 6. Riepilogo modifiche da implementare

1. **Migration**: aggiungere `extra` (boolean, default false) e `pulizia_fondo` (boolean, default da logica periodo) su `interventi`.
2. **Generazione batch abbonamento**: popolare `pulizia_fondo` leggendo dal periodo attivo per ciascuna `data_scadenza` generata.
3. **Form "Nuova visita extra"**: nuovo entry point UI da scheda cliente/abbonamento, con select abbonamento, che crea un intervento con `genere = 'abbonamento'`, `extra = true`, `data_pianificata = data_scadenza` (entrambe inserite manualmente).
4. **Form di chiusura intervento**: rendere `pulizia_fondo` modificabile (checkbox), mantenere/collegare la UI già esistente per marcare materiali da portare alla prossima visita.
5. **Logica di chiusura intervento**: se ci sono materiali marcati, eseguire la query next-by-scadenza (§2); su risultato univoco assegnare i materiali all'intervento trovato; su zero o più risultati, fallback al flusso esistente "materiali sospesi sul cliente".
6. **(Eventuale, non bloccante)** Report/fatturazione: query per identificare pulizie fondo extra a posteriori, confrontando `interventi.pulizia_fondo` col valore previsto dal periodo.
