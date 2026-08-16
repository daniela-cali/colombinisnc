# 5. Operatività: interventi, materiali, calendario

## 5.1 Il ciclo di vita di un intervento

`Operativo\InterventiController` · `InterventiModel` · rotte `operativo/interventi/*`

Un intervento nasce in tre modi: creato a mano, generato da un abbonamento accettato, o
aperto da un cantiere. Qualunque sia l'origine, attraversa gli stessi stati.

```
                          ┌──────────────┐
                          │ da_pianificare│  ← nasce qui (o con data, e allora è già pianificato)
                          └──────┬───────┘
        assegnazione di una data │
                                 ▼
                          ┌──────────────┐
                          │  pianificato │ ──► annullato
                          └──────┬───────┘
                "Inizio lavoro"  │
                                 ▼
                          ┌──────────────┐
                          │   in_corso   │ ──► annullato
                          └──────┬───────┘
            "Completa intervento"│
                                 ▼
                          ┌──────────────┐
                          │  completato  │
                          └──────────────┘

sospeso: stato laterale, assegnato in blocco quando l'abbonamento padre viene sospeso
```

Due comportamenti automatici: impostare una data pianificata su un intervento
`da_pianificare` lo porta da solo a `pianificato`; togliere la pianificazione dal
calendario lo riporta nel pool. Il bottone "Inizio lavoro" scrive `data_inizio_lavoro`, la
chiusura scrive `data_completamento`: servono al calcolo futuro della durata media reale
per tipo di intervento.

`annullato` **non è** un'eliminazione: il record resta con il suo storico. L'eliminazione
fisica esiste ma è riservata a chi ha il permesso `interventi.elimina`, e nella scheda
compare solo per gli interventi già annullati.

### Il codice intervento

Il formato è `PREFISSO-NNNN`. Il prefisso dice da dove viene l'intervento:

- `INT` — creato a mano;
- il `prefisso_codice` del tipo (`PIS`, `ADD`…) — occorrenza generata da un abbonamento;
- `EXT` — visita extra fuori dal piano dell'abbonamento.

La numerazione **non** usa `MAX(codice)` né `AUTO_INCREMENT`, e la ragione merita di essere
ricordata perché entrambe le strade sembrano ovvie e sono sbagliate. `MAX(codice)`
regredisce dopo una cancellazione: eliminato `INT-0010`, il successivo sarebbe di nuovo
`INT-0010`. `AUTO_INCREMENT` letto da `information_schema` è inaffidabile, perché InnoDB
aggiorna quella vista in modo asincrono e può restituire un valore vecchio.

Si usa invece un contatore atomico in `settings`, una riga per prefisso
(`class = 'Interventi'`, `key = 'seq_INT'`), letto con `SELECT ... FOR UPDATE` dentro una
transazione: se due utenti generano un codice nello stesso istante, il secondo aspetta che
il primo abbia incrementato. La riga viene creata al primo utilizzo di un prefisso, quindi
un nuovo tipo di intervento non richiede alcuna migration.

### Le sezioni per area

Il menu divide gli interventi in Generici, Piscine e Addolcitori, più la vista **Tutti**
che non filtra nulla — utile quando non si conosce a priori la categoria. Non ci sono
rotte né view duplicate: una sola view parametrica su `?sezione`, e la categoria viene dal
tipo di intervento (`tipi_intervento.categoria`). I Generici comprendono anche gli
interventi senza tipo.

Per le piscine esiste la **fase stagionale**: i flag `apertura` e `chiusura`, mutuamente
esclusivi, visibili nel form solo per i tipi di categoria piscine, con le pillole di filtro
dedicate e un badge nelle liste.

### Il diario

Ogni intervento ha un diario di note datate (`interventi_note`), scrivibile dal form di
modifica accanto ai materiali e in sola lettura nella scheda. Esiste perché il campo `note`
unico veniva sovrascritto a ogni aggiornamento, mentre i lavori multi-visita hanno bisogno
di una cronologia.

Il caso d'uso che l'ha motivato sono le aperture piscina: *15.06 vuotata → 20.06 in
riempimento → 24.06 avviata*.

