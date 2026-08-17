# CSRF — mappa dei punti da testare

Riferimento: punto 1 di `2026-08-16-review-progetto.md`.
Preparata **prima** di attivare il filtro, per sapere cosa aspettarsi.

## Esito del collaudo — 17 agosto 2026

Configurazione applicata: filtro `csrf` attivo, `$regenerate = false`, `$tokenRandomize = true`.

**Le tre prove che dimostrano che la protezione è attiva** (non solo che non rompe nulla):

| Prova | Esito atteso | Esito |
|---|---|---|
| POST con header `X-CSRF-TOKEN` valido (percorso del calendario) | accettata | **200** |
| POST **senza** token | rifiutata | **403** |
| POST con token **fasullo** | rifiutata | **403** |

**Form verificati con POST reale, tutti accettati (nessun 403):**

| Endpoint | Esito |
|---|---|
| `POST /login` | 303 |
| `POST /profilo/versione-vista` (modal Novità, token nel body) | 200 |
| `POST /profilo/aggiorna` | 200 |
| `POST /anagrafiche/clienti/store` — creazione reale | 303 |
| `POST /anagrafiche/clienti/29/update` | 303 |
| `POST /anagrafiche/clienti/29/posizione` | 200 |
| `POST /anagrafiche/clienti/29/delete` — eliminazione reale | 200 |
| `POST /anagrafiche/personale/store` · `/3/update` | 200 |
| `POST /cantieri/store` · `/22/update` · `/22/posizione` | 200 |
| `POST /abbonamenti/store` · `/29/update` | 200 |
| `POST /operativo/interventi/store` · `/774/update` | 200 |
| `POST /operativo/materiali/store` | 200 |
| `POST /magazzino/articoli/store` · `/6/update` | 200 |
| `POST /impostazioni/parametri` · `/parametri/logo` | 200 |
| `POST /impostazioni/tipi-intervento/store` | 200 |
| `POST /impostazioni/categorie-articoli/store` | 200 |

**Form verificati solo per presenza del token renderizzato** (non inviati, per non alterare
dati: sono azioni che cambiano stato). Tutti risultano avere il campo CSRF:
`cantieri/*/stato`, `cantieri/note/aggiungi`, `abbonamenti/*/stato`,
`abbonamenti/accetta-multiplo`, `interventi/*/inizia|chiudi|annulla`,
`interventi/note/aggiungi`, `personale/assenze/aggiungi`, `import-clienti/analizza`,
e tutti i form `*/delete` di ogni sezione.

**Metodo.** Per ogni pagina, ciascun form è stato inviato via `fetch` con il proprio
`FormData` — stesso percorso server, quindi stessa verifica CSRF. Poiché il `FormData`
porta i valori già presenti nel form, gli update hanno risalvato i valori esistenti e i
"nuovo" hanno inviato campi vuoti respinti dalla validazione: **nessun record spazzatura
creato** (verificato: tipi intervento 8→8, categorie 5→5, materiali dell'intervento 774
invariati, parametri aziendali e profilo intatti).

### Ancora da provare a mano

- **Drag&drop reale sul calendario** (l'endpoint `sposta` è verificato con l'header, ma non
  il gesto)
- **I due upload con un file vero**: logo e import clienti (il `FormData` non può portare
  un file, quindi sono stati inviati senza allegato)
- **Le azioni di stato** elencate sopra: token presente, invio non eseguito
- **Magic link**, se resta attivo — vedi punto 7 della review

## Stato della copertura (verificato staticamente)

| Cosa | Esito |
|---|---|
| `<form>` nelle view | 63 in 29 file |
| `csrf_field()` nelle view | 62 in 28 file |
| Scarto | `operativo/viaggio/index.php:59` — `method="get"`, non serve |
| Conteggi per file | coincidono ovunque: **nessun form POST scoperto** |
| `form_open()` | non usato in nessun punto |
| POST via JS | 4, **tutte con token** (dettaglio sotto) |
| Rotte POST in `Routes.php` | 63, tutte con un chiamante fra i form o le 4 chiamate JS |

