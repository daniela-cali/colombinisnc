# Spec — Coerenza delle azioni nelle card (posizione, stile, ordine)

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale. Questo documento riguarda solo la collocazione e l'aspetto dei pulsanti di azione nelle view; non modifica nessuna logica applicativa.

## 1. Contesto e problema di partenza

Le stesse identiche azioni si trovano in punti diversi della pagina a seconda della sezione. Non è un errore di nessuno: sono scelte fatte in momenti diversi, ognuna ragionevole presa da sola. Messe insieme però costringono l'utente a ri-orientarsi a ogni pagina, e questo è il costo che un utente di fretta non può permettersi.

Censimento sullo stato attuale, verificato su **v0.33.1**. I filtri a tendina introdotti in v0.32.0/v0.33.0 non hanno modificato nessuna delle collocazioni descritte qui.

### 1.1 Stessa azione, posizione diversa

Su un form di modifica, le azioni **Annulla / Elimina / Salva**:

| In alto (`card-tools`) | In basso (`card-footer`) |
|---|---|
| `anagrafiche/clienti/edit.php` | `cantieri/edit.php` |
| `anagrafiche/personale/edit.php` | `abbonamenti/edit.php` |
| `magazzino/articoli/edit.php` | `operativo/interventi/edit.php` |

Su una scheda di lettura, l'azione **Modifica**:

| In alto (`card-tools`) | In basso (`card-footer`) |
|---|---|
| `anagrafiche/personale/show.php` | `operativo/interventi/show.php` |
| `magazzino/articoli/show.php` | |
| `cantieri/show.php` | |
| `abbonamenti/show.php` | |

Caso limite: `magazzino/articoli/nuovo.php` ha **Annulla in alto e Salva in basso**, cioè le due metà della stessa decisione agli estremi opposti della pagina.

### 1.2 Stessa azione, stile diverso

| Azione | Varianti presenti oggi |
|---|---|
| Salva | `btn-primary btn-sm` (7 view) · `btn-warning btn-sm` (`abbonamenti/edit.php`) · `btn-primary` senza `btn-sm` (`clienti/nuovo`, `personale/nuovo`, `articoli/nuovo`) |
| Annulla | `btn-outline-secondary btn-sm` (5 view) · `btn-secondary btn-sm` (4 view) · `btn-secondary` senza dimensione (2 view) |

Quindi la stessa azione cambia **colore, riempimento e dimensione** a seconda di dove sei.

### 1.3 Stessa azione, ordine diverso

Nei footer già esistenti l'ordine varia: a volte `Elimina · Annulla · Salva`, a volte `Annulla · Elimina · Salva`, a volte solo `Annulla … Salva` spinti agli estremi con `ms-auto`.

### 1.4 Un dettaglio tecnico che condiziona il lavoro

Nelle `card-tools` diverse view scrivono il pulsante come `class="btn btn-sm"`, **senza classe di variante** (`anagrafiche/personale/show.php`, `cantieri/show.php`, `abbonamenti/show.php`). In Bootstrap 5 un pulsante senza variante è trasparente e prende il colore del testo circostante: nell'intestazione di una card funziona, ma spostato in un `card-footer` su fondo chiaro diventerebbe testo semplice, indistinguibile da un'etichetta.

Va detto anche che il sistema `--card-accent` descritto in `CLAUDE.md` (pulsanti che ereditano il colore della card) **oggi non esiste in `custom.css`**: non ci sono regole `.card-tools .btn` né variabili `--card-accent`. Quei pulsanti sono nudi, non colorati per ereditarietà. Quindi spostarli richiede sempre di assegnare una variante esplicita.

## 2. Soluzione: una regola sola

> **In basso le azioni che agiscono sul record di questa card.**
> **In alto solo quelle che ne creano o aprono un altro.**

### 2.1 Perché questa formulazione

La prima versione considerata era "in basso ciò che conclude, in alto ciò che apre", ma non risolveva il caso **Modifica**: apre un form, quindi "apre", ma agisce sul record che stai guardando. La formulazione per *oggetto dell'azione* invece decide senza ambiguità ogni caso incontrato nel censimento.

### 2.2 Applicazione

**In basso, nel `card-footer`:**

- Form (`nuovo`, `edit`): Salva, Annulla, Elimina.
- Schede (`show`): Modifica, Elimina, Completa intervento, Inizio lavoro, Annulla intervento.
- Il link di ritorno (Annulla, "Scheda cliente", "Torna all'elenco") fa parte del gruppo azioni e sta con loro.

Motivo: un form si compila dall'alto in basso e si conclude in basso; chiedere di risalire in cima per salvare va contro il senso di lettura. Le schede seguono lo stesso schema per non introdurre una seconda regola da ricordare.

**In alto, nel `card-tools`:**

- "Nuovo X" nelle liste (`index`) — crea un record diverso da quelli elencati, e sotto una tabella lunga un footer sarebbe irraggiungibile.
- "Nuovo intervento" dentro la scheda cantiere, "Nuova visita extra" dentro la scheda abbonamento — creano un altro record.
- "Stampa PDF" — produce un output, non modifica il record.
- Guida e tooltip informativi.

