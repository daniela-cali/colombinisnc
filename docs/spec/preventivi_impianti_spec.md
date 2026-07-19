# Spec — Milestone Preventivi & Impianti

> Documento di lavoro, generato da sessione di ragionamento con Claude (chat).
> Stato: **in fase di progettazione concettuale**, nessuna implementazione ancora avviata.
> Da rivedere/completare prima di passare a Claude Code per lo sviluppo.

---

## 1. Visione generale e ordine logico

Il flusso parte dai **preventivi**, non dagli impianti cliente. Sequenza concettuale:

1. Si crea un **preventivo** per un cliente, composto da una o più **schede impianto** (prese dall'anagrafica globale, con prezzo eventualmente variato)
2. Il preventivo viene esportato in **Word → PDF** e salvato come documento immutabile
3. Se il preventivo viene **accettato**, diventa un **Lavoro** → si apre un **cantiere**
4. All'apertura del cantiere vengono **generati automaticamente** gli impianti nella tabella "impianti cliente", con riferimento alla scheda tecnica originale
5. (Da valutare in futuro, non ora) gli **interventi** potranno essere collegati a un impianto cliente specifico

Per il pregresso (clienti/impianti già esistenti prima del sistema): inserimento **manuale** delle schede impianto cliente, senza passare da un preventivo.

Principio guida: **"replicate before automating"** — replicare il flusso cartaceo attuale di costruzione preventivo, poi introdurre struttura/automazione.

---

## 2. Modello dati — Impianti

### 2.1 Schede tecniche impianto (anagrafica globale)

Tabella tipo "a catalogo", concettualmente simile a un'anagrafica articoli:

- Dati tecnici standard (marca, modello, potenza, descrizione tecnica, ecc.)
- **Prezzo di listino**
- Riutilizzabile su più preventivi e più clienti
- È la fonte "pulita" di riferimento — non va sporcata da variazioni cliente-specifiche

### 2.2 Impianti cliente

Tabella che rappresenta l'istanza installata presso un cliente. Non duplica i dati della scheda tecnica, ma vi fa riferimento tramite FK:

- FK a scheda tecnica impianto
- FK a cliente
- Campi propri dell'istanza: matricola, data installazione, garanzia, eventuale documentazione/scheda tecnica specifica
- Generata automaticamente all'apertura cantiere (da preventivo accettato) oppure inserita manualmente (pregresso)

### 2.3 Gestione documentale prodotti chimici (schede di sicurezza)

**Rimandata alla milestone "Magazzino avanzato"** — non fa parte di questa milestone.

### 2.4 Collegamento interventi ↔ impianto cliente

**Da valutare in futuro**, non decisa/implementata ora. Non bloccante per questa milestone.

---

## 3. Modello dati — Preventivi

Tabelle emerse dal ragionamento (nomi indicativi, da confermare in fase di implementazione):

### `tipi_preventivo`
- Es. piscina, vasca idromassaggio, impianto osmosi, ecc.
- Contiene il testo di introduzione standard per il frontespizio, variabile per tipologia
- L'introduzione resta comunque editabile per il singolo preventivo

### `condizioni_fornitura`
- Template standard salvati (es. "30% ordine – 30% consegna – 40% saldo")
- Selezionabile come default, editabile per singolo preventivo

### `preventivi`
- FK cliente
- FK tipo preventivo
- Data, luogo
- Condizioni di fornitura scelte/editate
- Stato (es. bozza / inviato / accettato / rifiutato)
- Path del PDF generato (una volta congelato)

### `preventivo_impianti`
- FK preventivo
- FK scheda tecnica impianto
- **`prezzo_applicato`** — indipendente dal prezzo di listino, precompilato da esso ma liberamente modificabile per il singolo preventivo/cliente (i prezzi reali spesso si scostano dal listino)
- Eventuale descrizione override per quel preventivo specifico

---

## 4. Generazione documento Word/PDF

### 4.1 Approccio: template Word + PHPWord (TemplateProcessor)

- Il template `.docx` viene **creato e formattato direttamente in Word** dall'utente (Daniela), con lo stile aziendale definitivo (font, logo, colori, layout) — nessun codice coinvolto nello stile
- Il codice si limita a riempire un template esistente con i dati, non costruisce il documento da zero

### 4.2 Struttura del template

1. **Frontespizio**: intestazione cliente, logo, data, luogo, introduzione — segnaposto singolo `${introduzione}` che pesca il testo standard dal tipo di preventivo scelto (editabile)
2. **Schede impianto ripetute**: non una semplice tabella, ma un **blocco ripetuto** (titolo + descrizione + box prezzo) per ogni impianto nel preventivo → uso di **`cloneBlock`** di PHPWord, delimitato nel template da `${#impianto}` ... `${/impianto}`. Daniela disegna in Word una sola scheda-esempio, il sistema la ripete per ogni impianto
3. **Condizioni di fornitura**: segnaposto `${condizioni}`, valorizzato dal template scelto in `condizioni_fornitura` (o versione editata)

### 4.3 Segnaposto — convenzioni

- Dati singoli: `${nome_variabile}` (es. `${nome_cliente}`, `${prezzo_totale}`)
- Blocchi ripetuti: `${#blocco}` ... `${/blocco}` (impianti nel preventivo)
- Righe di tabella ripetute (se servissero altrove, es. riepilogo prezzi tabellare): `cloneRow` — una riga esempio in Word, ripetuta automaticamente

### 4.4 Pipeline di generazione

1. Template master `.docx` salvato in `storage/templates/` (cambia raramente, solo quando si aggiorna lo stile)
2. Il codice compila il template coi dati reali (cliente, impianti scelti, prezzi applicati, condizioni) → nuovo `.docx` compilato
3. Conversione `.docx` → PDF (LibreOffice headless via CLI, o alternativa tipo Gotenberg)
4. Il **PDF è lo snapshot immutabile** del preventivo: una volta generato/accettato non deve più cambiare anche se la scheda tecnica viene aggiornata in seguito

---

## 5. Storage e accesso

- PDF salvati su filesystem server, path strutturato indicativo: `storage/preventivi/{cliente_id}/{anno}/{preventivo_id}.pdf`
- Nel DB si salva solo il **path**, non il blob
- Nella scheda cliente (show view), elenco automatico dei preventivi associati con link diretto al PDF — nessuna gestione documentale complessa necessaria per questo, è una semplice query su FK cliente_id già presente nel modello
- Se in futuro lo spazio disco diventasse un problema: valutare storage separato tipo object storage self-hosted (es. MinIO), mantenendo solo il path in DB — **non è un problema di questa milestone**
- Sicurezza server (HTTPS, hardening, backup, permessi): **fuori scope di questa spec**, da affrontare in sede separata con i sistemisti

---

## 6. Punti aperti / da decidere prima o durante l'implementazione

- [ ] Naming definitivo delle tabelle e dei campi
- [ ] Stato/workflow del preventivo (bozza → inviato → accettato/rifiutato) e transizioni ammesse
- [ ] Dettaglio esatto del layout del template Word (a cura di Daniela in Word)
- [ ] Se e come gestire più condizioni di fornitura contemporaneamente nello stesso preventivo (es. acconti differenti per impianto diverso) — al momento si presume condizioni uniche per preventivo
- [ ] Meccanismo di generazione automatica cantiere → impianti cliente all'accettazione (dettaglio da specificare)
- [ ] Collegamento interventi ↔ impianto cliente (rimandato)
- [ ] Gestione documentale schede di sicurezza prodotti chimici (rimandato a Magazzino avanzato)

---

## 7. Fuori scope per questa milestone

- Route optimization, resource timeline
- Collegamento interventi-impianto
- Gestione documentale avanzata (versioning, permessi granulari, ricerca full-text)
- Schede di sicurezza prodotti chimici
- Audit di sicurezza infrastrutturale del server