Le quattro POST via JavaScript:

| Punto | Come passa il token | Rinnova l'hash |
|---|---|---|
| `public/js/calendario.js:275, 403, 638` | header `X-CSRF-TOKEN` | sì |
| `app/Cells/promemoria_oggi.php:55` | header `X-CSRF-TOKEN` | sì |
| `app/Views/layouts/admin.php:374` | campo nel body (`csrf_token()` / `csrf_hash()`) | no — vedi sotto |

## Decisione da prendere prima di attivare

`app/Config/Security.php:74` → `$regenerate = true`.

CI4 rigenera il token a ogni POST verificata e invalida il precedente. Il codice AJAX
aggiorna il proprio hash per le chiamate successive, ma **non aggiorna gli `<input>` dei
form già presenti nella pagina**: dopo una POST via JS, tutti i form di quella pagina hanno
un token scaduto.

Scenari che ne derivano — sono la parte insidiosa del collaudo:

1. **Modal novità** — parte al caricamento dopo un cambio versione, invalida i form della
   pagina su cui compare. Colpisce tutti gli utenti alla prossima release.
2. **Promemoria di oggi** — stesso effetto, su ogni pagina in cui la cell è renderizzata.
3. **Calendario** — dopo un drag&drop, i 3 form della pagina sono scaduti.

**Opzione A (consigliata):** `$regenerate = false`. Il token resta valido per tutta la
sessione, che scade con `Session::$expiration` (7200s). *Nota:* `Security::$expires` non
c'entra — è la scadenza del **cookie** CSRF, e qui `$csrfProtection` è `'session'`, quindi
quel valore non è in uso.

**Opzione B:** tenere `true` e far aggiornare a ogni risposta AJAX tutti gli
`input[name="csrf_test_name"]` della pagina. Tre punti di JS in più da mantenere.

### Perché A, anche pensando alla sicurezza di un sistema esposto 24/7

La rotazione del token sembra più sicura, ma protegge solo dal riuso di un token già
catturato — e i canali da cui un token può essere catturato sono gli stessi da cui esce il
cookie di sessione. Chi ha il cookie di sessione non ha bisogno del CSRF.

- **XSS** → l'attaccante legge il token fresco dal DOM: la rotazione è inutile
- **Rete** → coperto da HTTPS (da attivare al go-live)
- **Referrer / log** → i token stanno nel body POST, non in URL: non trapelano
- **Side-channel di compressione (BREACH)** → l'unico realistico, e la difesa è
  `$tokenRandomize`, **non** `$regenerate`

Il costo di `true`, invece, è concreto e quotidiano: con **più tab aperte** — situazione
normale su un gestionale — un salvataggio in una tab invalida i form di tutte le altre.
Una protezione che intralcia il lavoro finisce disattivata, ed è così che si perde
sicurezza davvero.

### Modifica che aggiunge sicurezza reale

`app/Config/Security.php:27` → `$tokenRandomize = true`.

