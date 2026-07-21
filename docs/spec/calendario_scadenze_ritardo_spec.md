# Spec — Scadenze in ritardo / ferme da più di una settimana nel Calendario

> Nasce dal punto 7.R della riunione del 10-11/07/2026 (`docs/spec/idee.txt`): "verificare scadenze aperte.. così crea troppo casino". Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale.

## 1. Contesto e problema di partenza

Il calendario (`operativo/calendario/index.php`) mostra oggi una barra di avviso "Scadenze aperte" alimentata da `InterventiModel::scadenzeAperte()`: elenca in badge tutti gli interventi con `data_scadenza` valorizzata, non completati/annullati, senza nessun limite temporale per gli interventi singoli (solo quelli da abbonamento sono limitati al mese corrente). Con dati reali il numero cresce senza controllo e la barra diventa un muro di badge — il problema segnalato in riunione.

Il click su un badge oggi porta semplicemente alla scheda dell'intervento (`operativo/interventi/{id}`), uscendo dal calendario.

Parallelamente è emersa un'esigenza diversa e più mirata: un colpo d'occhio specifico sulle scadenze **già in ritardo** o **ferme da troppo tempo senza essere pianificate**, che sia anche immediatamente azionabile (pianificarle o ripianificarle) senza lasciare la pagina.