**Fuori dalla regola: i filtri.** I filtri a tendina (`partials/filtro_tendina.php`, dalla v0.32.0) stanno nel `card-body` sopra la tabella, non nel `card-tools`, e lì restano: non sono azioni su un record ma controlli sul contenuto mostrato, appartengono alla tabella che filtrano e nelle liste sono troppi per stare in una barra di intestazione.

### 2.3 Il pezzo che rende sostenibile "sempre in basso"

L'obiezione ovvia è che su un form lungo (es. `clienti/edit`) il pulsante Salva in fondo obbliga a scrollare. La risposta esiste già nel progetto: la classe `.azione-cta-mobile` in `custom.css`, introdotta per la scheda intervento (`mobile_ux_spec.md` §2.2), che sotto i 576px ancora l'azione principale in basso allo schermo, sempre raggiungibile.

Questa spec **generalizza quel pattern** invece di reinventarlo: le regole oggi legate a `.intervento-azioni` diventano una classe riutilizzabile `.card-azioni`, che ogni footer di form e scheda applica.

### 2.4 Classi semantiche per ordine e stile

Sul `card-footer` si applica `.card-azioni`. Ogni pulsante riceve una classe semantica che ne determina ordine e collocazione, così l'ordine non dipende più da come è scritto il markup:

| Classe | Azioni | Stile standard |
|---|---|---|
| `.azione-primaria` | Salva, Completa intervento, Inizio lavoro | `btn btn-sm btn-primary` (`btn-success` per i cambi di stato positivi: Completa, Inizio lavoro) |
| `.azione-secondaria` | Modifica | `btn btn-sm btn-primary` |
| `.azione-ritorno` | Annulla, Scheda cliente, Torna all'elenco | `btn btn-sm btn-outline-secondary` |
| `.azione-distruttiva` | Elimina, Annulla intervento | `btn btn-sm btn-outline-danger` |

**Ordine su desktop** (da sinistra): `.azione-ritorno` a sinistra con `me-auto`, poi spinte a destra `.azione-distruttiva`, `.azione-secondaria`, `.azione-primaria`. La distruttiva non è mai adiacente alla primaria.

**Ordine su mobile** (sotto 576px, impilati a piena larghezza, minimo 44px di altezza): primaria, secondaria, ritorno, distruttiva staccata in fondo. La primaria porta `.azione-cta-mobile` quando è l'azione del momento.

**Dimensione**: sempre `btn-sm`. È già la scelta maggioritaria e mantiene i footer compatti.

**Colore**: `btn-warning` su "Salva modifiche" in `abbonamenti/edit.php` va verificato prima di cambiarlo — se fosse una scelta voluta per richiamare il giallo della card abbonamenti, allora la regola da documentare è un'altra ("il primario segue il colore della card") e va applicata ovunque, non tolta lì. In assenza di una ragione, si uniforma a `btn-primary`.

## 3. Modifiche file per file

### 3.1 CSS — `public/css/custom.css`

- Rinominare le regole `.intervento-azioni` in `.card-azioni` (generalizzazione, stesso comportamento).
- Sostituire le classi specifiche `.btn-azione-inizia`, `.btn-azione-completa`, `.btn-azione-modifica`, `.btn-azione-scheda-cliente`, `.btn-azione-annulla`, `.btn-azione-elimina` con le quattro semantiche di §2.4.
- Aggiungere le regole desktop (allineamento e ordine) che oggi sono scritte inline nelle singole view come utility Bootstrap.
- `.azione-cta-mobile` resta invariata.

Nessuno stile inline nelle view, nessun file CSS nuovo: la sezione è piccola e appartiene a `custom.css`.

### 3.2 View da spostare (da `card-tools` a `card-footer`)

| File | Cosa si sposta | Nota |
|---|---|---|
| `anagrafiche/clienti/edit.php` | Annulla · Elimina · Salva modifiche | Il pulsante Salva usa `form="form-update"`: funziona identico nel footer, l'attributo `form` non richiede prossimità |
| `anagrafiche/personale/edit.php` | Annulla · Elimina (condizionato a `$puoEliminare`) · Salva | Idem |
| `magazzino/articoli/edit.php` | Annulla · Elimina · Salva | Idem |
| `anagrafiche/personale/show.php` | Modifica | Oggi è `btn btn-sm` nudo: **assegnare variante esplicita** (§1.4) |
| `magazzino/articoli/show.php` | Torna all'elenco | Diventa `.azione-ritorno` |
| `cantieri/show.php` | Modifica | Il PDF **resta** in `card-tools`. Pulsante nudo: assegnare variante |
| `abbonamenti/show.php` | Modifica | "Nuova visita extra" **resta** in `card-tools`. Pulsante nudo: assegnare variante |

### 3.3 View da consolidare

| File | Cosa cambia |
|---|---|
| `magazzino/articoli/nuovo.php` | Annulla scende dal `card-tools` al `card-footer` insieme a Salva; il `card-tools` sparisce |

### 3.4 View già in posizione corretta, da normalizzare

