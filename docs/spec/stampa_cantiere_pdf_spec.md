# Spec — Stampa PDF scheda cantiere

> Da leggere insieme a `docs/spec/stampa_cliente_pdf_spec.md` (prima scheda del branch `stampe-pdf`, definisce pattern tecnico e palette grafica) e a `docs/ANALISI.md` per il contesto architetturale generale.

## 1. Contesto e stato di partenza

Seconda scheda del branch `stampe-pdf` (dopo Cliente, già fatta). Il pattern tecnico è già stabilito e si riusa senza modifiche: metodo `pdf()` dedicato nel controller, view HTML/CSS autonoma (no `extend layouts/admin`), `Dompdf\Options` con `defaultFont = Helvetica` e `isRemoteEnabled = false`, stream inline (`Attachment => false`), palette ripresa dal vecchio progetto (blu `#2980b9`, grigi `#6b7280`/`#e5e7eb`).

## 2. Obiettivo

A differenza del PDF Cliente (documento **operativo essenziale**, solo "cosa resta da fare"), il PDF Cantiere è un **riepilogo completo**: decisione esplicita dell'utente, confermata dal mockup disegnato a mano (`docs/spec/mockup pdf per cantiere.png`, vedi §3). Un cantiere è un progetto che dura mesi o anni — chi lo stampa (o lo consulta a fine lavori, o lo allega a un contenzioso/consuntivo) vuole vedere **tutto lo storico**, non solo i prossimi passi:

- **Anagrafica cliente e dati cantiere completi**, non un sottoinsieme essenziale come nel PDF Cliente.
- **Diario completo**, non le ultime 3 note come nel riquadro sintetico usato dentro il PDF Cliente.
- **Tutti gli interventi**, in tutti gli stati (inclusi completati e annullati), non solo i due gruppi "da pianificare/pianificati" del PDF Cliente — con i relativi materiali (portati e da portare) elencati intervento per intervento.

## 3. Contenuto e decisioni chiave

Struttura confermata da un mockup disegnato a mano dall'utente (`docs/spec/mockup pdf per cantiere.png`): due riquadri affiancati in alto, poi due sezioni a piena larghezza sotto.

### 3.1 Riquadri affiancati: anagrafica cliente completa | dati cantiere completi
Due tabelle `dettagli` (etichetta/valore) affiancate in una tabella a due colonne 50/50 (stesso pattern HTML già usato per i riquadri cantiere nel PDF Cliente, §3.5 di quella spec):

- **Sinistra — Cliente**: anagrafica **completa**, non solo denominazione+indirizzo come inizialmente ipotizzato — stessi campi già mostrati nella sezione "Anagrafica" del PDF Cliente (denominazione, indirizzo/cap/città/provincia, zona, telefono, email, P.IVA, codice fiscale, note), ciascuno solo se presente. Serve una `find()` separata su `ClientiModel` (`conCliente()` del cantiere restituisce solo `cliente_denominazione`, non basta).
- **Destra — Cantiere**: titolo, badge stato (`CantieriModel::STATI_LABEL`, classi `.st-cantiere-*` vedi §4), tipo (`TIPI_LABEL`), data inizio, data fine prevista, note cantiere se presenti.

### 3.2 Diario — completo, nessun troncamento (piena larghezza)
Tutte le note da `CantieriNoteModel::perCantiere($id)` (esiste già, ordina `data_nota DESC`), nessun `array_slice()`. Sezione omessa solo se il diario è vuoto.

### 3.3 Elenco interventi con materiali — sezione unica, non due separate
Il mockup mostra **un'unica sezione** "Elenco interventi con materiali portati/da portare", non due sezioni distinte come ipotizzato in una prima stesura di questa spec. Per ciascun intervento (tutti gli stati, nessun filtro — storico completo, ordinati per `data_pianificata DESC` come già restituito da `InterventiModel::perCantiere($id)`):

- Riga di intestazione dell'intervento: codice, tipo, tecnico assegnato (`tecnico_nome`), data pianificata, badge stato (tutti e 6 gli stati intervento, non solo i 2 già mappati nel PDF Cliente — serve estendere la mappa badge, vedi §4).
- Sotto, elenco dei suoi materiali — **sia consegnati che da portare**, non solo i "da portare" come in una prima stesura: per ogni riga materiale, descrizione, quantità e un badge di stato (`InterventiMaterialiModel::STATI_LABEL`: "Da portare" / "Consegnato") così il documento mostra a colpo d'occhio cosa è già stato portato in cantiere e cosa manca ancora, intervento per intervento.
- Righe materiali omesse per un intervento che non ne ha; l'intera riga intervento resta comunque (mostra almeno codice/data/stato anche senza materiali collegati).

