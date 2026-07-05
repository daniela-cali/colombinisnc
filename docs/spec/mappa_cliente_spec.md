# Spec — Mappa Leaflet nella scheda cliente + posizionamento manuale del pin

> Da leggere insieme a `docs/ANALISI.md` per il contesto architetturale generale del progetto (Colombini SNC Gestionale). Questo documento copre solo la funzionalità descritta qui sotto.

## 1. Contesto e stato di partenza

Leaflet è già una dipendenza frontend del progetto (v0.18.1, `assets/vendor/leaflet/`) ed è già usato nella dashboard mobile del tecnico (`app/Views/dashboard/tecnico.php`): un modal con mappa e marker fisso sulla posizione dell'intervento, più un link "Apri in Google Maps".

La tabella `clienti` ha già tutti i campi necessari per una posizione geografica:
- `lat`, `lng` (`DECIMAL(10,7)`, nullable)
- `geocoded_at` (`DATETIME`, nullable)
- `geocodifica_fallita` (`TINYINT`, default 0)
- `distanza_sede` (calcolata via haversine in `ClientiModel::normalizza()` quando lat/lng sono presenti e la sede è a sua volta geocodificata)

La geocodifica automatica esiste già nei form nuovo/modifica cliente (`app/Views/anagrafiche/clienti/nuovo.php`, `edit.php`): un bottone `[data-geocoder]` chiama Nominatim via JS (`public/js/geocoding.js`) usando indirizzo+CAP+città+nazione, e popola i campi hidden lat/lng/geocoded_at/geocodifica_fallita che vengono poi salvati col resto del form.

**Quello che manca:** la scheda cliente (`show.php`, sola visualizzazione) non mostra nessuna mappa. E se la geocodifica automatica fallisce (indirizzo non trovato da Nominatim) o non è mai stata eseguita, non c'è modo di impostare una posizione manualmente.

## 2. Obiettivo

1. Mostrare una mappa Leaflet nella scheda cliente (`show.php`), con marker sulla posizione geocodificata, quando disponibile.
2. Permettere di **correggere manualmente il pin sempre**, non solo quando la geocodifica automatica è fallita — utile anche quando Nominatim trova un punto impreciso (es. centro città invece dell'indirizzo esatto). Decisione presa esplicitamente durante il brainstorming: l'alternativa "solo su geocodifica fallita" è stata scartata perché limiterebbe troppo i casi d'uso reali.

## 3. Centraggio iniziale della mappa — logica a cascata

Quando si apre la scheda cliente, il punto su cui centrare la mappa dipende da quali dati sono disponibili:

1. **`lat`/`lng` presenti e valide** → centro esatto lì, zoom ravvicinato (16), marker fisso (mappa di sola visualizzazione finché non si preme "Correggi posizione").
2. **Mancano (mai geocodificato) o `geocodifica_fallita = 1`, ma `citta` è compilata** → all'apertura della pagina, un fetch JS verso Nominatim (stesso pattern di `geocoding.js`, ma query ridotta a `citta, nazione` — niente via/civico) recupera un punto approssimativo della città. La mappa si centra lì con zoom a scala cittadina (~13). **Questo risultato non viene salvato** su `clienti`: serve solo a orientare la mappa, non è una geocodifica del cliente. Il pin resta assente finché l'utente non lo posiziona manualmente.
3. **Nessuna città e nessuna posizione** → fallback finale sulla sede aziendale (`setting('Azienda.sede_lat')` / `sede_lng`), zoom più ampio (~10, scala regionale). Pin da posizionare manualmente.

In tutti i casi diversi dal primo, il marker parte assente: l'utente clicca sulla mappa o lo trascina per posizionarlo.

**Sede aziendale sempre visibile**: indipendentemente dal caso sopra, se `Azienda.sede_lat`/`sede_lng` sono impostate viene sempre disegnato un pallino rosso fisso (`L.circleMarker`, non un pin) con tooltip "Sede aziendale". È deliberatamente non trascinabile e non entra mai in modalità modifica: serve solo da riferimento visivo di contesto/distanza, distinto nella forma dal pin blu del cliente per non generare confusione. Può ricadere fuori dall'inquadratura quando la mappa è zoomata sulla posizione esatta del cliente (zoom 16) — non è un problema, riappare semplicemente quando la mappa è a scala città/regione.

## 4. Flusso di salvataggio della posizione manuale

- Bottone **"Correggi posizione"** in scheda cliente: attiva la modalità modifica, rende il marker trascinabile e abilita il click-to-place sulla mappa. Compaiono i bottoni "Salva posizione" / "Annulla".
- **"Salva posizione"** invia un **form POST reale** (niente fetch/AJAX/JSON — scelta deliberata per coerenza con il resto del progetto, che non usa endpoint JSON per le scritture) con due hidden input (`lat`, `lng`) popolati dal JS al momento del posizionamento, verso un nuovo endpoint dedicato.
- Il controller aggiorna `lat`, `lng`, `geocoded_at` (= adesso) e forza `geocodifica_fallita = 0`, poi fa redirect alla stessa scheda cliente (`#sec-posizione`) con flashdata di conferma.
- Nessuna modifica al model `ClientiModel`: i campi sono già in `$allowedFields` e `normalizza()` ricalcola già `distanza_sede`/`zona` quando lat/lng sono presenti — il nuovo metodo del controller eredita gratis questo comportamento passando per `$model->update()`.

## 5. Riepilogo modifiche da implementare

1. **`app/Views/anagrafiche/clienti/show.php`**: nuova sezione "Posizione" (`section-anchor` `sec-posizione`, voce in `page-nav`), contenitore mappa Leaflet, badge di stato (geocodificato / non geocodificato / geocodifica fallita), bottoni "Correggi posizione" / "Salva posizione" / "Annulla", link "Apri in Google Maps". Script con la logica di centraggio a cascata (§3) e gestione drag/click del marker.
2. **`app/Config/Routes.php`**: nuova rotta nel gruppo `clienti` già esistente:
   ```php
   $routes->post('(:num)/posizione', 'Anagrafiche\ClientiController::aggiornaPosizione/$1');
   ```
3. **`app/Controllers/Anagrafiche/ClientiController.php`**: nuovo metodo `aggiornaPosizione(int $id)` — sotto-risorsa (stesso schema di `PersonaleController::aggiungiAssenza()`/`CantieriController::aggiungiNota()`), valida lat/lng numerici in range, aggiorna il cliente, redirect con flashdata.
4. **`ClientiModel`**: nessuna modifica.
5. **`public/css/custom.css`**: regola `#mappaCliente { height: ...; }`, stesso pattern di `#mappaTecnico` in `dashboard-tecnico.css`.

## 6. Esplicitamente fuori scope per ora

- Nessuna persistenza del centro "città" calcolato al volo (punto 2 della cascata in §3) — è solo un aiuto visivo momentaneo.
- Nessuna integrazione con l'ottimizzazione percorsi OpenRouteService prevista per v1.0.0: questa spec riguarda solo la visualizzazione/correzione manuale in scheda cliente.