Qui non si sposta niente: si applicano `.card-azioni` e le classi semantiche, si uniformano colore, dimensione e ordine.

| File | Cosa cambia |
|---|---|
| `abbonamenti/edit.php` | `btn-warning` → primaria (vedi verifica in §2.4); `btn-secondary` → ritorno |
| `abbonamenti/nuovo.php` | `btn-secondary` → ritorno |
| `cantieri/edit.php`, `cantieri/nuovo.php` | `btn-secondary` → ritorno |
| `anagrafiche/clienti/nuovo.php`, `anagrafiche/personale/nuovo.php` | aggiungere `btn-sm`; `btn-secondary` → ritorno |
| `operativo/interventi/nuovo.php` | classi semantiche |
| `operativo/interventi/edit.php` | classi semantiche; ordine Elimina/Annulla/Salva secondo §2.4 |
| `operativo/interventi/show.php` | migrazione da `.intervento-azioni` e `.btn-azione-*` alle classi nuove; comportamento invariato |

### 3.5 View che non si toccano

Tutte le `index.php` con "Nuovo X" in `card-tools`: sono già conformi alla regola. Lo stesso vale per filtri, guida e tooltip in alto.

### 3.6 Convenzione da scrivere in `CLAUDE.md`

Una sezione nuova che riporti la regola di §2, la tabella delle classi semantiche di §2.4 e la nota di §1.4 sui pulsanti senza variante. Serve perché ogni view scritta dopo nasca già conforme: è il punto che rende il lavoro definitivo invece di temporaneo.

Va anche corretta la sezione esistente su `--card-accent`, che descrive un sistema non presente nel CSS (§1.4).

## 4. Ordine di lavorazione

Una sezione per volta, testando man mano — non serve un unico passaggio globale. Nessun controller e nessun model vengono toccati: si sposta markup dentro la stessa view, quindi un eventuale errore riguarda solo quella pagina ed è visibile subito.

1. CSS: `.card-azioni` e classi semantiche in `custom.css`; migrazione di `operativo/interventi/show.php` come banco di prova (è la view che ha già il comportamento desiderato).
2. Form `edit` delle tre sezioni con i pulsanti in alto (clienti, personale, articoli).
3. Schede `show` (personale, articoli, cantieri, abbonamenti).
4. `magazzino/articoli/nuovo.php`.
5. Normalizzazione di stile e ordine sulle view già a posto.
6. Sezione in `CLAUDE.md`.

## 5. Alternative scartate

- **Uniformare tutto in alto (`card-tools`)**: sarebbe altrettanto coerente ma peggiore. Su form lunghi il Salva finisce lontano dal punto in cui si sta scrivendo, e su mobile la barra dell'intestazione — dove lo spazio orizzontale è minimo — dovrebbe contenere Annulla, Elimina e Salva insieme.
- **Un partial condiviso `_azioni_form.php`**: sembra più pulito ma i pulsanti variano troppo (chi ha Elimina, chi ha `from`, chi ha condizioni di stato o di permesso come `$puoAgire` e `$puoEliminare`). Finirebbe pieno di parametri, più complesso del problema che risolve. Meglio convenzione + CSS condiviso, lasciando il markup esplicito e leggibile in ogni view.

  Va detto che il progetto ha un controesempio riuscito: `partials/filtro_tendina.php` (v0.32.0) è esattamente un partial parametrico condiviso, e funziona bene. La differenza sta nella forma di ciò che si astrae. Un filtro ha sempre la stessa struttura — etichetta, icona, elenco di voci, colonna su cui agisce — e cambia solo nei dati, quindi i parametri sono pochi e stabili. Un gruppo di azioni invece cambia nella *struttura*: alcune sono `<a>`, altre `<button>` dentro un `<form>` con `csrf_field()` e `from`, altre aprono un modal, e quali compaiono dipende da stato del record e permessi dell'utente. Astrarre quella varietà richiederebbe di passare al partial condizioni e frammenti di markup, cioè spostare la complessità senza ridurla. Se in futuro le azioni si dimostrassero più uniformi del previsto, la decisione si può riaprire: il precedente dei filtri mostra che quando la forma è stabile il partial è la scelta giusta.
- **Rinominare le classi `.btn-azione-*` mantenendo anche le vecchie come alias**: eviterebbe di toccare `interventi/show.php`, ma lascerebbe due nomenclature vive contemporaneamente — esattamente il tipo di ambiguità che questa spec vuole eliminare.

## 6. Fuori scope

- Qualsiasi modifica a controller, model, rotte o logica applicativa.
- Il comportamento mobile della scheda intervento, già definito e implementato in `mobile_ux_spec.md` §2.2: qui viene solo generalizzato, non ridiscusso.
- Le liste DataTables e la loro resa su mobile (`mobile_ux_spec.md` §3).
- I filtri a tendina e la loro collocazione (§2.2): restano dove sono.
- Colori e identità visiva delle card (`card-primary`, `card-outline`, ecc.): questa spec riguarda i pulsanti, non le card.
- Il manifest PWA e gli altri punti aperti di `mobile_ux_spec.md`.
