# 1. Introduzione

## 1.1 Che cos'è questo sistema

Il gestionale Colombini SNC è un'applicazione web interna per un'azienda che si occupa di
**trattamento acqua e costruzione piscine**. Copre l'intero ciclo del lavoro sul campo:
l'anagrafica dei clienti, la pianificazione degli interventi tecnici, i contratti di
manutenzione ricorrente, i cantieri, i materiali che i tecnici portano con sé e il
magazzino da cui quei materiali provengono.

Sostituisce una gestione fatta di carta e Google Calendar. Gli obiettivi dichiarati
all'inizio del progetto restano il metro di giudizio di ogni funzionalità:

- eliminare le sovrapposizioni di appuntamenti;
- garantire la puntualità delle visite in abbonamento;
- tenere traccia dei materiali portati a ogni visita;
- dare ai tecnici visibilità sui propri interventi dallo smartphone;
- conservare uno storico degli interventi per cliente.

## 1.2 A chi si rivolge questo manuale

A chi sviluppa e mantiene il codice. Presuppone familiarità con PHP e con il pattern MVC,
ma non con questo progetto in particolare: spiega **come è fatto** il sistema e soprattutto
**perché è fatto così**, perché è la seconda parte quella che si perde per prima.

Non è un manuale d'uso per l'utente finale. Quella funzione è già coperta dentro
l'applicazione stessa, dalle guide contestuali di sezione (capitolo 7.5).

## 1.3 Come è organizzato

I capitoli 2 e 3 descrivono le fondamenta — architettura e modello dati — e vanno letti
per primi. I capitoli dal 4 al 7 descrivono i moduli funzionali: ognuno è autonomo e si
può leggere quando serve mettere le mani su quella parte. Il capitolo 8 raccoglie tutto
ciò che riguarda accessi e permessi, che attraversa trasversalmente tutti i moduli. I
capitoli 9 e 10 danno la prospettiva storica e lo stato attuale del lavoro.

Dove una decisione ha una specifica scritta, il capitolo la cita per nome
(`docs/spec/<nome>_spec.md`): la spec contiene il ragionamento completo, incluse le
alternative scartate, che qui viene riassunto.

## 1.4 Ruoli

Il sistema riconosce cinque gruppi utente. La differenza fra `admin` e `developer` è
sottile ma reale: identici nei permessi applicativi, il secondo vede in più le voci
tecniche del changelog e la documentazione di sviluppo.

| Gruppo | Chi è | Cosa può fare |
|---|---|---|
| `admin` | titolari dell'azienda | tutto |
| `developer` | chi sviluppa il gestionale | tutto, più le voci `[DEV]` del changelog |
| `ufficio` | staff amministrativo | tutto tranne le Impostazioni |
| `tecnico` | operatore sul campo | i propri interventi; sola lettura su contratti e cantieri |
| `cliente` | cliente finale | previsto per un portale futuro, oggi senza funzioni proprie |

Un utente può appartenere a più gruppi. La distinzione ricorrente nel codice è quella di
**tecnico puro** — chi è nel gruppo `tecnico` e in nessuno dei gruppi di gestione — sulla
quale si basano sia la sidebar ridotta sia le restrizioni descritte al capitolo 8.

## 1.5 Glossario

I termini che seguono ricorrono nel codice con un significato preciso, spesso diverso da
quello che l'intuito suggerirebbe.

| Termine | Significato nel sistema |
|---|---|
| **Intervento** | la singola visita tecnica presso un cliente. È l'unità di lavoro attorno a cui ruota tutto |
| **Abbonamento** | il contratto di manutenzione ricorrente. Non è un intervento: è il contratto che *genera* gli interventi |
| **Periodo** | tratto di un abbonamento con una propria frequenza di visita. Un abbonamento ne ha uno o più |
| **Occorrenza** | intervento generato automaticamente da un abbonamento, distinto da uno creato a mano |
| **Visita extra** | intervento fuori dal piano dell'abbonamento, ma collegato ad esso (`extra = 1`) |
| **Cantiere** | progetto che raggruppa più interventi per un cliente: nuova costruzione, ristrutturazione o manutenzione straordinaria |
| **Pool** | l'elenco laterale del Calendario con gli interventi ancora da pianificare, trascinabili sulla griglia |
| **Materiale sospeso** | materiale segnato per un cliente ma non ancora legato a un intervento: il promemoria "da portare al prossimo giro" |
| **Foglio di viaggio** | la stampa giornaliera degli interventi di una giornata, raggruppati per zona |
| **Zona** | fascia geografica del cliente lungo la costa, calcolata dalla longitudine: Ventimiglia, Ceriale o Savona |
| **Promozione** | il passaggio di un cliente dall'archivio storico importato all'anagrafica vera |
| **Dispatcher** | ruolo operativo, non un gruppo utente: chi in ufficio assegna le date agli interventi |

## 1.6 Distinzioni che è facile confondere

Tre coppie di concetti hanno nomi simili e significati distinti. Sono la causa più
frequente di fraintendimenti quando si legge il codice per la prima volta.

**`priorita` e `urgenza` sono campi diversi.** `priorita` classifica l'origine
dell'intervento (`abbonamento`, `normale`, `urgente`); `urgenza` è un flag booleano
indipendente che può essere alzato su qualunque intervento, compresi quelli in
abbonamento.

**`data_pianificata` e `data_scadenza` rispondono a domande diverse.** La scadenza dice
*entro quando* l'intervento deve essere eseguito e nasce col contratto; la data pianificata
dice *quando è stato fissato* l'appuntamento e viene assegnata dopo, dal dispatcher. Le
occorrenze di un abbonamento nascono con la scadenza valorizzata e la data pianificata a
`NULL`.

**Annullare non è eliminare.** `annullato` è uno stato dell'intervento: il record resta,
con il suo storico. L'eliminazione è una cancellazione fisica dal database, riservata a
ufficio e amministratori.

## 1.7 Fonti di questo documento

Il manuale riorganizza per argomento materiale che nel repository è ordinato per data:
`docs/ANALISI.md` (analisi e milestone), `CHANGELOG.md` (74 rilasci), le venti
specifiche implementate in `docs/spec/`, `docs/schema.html` e il codice sorgente. Lo
schema del database è stato verificato interrogando direttamente il database di sviluppo,
non solo leggendo la documentazione.
