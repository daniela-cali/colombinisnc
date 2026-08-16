# 10. Stato attuale e prospettive

## 10.1 Dove siamo

Alla **v0.27.0** (16 agosto 2026) il gestionale copre l'intero flusso operativo:
anagrafiche, interventi, calendario e pianificazione, abbonamenti, cantieri, materiali,
magazzino anagrafico, stampe, permessi per ruolo. È in uso quotidiano in sviluppo con dati
reali, che è il modo in cui sono emerse la maggior parte delle correzioni del ramo 0.24.x.

La **v1.0.0 è prevista per metà settembre 2026** e comprende tre cose:

- test e correzioni generali;
- ottimizzazione dei percorsi con **OpenRouteService** (calcolo del giro giornaliero per
  tecnico), l'unica integrazione esterna prevista e finora sempre rimandata perché utile ma
  non bloccante;
- deploy su Nginx con il dominio `colombini.metesoftware.it`.

Ricordare che **il database di sviluppo va svuotato prima del go-live** (capitolo 8.7).

## 10.2 Previsto dopo la release

Da pianificare in base alle priorità che emergeranno dall'uso reale, non secondo un ordine
già deciso.

**Anagrafica impianti** — una tabella `impianti` (piscina, addolcitore, acquedotto…) e una
`clienti_impianti` con l'indirizzo specifico quando differisce da quello del cliente,
collegata agli interventi tramite la colonna `impianto_id` già presente e volutamente
lasciata libera dalla v0.8.0. È il modello corretto a lungo termine per il multi-proprietà,
e assorbirebbe i campi di luogo aggiunti sui cantieri.

**Richieste di intervento** — una tabella `richieste` con il flusso richiesta →
approvazione → conversione in intervento, e un contatore in barra laterale per quelle in
attesa.

**Magazzino avanzato** — movimenti di carico e scarico, scarico automatico quando un
articolo finisce sui materiali di un intervento, soglia minima con avviso di sottoscorta,
import dei ricambi da un database esterno.

**Preventivi** — testata e righe, con conversione del preventivo accettato in intervento o
abbonamento. Esiste una specifica (`preventivi_impianti_spec.md`) ma è dichiaratamente in
fase concettuale, senza alcuna implementazione avviata.

**Più avanti**: portale clienti (la colonna `clienti.user_id` è già predisposta), portale
tecnici come applicazione dedicata, notifiche via email o push, firma del cliente alla
chiusura dell'intervento (salvata in base64 su colonna, non come file su disco).

Prima di costruire il portale tecnici va rivisto l'intero gestionale in ottica
mobile-first: oggi è progettato per il desktop, e l'adattamento mobile è mirato ai punti
che il tecnico usa davvero.

Esiste anche una specifica tecnica ed economica per l'integrazione del **centralino
Voxloud**, valutata e non implementata.

## 10.3 Temi aperti e debiti noti

Elencati per onestà: sono cose che chi riprende il progetto scoprirebbe comunque, meglio
saperle prima.

**Le zone sono tre e fisse.** Ventimiglia, Ceriale e Savona sono una macro-semplificazione:
nella pratica si suddividono ulteriormente (Varazze-Loano dentro Savona). L'evoluzione
prevista è una tabella `zone` con `nome`, `lng_min`, `lng_max` e `ordine`, mantenendo la
stessa logica di assegnazione dalla longitudine ma con numero e nomi liberi. Il pool del
calendario raggrupperebbe di conseguenza.

**Due elenchi non usano ancora i filtri condivisi.** `cantieri/index.php` e
`operativo/interventi/index.php` hanno ancora la logica di filtro scritta a mano nella view,
mentre la scheda cliente e l'elenco abbonamenti sono già passati a `public/js/search-bar.js`.
La migrazione richiede di generalizzare lo script da due a N colonne per filtro.

**Il campo `note` degli interventi convive con il diario.** Dopo l'introduzione di
`interventi_note` il campo singolo è in gran parte ridondante, ma è ancora lì: va deciso se
migrarne il contenuto e rimuoverlo.

**Il calendario è aperto ai tecnici.** Vedi capitolo 8.5: un tecnico può ripianificare
interventi di colleghi. È una scelta consapevole, non un'omissione, ma resta da rivedere se
diventa un problema.

