# Spec — Coerenza delle azioni nelle card (posizione, stile, ordine)

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale. Questo documento riguarda solo la collocazione e l'aspetto dei pulsanti di azione nelle view; non modifica nessuna logica applicativa.

> **Nota di revisione — implementato in v0.34.0.** La regola di §2 è stata **ribaltata** rispetto alla prima stesura di questo spec, e proprio sul caso che ne era l'esempio principale. Il ragionamento che ha portato al cambio è in §2.1: vale la pena leggerlo prima di §2, perché è il pezzo di storia che il codice da solo non racconta.

## 1. Contesto e problema di partenza

Le stesse identiche azioni si trovano in punti diversi della pagina a seconda della sezione. Non è un errore di nessuno: sono scelte fatte in momenti diversi, ognuna ragionevole presa da sola. Messe insieme però costringono l'utente a ri-orientarsi a ogni pagina, e questo è il costo che un utente di fretta non può permettersi.

Censimento sullo stato di partenza, verificato su **v0.33.1**.

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

| Azione | Varianti presenti prima |
|---|---|
| Salva | `btn-primary btn-sm` (7 view) · `btn-warning btn-sm` (`abbonamenti/edit.php`) · `btn-primary` senza `btn-sm` (`clienti/nuovo`, `personale/nuovo`, `articoli/nuovo`, le tre pagine dell'import) |
| Annulla | `btn-outline-secondary btn-sm` (5 view) · `btn-secondary btn-sm` (4 view) · `btn-secondary` senza dimensione (2 view) |
| Modifica | `btn-primary` con etichetta · `btn btn-sm` **nudo** con etichetta · `btn btn-sm` **nudo** con la sola matita (2 view) |
| Nuovo X | `btn-primary` con etichetta completa (4 elenchi) · `btn btn-sm` **nudo** con la sola parola "Nuovo" (cantieri, abbonamenti) |

Quindi la stessa azione cambiava **colore, riempimento, dimensione e perfino le parole** a seconda di dove ti trovavi.

### 1.3 Stessa azione, ordine diverso

Nei footer già esistenti l'ordine variava: a volte `Elimina · Annulla · Salva`, a volte `Annulla · Elimina · Salva`, a volte solo `Annulla … Salva` spinti agli estremi con `ms-auto`.

### 1.4 Il difetto tecnico alla radice

Diverse view scrivevano il pulsante come `class="btn btn-sm"`, **senza classe di variante**. In Bootstrap 5 un pulsante senza variante è trasparente e prende il colore del testo circostante: nell'intestazione di una card sembra funzionare, ma è un caso — nessuno l'ha scelto. È la causa comune sia delle matite grigie sia dei "Nuovo" spenti, e spostato in un `card-footer` su fondo chiaro quel pulsante diventerebbe testo semplice, indistinguibile da un'etichetta.

Va detto anche che il sistema `--card-accent` descritto in `CLAUDE.md` (pulsanti che ereditano il colore della card) **non esisteva in `custom.css`**: non c'erano regole `.card-tools .btn` né variabili `--card-accent`. Quei pulsanti erano nudi, non colorati per ereditarietà. La sezione di `CLAUDE.md` è stata corretta di conseguenza.

## 2. La regola

> **In alto gli strumenti che ti portano altrove**: Modifica, Stampa PDF, Nuovo X.
> **In basso le azioni che decidono qualcosa e chiudono il discorso qui**: Salva, Annulla, Elimina, Completa intervento, Sospendi, Disdici.

### 2.1 Perché *questa* e non quella scritta all'inizio

La prima stesura di questo spec proponeva una regola diversa: *in basso le azioni che agiscono sul record di questa card, in alto solo quelle che ne creano o aprono un altro*. Ragionava sull'**oggetto** dell'azione, e da lì concludeva che Modifica dovesse scendere nel footer, perché agisce sul record che stai guardando.

Applicandola è emerso che portava a spostare **quattro** schede per farle assomigliare a **una**: Modifica stava già in alto in `personale/show`, `cantieri/show`, `abbonamenti/show` e `clienti/show`, e in basso solo in `interventi/show`. Una regola che deve riscrivere la maggioranza del codice esistente per essere rispettata di solito sta descrivendo male il problema.

La regola attuale ragiona invece sul **tipo** di azione, e ha tre vantaggi concreti: conferma il codice già scritto invece di contraddirlo, tiene separate due cose che l'utente percepisce come diverse — gli strumenti di una scheda e le decisioni su un form — e lascia in piedi la parte del lavoro che serviva davvero, cioè i form con Salva in fondo. L'unica view spostata diventa `interventi/show`, dove Modifica **sale** nel `card-tools`.

Resta valido il motivo per cui i form vogliono i pulsanti in basso: un form si compila dall'alto in basso e si conclude in basso; chiedere di risalire in cima per salvare va contro il senso di lettura, e su mobile obbligherebbe a scorrere all'indietro tutta la pagina.

### 2.2 Applicazione

**In alto, nel `card-tools`:**

- **Modifica** in ogni scheda `show`.
- **Stampa PDF**, che produce un output senza modificare il record.
- **Nuovo X** negli elenchi, e anche dentro una scheda quando crea un altro record ("Nuovo intervento" nella scheda cantiere, "Nuova visita extra" nella scheda abbonamento). Sotto una tabella lunga un footer sarebbe irraggiungibile.
- Guida e tooltip informativi.

**In basso, nel `card-footer`:**

- Form (`nuovo`, `edit`): Salva, Annulla, Elimina.
- Schede (`show`): le azioni di stato — Completa intervento, Inizio lavoro, Annulla intervento, Sospendi, Disdici, Riapri.
- Il link di ritorno ("Annulla", "Scheda cliente", "Torna all'elenco") fa parte del gruppo azioni e sta con loro.

**Fuori dalla regola: i filtri.** I filtri a tendina (`partials/filtro_tendina.php`, dalla v0.32.0) stanno nel `card-body` sopra la tabella e lì restano: non sono azioni su un record ma controlli sul contenuto mostrato, appartengono alla tabella che filtrano e nelle liste sono troppi per stare in una barra di intestazione.

### 2.3 Il pezzo che rende sostenibile "il form si salva in fondo"

L'obiezione ovvia è che su un form lungo (es. `clienti/edit`) il pulsante Salva in fondo obbliga a scorrere. La risposta esisteva già nel progetto: la classe `.azione-cta-mobile` in `custom.css`, introdotta per la scheda intervento (`mobile_ux_spec.md` §2.2), che sotto i 576px ancora l'azione principale in basso allo schermo, sempre raggiungibile.

Questa spec **generalizza quel pattern** invece di reinventarlo: le regole prima legate a `.intervento-azioni` sono diventate la classe riutilizzabile `.card-azioni`, che ogni footer di form e scheda applica.

### 2.4 Classi semantiche per ordine e stile

Sul `card-footer` si applica `.card-azioni`. Ogni pulsante riceve una classe semantica che ne determina ordine e collocazione, così l'ordine non dipende più da come è scritto il markup. **La classe va sul figlio diretto** di `.card-azioni`: l'`<a>`, il `<button>`, oppure il `<form>` che lo racchiude quando l'azione è un POST — è il figlio diretto a essere l'elemento flex che riceve l'`order`.

| Classe | Azioni | Stile |
|---|---|---|
| `.azione-primaria` | Salva, Completa intervento, Inizio lavoro | `btn btn-sm btn-primary` (`btn-success` per i cambi di stato positivi) |
| `.azione-ritorno` | Annulla, Scheda cliente, Torna all'elenco | `btn btn-sm btn-outline-secondary` |
| `.azione-distruttiva` | Elimina, Annulla intervento | `btn btn-sm btn-outline-danger` |
| `.azione-secondaria` | *(prevista, oggi inutilizzata: era Modifica, che è risalita in alto)* | `btn btn-sm btn-primary` |

**Ordine su desktop** (da sinistra): `.azione-ritorno` a sinistra con `margin-right: auto`, poi spinte a destra `.azione-distruttiva`, `.azione-secondaria`, `.azione-primaria`. La distruttiva non è mai adiacente alla primaria.

**Ordine su mobile** (sotto 576px, impilati a piena larghezza, minimo 44px di altezza): primaria, secondaria, ritorno, distruttiva staccata in fondo. La primaria porta `.azione-cta-mobile` quando è l'azione del momento.

Il `padding-bottom` di 4.5rem che lascia spazio al CTA ancorato è **incondizionato** su mobile, non limitato ai soli footer che il CTA ce l'hanno davvero. Scartato `:has()`, che avrebbe evitato lo spazio vuoto altrove ma richiede iOS Safari 15.4+: su un telefono più vecchio l'ultimo pulsante finirebbe coperto e non cliccabile, e il prezzo di quel rischio è più alto di qualche decina di pixel di vuoto.

### 2.5 Stile dei pulsanti in alto

| Azione | Stile | Note |
|---|---|---|
| Modifica | `btn btn-sm btn-outline-primary` + `bi-pencil` | standard preso da `clienti/show`, che l'aveva già giusto |
| Stampa PDF | `btn btn-sm btn-outline-secondary` + `bi-file-earmark-pdf` | |
| Nuovo X | `btn btn-sm btn-primary` + `bi-plus-lg` | **pieno**, ed etichetta completa: "Nuovo cantiere", non "Nuovo" |

La distinzione fra pieno e contornato non è decorativa: su un elenco "Nuovo X" è *l'azione* per cui sei su quella pagina, mentre Modifica e PDF sono strumenti di supporto. Uniformarli tutti avrebbe fatto perdere quella gerarchia.

**Su mobile l'etichetta sparisce, l'icona no.** In una card in colonna stretta l'intestazione contiene già titolo e badge: aggiungere due pulsanti con etichetta la manda a capo e la gonfia. L'etichetta va quindi in uno `<span class="d-none d-sm-inline ms-1">`, con `title` sul pulsante che fa da tooltip — pattern già presente nel progetto in `impostazioni/tipi_intervento/_icona_picker.php`. Lo spazio si mette sullo `span` e non sull'icona, altrimenti su telefono l'icona resta scentrata nel bersaglio.

Contropartita necessaria: `.card-tools .btn` riceve su mobile `min-height` e `min-width` di 44px, perché un'icona sola è un bersaglio piccolo per chi la preme in cantiere.

## 3. Modifiche file per file

### 3.1 CSS — `public/css/custom.css`

- `.intervento-azioni` generalizzata in `.card-azioni`; le sei classi specifiche `.btn-azione-*` sostituite dalle quattro semantiche di §2.4.
- Aggiunte le regole desktop (flex, `gap`, allineamento e `order`), che prima stavano nelle view come utility Bootstrap.
- Aggiunta l'area minima toccabile di 44px per `.card-tools .btn` su mobile.
- `.azione-cta-mobile` invariata.
- Nuova `.mat-pagina` accanto alle regole `.mat-*`: la larghezza massima della pagina Materiali stava come `style="max-width:900px"` nel markup, ed è passata a 1200px in CSS.

### 3.2 View con azioni spostate in basso

| File | Cosa si è spostato |
|---|---|
| `anagrafiche/clienti/edit.php` | Annulla · Elimina · Salva modifiche |
| `anagrafiche/personale/edit.php` | Annulla · Elimina (condizionato a `$puoEliminare`) · Salva |
| `magazzino/articoli/edit.php` | Annulla · Elimina · Salva |
| `magazzino/articoli/nuovo.php` | Annulla scende dal `card-tools` e si ricongiunge a Salva; il `card-tools` sparisce |

In tutti e tre gli `edit` il pulsante Salva usa `form="form-update"` e continua a funzionare dal footer: l'attributo `form` non richiede prossimità nel DOM. Il `form-update` era già chiuso prima della fine della card, quindi il form di Elimina non finisce annidato dentro quello di update.

### 3.3 View con azioni spostate in alto

| File | Cosa si è spostato |
|---|---|
| `operativo/interventi/show.php` | **Modifica sale** nel `card-tools`, accanto a "Creato il". Con lei sale il calcolo di `$editFrom` e le due condizioni che la governano (`$puoAgire`, stato diverso da annullato) |

### 3.4 View normalizzate senza spostamenti

Footer già in posizione corretta: applicate `.card-azioni` e le classi semantiche, uniformati colore, dimensione e ordine.

`cantieri/edit.php` · `cantieri/nuovo.php` · `abbonamenti/edit.php` · `abbonamenti/nuovo.php` · `anagrafiche/clienti/nuovo.php` · `anagrafiche/personale/nuovo.php` · `impostazioni/parametri.php` · le tre pagine di `impostazioni/import_clienti/`.

Note puntuali:

- `abbonamenti/edit.php` aveva `btn-warning` su "Salva modifiche", unico caso in tutto il gestionale. Verificato con l'utente che non era una scelta voluta per richiamare il giallo della card: uniformato a `btn-primary`.
- `impostazioni/parametri.php` non aveva un `card-footer` ma un `d-flex` libero fuori dalla card. `.card-azioni` non dipende da `.card-footer`, quindi si applica lo stesso — e quella pagina guadagna l'impilamento su mobile che non aveva.
- Le tre pagine dell'import erano prive di `btn-sm`: erano le uniche col pulsante a grandezza piena, e si notava.

### 3.5 Pulsanti in alto normalizzati

`anagrafiche/clienti/show.php` · `anagrafiche/personale/show.php` · `cantieri/show.php` · `abbonamenti/show.php` (Modifica, PDF) · `cantieri/index.php` · `abbonamenti/index.php` (Nuovo X) · `impostazioni/import_clienti/` (i due passaggi fra le pagine, resi `btn-outline-secondary`).

### 3.6 View che non si toccano

`cantieri/show.php` e `abbonamenti/show.php` conservano i loro `card-footer` di azioni di stato così come sono: i pulsanti sono impilati a piena larghezza perché la card sta in `col-md-4`, ed è la scelta giusta a quella larghezza. Le quattro classi semantiche non si applicano bene lì — Sospendi, Disdici, Riattiva sono tutti cambi di stato, nessuno è "il primario" — e forzarcele avrebbe peggiorato una pagina che funziona.

`anagrafiche/clienti/materiali.php` cambia solo la larghezza massima. Le sue `card border-0 shadow-sm` e l'intestazione col ritorno a sinistra sono **identiche a `clienti/show.php`**: uniformarle allo stile `card-outline card-primary` avrebbe reso la sotto-pagina diversa dalla sua pagina madre, cioè l'opposto dell'obiettivo.

Tutte le `index.php` con "Nuovo X" nel `card-tools` erano già conformi alla regola.

## 4. Fuori scope

- Qualsiasi modifica a controller, model, rotte o logica applicativa.
- Il comportamento mobile della scheda intervento, già definito in `mobile_ux_spec.md` §2.2: qui viene solo generalizzato.
- Le liste DataTables e la loro resa su mobile (`mobile_ux_spec.md` §3).
- I filtri a tendina e la loro collocazione: restano dove sono.
- Colori e identità visiva delle card (`card-primary`, `card-outline`, ecc.): questa spec riguarda i pulsanti, non le card.
- Il manifest PWA e gli altri punti aperti di `mobile_ux_spec.md`.
- L'init condiviso delle DataTable e le etichette di validazione, arrivati nella stessa versione ma per strade indipendenti: sono documentati nel `CHANGELOG.md` della v0.34.0.

## 5. Alternative scartate

- **La regola per oggetto dell'azione** ("in basso ciò che agisce su questo record"), cioè la prima stesura di questo documento. Scartata in corso d'opera: vedi §2.1.
- **Uniformare tutto in alto (`card-tools`)**: sarebbe altrettanto coerente ma peggiore. Su form lunghi il Salva finisce lontano dal punto in cui si sta scrivendo, e su mobile la barra dell'intestazione — dove lo spazio orizzontale è minimo — dovrebbe contenere Annulla, Elimina e Salva insieme.
- **Un partial condiviso `_azioni_form.php`**: sembra più pulito ma i pulsanti variano troppo (chi ha Elimina, chi ha `from`, chi ha condizioni di stato o di permesso come `$puoAgire` e `$puoEliminare`). Finirebbe pieno di parametri, più complesso del problema che risolve. Meglio convenzione + CSS condiviso, lasciando il markup esplicito e leggibile in ogni view.

  Va detto che il progetto ha un controesempio riuscito: `partials/filtro_tendina.php` (v0.32.0) è esattamente un partial parametrico condiviso, e funziona bene. La differenza sta nella forma di ciò che si astrae. Un filtro ha sempre la stessa struttura — etichetta, icona, elenco di voci, colonna su cui agisce — e cambia solo nei dati, quindi i parametri sono pochi e stabili. Un gruppo di azioni invece cambia nella *struttura*: alcune sono `<a>`, altre `<button>` dentro un `<form>` con `csrf_field()` e `from`, altre aprono un modal, e quali compaiono dipende da stato del record e permessi dell'utente. Se in futuro le azioni si dimostrassero più uniformi del previsto la decisione si può riaprire.
- **Mantenere le vecchie `.btn-azione-*` come alias delle nuove**: eviterebbe di toccare `interventi/show.php`, ma lascerebbe due nomenclature vive contemporaneamente — esattamente l'ambiguità che questa spec vuole eliminare.
- **`:has()` per il padding del CTA su mobile**: vedi §2.4.