### La scheda su smartphone

La scheda intervento è la pagina che i tecnici usano davvero sul campo, e il criterio di
progettazione è stato **ridurre i tap e le decisioni**. Ogni schermata risponde a una sola
domanda — *dove vado, cosa porto, ho finito* — con azioni grandi e testi espliciti.

- Indirizzo e bottoni **Naviga** e **Chiama** a un tocco sotto il nome del cliente. Naviga
  usa la posizione del cantiere quando c'è, altrimenti quella del cliente; se il cantiere ha
  un referente compare anche "Chiama referente", senza alcun ripiego sul telefono del
  cliente.
- Sotto i 576px i bottoni del piede si impilano a piena larghezza, con l'azione distruttiva
  staccata dalle altre. L'ordine si governa con la proprietà CSS `order`, senza toccare il
  markup desktop.
- **L'azione del momento resta ancorata in basso** durante lo scorrimento — "Inizio lavoro"
  se l'intervento è pianificato, "Completa intervento" se è in corso — in verde per
  distinguersi dalla barra blu: è sempre raggiungibile col pollice senza scorrere fino in
  fondo.
- I testi dei modal sono espliciti: mai un "Chiudi" ambiguo accanto a "Chiudi intervento",
  ma *"No, torna indietro"* e *"Sì, segna come completato"*. Per la stessa ragione l'azione
  è stata rinominata da "Chiudi intervento" a **"Completa intervento"** nell'interfaccia,
  lasciando invariati rotte e nomi di metodo: è terminologia utente, non codice.
- I messaggi di esito su mobile compaiono ancorati in basso come notifiche, non in cima
  alla pagina dove resterebbero fuori schermo.

Due cose valutate e scartate: rifare le liste DataTables in versione a schede per mobile
(le tabelle restano strumenti da ufficio, il tecnico ha già la sua agenda) e introdurre un
framework frontend, che non porterebbe alcun beneficio a questo target.

## 5.2 Materiali

`Operativo\MaterialiController` · `InterventiMaterialiModel`

Un materiale è sempre legato a un cliente e **facoltativamente** a un intervento. Se
`intervento_id` è vuoto il materiale è *sospeso*: un promemoria da portare al prossimo giro,
visibile nella scheda cliente con un mini-form per aggiungerlo al volo.

Ogni materiale può venire dal catalogo articoli oppure essere descritto a mano: la
descrizione viene comunque copiata sulla riga al salvataggio, così resta leggibile anche se
l'articolo viene poi eliminato dal catalogo.

Quando si crea un intervento per un cliente che ha materiali sospesi, il form li elenca con
delle caselle di selezione. I sospesi scelti vengono **spostati, non copiati**: si valorizza
`intervento_id` sulla riga esistente, senza crearne di nuove. Di conseguenza, prima di
eliminare un intervento i suoi materiali vengono liberati (`intervento_id = NULL`) così
sopravvivono al `CASCADE` e tornano sospesi da soli.

### La chiusura di un intervento

Il modal di completamento mostra una **checklist con una riga per materiale**, con la
casella già spuntata: nella stragrande maggioranza dei casi il tecnico porta tutto e deve
solo togliere le eccezioni. Con due o più materiali compare anche un "seleziona/deseleziona
tutto", il cui uso principale è azzerare in un colpo quando non è stato consegnato nulla.

Prima era una domanda binaria "hai consegnato i materiali?" che valeva in blocco: con tre
materiali di cui due consegnati e uno no, la situazione non era rappresentabile.

Lato server si calcola l'insieme dei materiali da portare dell'intervento e il sottoinsieme
spuntato; la differenza sono i non consegnati. I primi passano a `consegnato`, i secondi
tornano sospesi con una nota `[Da INT-0042]` che ricorda da dove arrivano. Entrambi i
metodi filtrano anche per `intervento_id`, come controllo di appartenenza contro un POST
manomesso.

