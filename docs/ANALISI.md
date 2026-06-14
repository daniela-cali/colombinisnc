# Analisi del Software — [Colombini Snc]

## 1. Introduzione e contesto
### 1.1 Scopo del sistema
Gestionale interno per Colombini Snc, azienda specializzata nel 
trattamento acqua e costruzione piscine. Il sistema supporta la 
pianificazione e il tracciamento degli interventi tecnici presso 
i clienti, la gestione dei dipendenti e la visualizzazione del 
calendario lavori.
### 1.2 Obiettivi di business
- Sostituire la gestione manuale (carta/Google Calendar) degli interventi
- Ridurre le sovrapposizioni di appuntamenti
- Garantire puntualità nelle visite
- Gestione interventi programmati da abbonamenti
- Tenere traccia di materiali portati alle visite
- Dare ai tecnici visibilità sui propri interventi da smartphone
- Avere uno storico degli interventi per cliente
### 1.3 Stakeholder
| Ruolo | Descrizione | Accesso |
|-------|-------------|---------|
| Admin | Sviluppatore e titolari | Completo |
| Ufficio | Staff interno | Gestione interventi e calendario |
| Tecnico | Operatori sul campo | Visualizzazione e aggiornamento propri interventi |
| Cliente | Clienti aziendali | Accesso solo agli interventi a loro assegnati o richiesti da futuro portale|

### 1.4 Glossario
- **Intervento**: visita tecnica eseguita presso un cliente
- **Tecnico**: dipendente che esegue l'intervento sul campo
- **Stato intervento**: fase corrente (es. pianificato, in corso, completato)
- **Dispatcher**: figura dell'ufficio che pianifica e assegna gli interventi

## 2. Requisiti funzionali

### 2.1 Funzionalità

#### MVP
- Gestione utenti e permessi per ruolo
- Anagrafica clienti
- Anagrafica tecnici
- Creazione e assegnazione interventi
- Calendario interventi
- Aggiornamento stato intervento

#### Versioni successive
- Creazioni manutenzioni in abbonamento
- Dashboard dedicata ai tecnici con aggiornamento stato intervento
- Gestione magazzino
- Anagrafiche impianti
- Gestione creazione preventivi
- Sistema VRP con API OpenRouteService (OSR)
- Report e stampe
- Statistiche

### 2.2 Flussi operativi
### 2.3 Regole di business

## 3. Requisiti non funzionali

### 3.1 Performance
- Tempo di risposta pagine < 2 secondi
- Supporto a ~10 utenti simultanei

### 3.2 Sicurezza
- Autenticazione tramite sessione CI4
- Autorizzazioni per ruolo (admin, ufficio, tecnico, cliente)
- Protezione CSRF integrata in CI4
- Accesso tecnici limitato ai propri interventi

### 3.3 Disponibilità
- Sistema operativo sempre hostato su VPS esterna con dominio collegato
- Backup giornaliero del database MySQL su altra VPS esterna 
- Deploy su Nginx self-managed

### 3.4 Scalabilità
- Architettura sufficiente per ~10 tecnici e crescita clienti nel medio termine
- Nessun requisito di scalabilità orizzontale previsto, un singolo server Nginx è sufficiente per le dimensioni previste

## 4. Architettura del sistema

### 4.1 Stack tecnologico
- **PHP**: 8.2+
- **CodeIgniter**: 4.7.3
- **MySQL**: 8.x
- **AdminLTE**: 4.0.2 con Bootstrap 5.3.8
- **Frontend**: nessun bundler (Vite/webpack) — dipendenze gestite via npm + comando `assets:publish`

### 4.2 Diagramma dei componenti

```mermaid
graph LR
    A[Browser] --> B[Nginx]
    B --> C[CodeIgniter 4]
    C --> D[MySQL - colombinisnc]
```

### 4.3 Integrazioni esterne
- **OpenRouteService API**: calcolo percorsi ottimali per gli 
  spostamenti dei tecnici *(priorità bassa — versioni future)*

## 5. Interfaccia utente

### 5.1 Struttura e navigazione

**Cruscotto**
- Dashboard: riepilogo interventi da pianificare,
  presenze/assenze tecnici con link diretto al calendario sotto l'elenco intervento
- Calendario (pianificazione interventi con FullCalendar)

**Anagrafiche**
- Clienti: scheda con dati base, mappa OpenStreetMap su Leaflet,
  link Google Maps, storico interventi, note, materiali
- Tecnici

**Assistenza**
- Interventi: elenco + creazione
- Viaggi: interventi del giorno raggruppati per tecnico (solo staff)
  → click intervento → scheda cliente

**Amministrazione** *(solo admin)*
- Utenti e permessi

### 5.2 Requisiti mobile
- Interfaccia utilizzabile da smartphone dai tecnici sul campo
- Vista calendario semplificata su schermi piccoli
- Bottoni e touch target adeguati al tocco
- Viaggio del giorno filtrato per tecnico loggato con link diretto al cliente per poter aggiungere note e materiali futuri
- Aggiornamento stato intervento accessibile con pochi tap
- Chiusura intervento con generazione di pdf di rapportino, domanda se consegnati i materiali richiesti e indicati nell'intervento


## 6. Gestione dei dati

### 6.1 Entità principali
### 6.1 Entità principali

| Entità | Descrizione |
|--------|-------------|
| `users` | Gestita da CI4 Shield — autenticazione |
| `personale` | Dati anagrafici di tutto il personale (tecnici e staff),
                collegato a `users` tramite FK nullable |
