# Spec — Luogo e referente sul cantiere

> **Stato: pronta per l'implementazione.** Concordata il 25.07.2026, questione di
> fatturazione (§ "Fatturazione cantieri con intermediario") risolta il 25.07.2026.
> Estesa lo stesso giorno con la geolocalizzazione del cantiere.

## Contesto / problema

Durante l'analisi dello storico interventi ricostruito dal calendario Google
(`docs/storico_ics/`) sono emersi due limiti della tabella `cantieri`:

1. **Il cantiere non ha un luogo proprio.** Eredita l'indirizzo del cliente via
   `cliente_id`, ma nei dati reali non sempre coincidono:
   - Andrea Salati è un **intermediario**: segue piscine a Laigueglia/Andora per conto di
     proprietari terzi (ignoti). Il suo indirizzo anagrafico non c'entra col cantiere.
   - Berrino Scofferi ha lavori su **due proprietà diverse** ("casa Mulino" e "casa
     Scofferi Indiana") — un solo indirizzo cliente non basta.
2. **Il referente operativo non ha un campo.** È l'informazione più usata nelle note del
   calendario storico ("avvisare Manuel appena si procede", "chiamare Simone Brasca
   328...."): la persona da contattare per QUEL cantiere, che spesso non è il cliente
   (custode, amministratore, impresa edile, intermediario).

## Soluzione concordata

Colonne nullable su `cantieri`, nessuna tabella nuova:

| Campo | Tipo | Significato |
|---|---|---|
| `indirizzo` | VARCHAR(255) NULL | Luogo del cantiere. **NULL = vale l'indirizzo del cliente** (caso normale) |
| `citta` | VARCHAR(100) NULL | Città del cantiere, stesso fallback |
| `referente` | VARCHAR(150) NULL | Testo libero: nome, ruolo e telefono ("Manuel (custode) 339 1234567") |
| `lat` / `lng` | DECIMAL(10,7) NULL | Posizione geografica del cantiere. **NULL = vale la posizione del cliente** |
| `geocoded_at` | DATETIME NULL | Timestamp dell'ultima geocodifica riuscita o correzione manuale |
| `geocodifica_fallita` | TINYINT default 0 | Stessa semantica del campo omonimo su `clienti` |

In più, nuova costante tipo cantiere in `CantieriModel` (nessun ALTER, il campo è già
VARCHAR): `TIPO_MANUTENZIONE_STRAORDINARIA = 'manutenzione_straordinaria'` con label.
Motivo: dallo storico ICS metà dei cantieri reali non sono né nuova costruzione né
ristrutturazione (sostituzione filtro, ricerca perdita, rifacimento telo).

### Perché così

- Il fallback su NULL mantiene invariato il caso comune (condomini, privati: cantiere a
  casa del cliente) — zero attrito nel form.
- Il referente come testo libero rispecchia l'uso reale: serve rispondere a "chi chiamo?",
  le figure secondarie vivono bene nel diario `cantieri_note`.
- **lat/lng inclusi fin da subito** (ripensamento rispetto alla prima versione di questa
  spec, che li rimandava a v1.0.0/ottimizzazione percorsi): non è più solo una feature
  futura, è il fix di un bug reale già presente oggi — vedi § "Geolocalizzazione del
  cantiere". Il pattern (Nominatim via `public/js/geocoding.js`, mappa Leaflet,
  correzione manuale del pin) esiste già identico su `clienti`
  (`docs/spec/mappa_cliente_spec.md`), quindi il costo di replicarlo è basso: si riusa
  lo stesso JS, non si scrive geocoding da zero.

## Geolocalizzazione del cantiere

**Il bug che lo motiva:** `InterventiModel::agendaTecnicoPeriodo()` (query dietro la
dashboard mobile del tecnico) prende `indirizzo, citta, cap, lat, lng` **solo da
`clienti`**, ignorando `cantiere_id`. Per un intervento su un cantiere con luogo diverso
dal cliente (Salati, Berrino Scofferi "casa Mulino"), il pulsante "Apri in Google Maps"
nel modal mappa di `dashboard/tecnico.php` manda oggi il tecnico all'indirizzo
**sbagliato** (quello del cliente, non del cantiere). Non è un miglioramento rimandabile,
è una correzione di un comportamento scorretto già in produzione (dev) sui dati reali.

**Soluzione:** stesso pattern di `mappa_cliente_spec.md`, applicato a `cantieri`:
- form nuovo/edit cantiere: bottone geocoder (riuso diretto di `[data-geocoder]` /
  `public/js/geocoding.js`, stessa logica indirizzo+CAP+città+nazione → hidden fields);
- scheda cantiere: sezione mappa con la stessa cascata di centraggio a 3 livelli
  (posizione propria → posizione approssimata sulla città, non salvata → fallback sede
  aziendale), bottone "Correggi posizione" identico a quello cliente;
- nuovo endpoint `CantieriController::aggiornaPosizione(int $id)`, stesso schema di
  `ClientiController::aggiornaPosizione()`;
- **fix di `agendaTecnicoPeriodo()`**: join anche `cantieri` (via `interventi.cantiere_id`,
  nullable) e `COALESCE(cantieri.lat, clienti.lat)` / stesso per `lng`, `indirizzo`,
  `citta` — così il modal mappa e "Apri in Google Maps" in `dashboard/tecnico.php`
  puntano al posto giusto quando l'intervento ha un cantiere con posizione propria.

**Fuori scope anche qui:** nessuna integrazione con l'ottimizzazione percorsi
OpenRouteService (v1.0.0) — questa parte riguarda solo mostrare/correggere un singolo
pin, non calcolare un giro multi-tappa. Nessun `distanza_sede`/haversine su `cantieri`
per ora: è un concetto nato per il calcolo zona/distanza del cliente, non è detto serva
altrettanto per un cantiere — si aggiunge se emerge un caso d'uso concreto.

## Alternative scartate

- **Tabella `impianti`** (il placeholder `interventi.impianto_id` v0.11.0 esiste già):
  modello giusto a lungo termine (multi-proprietà, abbonamenti per sito) ma feature
  grossa — CRUD nuova, tutti i flussi da toccare — sproporzionata rispetto al bisogno
  attuale (2-3 cantieri su 21 con luogo diverso dal cliente). Le colonne su `cantieri`
  non la precludono: se nascerà `impianti`, i campi migrano lì e si droppano.
- **Tabella `cantieri_referenti`** (referenti multipli con ruolo): i casi multipli
  esistono (Ibica: amministratrice + custode + impresa) ma l'80% dei cantieri ha un solo
  nome; passare da campo a tabella in futuro è una migrazione banale.
- **Anagrafica contatti globale riusabile** (Domenico, geom. Sasso ricorrenti su più
  cantieri): sovraingegneria finché non c'è un caso d'uso concreto.
- **lat/lng + geocoding subito**: valutato inizialmente rimandabile a v1.0.0, poi
  reintrodotto nella stessa spec — vedi § "Geolocalizzazione del cantiere".

## Fatturazione cantieri con intermediario — risolta il 25.07.2026

Confermato in azienda: per i cantieri tipo Salati la fattura va **al proprietario
finale**, non a Salati. Salati è un intermediario che "presenta" il cliente — e la sua
identità è **nota fin dall'inizio** (non si scopre solo più avanti), quindi si può aprire
il cliente proprietario in anagrafica già al primo contatto:

- `cliente_id` = proprietario finale (serve comunque al contabile per fatturare);
- cantiere intestato a lui, con `indirizzo`/`citta` propri se diversi dall'anagrafica;
- `referente` = "Andrea Salati (intermediario) 339...".

Questa è ora la prassi **normale**, non più una prassi ponte in attesa di conferma.
La funzione "cambia intestatario cantiere" (re-intestare cantiere + interventi collegati
da Salati al proprietario) **non serve nel caso normale** — resta annotata come eccezione
per l'unico caso in cui l'identità del proprietario non fosse nota al momento
dell'apertura, da costruire solo se si presenta davvero.

## Modifiche previste, file per file (quando si implementerà)

1. **Migration** `AddLuogoReferenteToCantieri` — `addColumn` di `indirizzo`, `citta`,
   `referente`, `lat`, `lng`, `geocoded_at`, `geocodifica_fallita` su `cantieri` (dopo
   `titolo`), niente indici.
2. **`app/Models/CantieriModel.php`** — i nuovi campi in `$allowedFields`; costante
   `TIPO_MANUTENZIONE_STRAORDINARIA` + voce in `TIPI_LABEL`.
3. **View form cantiere** (nuovo/edit) — campi Indirizzo, Città (placeholder informativo
   sul fallback: "Indirizzo/Città cantiere, se diverso da anagrafica cliente"), Referente,
   bottone geocoder + hidden lat/lng (riuso `public/js/geocoding.js`).
4. **View scheda cantiere** — mostrare luogo (con fallback esplicito all'indirizzo
   cliente quando NULL), referente, sezione mappa con cascata di centraggio e bottone
   "Correggi posizione". Lo script Leaflet è stato estratto in
   `public/js/mappa-posizione.js` (contenitore generico `#mappa-posizione`), riusato
   identico da `clienti/show.php` e `cantieri/show.php` — nessuna logica specifica
   dell'entità, riceve tutto via `data-*`.
5. **Controller cantieri** — nessuna logica nuova per i campi testo (passano via
   `getPost()`, normalizzazioni nel model); nuovo metodo `aggiornaPosizione(int $id)`
   per il salvataggio manuale del pin.
6. **`app/Config/Routes.php`** — nuova rotta `POST cantieri/(:num)/posizione` nel gruppo
   esistente.
7. **`app/Models/InterventiModel.php`** — fix `agendaTecnicoPeriodo()`: join `cantieri`,
   `COALESCE(cantieri.lat, clienti.lat)` (e lng/indirizzo/citta) per usare la posizione
   del cantiere quando presente.
8. **`app/Views/dashboard/tecnico.php`** — nessuna modifica di logica, riceve già
   lat/lng dalla query corretta.
9. **`public/css/custom.css`** — regola `#mappa-posizione` (rinominata da `#mappaCliente`,
   ora condivisa con la scheda cliente).
10. **`docs/`** — schema DB HTML e log modifiche DB nella stessa commit della migration.

## Fuori scope

- Import dello storico ICS in `cantieri`/`cantieri_note` — va fatto **dopo il go-live**
  (il DB dev sarà svuotato); nel frattempo il deposito è `docs/storico_ics/`.
- Tabella `impianti`.
- Referenti multipli strutturati.
- Funzione "cambia intestatario cantiere" (solo annotata come eccezione rara).
- `distanza_sede`/haversine e integrazione OpenRouteService per i cantieri (v1.0.0).
- Referente/amministratore per comunicazioni formali a livello cliente (tema distinto,
  appuntato separatamente).