Dati: **nessun nuovo metodo nel model**. Nel controller si cicla su `$interventi = (new InterventiModel())->perCantiere($id)` e per ciascuno si allega `$iv['materiali'] = (new InterventiMaterialiModel())->perIntervento($iv['id'])` (metodo già esistente, usato dalla scheda intervento) — stesso pattern già usato nel PDF Cliente per allegare note/interventi a ogni cantiere.

## 4. Stile grafico

Stessa palette e stesse classi CSS del PDF Cliente (riusate, nessuna nuova classe base). Estensione necessaria solo per i **badge di stato intervento**: il PDF Cliente definisce badge solo per priorità (`urgente`/`ordinario`/`programmato`) e per i 2 stati cantiere usati lì (`s-completato`/`s-in_corso`, riferiti allo stato *cantiere* aperto/sospeso, nome fuorviante ma isolato in quel file). Qui servono badge per i 6 stati **intervento** (`InterventiModel::STATI_LABEL`), quindi si introducono classi dedicate distinte per non confondersi con quelle già presenti nel PDF Cliente (ogni view PDF ha il proprio `<style>` isolato, nessun CSS condiviso tra le due):

| Stato           | Classe PDF        | Colori (bg / testo)      |
|-----------------|--------------------|---------------------------|
| da_pianificare  | `.st-da-pianificare` | `#e5e7eb` / `#374151` (grigio, come "ordinario") |
| pianificato     | `.st-pianificato`    | `#dbeafe` / `#1e40af` (blu, come "programmato") |
| in_corso        | `.st-in-corso`       | `#fef3c7` / `#92400e` (ambra) |
| completato      | `.st-completato`     | `#d1fae5` / `#065f46` (verde) |
| annullato       | `.st-annullato`      | `#fee2e2` / `#991b1b` (rosso) |
| sospeso         | `.st-sospeso`        | `#ffedd5` / `#9a3412` (arancio, distinto da "in corso") |

Badge stato **cantiere** in intestazione (§3.1): stessa idea, 3 classi `.st-cantiere-aperto`/`.st-cantiere-sospeso`/`.st-cantiere-chiuso` (verde/ambra/grigio) — nomi distinti dai badge stato intervento per evitare ambiguità nello stesso documento.

## 5. Riepilogo modifiche da implementare

1. **`app/Config/Routes.php`**: nuova rotta nel gruppo `cantieri` già esistente:
   ```php
   $routes->get('(:num)/pdf', 'CantieriController::pdf/$1');
   ```
2. **`app/Controllers/CantieriController.php`**: nuovo metodo `pdf(int $id)`, stesso schema di `ClientiController::pdf()` — carica cantiere via `conCliente($id)` (redirect con errore se non trovato), carica il cliente completo con `(new ClientiModel())->find($cantiere['cliente_id'])`, diario completo (`CantieriNoteModel::perCantiere($id)`), interventi non filtrati (`InterventiModel::perCantiere($id)`) con materiali allegati per ciascuno (`InterventiMaterialiModel::perIntervento($iv['id'])`, §3.3), costruisce `Dompdf` con le stesse `Options` già usate altrove, `setPaper('A4', 'portrait')`, stream inline.
3. **Nuova view `app/Views/cantieri/pdf_scheda_cantiere.php`**: HTML/CSS autonomo, palette e struttura riprese da `pdf_scheda_cliente.php` (stessa intestazione azienda con logo base64 da `Azienda.sede_*`), riquadri cliente/cantiere affiancati (§3.1), nuove classi badge stato intervento/cantiere (§4).
4. **`app/Views/cantieri/show.php`**: nuovo bottone "Stampa PDF" nel `card-header` della colonna dettagli (accanto a "Modifica"), link a `cantieri/{id}/pdf`.
5. Nessuna modifica ai model: tutti i metodi necessari esistono già (`CantieriModel::conCliente()`, `CantieriNoteModel::perCantiere()`, `InterventiModel::perCantiere()`, `InterventiMaterialiModel::perIntervento()`, `ClientiModel::find()`) — solo query aggiuntive nel controller (una `find()` cliente + un `perIntervento()` per ogni intervento del cantiere, tipicamente pochi).

## 6. Esplicitamente fuori scope per ora

- Logo aziendale (già gestito genericamente dal pattern comune, non specifico di questa scheda).
- Estensione ad Abbonamento — prossima scheda del branch dopo questa.
- Qualsiasi filtro/troncamento sullo storico: la scelta esplicita di questa scheda è "tutto", al contrario del Cliente.