Subito dopo la chiusura si apre **sempre** un secondo modal, *"Materiali per la prossima
visita"*: permette di aggiungere sospesi senza uscire dalla scheda — il caso del tecnico
che si accorge sul posto che manca qualcosa. Mostra anche i sospesi già presenti per quel
cliente, **esclusi quelli appena liberati dalla checklist**: rivederli lì darebbe
l'impressione di un errore.

I due modal si presentano in sequenza e non sovrapposti grazie alla coda
`window.enqueueModal` definita nel layout, la stessa che gestisce il modal delle novità e
quello dei promemoria all'accesso.

### La riassegnazione automatica alla prossima visita

Quando i materiali non consegnati appartengono a un intervento di un abbonamento, il
sistema cerca di assegnarli direttamente alla visita successiva **dello stesso
abbonamento**, invece di lasciarli genericamente sospesi sul cliente.

Non serve alcuna colonna di sequenza: `data_scadenza` è già univoca dentro un abbonamento e
basta come criterio d'ordine.

```sql
SELECT * FROM interventi
WHERE abbonamento_id = ?
  AND priorita = 'abbonamento'
  AND data_scadenza > ?      -- scadenza dell'intervento appena chiuso
ORDER BY data_scadenza ASC
LIMIT 1
```

Il filtro su `priorita` include di proposito anche le visite extra: una visita extra deve
poter intercettare i materiali se cade cronologicamente prima della prossima occorrenza
regolare.

**Se la query restituisce zero risultati o più di uno, il sistema non prova a indovinare**:
i materiali restano sospesi sul cliente, con la gestione manuale già esistente. Rendere il
caso visibile all'operatore è preferibile a un'assegnazione sbagliata e silenziosa.

## 5.3 Calendario

`Operativo\CalendarioController` · `public/js/calendario.js` · `public/css/calendario.css`

La pagina di pianificazione: una griglia FullCalendar con viste giorno, settimana e mese,
fascia oraria 07:00–20:00, eventi colorati con il colore del tecnico. Sul calendario
compaiono anche le assenze, come eventi arancioni a tutta giornata, e i promemoria
aziendali in viola.

### Il pool "Da pianificare"

La barra laterale con gli interventi che aspettano una data. Sono raggruppati per **zona
geografica** e, dentro ogni zona, per origine: Generici, Cantieri, Abbonamenti — ciascun
gruppo pieghevole e con il proprio conteggio. Il secondo livello si apre già chiuso, per
ridurre lo scorrimento iniziale. L'ordinamento è per urgenza e poi per data di
inserimento.

Si trascina una scheda sulla griglia, si sceglie tecnico e ora nel modal che compare, e
l'intervento passa a `pianificato`. **L'orario si precompila da solo**: subito dopo la fine
dell'ultimo intervento già pianificato per quel tecnico in quella giornata, oppure
all'inizio giornata configurato in Impostazioni se non ne ha. È solo un suggerimento
comodo, sempre modificabile.

Se l'intervento aveva già un tecnico assegnato pur essendo ancora da pianificare, quel
tecnico viene mostrato nella scheda e preselezionato nel modal — il pool era stato
progettato assumendo che non potesse succedere, e per un periodo lo perdeva per strada.

**Il pool segue il periodo visibile sul calendario**, non un mese fisso. Prima mostrava
tutte le occorrenze del mese corrente, il che permetteva di pianificare più visite dello
stesso abbonamento fuori dal loro ordine cronologico: se poi nasceva un materiale sospeso,
la visita successiva era già stata pianificata da giorni e il meccanismo di aggancio non
aveva più occasione di scattare.

Ora navigando con avanti e indietro il pool si aggiorna. Con tre precisazioni importanti:

- il filtro vale **solo** per le occorrenze regolari da abbonamento; gli interventi normali
  e le visite extra restano sempre visibili, perché non hanno una cadenza;
- il filtro è un limite superiore soltanto, quindi **gli arretrati non spariscono mai**:
  andare avanti nel tempo allarga cosa si vede in avanti, non nasconde ciò che è indietro;
