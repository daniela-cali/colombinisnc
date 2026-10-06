# Backlog

Punti aperti, idee rimandate e rifiniture: quello che non è ancora una spec né un lavoro in
corso. Sostituisce `docs/spec/idee.txt` (appunti della riunione del 10/07/2026, tutti chiusi
fra v0.24.4 e v0.24.26) e le note sparse nelle memorie di Claude.

Quando un punto diventa lavoro vero si fa il brainstorming, poi eventualmente lo spec in
`docs/spec/`, e qui resta solo il rimando. Quando è chiuso si cancella: la storia sta nel
`CHANGELOG.md`.

La review del 16/08/2026 ha un suo indice con la colonna Stato,
`docs/review/2026-08-16-review-progetto.md`: i suoi punti non si ricopiano qui, salvo quelli
che hanno un contesto in più. Aperti al 05/10/2026: 7 (SMTP), 10 (privacy assenze), 11 (Font
Awesome), 13, 14, 15, 17, 18 (automazione del backup), 19, 19-bis, 20 (checklist di go-live).

## Infrastruttura

- **Aggiornamento dello stack.** Le minori e le patch sono fatte in v0.35.1, AdminLTE 4.10 in
  v0.35.2. L'ordine deciso nel brainstorming del 05/10/2026 per il resto:
  - **DataTables 2 → 3** (con Responsive 4 e RowGroup 2) e **FullCalendar 6 → 7**: prima si
    leggono le guide alla migrazione e si stima, file per file, cosa si rompe. Poi si decide
    una alla volta. Mezza giornata: si fa prima del go-live; se tocca mezzo `calendario.js`,
    si rimanda finché non porta un vantaggio vero.
  - **PHPUnit 10 → 13**: lasciato com'è. Ci sono solo i tre test d'esempio dello scaffolding.
    Se ne riparla se e quando si decide di scrivere test, che la v1.0.0 prevede.
- **SSH con password ancora attivo sul server** (sistemista).
  `/etc/ssh/sshd_config.d/50-cloud-init.conf` contiene `PasswordAuthentication yes`, che
  prevale sul `no` di `sshd_config`, e i bot ci provano di continuo. Gli esposti sono gli
  utenti con `sudo` (`nhildra`, `whitedragon`); `colombini-dev` non ha password. Non si
  disattiva in blocco perché `whitedragon` entra con password. Le strade: un blocco `Match`,
  fail2ban, oppure la chiave anche per lui.
- **Automazione del backup** (sistemista, punto 18 della review): cron notturno, rotazione dei
  dump, copia fuori dalla VPS. Il backup manuale è già non interattivo, vedi `docs/deploy.md`.
- **Cron del batch abbonamenti scaduti**: `php spark batch:abbonamenti-scaduti --force` è pronto per
  girare da cron (`docs/spec/abbonamenti_scaduti_batch_spec.md`), ma il cron non esiste ancora.
- Utenti MariaDB separati admin/app e un pool PHP-FPM dedicato anche per la produzione:
  rimasti fuori scope nella spec dell'ambiente.

## Funzionalità

- **Menù senza sottomenu degli interventi.** Le parole di Daniela: «il menù non deve più
  filtrare creando sottomenù». L'idea emersa il 28/08/2026 era una pagina unica con una tendina
  Categoria o Tipo al posto delle tre voci generale/piscine/addolcitori. È stata ritirata
  insieme al server-side processing (punto successivo), ma non è stata scartata in sé.
- **Volume degli interventi.** A regime saranno 7-8.000 l'anno. La DataTable client-side
  diventa fastidiosa sopra le ~3.000 righe e inusabile su mobile sopra le ~5.000. Il problema
  arriverà con lo storico accumulato, quindi dal 2028 e non nel 2027. Valutato e rimandato il
  27/08/2026, con questo criterio: «poi se non andrà bene si modificherà».
  - Quando si riprende, la strada è il server-side processing. DataTables spedisce da sé i
    valori di `column().search()`, quindi `data-pill-filtri` e `search-bar.js` restano intatti.
  - I `render` delle colonne vanno in `public/js/interventi-tabella.js`, con i dati che
    vengono da PHP passati come `data-config` sulla `<table>`: «non voglio tutto nella view».
  - Scartata la "finestra operativa" con i soli interventi aperti: quando si accettano gli
    abbonamenti di stagione nascono insieme migliaia di righe `da_pianificare`.
