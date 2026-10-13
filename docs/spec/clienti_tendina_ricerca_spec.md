# Spec — Scelta del cliente con ricerca mentre si scrive

> Concordata il 12.10.2026.

## Contesto / problema

Nei form di nuovo intervento, nuovo cantiere e nuova proposta di abbonamento il cliente si
sceglie da una `<select>` nativa con tutti i clienti. Già con qualche decina di nomi è
macchinosa, sul telefono e anche sul PC; a regime, con l'anagrafica importata da Ad Hoc,
diventa una lista di centinaia di righe da scorrere.

Nel farlo sono emersi due difetti collegati:

- cantieri e abbonamenti ordinavano la tendina per `ragsoc`, che per le persone fisiche è
  vuota: tutti i privati finivano in cima, prima delle società;
- Tom Select, già in uso per materiali e modalità di pagamento, era caricato view per view, e
  il correttivo del bug #729 (la pagina salta a ogni apertura del menu) c'era solo in due dei
  quattro punti. Aggiungere la tendina cliente avrebbe portato a caricarlo due volte nella
  stessa pagina in `interventi/nuovo` e `abbonamenti/nuovo`.

## Soluzione concordata

Tom Select sopra la `<select>` esistente, con la città sotto il nome.

### Decisioni e perché

1. **Tom Select, già presente nel progetto.** Lavora sopra la `<select>`: il form invia lo
   stesso `cliente_id`, quindi validazione, `old()`, sistema `from` e controller non cambiano.
   Anche il listener `change` del form intervento (sospesi del cliente) continua a funzionare,
   perché Tom Select aggiorna la `<select>` originale e ne lancia l'evento.
2. **Ricerca nel browser, senza endpoint.** Le opzioni sono già nella pagina: Tom Select le
   filtra da sé e regge senza fatica qualche migliaio di righe. Un endpoint di ricerca si
   aggiunge solo se in produzione la pagina diventasse lenta.
3. **Al massimo 8 risultati nel menu.** Su iPhone il menu di Tom Select non scorre col dito
   (scorre la pagina sotto): per questo i materiali su mobile usano la `<select>` nativa, vedi
   `_form_materiale_scripts.php`. Un menu che non ha bisogno di scorrere aggira il difetto: si
   scrivono tre o quattro lettere e il cliente cercato è fra i primi. Si perde la possibilità di
   sfogliare tutti i clienti in ordine alfabetico, che con centinaia di nomi nessuno farebbe.
   **Da verificare sul telefono**: se non bastasse, l'alternativa è una finestra a tutto schermo
   con campo di ricerca e lista, fatta di elementi normali.
4. **Città sotto il nome, e ricerca anche per città.** Distingue gli omonimi. Il codice cliente
   resta fuori: non si usa per cercare.
5. **Un partial per la tendina, un attributo per il comportamento.** La `<select>` si stampa
   con `partials/select_cliente.php`, che mette `data-cerca-cliente`; lo script condiviso
   trasforma ogni `<select>` con quell'attributo. Una view nuova eredita la ricerca includendo
   il partial, senza scrivere JavaScript.
6. **Tom Select caricato una volta sola, dal layout**, con `initTomSelect(el, opzioni)` in
   `public/js/tom-select-init.js` che applica sempre il correttivo #729: è lo stesso criterio di
   `initTabella()` per le DataTable. Una pagina che non lo usa scarica un file in più, ma già in
   cache dopo la prima visita.
7. **`ClientiModel::perTendina()`** dà solo `id`, `cliente_denominazione` e `citta`, ordinati per
   denominazione: niente `findAll()` di tutte le colonne né il conteggio degli interventi di
   `elencoCompleto()`. Con un id restituisce solo quel cliente, per il form intervento aperto da
   una scheda.

## Alternative scartate

- **`<input>` + `<datalist>`**: restituisce il testo e non l'id, e il comportamento cambia molto
  fra browser; su iPhone è scomoda.
- **Autocomplete scritto a mano**: tastiera, accessibilità e chiusura al clic fuori da rifare,
  per ottenere quello che Tom Select fa già.

## Modifiche file per file

- `app/Models/ClientiModel.php` — nuovo `perTendina(?int $soloId = null)`.
- `app/Controllers/Operativo/InterventiController.php`, `CantieriController.php`,
  `AbbonamentiController.php` — i `nuovo()` usano `perTendina()`; `CantieriController::edit()`
  non carica più l'elenco clienti, che la view non usa.
- `app/Views/partials/select_cliente.php` — nuovo.
- `app/Views/operativo/interventi/nuovo.php`, `cantieri/nuovo.php`, `abbonamenti/nuovo.php` —
  includono il partial; il campo in sola lettura usa `ClientiModel::denominazione()`.
- `public/js/tom-select-init.js` — nuovo: `initTomSelect()` e attivazione delle tendine cliente.
- `app/Views/layouts/admin.php` — carica CSS, JS di Tom Select e `tom-select-init.js`.
- `operativo/interventi/show.php`, `edit.php`, `nuovo.php`, `_form_materiale_scripts.php`,
  `anagrafiche/clienti/show.php`, `abbonamenti/_modalita_pagamento.php` — tolti i caricamenti
  di Tom Select e i correttivi copiati, `new TomSelect` diventa `initTomSelect`.
- `public/css/custom.css` — città nel menu, testo a 16px su mobile.

## Fuori scope

- Endpoint di ricerca lato server (vedi decisione 2).
- Ricerca anche nelle altre tendine lunghe (tecnici, tipi di intervento): il meccanismo è pronto,
  ma si applica quando serve.
- I materiali su mobile restano con la `<select>` nativa: lì gli articoli si sfogliano per
  categoria, quindi il menu deve scorrere.