Il concetto più generico di "scadenze entro un orizzonte futuro" (es. entro la settimana, per una visione d'insieme non urgente) è **un'esigenza diversa** e viene gestito separatamente in dashboard (vedi §6 Fuori scope) — non fa parte di questa spec/branch.

## 2. Soluzione

### 2.1 Cosa entra nel colpo d'occhio "in ritardo"

Sostituisce interamente il concetto attuale di `scadenzeAperte()`. Un intervento compare se non è `completato` né `annullato`, e in più:

| Stato dell'intervento | Condizione per comparire |
|---|---|
| `da_pianificare` | `data_scadenza < oggi` **oppure** `created_at <= oggi - 7 giorni` |
| `pianificato` / `in_corso` | `data_scadenza < oggi` **oppure** `data_pianificata < oggi` |

**Perché la distinzione per stato**: "fermo da più di una settimana" (`created_at`) è un problema solo per chi non ha ancora una data — un intervento già pianificato non è "fermo", ha semplicemente una data futura assegnata. Applicare il criterio `created_at` anche ai già pianificati marcherebbe come "in ritardo" interventi vecchi ma perfettamente in programma, generando falsi allarmi.

**Perché `data_pianificata < oggi` per i già pianificati**: è il caso dell'appuntamento mancato — l'intervento aveva una data fissata, quella data è passata, ma nessuno l'ha chiuso (`completato`) né annullato. È un segnale più diretto e più grave di una `data_scadenza` superata, e vale **anche quando `data_scadenza` non è mai stata impostata** — un intervento non ha bisogno di una scadenza formale per poter risultare "saltato". Per questo, a differenza della versione precedente di questa spec, non si richiede più che `data_scadenza IS NOT NULL` come filtro di base: la condizione ora è specifica per riga, non globale.

### 2.2 Interazione: riusare i meccanismi di drag esistenti, non costruirne di nuovi

Non serve nessuna nuova infrastruttura di drag & drop. Il calendario ha già due meccanismi di trascinamento indipendenti:

- **Pool sidebar** (`FullCalendar.Draggable` su `#pool-container`, `public/js/calendario.js:82`): ogni intervento `da_pianificare` è già una `.pool-card` trascinabile sul calendario.
- **Eventi già pianificati** (`editable: true` + `eventDrop`, `public/js/calendario.js:374,386`): un evento già sul calendario è già trascinabile per essere ripianificato.

La barra "in ritardo" diventa quindi solo un **indice cliccabile** che porta l'attenzione sull'elemento giusto, già interattivo. **A differenza della sidebar pool (nascosta su mobile, vedi `index.php:43`), questa barra è nella colonna principale del calendario e resta visibile anche su mobile** — l'interazione va quindi differenziata per device, riusando lo stesso controllo già presente in `calendario.js:353` (`var isMobile = window.innerWidth < 768`):

**Desktop:**
- Badge di un intervento **da pianificare** → click singolo: espande il pannello pool se è in modalità mini, apre (se chiuse) le collapse Bootstrap di zona/sottogruppo che contengono quella card, fa scroll fino alla card e la evidenzia con un flash temporaneo (classe CSS con animazione, poi rimossa). Nessuna navigazione fuori pagina.
- Badge di un intervento **già pianificato/in corso** → click singolo: `calendar.gotoDate(data_pianificata)` per portare in vista la data in cui si trova, poi `calendar.getEventById(id)` per recuperare l'elemento e applicargli lo stesso flash di evidenziazione. Da lì il drag per ripianificare è già quello esistente (`eventDrop`).
- **Doppio click** su qualunque badge → naviga alla scheda intervento (`/operativo/interventi/{id}`), uscendo dal calendario.

**Mobile:**
- **Tap** su qualunque badge (qualunque stato) → naviga direttamente alla scheda intervento. Nessuno scroll-in-pagina: la sidebar pool non esiste su mobile, quindi per un intervento da pianificare non c'è nulla da evidenziare a schermo. Nessun doppio tap: non è un gesto affidabile su touch.
- Questo non è solo un fallback tecnico: è il percorso di pianificazione previsto da mobile. Chi usa il calendario da telefono (tipicamente il titolare, che è anche tecnico) pianifica l'intervento direttamente dalla sua scheda, non dalla barra pool — che infatti su mobile non esiste. Il tap sul badge lo porta lì in un solo gesto, senza doverlo cercare altrove.

### 2.3 Distinzione visiva: tre motivi possibili

Badge concettualmente diversi (colore/icona), calcolati lato server insieme al motivo — il primo che si applica, in ordine di gravità:

- **Appuntamento mancato**, in UI **"Non completato"** (solo `pianificato`/`in_corso`, `data_pianificata` superata): badge rosso scuro, icona calendario-x, tooltip "Appuntamento del gg/mm risulta non completato".
- **In ritardo** (`data_scadenza` superata, qualunque stato): badge rosso, icona orologio, tooltip "In ritardo di N giorni".
- **Fermo** (solo `da_pianificare`, solo `created_at` vecchio e scadenza non ancora superata): badge giallo/warning, icona clessidra, tooltip "Fermo da N giorni".

Le chiavi interne (model, dataset JS) restano `mancato`/`ritardo`/`fermo`; solo l'etichetta mostrata all'utente per il primo motivo è "Non completato" (deciso in fase di test, vedi §2.4).

### 2.4 Evoluzione in fase di test: raggruppamento a pill collassabili

La versione inizialmente implementata seguiva l'idea scritta sopra: un'unica fila con tutti i badge mescolati, ordinati per urgenza. Testando con i dati demo (che contengono molte scadenze ormai vecchie, generando decine di badge) l'utente ha fatto notare che una fila piatta è di nuovo difficile da scandire — lo stesso problema di fondo che questa spec voleva risolvere, solo spostato di un livello.

**Soluzione adottata**: la barra (rinominata da "In ritardo" a **"Attenzione"**, per non confondersi col nome di uno dei tre motivi) mostra tre **pill riassuntive** in linea, una per motivo, ciascuna con etichetta, icona, conteggio e un proprio tooltip esplicativo del criterio. Al click una pill si espande (Bootstrap Collapse, stesso meccanismo già usato per le collapse zona/sottogruppo del pool) mostrando sotto la fila dei badge dei singoli interventi di quel motivo — identici a quelli descritti in §2.2/§2.3, stesso click-handler. Un gruppo senza interventi non genera nessuna pill.

Il raggruppamento per motivo (`scadenzePerMotivo`) è fatto nel controller a partire dall'array piatto già ordinato dal model, con lo stesso pattern già usato per `poolPerZona` — nessuna nuova logica di ordinamento, solo uno smistamento nei tre bucket.

## 3. Alternative scartate

**Fascia draggable dedicata** (es. striscia all-day nel FullCalendar con una propria istanza di drag): scartata. Duplicherebbe parte del meccanismo già esistente nel pool per gli stessi identici dati, aumentando la superficie di bug senza un vantaggio reale — gli interventi in ritardo sono già, per definizione, o nel pool o già eventi sul calendario. Isolerebbe visivamente le urgenze meglio della semplice evidenziazione, ma a un costo di manutenzione non giustificato rispetto al problema reale (visibilità, non mancanza di drag).

**Tenere `scadenzeAperte()` invariata e aggiungere una barra separata**: scartata per non duplicare due concetti sovrapposti (scadenze "aperte" generiche vs "in ritardo") nella stessa pagina — fonte di confusione su quale badge guardare. Il concetto generico "entro un orizzonte futuro" si sposta in dashboard (§6).

## 4. Modifiche alla query

`InterventiModel::scadenzeAperte()` viene sostituito da `scadenzeInRitardo()`, che:
- Aggiunge alle colonne selezionate: `stato`, `data_pianificata`, `created_at` (oggi seleziona solo `id`, `data_scadenza`, `cliente_denominazione`).
- Non richiede più `data_scadenza IS NOT NULL` come filtro globale — la condizione è specifica per stato (§2.1), e per i già pianificati scatta anche senza scadenza impostata.
- Applica il filtro combinato per stato descritto in §2.1:
  ```sql
  WHERE stato NOT IN ('completato', 'annullato')
    AND (
      (stato = 'da_pianificare' AND (data_scadenza < CURDATE() OR created_at <= CURDATE() - INTERVAL 7 DAY))
      OR
      (stato IN ('pianificato', 'in_corso') AND (data_scadenza < CURDATE() OR data_pianificata < CURDATE()))
    )
  ```
- Calcola il motivo (in PHP dopo il fetch, per semplicità — non serve nel WHERE) seguendo l'ordine di gravità di §2.3: prima "appuntamento mancato" (se pianificato/in_corso e `data_pianificata < oggi`), poi "in ritardo" (se `data_scadenza < oggi`), infine "fermo" (`created_at` vecchio).
- Ordina per urgenza: appuntamenti mancati e in ritardo prima (per data più vecchia), poi i fermi.

**Eccezione abbonamenti, scoperta testando con dati reali (non prevista dalla prima stesura di questa spec)**: gli interventi da abbonamento vengono generati in blocco con largo anticipo (es. a inizio anno), quindi il loro `created_at` è quasi sempre "vecchio di più di 7 giorni" per costruzione, anche quando la vera scadenza è lontana (es. dicembre) — il criterio "fermo" li marcava sempre, a torto. Il vecchio `scadenzeAperte()` aveva già questa eccezione (`abbonamento_id IS NULL OR data_scadenza <= LAST_DAY(CURDATE())`), persa nella riscrittura iniziale. Il WHERE corretto per il ramo `da_pianificare` diventa:
```sql
stato = 'da_pianificare' AND (
    data_scadenza < CURDATE()
    OR (
        created_at <= CURDATE() - INTERVAL 7 DAY
        AND (abbonamento_id IS NULL OR data_scadenza <= LAST_DAY(CURDATE()))
    )
)
```
Il criterio via `data_scadenza < CURDATE()` (vero ritardo) resta invariato e vale per tutti, abbonamento compreso: se la scadenza è già passata è comunque un problema reale indipendentemente da quando è stato creato l'intervento.

## 5. Riepilogo modifiche da implementare

1. **`app/Models/InterventiModel.php`**: sostituito `scadenzeAperte()` con `scadenzeInRitardo()` (§4, con l'eccezione abbonamenti); rimosso il vecchio metodo, nessun altro punto del codice lo usava oltre al controller del calendario.
2. **`app/Controllers/Operativo/CalendarioController.php`**: chiamata al nuovo metodo del model **più** raggruppamento del risultato per motivo (`scadenzePerMotivo`, §2.4) — cambiamento strutturale in più rispetto alla stesura iniziale della spec, che prevedeva solo l'aggiornamento del nome metodo.
3. **`app/Views/operativo/calendario/index.php`**: blocco badge riscritto come tre pill collassabili (§2.4) invece della fila piatta prevista inizialmente — ogni pill contiene i badge dei singoli interventi con dataset (`data-id`, `data-stato`, `data-pianificata`) e tooltip; docblock `@var` aggiornato su `$scadenzePerMotivo`.
4. **`public/js/calendario.js`**: nuovo click-handler dei badge con la logica di scroll+espandi+flash per il pool e `gotoDate`+flash per gli eventi pianificati (§2.2) — invariato rispetto al piano nonostante il contenitore sia cambiato, grazie alla delega degli eventi; inizializzazione esplicita dei tooltip sulle pill (usano `data-bs-toggle="collapse"`, non intercettate dal loop automatico di `admin.php`).
5. **`public/css/calendario.css`**: nuova classe di evidenziazione temporanea `.cal-flash` (flash/pulse), riusabile sia su `.pool-card` sia sull'elemento evento FullCalendar; varianti colore per le tre pill/badge (`badge-scadenza-mancato/ritardo/fermo`).

## 6. Fuori scope

- Il concetto generico di "scadenze entro un orizzonte futuro" (es. entro la settimana, per una visione d'insieme senza carattere d'urgenza) non fa parte di questa spec: verrà affrontato separatamente come card espandibile in dashboard, pensata fin da subito come futuro modulo di una dashboard personalizzabile.
- Nessuna nuova infrastruttura di drag & drop (vedi §3): l'azionabilità si appoggia interamente sui due meccanismi già esistenti.
- Nessuna automazione dello spostamento: l'utente resta l'unico a decidere quando e dove ripianificare — la feature aiuta a trovare l'elemento, non lo sposta da sola.
- Non tocca la generazione batch degli abbonamenti né altra logica di `data_scadenza` esistente altrove (es. `poolDaPianificare()`, next-by-scadenza dei materiali sospesi).
