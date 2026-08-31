# Spec — UX mobile per i tecnici: valutazione e piano interventi

> Da leggere insieme a `docs/ANALISI.md` per il contesto generale. Questo documento raccoglie la valutazione UX dell'app su mobile e il piano operativo degli interventi, in ordine di priorità. Non copre modifiche funzionali al dominio (abbonamenti, materiali, ecc.).

## 1. Contesto e target

Il target mobile sono i **tecnici sul campo**: persone con poco tempo, spesso con guanti o mani occupate, poco avvezze al mondo informatico. Usano l'app dal telefono per: vedere l'agenda del giorno, navigare verso il cliente, chiamarlo, segnare l'intervento come completato, annotare materiali per la prossima visita.

Criterio guida per ogni scelta: **ridurre i tap e le decisioni**. Ogni schermata mobile deve rispondere a una sola domanda ("dove vado?", "cosa porto?", "ho finito?") con azioni grandi e testi espliciti, mai gergo gestionale.

### Cosa funziona già bene (non toccare)

- `app/Views/dashboard/tecnico.php` — agenda mobile-first a 3 giorni: tab sticky, card con orario/cliente/indirizzo/materiali, modal mappa con deep-link Google Maps, sezione urgenti. È il modello di riferimento per tutto il resto.
- Sidebar filtrata per ruolo (`is_solo_tecnico()`): il tecnico non vede sezioni amministrative.
- Input nativi (`datetime-local`, `date`): aprono i picker del telefono.
- Viewport corretto, dark mode, flash message centralizzati nel layout.

## 2. Interventi previsti (in ordine di priorità)

### 2.1 Indirizzo e telefono nella scheda intervento ⭐ priorità massima

**Problema.** `app/Views/operativo/interventi/show.php` mostra il cliente solo come link alla scheda anagrafica. Per vedere l'indirizzo o chiamare, il tecnico deve cambiare pagina. Sul campo sono le due informazioni più usate in assoluto.

**Soluzione.** Nel blocco "Cliente" della scheda intervento aggiungere:

- l'**indirizzo completo** del cliente (via, CAP, città) sotto il nome;
- bottone **"Naviga"** → link `https://www.google.com/maps/dir/?api=1&destination=LAT,LNG` (se il cliente ha coordinate) o `...&destination=INDIRIZZO+URLENCODED` come fallback;
- il **telefono** come link `tel:` — un tap per chiamare. Se il cliente ha più numeri, mostrarli tutti.

**File coinvolti:**

| File | Modifica |
|---|---|
| `app/Controllers/Operativo/InterventiController.php` | `show()`: il record `$cliente` è già passato alla view — verificare che contenga indirizzo, telefono, lat/lng (dovrebbe già, arriva dal model clienti) |
| `app/Views/operativo/interventi/show.php` | Blocco cliente: indirizzo + bottoni `Naviga` / `Chiama` |
| `public/css/custom.css` | Eventuale classe per la riga contatti (niente stile inline) |

### 2.2 Bottoni azione touch-friendly nella scheda intervento