- agganciarlo al periodo *visibile* invece che a una settimana fissa calcolata sul server
  fa funzionare da solo sia la vista Settimana sia quella Giorno su mobile, e resterebbe
  coerente anche aggiungendo altre viste.

L'aggiornamento avviene via AJAX sul callback `datesSet` e il server restituisce **HTML già
reso**, non JSON. È una decisione presa in corso d'opera: il markup del pool contiene
raggruppamenti annidati, badge, icone e materiali per scheda, e riscriverlo in JavaScript
avrebbe significato mantenere la stessa presentazione in due linguaggi. Il markup vive in
un'unica view parziale `_pool.php`, usata sia dal caricamento iniziale sia dal
rinfrescamento. Funziona perché sia il trascinamento di FullCalendar sia i pieghevoli di
Bootstrap sono agganciati al contenitore e non alle singole schede, quindi sopravvivono
alla sostituzione del contenuto.

### La barra "Attenzione"

Tre pillole pieghevoli in cima al calendario, ciascuna con il proprio conteggio e un
suggerimento che spiega il criterio:

| Pillola | Chi ci finisce |
|---|---|
| **Non completato** | pianificato o in corso, con la data dell'appuntamento ormai passata |
| **In ritardo** | scadenza superata, in qualunque stato |
| **Fermo** | da pianificare, inserito da più di 7 giorni |

La distinzione per stato non è un dettaglio: *fermo da una settimana* è un problema solo
per chi non ha ancora una data. Applicare lo stesso criterio a un intervento già pianificato
segnalerebbe come in ritardo lavori vecchi ma perfettamente in programma.

C'è un'eccezione, emersa provando con dati reali: gli interventi da abbonamento nascono
tutti insieme con largo anticipo, quindi il loro `created_at` è quasi sempre più vecchio di
sette giorni **per costruzione**. Venivano marcati "fermi" a torto anche con la scadenza a
dicembre. Il criterio "fermo" li considera solo quando la scadenza rientra nel mese
corrente; il criterio "in ritardo", che guarda la scadenza vera, continua a valere per
tutti.

Un clic su un intervento della pillola non porta via dalla pagina: se è da pianificare apre
il pool, espande i gruppi che lo contengono, scorre fino alla sua scheda e la fa lampeggiare;
se è già pianificato porta il calendario alla sua data e fa lampeggiare l'evento. Da lì il
trascinamento per ripianificare è quello che c'era già. Il doppio clic apre la scheda.

Su mobile il comportamento è diverso e non è un ripiego: il pool non esiste su schermo
piccolo, quindi un tocco porta direttamente alla scheda dell'intervento, che è il modo in
cui si pianifica da telefono.

Questa barra ha sostituito la precedente "Scadenze aperte", che elencava tutte le scadenze
senza limiti temporali e con dati reali diventava un muro di badge — cioè il problema
stesso che avrebbe dovuto risolvere. Anche la prima versione della sostituzione, una fila
piatta ordinata per urgenza, aveva lo stesso difetto spostato di un livello: il
raggruppamento in tre pillole è nato provando la pagina, non progettandola.

## 5.4 Foglio di viaggio

`Operativo\ViaggioController` · rotte `operativo/viaggi/*`

La vista giornaliera di tutti gli interventi pianificati, ordinati per ora e raggruppati
per zona, con la stessa palette di colori usata nel pool e nell'elenco clienti. Si naviga
per data e si filtra per tecnico con le solite pillole; il bottone PDF **segue il filtro
attivo**, generando il foglio del singolo tecnico.

I materiali da portare compaiono come elenco puntato dentro la riga, con la quantità sempre
esplicita. Il PDF è A4 orizzontale, con l'intestazione aziendale, le fasce di zona colorate,
le righe urgenti evidenziate e una colonna Firma lasciata vuota.

La vista è di sola lettura e non salva nulla: gli interventi sono già nel database, e la
stampa è semplicemente l'istantanea che il tecnico si porta dietro.

Una nota tecnica su dompdf: le regole `@page` non vengono applicate, quindi i margini si
impostano come `padding` sul `body`. E niente flexbox o grid nelle view dei PDF — solo
tabelle.
