# 7. Dashboard, magazzino e impostazioni

## 7.1 Dashboard

`DashboardController` · rotta `/`

La dashboard **cambia in base al ruolo** di chi la apre. Il controller carica due insiemi
di dati con altrettanti metodi privati, `caricaDatiUfficio()` e `caricaDatiTecnico()`, e chi
appartiene a entrambi i mondi — il titolare che è anche tecnico — vede tutte e due le
sezioni.

**Admin e ufficio** vedono contatori sintetici e card operative:

| Card | Contenuto |
|---|---|
| Interventi di oggi | conteggio e elenco dei pianificati, con link a calendario e foglio di viaggio |
| Urgenti non pianificati | gli interventi urgenti ancora senza data |
| Abbonamenti in scadenza | i prossimi 30 giorni, con badge del tempo rimasto: rosso sotto i 7 giorni, giallo sotto i 15 |
| Assenti oggi | chi è assente, con il tipo di assenza |
| Interventi in conflitto | pianificati su un tecnico poi risultato assente (capitolo 4.3) |
| Promemoria in arrivo | divisi in "oggi" e "prossimi giorni" |

**I tecnici** hanno una vista pensata per il telefono: l'agenda dei prossimi tre giorni —
oggi, domani, dopodomani — con tab fisse in alto e una scheda per intervento che riporta
orario, cliente, indirizzo, materiali da portare e i bottoni per navigare e chiamare. Sotto,
i propri interventi urgenti non ancora pianificati.

Il bottone **Naviga** è l'azione principale sulla scheda e apre direttamente Google Maps;
l'anteprima della mappa Leaflet è retrocessa ad azione secondaria con la sola icona. Prima
era il contrario, e la cosa che il tecnico fa più spesso — prima di salire in macchina —
costava due tocchi invece di uno.

Un tecnico puro viene indirizzato a questa vista, e la sua barra laterale mostra solo
Dashboard, Clienti, Calendario e Interventi.

## 7.2 Promemoria e avvisi

`PromemoriaController` · `AvvisiCell` · `PromemoriaOggiCell`

I promemoria sono eventi aziendali ad hoc con data e ora, gestiti dall'ufficio dal
Calendario, dove compaiono in viola e in sola lettura per i tecnici. Se la data di fine non
viene indicata, il model la imposta a un'ora dopo l'inizio.

Tre punti di visibilità: gli eventi sul calendario, la **campanella in navbar** (una view
cell, predisposta per aggregare in futuro anche altre notifiche) e le card della dashboard.

All'accesso, i promemoria di giornata compaiono in un modal forzato. Il bottone "Ho letto"
registra la lettura in `promemoria_dismiss`, quindi **per utente e non per browser**: la
stessa persona apre il gestionale dal PC dell'ufficio e dal telefono, e un `localStorage`
avrebbe fatto ricomparire il modal sul secondo dispositivo. I promemoria già letti restano
comunque visibili, con una spunta verde.

I modal che si aprono da soli all'accesso — novità di versione e promemoria — si presentano
**in sequenza** grazie alla coda `window.enqueueModal` definita nel layout. Prima si
sovrapponevano.

Non esiste una tabella `notifiche` generica: i promemoria scadono per data e non hanno
bisogno di uno stato di lettura oltre a quello descritto. Si costruirà quando ci saranno
requisiti concreti oltre a questi.

## 7.3 Magazzino

`Magazzino\ArticoliController` · `ArticoliModel` · rotte `magazzino/articoli/*`

Un **catalogo anagrafico**, non una gestione di magazzino: la colonna `giacenza` esiste ma
nessun flusso la movimenta, e non c'è scarico automatico quando un articolo finisce sui
materiali di un intervento.

Gli articoli sono organizzati in categorie (Prodotti, Attrezzature, Apparecchiature,
Ricambi) e servono soprattutto come sorgente per la selezione dei materiali: nel form si
scelgono per categoria e articolo con un autocomplete Tom Select, che accetta anche testo
libero per gli articoli fuori catalogo.

> Su WebKit per iOS il menu a tendina di Tom Select non riceve lo scorrimento con il dito
> quando la lista supera l'altezza visibile — un problema noto della libreria, condiviso con
> altre analoghe. Sotto i 768px il campo torna quindi a essere un `<select>` nativo, che il
> sistema operativo scorre da sé, con una voce "Descrizione libera…" a coprire i casi fuori
> catalogo. Si perde solo la ricerca mentre si digita, non la categorizzazione.

L'eliminazione di un articolo richiede il permesso `magazzino.elimina`; creazione e
modifica sono aperte anche ai tecnici (capitolo 8.3).

## 7.4 Impostazioni