| `clienti` | Anagrafica clienti con coordinate per mappa |
| `interventi` | Intervento tecnico presso un cliente |
| `tipi_intervento` | Tipologie di intervento |
| `stati_intervento` | Stati possibili dell'intervento |
| `viaggi` | Raggruppamento interventi per tecnico e giorno |
| `note` | Note operative collegate a cliente o intervento |
| `materiali` | Materiali utilizzati o da portare |

### 6.2 Autenticazione e autorizzazioni

**CI4 Shield** gestisce autenticazione e permessi:
- `users` — credenziali di accesso
- `groups` — ruoli (`admin`, `developer`, `ufficio`, `tecnico`, `cliente`)
- `users_groups` — relazione utente/gruppo

**`personale`** gestisce i dati operativi:
- Anagrafica (nome, cognome, telefono, colore calendario)
- Collegato a `users` tramite `user_id` FK nullable
- Utilizzato dall'applicazione indipendentemente da Shield

```mermaid
graph TD
    A[users - Shield] -->|user_id| B[personale]
    A -->|appartiene a| C[groups - Shield]
    C --> D[admin]
    C --> E[ufficio]
    C --> F[tecnico]
```

### 6.3 ER Diagram
![ER Diagram](img/users.png)
### 6.4 Flussi di input/output
> Da compilare

### 6.5 Politiche di retention
> Da compilare

## 7. Piano di progetto

### 7.1 Milestone e fasi

#### ✅ v0.1.0 — Inizializzazione progetto
- Setup CI4 + AdminLTE 4 + Bootstrap 5.3.8
- Pipeline asset npm + assets:publish
- Layout admin con sidebar, navbar, dark mode
- Localizzazione italiana

#### ✅ v0.2.0 — Autenticazione
- Login/logout con CI4 Shield
- Gruppi utente: admin, staff, tecnici, clienti
- Protezione rotte con filtri Shield

#### ✅ v0.3.0 — Impostazioni base
- Navbar con dati utente loggato
- Pagina impostazioni con card

#### ✅ v0.4.0 — Anagrafica personale
- Migrazione: rimozione campi custom (nome, cognome, telefono) da users
- Creazione tabella `personale` con FK a `users`
- CRUD personale

#### ✅ v0.5.0 — Parametri generali
- Tabella `impostazioni` (class / key / value)
- Sede aziendale: nome, indirizzo, CAP, città, telefono, sito, logo, lat/lng + geocodifica
- Orari aziendali: inizio/fine giornata, pausa pranzo
- Durate standard interventi per tipo (sale, filtri, piscine, addolcitori, acquedotti, commerciale)

#### ✅ v0.6.0 — Profilo e visualizzazione changelog
- Visualizzazione changelog filtrata per `[DEV]` e `[APP]` a seconda del gruppo utente loggato
- Migrazione campo `ultima_versione_vista` su `users`; modal novità all'avvio con aggiornamento via AJAX
- Voce "Profilo" nel dropdown utente collegata alla scheda dipendente
- Pannello utente nella sidebar con nome, ruolo e link al profilo
- Restyling palette colori: navbar blu medio, sidebar-brand scura, separatore teal, versione nel footer
- Avatar rinviato a versione futura

#### 🔲 v0.7.0 — Anagrafica clienti
- CRUD clienti con coordinate, zona (-1/0/1), tecnico preferito, distanza dalla sede

#### 🔲 v0.8.0 — Interventi
- CRUD interventi
- Tipi e stati intervento
- Creazione intervento da scheda cliente

#### 🔲 v0.9.0 — Calendario
- Integrazione FullCalendar
- Visualizzazione interventi
- Creazione/modifica da calendario

#### 🔲 v0.10.0 — Viaggi
- Vista giornaliera per tecnico
- Accesso scheda cliente da intervento
- Note e materiali

#### 🔲 v0.11.0 — Release
- Dashboard riepilogativa completa
- Presenze/assenze tecnici
- Test e fix generali
- Deploy su Nginx

### 7.2 Funzionalità versioni future
- Report e stampe PDF
- Notifiche
- Statistiche
- Integrazione OpenRouteService (percorsi ottimali)
- Portale tecnici mobile dedicato

### 7.3 Rischi e mitigazioni

| Rischio | Probabilità | Mitigazione |
|---------|-------------|-------------|
| Scope creep prima della v1.0 | Alta | Rispettare le milestone, rimandare tutto il resto alle versioni future |
| Complessità FullCalendar + API REST | Media | Prototipare prima con dati statici |
| Usabilità mobile per i tecnici | Media | Test sul campo dalla v0.7.0 |

## 8. Decisioni tecniche

### Nessun bundler (Vite/Webpack)
Scelto di gestire le dipendenze frontend tramite npm + comando 
`assets:publish` di CI4. Evita la complessità di configurazione 
di un bundler per un progetto di questa scala.

### CI4 Shield per l'autenticazione
Scelto Shield invece di un sistema custom per non reinventare 
la ruota su autenticazione, gestione sessioni e permessi. 
Fornisce gruppi e filtri già integrati con CI4.

### Tabella `personale` separata da `users`
`users` è gestita da Shield e non va modificata. `personale` 
contiene i dati anagrafici operativi di tutto il personale 
(tecnici e staff), collegata a `users` tramite FK nullable. 
Permette profili personalizzati senza toccare Shield.

### Leaflet + OpenStreetMap per le mappe
Scelto Leaflet per la visualizzazione mappe nella scheda cliente. 
Nessuna API key necessaria. Link a Google Maps tramite coordinate 
per navigazione sul campo dai tecnici.

### OpenRouteService rimandato
Integrazione per percorsi ottimali rimandata a versioni future — 
funzionalità utile ma non bloccante per il MVP.