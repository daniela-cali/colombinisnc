# 9. Storico delle versioni

Il progetto è partito il **10 giugno 2026** e ha prodotto 74 rilasci in poco più di due
mesi. Questo capitolo riassume il percorso: il dettaglio completo, voce per voce, è in
`CHANGELOG.md`, mentre `docs/ANALISI.md` §7.1 tiene la stessa cronologia in forma di
milestone.

## 9.1 Le versioni minori

Ogni riga ha introdotto un modulo o un cambiamento strutturale.

| Versione | Data | Contenuto |
|---|---|---|
| 0.1.0 | 10/06/2026 | inizializzazione: CodeIgniter 4, AdminLTE 4, pipeline npm, layout, localizzazione italiana |
| 0.2.0 | 12/06/2026 | autenticazione con Shield, gruppi utente, protezione delle rotte |
| 0.3.0 | 12/06/2026 | impostazioni di base e navbar con i dati dell'utente |
| 0.4.0 | 13/06/2026 | anagrafica personale, con la tabella separata da `users` |
| 0.5.0 | 14/06/2026 | parametri generali: sede, orari, durate standard |
| 0.6.0 | 14/06/2026 | profilo utente e changelog filtrato per ruolo |
| 0.7.0 | 14/06/2026 | anagrafica clienti, geocodifica, zone, DataTables |
| 0.8.0 | 15/06/2026 | interventi: CRUD, tipi, stati, priorità |
| 0.9.0 | 15/06/2026 | magazzino di base: categorie e articoli |
| 0.10.0 | 18/06/2026 | materiali sugli interventi, scheda cliente |
| 0.11.0 | 18/06/2026 | materiali sospesi, legati al cliente senza intervento |
| 0.12.0 | 21/06/2026 | **calendario** FullCalendar, pool e trascinamento |
| 0.13.0 | 21/06/2026 | foglio di viaggio e prima stampa PDF |
| 0.14.0 | 23/06/2026 | **abbonamenti**: periodi, generazione in blocco, catena dei rinnovi |
| 0.15.0 | 24/06/2026 | prossima visita, visite extra, pulizia del fondo |
| 0.16.0 | 26/06/2026 | diario note sugli interventi |
| 0.17.0 | 27/06/2026 | **cantieri** e relativo diario |
| 0.18.0 | 27/06/2026 | dashboard differenziata per ruolo |
| 0.19.0 | 28/06/2026 | adattamento mobile e agenda del tecnico |
| 0.20.0 | 30/06/2026 | guide contestuali di sezione |
| 0.21.0 | 01/07/2026 | promemoria, campanella avvisi, dashboard riorganizzata |
| 0.22.0 | 05/07/2026 | assenze del personale |
| 0.23.0 | 05/07/2026 | mappa nella scheda cliente, correzione manuale del pin |
| 0.24.0 | 06/07/2026 | stampe PDF di scheda cliente e cantiere |
| 0.25.0 | 05/08/2026 | creazione di un intervento da una nota di cantiere |
| 0.26.0 | 15/08/2026 | abbonamenti: proposta e accettazione |
| 0.27.0 | 16/08/2026 | import dei clienti dall'anagrafica storica |
| 0.28.0 | 23/08/2026 | **gestione account, ruoli e profilo**: logica unificata nel model, escalation dell'ufficio chiusa, sospensione al posto dell'eliminazione |

## 9.2 Il ramo 0.24.x

Trentatré rilasci fra il 6 luglio e il 2 agosto, il tratto più denso del progetto. Non ha
introdotto moduli nuovi: ha rifinito quelli esistenti sulla base di quanto emerso provandoli
con dati reali, e ha chiuso i punti annotati nella riunione del 10 luglio. I più
significativi:

| Versione | Contenuto |
|---|---|
| 0.24.1 | blocco intelligibile alla cancellazione di un cliente; view di consultazione |
| 0.24.11 | sottogruppi generico/cantiere/abbonamento dentro le zone del pool |
| 0.24.13 | blocco del tecnico assente e notifica dei conflitti retroattivi |
| 0.24.15 | orario suggerito nel modal di pianificazione |
| 0.24.19 | foglio di viaggio: filtro per tecnico e PDF ristilizzato |
| 0.24.21 | il JavaScript del calendario esce dalla view in un file dedicato |
| 0.24.22 | la barra "Attenzione" sostituisce "Scadenze aperte" |
| 0.24.23 | **rotte protette** per personale e impostazioni |
| 0.24.24 | scadenze duplicate e copertura garantita dei periodi |
| 0.24.25 | il pool segue il periodo visibile sul calendario |
| 0.24.26 | checklist itemizzata dei materiali alla chiusura |
| 0.24.28 | cantieri: luogo, referente e geolocalizzazione propri |
| 0.24.29 | revisione della UX mobile per i tecnici |
| 0.24.31 | **restrizioni server-side** per il ruolo tecnico |

Le versioni intermedie non elencate sono in larga parte correzioni: leggibilità in tema
scuro, comportamento dei datepicker su iOS, icone con il prefisso sbagliato, sovrapposizione
dei modal all'accesso.

## 9.3 Cosa insegna questa cronologia

Tre schemi ricorrenti, utili a chi riprende il progetto.

**Molte decisioni sono cambiate provandole, non progettandole.** La barra "Attenzione" è
stata riscritta due volte perché la prima versione riproduceva il problema che doveva
risolvere; il pool è passato dal mese fisso al periodo visibile solo dopo aver capito quale
effetto collaterale produceva; il modal di chiusura è passato dalla domanda binaria alla
checklist dopo aver provato il flusso reale. Le specifiche in `docs/spec/` registrano
questi ripensamenti dentro il documento stesso, invece di riscriverne la storia.

**Diversi bug erano latenti, non visibili.** Le scadenze duplicate erano mascherate da un
ripiego a valle; le tre chiavi esterne invertite non avevano ancora causato danni; lo stato
`scaduto` mai persistito non produceva errori perché tutte le viste passavano dai tre metodi
giusti. Sono stati corretti perché la garanzia mancante avrebbe fatto pagare il conto a
ogni funzionalità futura.

**La protezione lato server è arrivata dopo l'interfaccia.** Per un lungo tratto le sezioni
riservate erano solo nascoste dal menu. È il tipo di debito che non si vede finché non lo si
cerca: vale la pena, aggiungendo un modulo nuovo, chiedersi subito quali rotte lo espongono
e a chi.