- **Sistema `from` unificato** (punto 17 della review). Il ritorno alla pagina di origine non
  è applicato in modo coerente. Un caso noto: i mini-form del diario in
  `operativo/interventi/edit.php` sovrascrivono `from` con un link fisso a `#sec-diario`, e
  chi arrivava dalla scheda cliente perde il contesto.
  - Prima di decidere, mappare tutti gli usi con un `grep` su `name="from"`, `getGet('from')`
    e `getPost('from')`.
  - Poi un brainstorming fra tre strade: fix mirati, meccanismo unico, oppure una pila delle
    pagine visitate (idea di Daniela, da pensare).
  - Tocca tutte le pagine: probabilmente serve un branch.
- **Clienti potenziali.** La colonna `clienti.potenziale` esiste dalla v0.30.0, ma non ha
  interfaccia e non è in `$allowedFields`. È un flag e non un prefisso nel codice: decisione 10
  di `docs/spec/numeratori_atomici_spec.md`. Le domande ancora aperte:
  - basta un flag, o serve distinguere chi è ancora in ballo da chi ha detto no (`VARCHAR`
    `potenziale`/`cliente`/`perso`)?
  - cosa succede alla scheda di chi rifiuta?
  - come si mostra: badge in elenco, filtro nella tendina?
  - va escluso dalle tendine degli interventi?
  - come si converte in cliente?
- **Referente per le comunicazioni del cliente**, per esempio l'amministratore di condominio.
  È distinto dal referente di cantiere, che è operativo («chi chiamo per entrare»): questo
  serve per la corrispondenza formale. Oggi c'è solo il campo libero `clienti.contatti`.
  Prima di progettare, capire se c'è già un caso d'uso che si rompe.
- **Card in dashboard "scadenze entro la settimana"**: la vista d'insieme non urgente, nata
  separando il punto 7.R della riunione. La parte urgente è la barra "Attenzione" del
  calendario (v0.24.22). Nessuna spec ancora.
- **Import clienti, fase 2**: autocomplete della ragione sociale nel form Nuovo cliente,
  riusando la rotta di precompilazione `?adhoc=<id>`. Da valutare dopo aver usato l'elenco sul
  campo. Il primo import reale in produzione, con l'export definitivo, non è ancora fatto.

## Calendario

- **Barra "Attenzione" ferma dopo un trascinamento.** `eventDrop` ed `eventReceive` in
  `calendario.js` salvano la nuova data ma non ricalcolano i contatori, che si aggiornano solo
  al ricaricamento. Per aggiornarli subito servirebbero un endpoint che ricalcoli
  `scadenzePerMotivo`, il ridisegno della barra e la reinizializzazione di tooltip e collapse.
- **Scorrimento automatico durante il trascinamento.** Portando una card vicino al bordo della
  griglia, si dovrebbe passare da soli al giorno o alla settimana accanto. FullCalendar non lo
  fa: il suo auto-scroll è solo verticale e qui non si applica, perché c'è `height: 'auto'`.
  Va costruito a mano: si traccia il puntatore durante il drag e si chiama
  `calendar.prev()`/`next()` con un debounce.
- **Vista Giorno su mobile, eventi sovrapposti illeggibili.** Con tre eventi nello stesso
  orario le colonne sono troppo strette per un iPhone. Da provare `slotEventOverlap`,
  `eventMaxStack`, una larghezza minima, oppure la vista elenco sugli schermi stretti.

## Materiale rimasto sul PC di casa

Non è nel repository e non è sul server.

- **Seeder demo di cantieri e interventi**, in `git stash` nel clone di casa (`stash@{0}`):
  `CantieriStoricoZonaSeeder`, `InterventiDemoSeeder`, `PuliziaCantieriExtraSeeder`. Nascono
  dall'export del Google Calendar aziendale (Downloads, `colombinisnc@gmail.com.ics`) e dai
  file di testo annotati a mano da Daniela. Il seeder si scrive insieme, partendo dalle sue
  annotazioni: nessuna classificazione automatica.
- **Ricostruzione dello storico dal calendario** (`docs/storico_ics/`, mai committata):
  - `andrea-salati.md` va corretto: il vero cliente delle piscine A1/A2 è Paolo Rossi. Chiedere
    conferma prima di riscriverlo.
  - `zona-ceriale-lavori.md` aspetta la revisione di Daniela.
  - Poi le altre zone.
  - L'import nel database si fa solo dopo il go-live.

## Valutati e lasciati così

- Il controllo `+` di DataTables sulle colonne-link a volte apre la scheda con un doppio click
  millimetrico. È accettato: non riproporlo.