**Nessun test automatico.** Non esiste alcuna suite: ogni verifica è manuale. È il debito
più rilevante in vista della v1.0.0, dove "test e fix generali" è una delle tre voci.

**L'elenco dei clienti da migrare è client-side.** Con circa 2600 righe DataTables regge ma
è pesante, e l'elenco si assottiglia man mano che la migrazione avanza. Introdurre
l'elaborazione lato server solo per quella schermata sarebbe sproporzionato, dato che nel
progetto non esiste ancora alcuna infrastruttura del genere.

**Eventi sovrapposti nella vista Giorno su mobile.** Difetto grafico noto e non
prioritizzato.

**Un manifest PWA è stato valutato e rimandato**: costo e beneficio non lo giustificavano
al momento della valutazione. Andrà ripreso insieme al portale tecnici.

**La documentazione dello schema si disallinea da sola.** Le quattro divergenze rilevate
scrivendo questo manuale sono state corrette in `docs/schema.html` con la v0.27.1, ma erano
tutte dello stesso tipo: migrazione applicata, pagina non aggiornata. La regola di progetto
c'è già — `docs/schema.html` si aggiorna nella stessa commit della migrazione — e nessuna
verifica automatica la fa rispettare. Un controllo che confronti `information_schema` con la
pagina sarebbe fattibile e non esiste.

## 10.4 Rischi

Dall'analisi di progetto, ancora attuali.

| Rischio | Probabilità | Come si tiene sotto controllo |
|---|---|---|
| Espansione dell'ambito prima della v1.0 | alta | rispettare le milestone, rimandare tutto il resto |
| Usabilità mobile per i tecnici | media | prova sul campo dopo l'entrata in funzione in ufficio |
| Complessità di FullCalendar e delle API | media | prototipare con dati statici prima di integrare |

Il primo è il più concreto, ed è visibile nella cronologia stessa: il ramo 0.24.x conta
trentatré rilasci di rifinitura. Non è tempo sprecato — quasi tutti nascono da problemi
reali riscontrati usando il sistema — ma è la conferma che l'ambito tende ad allargarsi da
solo.

## 10.5 Mantenere aggiornato questo manuale

Il manuale è generato da `docs/manuale/*.md` tramite lo script Python
`tools/manuale/genera_manuale.py`:

```bash
python tools/manuale/genera_manuale.py
```

Lo script legge i capitoli in ordine alfabetico di nome file, per questo sono numerati, e
produce `docs/Manuale_Tecnico_Colombini.docx`. La versione in copertina viene letta
automaticamente dall'intestazione più recente di `CHANGELOG.md`, quindi non va aggiornata a
mano.

Serve `python-docx` (`pip install python-docx`); non c'è altra dipendenza.

**In versione sono solo i capitoli `.md`.** Il `.docx` è un prodotto, non un sorgente: è
escluso dal repository tramite `.gitignore` e si rigenera quando serve.

**Le correzioni si fanno sul Markdown, mai sul `.docx`**: il documento viene ricostruito da
zero a ogni esecuzione, quindi qualunque modifica fatta direttamente su di esso andrebbe
persa alla rigenerazione successiva — indice manuale compreso. Aggiungere un capitolo
significa aggiungere un file con il numero giusto nel nome.

**L'indice si comporta diversamente nei due programmi.** Lo script inserisce un campo TOC
di Word: in Word si popola da solo all'apertura, o con Ctrl+A seguito da F9. **LibreOffice
Writer non importa quel campo** come indice nativo — verificato convertendo il documento in
ODF: non ne resta traccia — quindi lì "Aggiorna tutto" non ha nulla da aggiornare e
l'indice va inserito a mano (Inserisci → Sommario e indice), ripetendo l'operazione dopo
ogni rigenerazione.

Il Markdown riconosciuto è un sottoinsieme volutamente ristretto — titoli fino al quarto
livello, paragrafi, liste su due livelli, tabelle, blocchi di codice, citazioni e
formattazione inline. Tutto il resto viene reso come testo normale, quindi conviene restare
dentro quel perimetro.