L'intera sezione è protetta dal permesso `impostazioni.manage`, che dalla v0.24.31 hanno
**solo admin e developer**: l'ufficio ne è stato escluso.

### Parametri generali

`Impostazioni\GeneraleController` — i dati della sede aziendale (nome, indirizzo, CAP,
città, telefono, sito, logo, coordinate, con geocodifica), gli orari aziendali e le due
soglie di longitudine che determinano le zone dei clienti. Sono i dati che intestano tutti
i PDF e che alimentano il suggerimento dell'orario nel modal di pianificazione.

I valori vivono in `settings` sotto la classe `Azienda`.

### Tipi di intervento

`Impostazioni\TipiInterventoController` — il catalogo dei tipi di lavoro. Ogni tipo governa
molto più della propria etichetta: la sezione in cui l'intervento compare (`categoria`), la
durata proposta, il prefisso del codice, se il tipo è utilizzabile negli abbonamenti
(`abbonabile`), se prevede la pulizia del fondo, e il testo standard delle operazioni che
precompila le proposte di abbonamento.

### Categorie articoli

`Impostazioni\CategorieArticoliController` — un CRUD minimo, stesso schema dei tipi di
intervento.

### Utenti applicativi

`Impostazioni\UtentiController` — creazione e gestione degli account Shield e della loro
appartenenza ai gruppi, distinta dall'anagrafica del personale (capitolo 4.2).

### Import clienti

Descritto al capitolo 4.4.

## 7.5 Guide contestuali

Il pulsante `?` nella barra superiore apre, in un modal a schermo intero su mobile, la
guida della sezione corrente. Compare **solo se il file esiste**: il layout legge la
variabile `$help_sezione`, passata dal solo metodo `index()` di ogni controller, e cerca
`app/Views/help/<sezione>.php`.

Un file per sezione, non per singola pagina: la guida descrive il flusso completo — come
si crea, come si modifica, quali sono le regole di cancellazione — non la meccanica dei
bottoni. Esistono guide per dashboard (una per l'ufficio e una per i tecnici), personale,
clienti, calendario, interventi, abbonamenti, cantieri, articoli, impostazioni, tipi
intervento, categorie articoli, utenti, foglio di viaggio e import clienti.

Queste guide sono la vera documentazione per l'utente finale: questo manuale non le
sostituisce.

## 7.6 Changelog e novità di versione

`CHANGELOG.md` non è solo un file per gli sviluppatori: è la **sorgente delle novità
mostrate dentro l'applicazione**. Ogni voce è marcata con il proprio destinatario:

- `[APP]` — funzionalità o modifiche visibili all'utente finale;
- `[DEV]` — modifiche tecniche: refactoring, migration, dipendenze, fix interni.

Al primo accesso dopo un rilascio compare un modal con le novità, confrontando il file con
`users.ultima_versione_vista`. **Solo il gruppo `developer` vede le righe `[DEV]`**; tutti
gli altri, `admin` compreso, vedono solo le `[APP]`.

Il caso limite è gestito: quando una versione contiene solo modifiche tecniche, il modal
mostra un avviso esplicito invece di restare vuoto — cosa che è successa davvero, con un
primo tentativo di correzione che ne aveva mascherato il sintomo senza risolverlo.

Le voci passano da un convertitore Markdown minimo (`changelog_helper`) che interpreta
grassetto, corsivo e codice: prima la sintassi `**...**` compariva a schermo così com'era.

## 7.7 Le stampe PDF

Tutte le stampe usano dompdf con lo stesso schema, definito la prima volta in
`ViaggioController::pdf()`:

- un metodo `pdf()` nel controller, accanto a `show()`, senza logica di generazione nel
  model;
- una view HTML dedicata, separata da quella a schermo, con il CSS nel file stesso;
- `isRemoteEnabled = false`, quindi nessuna chiamata di rete durante la generazione: le
  immagini, logo compreso, si incorporano come data URI in base64;
- `stream($nomefile, ['Attachment' => false])`, che apre il PDF nel browser invece di
  forzare il download.

Vincoli di dompdf da ricordare: niente flexbox e niente grid — i layout a colonne si fanno
con le tabelle — e la regola `@page` non viene applicata, quindi i margini si impostano
come `padding` sul `body`.

La palette grafica è ripresa dal progetto precedente, già rifinita su più iterazioni:
accento blu `#2980b9`, intestazione a due colonne con logo e dati aziendali a sinistra e
titolo a destra, sezioni con intestazione in maiuscoletto e filetto sotto, tabelle
etichetta/valore, badge colorati per stato e priorità.

Le tre stampe esistenti sono il foglio di viaggio (capitolo 5.4), la scheda cliente
(capitolo 4.1) e la scheda cantiere (capitolo 6.9). Quelle di intervento e abbonamento
sono rimandate senza una scadenza definita.