Maschera il token in modo diverso a ogni risposta, chiudendo il vettore BREACH. Nessun
costo funzionale: tutti i form della pagina smascherano allo stesso token, quindi nessuna
scadenza incrociata. Va comunque provata insieme al resto, perché cambia la stringa del
token a ogni richiesta (il rinnovo dell'hash lato AJAX continua a funzionare).

### In scala

Rispetto all'esposizione reale di un sistema sempre online, questa scelta è un dettaglio
accanto a: HTTPS forzato con cookie `secure` (punto 20), l'upload del logo che permette di
scrivere un `.php` eseguibile (punto 2), il controllo mancante su `update()` (punto 3).

Nota su `$redirect` (riga 85): è `true` solo in produzione. In sviluppo un fallimento CSRF
solleva una `SecurityException` visibile — comodo, l'errore non passa inosservato.

## Sequenze AJAX → form (solo se resti su `$regenerate = true`)

- [ ] Pagina con modal novità aperto e chiuso → salvare un form qualsiasi di quella pagina
- [ ] Pagina con promemoria di oggi liquidati → salvare un form di quella pagina
- [ ] Calendario: drag&drop di un intervento → poi usare uno dei form della pagina
- [ ] Calendario: due drag&drop consecutivi (il secondo usa l'hash rinnovato dal primo)
- [ ] Promemoria multipli liquidati in sequenza (`dismissNext` concatenato)

## Form da provare, per sezione

Il numero fra parentesi è quanti form contiene la view.

### Operativo
- [ ] `operativo/interventi/nuovo.php` (1) — creazione, incluso l'aggancio materiali sospesi
- [ ] `operativo/interventi/edit.php` (5) — update, delete, e le azioni di stato
- [ ] `operativo/interventi/show.php` (4) — inizia, chiudi, annulla, note
- [ ] `operativo/interventi/_form_materiale.php` (1) — aggiunta materiale
- [ ] `operativo/calendario/index.php` (3)

### Anagrafiche
- [ ] `anagrafiche/clienti/nuovo.php` (1)
- [ ] `anagrafiche/clienti/edit.php` (2) — update, delete
- [ ] `anagrafiche/clienti/show.php` (3) — incluso il form posizione della mappa
- [ ] `anagrafiche/personale/nuovo.php` (1) — crea anche l'utente Shield
- [ ] `anagrafiche/personale/edit.php` (2)
- [ ] `anagrafiche/personale/show.php` (2) — assenze: aggiungi ed elimina

### Cantieri
- [ ] `cantieri/nuovo.php` (1)
- [ ] `cantieri/edit.php` (1)
- [ ] `cantieri/show.php` (7) — stato, delete, note aggiungi/elimina, posizione

### Abbonamenti
- [ ] `abbonamenti/nuovo.php` (1)
- [ ] `abbonamenti/edit.php` (1) — con periodi
- [ ] `abbonamenti/show.php` (6) — stato, accetta, rifiuta
- [ ] `abbonamenti/index.php` (3) — incluso `accetta-multiplo`

### Magazzino
- [ ] `magazzino/articoli/nuovo.php` (1)
- [ ] `magazzino/articoli/edit.php` (2)

### Impostazioni
- [ ] `impostazioni/parametri.php` (2) — parametri **e upload logo** (`enctype` multipart)
- [ ] `impostazioni/tipi_intervento/index.php` (3) — store, update, delete inline
- [ ] `impostazioni/categorie_articoli/index.php` (3) — idem
- [ ] `impostazioni/edit_utente_app.php` (2)
- [ ] `impostazioni/import_clienti/index.php` (1) — upload file
- [ ] `impostazioni/import_clienti/mappa.php` (1) — esecuzione import

### Profilo e accesso
- [ ] `profilo/index.php` (1)
- [ ] `auth/login.php` (1) — login
- [ ] Magic link, se resta attivo (vedi punto 7 della review: probabile disattivazione)

## Punti che meritano attenzione particolare

- **I due upload** (logo e import clienti): `multipart/form-data` è il caso in cui un token
  mancante si manifesta in modo meno leggibile.
- **Login**: se qualcosa va storto qui, si resta fuori. Provarlo per primo, con una seconda
  sessione già aperta in un altro browser come via di rientro.
- **Le view con molti form** (`cantieri/show.php` con 7, `abbonamenti/show.php` con 6): sono
  quelle in cui un `csrf_field()` potrebbe stare nel form sbagliato. I conteggi tornano, ma
  è l'unica cosa che la scansione statica non può escludere del tutto.
- **Import clienti**: il flusso è a due passi (analizza → mappa → esegui) con un file in
  `WRITEPATH` fra i due. Vale la pena provarlo intero, non solo il primo passo.

## Se un test fallisce

In sviluppo vedrai una `SecurityException` con "The action you requested is not allowed".
Nel 99% dei casi è un token scaduto per rigenerazione (vedi la decisione sopra), non un
`csrf_field()` mancante — quelli sono già stati esclusi dalla scansione.