**Problema.** Il `card-footer` di `show.php` ha 3–4 bottoni `btn-sm` (~31px di altezza, sotto la soglia dei 44px raccomandata per il touch) in fila orizzontale. "Chiudi intervento" (l'azione più frequente del tecnico) sta accanto ad "Annulla intervento" (distruttiva): su schermo piccolo il tap sbagliato è facile.

**Soluzione.** Solo CSS, con media query in `custom.css`:

- sotto i 576px i bottoni del footer si impilano full-width (`flex-direction: column`), altezza minima 44px;
- ordine su mobile: **Completa intervento** (verde, per primo), Modifica, Scheda cliente, e per ultimo — visivamente separato — Annulla intervento;
- l'ordine si gestisce con la proprietà CSS `order` sui bottoni, senza toccare il markup desktop.

**File coinvolti:**

| File | Modifica |
|---|---|
| `public/css/custom.css` | Media query `max-width: 575.98px` sul footer della scheda intervento (dare una classe dedicata al footer, es. `.intervento-azioni`) |
| `app/Views/operativo/interventi/show.php` | Aggiunta classe `.intervento-azioni` al `card-footer` |

### 2.3 Etichette esplicite nei modal di conferma

**Problema.** Nel modal di chiusura il bottone per uscire è "Annulla"; in quello di annullamento è "Chiudi". Per un utente poco avvezzo, "Chiudi" (esci dal popup) e "Chiudi intervento" (completa il lavoro) nella stessa finestra sono una trappola.

**Soluzione.** Frasi esplicite, mai verbi ambigui da soli:

- modal completa: dismiss = **"No, torna indietro"**, conferma = **"Sì, segna come completato"**;
- modal annulla: dismiss = **"No, torna indietro"**, conferma = **"Sì, annulla l'intervento"**;
- valutare la rinomina globale dell'azione da "Chiudi intervento" a **"Completa intervento"** (label UI e testi help; le rotte e i nomi di metodo `chiudi()` restano invariati — è solo terminologia utente).

**File coinvolti:**

| File | Modifica |
|---|---|
| `app/Views/operativo/interventi/show.php` | Testi dei due modal (`#modal-chiudi`, `#modal-annulla`) e bottoni del footer |
| `app/Views/help/*.php` | Allineare la terminologia dove compare "chiudi intervento" |

### 2.4 "Naviga" a un tap dall'agenda tecnico

**Problema.** In `dashboard/tecnico.php` il bottone "Mappa" apre il modal Leaflet e da lì "Apri in Google Maps": due tap per la cosa che il tecnico fa prima di mettersi in macchina.

**Soluzione.** Invertire le priorità delle azioni sulla card:

- bottone primario **"Naviga"** → apre direttamente Google Maps (deep-link già costruito nel modal, basta spostarlo sulla card);
- l'anteprima mappa (modal Leaflet) diventa azione secondaria (icona o bottone outline "Mappa");
- "Apri" resta com'è.

**File coinvolti:**

| File | Modifica |
|---|---|
| `app/Views/dashboard/tecnico.php` | Riordino bottoni in `.agenda-azioni`; link diretto Google Maps sulla card |
| `public/css/dashboard-tecnico.css` | Eventuale ritocco larghezze in `.agenda-azioni` |

### 2.5 Cambio stato a un tap: "Inizio lavoro"

**Problema.** Per mettere un intervento "in corso" oggi serve: Modifica → select stato → salva. Tre passaggi e un form intero per un'informazione binaria.

**Soluzione.** Stessa impostazione già usata per `chiudi`/`annulla`:

- nuova rotta `POST operativo/interventi/(:num)/inizia` → `InterventiController::inizia($1)` che porta lo stato a `in_corso` (solo da `pianificato`);
- bottone **"Inizio lavoro"** nella scheda intervento, visibile solo quando lo stato è `pianificato`;
- flusso finale del tecnico: arrivo → tap "Inizio lavoro" → lavoro → tap "Completa intervento". Zero form.

**File coinvolti:**

| File | Modifica |
|---|---|
| `app/Config/Routes.php` | Rotta `inizia` nel gruppo `operativo/interventi` |
| `app/Controllers/Operativo/InterventiController.php` | Metodo `inizia()` (validare la transizione di stato, redirect con flash) |
| `app/Views/operativo/interventi/show.php` | Bottone condizionato allo stato |

### 2.6 Manifest PWA: icona in home e schermo intero ✅ *(v0.35.0)*

**Problema.** Non esistevano `manifest.json` né `apple-touch-icon`: aggiunto alla home, il sito appariva con icona generica e si apriva con la barra del browser.

**L'icona era il vero problema.** L'unico logo del progetto, `public/uploads/logo_azienda.png`, è un banner **2993×595** — testo nero e onda blu, rapporto 5:1. Un'icona si guarda in un quadrato da ~60px: schiacciarci dentro il logo intero rende il testo illeggibile. Scelta con l'utente una **C bianca su fondo blu** (`#1a6fa8`, il primary del gestionale) con l'**onda del logo** sotto: una lettera si riconosce anche a 40px, dove un disegno diventa una macchia.

L'onda non è ridisegnata ma ripresa dai pixel veri del logo: si isolano quelli blu — il testo è nero, quindi si esclude da solo — e si ricolorano di bianco conservando l'alfa, così i bordi restano morbidi. Il generatore è uno script GD one-off, tenuto fuori dal repo perché dipende da un font di Windows; le icone prodotte sono committate, come già si fa con gli asset dei vendor.

**Realizzato:**

- `public/manifest.json`: `display: standalone`, `theme_color` `#1a6fa8`, icone 192/512 dichiarate `any maskable` — il fondo è a tinta piena e il contenuto sta nel 70% centrale, quindi il ritaglio circolare di Android non taglia niente;
- `public/assets/icons/` con 512, 192, `apple-touch-icon` 180 e 32;
- `public/favicon.ico` **rigenerata**: quella presente era il segnaposto di CodeIgniter, byte per byte identico al default del framework ed entrato con la commit di inizializzazione. Ora contiene 16/32/48 px. GD non scrive il formato ICO, ma un `.ico` è un piccolo indice seguito dalle immagini e dal Vista in poi ogni voce può contenere un PNG: l'indice è costruito a mano e verificato rileggendolo;
- i tag di `<head>` stanno in **`app/Views/partials/head_pwa.php`**, incluso dai due layout invece di essere copiato in entrambi. Due copie divergono, ed è esattamente così che era nato il difetto dello sticky in §2.7;
- iOS ignora in buona parte il manifest per lo schermo intero, quindi servono anche i suoi `<meta name="apple-mobile-web-app-*">`;
- checkbox **"Ricordami"**: già presente e cablato in `app/Views/auth/login.php`, con `allowRemembering` a 30 giorni. Nessuna modifica necessaria.

**Tre limiti da conoscere, nessuno dei quali è un difetto da correggere:**

1. **In sviluppo non è verificabile fino in fondo.** Il manifest richiede un contesto sicuro: su `http://192.168.1.133:8081` Chrome/Android lo ignora. Da iPhone "Aggiungi a Home" funziona lo stesso e mostra l'icona; la prova completa arriva col deploy su HTTPS.
2. **`display: standalone` toglie la barra del browser, quindi anche il pulsante Indietro** — su iOS resta la strisciata dal bordo. Qui è mitigato dai breadcrumb presenti in ogni pagina e dal sistema "from", che riporta al punto di partenza dopo salvataggi ed eliminazioni.
3. **Nessun funzionamento offline.** Il manifest non lo dà: serve un service worker, che è un lavoro a sé e tutt'altro che banale su dati veri. Senza campo il tecnico vede la pagina di errore del browser, e in standalone — senza barra indirizzi per riprovare — sembra che l'app sia rotta. **Esplicitamente fuori scope**, da valutare solo se emergerà come problema reale sul campo.

**File coinvolti:**

| File | Modifica |
|---|---|
| `public/manifest.json` | Nuovo |
| `public/assets/icons/` | 512, 192, apple-touch-icon 180, 32 |
| `public/favicon.ico` | Rigenerata (era il default di CodeIgniter) |
| `app/Views/partials/head_pwa.php` | Nuovo: tutti i tag di `<head>` in un punto solo |
| `app/Views/layouts/admin.php`, `auth.php` | Include del partial |

### 2.7 Rifiniture minori

Tutte e tre fatte.

- ✅ **Tab giorni sticky sotto l'header** *(v0.34.1)*. Confermato sul telefono: con `top: 0` le tab non sembravano sticky affatto — in realtà lo erano, ma si fermavano dietro la barra fissa e sparivano, che a schermo è indistinguibile da uno sticky che non funziona. Il meccanismo era già a posto: `custom.css` ripristina `overflow: visible` su `.app-main`, senza cui `position: sticky` non si calcola proprio. Mancava solo il valore. Ora `top: var(--altezza-header)`, e l'altezza della barra è una variabile in `:root` usata anche da `.section-anchor` nella scheda cliente, che il numero se lo teneva scritto a mano: è proprio da quel doppione che il difetto era nato.
- ✅ **Flash message su mobile**: sotto i 576px gli alert del layout sono ancorati in basso come un toast (`.app-main > .alert-dismissible` in `custom.css`), così restano visibili anche a pagina scrollata — caso tipico dopo un redirect con hash del sistema "from".
- ✅ **Tooltip "Shift+clic" in `interventi/index.php`**: nascosto sotto i 768px con `d-none d-md-inline`, è un concetto solo da scrivania.

## 3. Alternative scartate

- **Vista a card per le liste DataTables su mobile.** Le tabelle (interventi, clienti) restano strumenti da ufficio/desktop: il tecnico ha già l'agenda che copre il suo caso d'uso. Rifare le liste in doppia versione card/tabella costa molto e duplica markup. Se in futuro emerge un bisogno reale del tecnico su una lista, si valuterà quella singola lista.
- **Framework frontend (Vue/React/htmx).** L'impostazione server-rendered è semplice, veloce e adatta al progetto. Nessun beneficio UX per questo target giustifica la complessità.

## 4. Fuori scope

- **Offline / service worker**: utile in zone senza segnale ma complessità alta (cache, sincronizzazione). Da rivalutare dopo il go-live.
- Riscrittura delle tabelle DataTables per mobile (vedi §3).
- Notifiche push.
- Firma del cliente / foto a fine intervento (funzionalità nuove, non UX).

## 5. Ordine di sviluppo consigliato

1. **§2.1 + §2.2** — scheda intervento: contatti cliente + bottoni touch (miglior rapporto beneficio/sforzo, stesso file).
2. **§2.3** — etichette modal (pochi minuti, stesso file del punto 1).
3. **§2.4** — "Naviga" dall'agenda.
4. **§2.5** — bottone "Inizio lavoro" (unico punto con rotta + controller).
5. **§2.6** — manifest PWA.
6. **§2.7** — rifiniture, man mano che si testa sul telefono.

Ogni punto è autonomo e testabile da solo sul telefono in LAN (vedi "Accesso da smartphone in LAN" in `CLAUDE.md`).
