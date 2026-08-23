# Review del progetto — 16 agosto 2026

Revisione completa del codice alla **v0.27.1**, condotta leggendo controller, model, view,
configurazione, migrazioni e asset frontend, con verifica diretta sul database di sviluppo.

Ogni voce indica **file e riga**, perché è un problema, e come si corregge. Le voci sono
ordinate per priorità: le prime tre meritano attenzione prima del go-live, le ultime sono
inviti a fare meglio quando capita.

> **Nota di metodo.** Tutto ciò che segue è stato verificato sul codice, non dedotto. Dove
> resta un'incertezza — perché dipende dall'ambiente di produzione o da una scelta che spetta
> a te — è scritto esplicitamente. Nessuna modifica è stata applicata: questo documento è
> solo una lista di proposte.

> **Stato dei lavori — aggiornato al 23 agosto 2026.** La colonna *Stato* del sommario dice
> dove sta ogni voce. I punti 5, 6, 12 e 16 sono confluiti in
> `docs/spec/gestione_account_spec.md`, che li ha affrontati insieme perché hanno la stessa causa
> — la logica degli account duplicata in tre controller — e perché durante la stesura è emerso
> un buco di sicurezza che questa review non aveva individuato: un utente `ufficio` poteva
> promuoversi ad amministratore dalla scheda dipendente (§1.1 dello spec). Tutti e quattro sono
> **chiusi nella v0.28.0**.

## Sommario

| # | Titolo | Tipo | Priorità | Stato |
|---|---|---|---|---|
| 1 | Protezione CSRF disattivata | sicurezza | **alta** | ✅ chiuso in v0.27.2 |
| 2 | Upload del logo senza validazione, estensione presa dal client | sicurezza | **alta** | ✅ chiuso in v0.27.3 |
| 3 | `InterventiController::update()` non verifica il tecnico proprietario | sicurezza | **alta** | ✅ chiuso in v0.27.3 |
| 4 | `ClientiModel::generaCodice()` si rompe a INT-999 e non è atomico | bug | media | aperto |
| 5 | Un admin può eliminare o declassare sé stesso | sicurezza | media | ✅ chiuso in v0.28.0 |
| 6 | Creazione dipendente: email non verificata, nessuna transazione | bug | media | ✅ chiuso in v0.28.0 |
| 7 | Magic link attivo ma posta non configurata | bug | media | ◐ mitigato in v0.27.2 (link rimosso); SMTP da configurare |
| 8 | `CURDATE()` e fuso orario del server MySQL | bug latente | media | aperto |
| 9 | Periodi abbonamento sostituiti senza transazione | bug | media | ✅ chiuso in v0.28.1 |
| 10 | Assenze per malattia visibili a tutti | privacy | media | aperto — richiede una decisione |
| 11 | Font Awesome: ~570 KB per un solo utilizzo | performance | media | aperto |
| 12 | Due rotte puntano a metodi inesistenti | pulizia | bassa | ✅ chiuso in v0.28.0 |
| 13 | Accesso ai record non verificato in alcune azioni minori | sicurezza | bassa | aperto |
| 14 | Mass assignment: `codice` e `user_id` scrivibili dal POST | sicurezza | bassa | aperto |
| 15 | Errori non gestiti su record inesistenti | bug | bassa | aperto |
| 16 | `defaultGroup` è `ufficio` | sicurezza | bassa | ✅ chiuso in v0.28.0 |
| 17 | Il sistema `from` è ripetuto in venti punti | manutenibilità | bassa | aperto |
| 18 | Nessun backup del database | processo | media | aperto — prima del go-live |
| 19 | jQuery 4.0.0 da verificare | rischio | bassa | aperto |
| 19-bis | Una deroga alla convenzione `cliente_denominazione` | coerenza | bassa | aperto |
| 20 | Checklist di go-live | processo | — | da eseguire al deploy |

---

## 1. Protezione CSRF disattivata

> ✅ **Chiuso in v0.27.2.** Filtro `csrf` attivo nei globali, `regenerate` a `false` e
> `tokenRandomize` a `true`. Collaudato su tutte le POST reali dell'applicazione.

**Dove:** `app/Config/Filters.php:78`

Il filtro `csrf` è commentato nei filtri globali e non è applicato a nessuna rotta. Il
risultato è che **il token CSRF viene generato ma non verificato mai**.

Il paradosso è che tutto il resto dell'infrastruttura è già a posto e funzionante:

- 63 `csrf_field()` nelle view, in ogni form;
- `public/js/calendario.js` invia l'header `X-CSRF-TOKEN` e riaggiorna l'hash dalla risposta
  (righe 276, 284, 404, 409, 639, 643);
- i controller AJAX restituiscono `'csrf' => csrf_hash()` per il rinnovo del token.

È lavoro già fatto che oggi non protegge nulla. Nel changelog della v0.26.0 c'è scritto che
alcune azioni sono state spostate da GET a POST *perché* erano prive di protezione CSRF: la
protezione che si dava per acquisita non c'è.

`Config\Cookie::$samesite` è `Lax`, il che riduce il rischio pratico — i browser moderni non
inviano il cookie di sessione su una POST cross-site — ma è una mitigazione del browser, non
una difesa dell'applicazione, e non copre attacchi provenienti dallo stesso sito.

**Correzione:** decommentare `'csrf'` in `$globals['before']`. Va poi provato ogni form e
ogni chiamata AJAX: nessun file risulta avere più form che `csrf_field()`, e nessun form usa
`form_open()`, quindi la copertura sembra completa, ma è il tipo di cosa che si verifica
cliccando. Attenzione ai punti in cui una POST è generata da JS senza header (non ne ho
trovati, ma vanno confermati sul campo).

## 2. Upload del logo senza validazione, estensione presa dal client

> ✅ **Chiuso in v0.27.3.** Validazione `uploaded|is_image|mime_in|max_size`, estensione
> derivata dal MIME rilevato dal server, SVG escluso di proposito.

**Dove:** `app/Controllers/Impostazioni/GeneraleController.php:53-71`

```
$filename = 'logo_azienda.' . $logo->getClientExtension();
$logo->move($dir, $filename, true);
```

Tre problemi che si sommano:

1. **Nessuna validazione**: non c'è `is_image`, né `mime_in`, né `max_size`. Qualunque file
   viene accettato.
2. **`getClientExtension()` è l'estensione dichiarata dal browser**, cioè dall'utente. Basta
   rinominare un file per farlo passare.
3. **La destinazione è `public/uploads/`**, servita direttamente dal web server.

Messi insieme: si può caricare `logo.php` e ottenere un file PHP eseguibile a
`https://.../uploads/logo_azienda.php`. È esecuzione di codice remoto.

L'attenuante è che serve il permesso `impostazioni.manage`, quindi admin o developer — non
un tecnico. Non è quindi un buco aperto a chiunque, ma è una regola di base violata, e in
combinazione con il punto 1 basterebbe ingannare un admin.

Vale la pena notare il contrasto con `ImportClientiController::analizza()`, che fa tutto
correttamente: valida estensione e dimensione, salva in `WRITEPATH` (fuori dalla webroot),
genera il nome del file e pulisce dopo l'uso. L'upload del logo è l'eccezione, non la regola
del progetto.

**Correzione:** validare con `uploaded[sede_logo]|is_image[sede_logo]|mime_in[sede_logo,image/png,image/jpeg,image/webp]|max_size[sede_logo,2048]`
e costruire il nome dall'estensione derivata dal MIME (`getExtension()`), non da quella del
client. In alternativa, salvare con `getRandomName()`.

## 3. `InterventiController::update()` non verifica il tecnico proprietario

> ✅ **Chiuso in v0.27.3.** `accessoConsentito()` applicato in `update()` prima della
> validazione, leggendo il tecnico dal record salvato e non dal POST.

**Dove:** `app/Controllers/Operativo/InterventiController.php:324-364`

Tutte le azioni sull'intervento chiamano `accessoConsentito()` prima di agire — `show()`
(riga 204), `chiudi()` (223), `edit()` (290), `inizia()` (380), `annulla()` (414) — **tranne
`update()`**, che passa direttamente dalla validazione al salvataggio.

Conseguenza concreta: un tecnico a cui `edit()` nega l'accesso può comunque inviare una POST
a `operativo/interventi/<id>/update` e modificare l'intervento di un collega. Il form gli è
negato, il salvataggio no.

È esattamente la classe di problema chiusa nella v0.24.31 ("restrizioni server-side per il
ruolo tecnico") e nella v0.25.1: il controllo è stato applicato metodo per metodo e questo è
rimasto scoperto.

**Correzione:** replicare in `update()` il blocco già presente in `edit()`, subito dopo il
recupero del record. Tre righe.

Nota di contorno: `pianifica()` e `annullaPianificazione()` non fanno il controllo, ma lì è
una scelta consapevole e documentata (il calendario è condiviso fra i tecnici). `delete()`
non lo fa, ma è protetto dal permesso `interventi.elimina` che i tecnici non hanno: coperto.

## 4. `ClientiModel::generaCodice()` si rompe a INT-999 e non è atomico

**Dove:** `app/Models/ClientiModel.php:210-223`

```
->like('codice', 'INT-', 'after')->orderBy('codice', 'DESC')->first();
$numero = (int) substr($row['codice'], 4);
return 'INT-' . str_pad($numero + 1, 3, '0', STR_PAD_LEFT);
```

Tre difetti, in ordine di gravità:

1. **Il padding è a 3 cifre e l'ordinamento è alfabetico.** Dopo `INT-999` il codice
   successivo è `INT-1000`, che ordinato come stringa viene *prima* di `INT-999`: il massimo
   trovato resterà per sempre `INT-999` e la funzione restituirà sempre `INT-1000`. Siccome
   `clienti.codice` ha un vincolo `UNIQUE` (verificato sul database), dal cliente 1001 in poi
   il salvataggio fallisce con un errore SQL grezzo.
2. **Regredisce dopo una cancellazione**: eliminato l'ultimo cliente, il codice viene
   riassegnato a uno nuovo.
3. **Non è atomico**: due salvataggi simultanei ottengono lo stesso codice, e uno dei due
   fallisce sul vincolo `UNIQUE`.

La cosa notevole è che il progetto conosce già la soluzione: `InterventiModel::generaCodice()`
(riga 147) usa un contatore in `settings` con `SELECT … FOR UPDATE`, e il suo docblock spiega
per esteso perché `MAX(codice)` non va bene. Quel ragionamento non è stato riportato sui
clienti.

**Correzione:** la più economica è portare il padding a 4-5 cifre e ordinare per la parte
numerica (`ORDER BY CAST(SUBSTRING(codice, 5) AS UNSIGNED) DESC`), il che risolve subito il
punto 1. La corretta è riusare lo stesso contatore atomico degli interventi, con
`class = 'Clienti'`: una decina di righe, e i due generatori tornano a somigliarsi.

Quanto è urgente: i clienti importati dall'anagrafica storica conservano il codice contabile,
quindi la numerazione `INT-` cresce solo con i clienti nuovi non fiscali. Non è imminente, ma
è una bomba a orologeria silenziosa che esplode con un errore incomprensibile.

## 5. Un admin può eliminare o declassare sé stesso

> ✅ **Chiuso in v0.28.0** — `docs/spec/gestione_account_spec.md` (§1.2 e §5). Lo spec estende il
> perimetro: gli stessi difetti sono anche in `PersonaleController::update()` e `delete()`,
> che questa voce non aveva considerato. Il conteggio degli amministratori superstiti qui
> proposto si è rivelato non necessario — vedi §5 dello spec.

**Dove:** `app/Controllers/Impostazioni/UtentiController.php:52-116`

Nessuna delle due azioni ha una guardia:

- `updateUtenteApp()` accetta un elenco di gruppi qualsiasi, anche se l'utente sta togliendo
  `admin` a sé stesso, o all'unico amministratore rimasto;
- `deleteUtenteApp()` non confronta `$id` con l'utente collegato: si può cancellare il
  proprio account mentre lo si sta usando.

Nel caso peggiore — unico admin che si declassa o si elimina — nessuno può più entrare in
Impostazioni, e si recupera solo agendo sul database o rieseguendo il seeder.

**Correzione:** due controlli. Rifiutare l'eliminazione quando `$id === user_id()`, e
rifiutare il salvataggio quando l'operazione lascerebbe zero utenti nel gruppo `admin`. Il
messaggio all'utente vale più del controllo stesso: "non puoi rimuovere l'ultimo
amministratore".

## 6. Creazione dipendente: email non verificata, nessuna transazione

> ✅ **Chiuso in v0.28.0** — `docs/spec/gestione_account_spec.md` (§1.3 e §3). La transazione sta in
> `UserModel::creaAccount()`, insieme al resto della logica di creazione.

**Dove:** `app/Controllers/Anagrafiche/PersonaleController.php:84-138`

Le regole validano `username` con `is_unique[users.username]` ma **l'email non ha
`is_unique`**. Shield ha un vincolo `UNIQUE (type, secret)` su `auth_identities` (verificato
sul database), quindi inserire un dipendente con un'email già usata non crea un doppione: fa
scoppiare un'eccezione del database, con la schermata di errore al posto di un messaggio
comprensibile.

Peggio, la sequenza non è in transazione:

```
$users->save($user);              // 1. utente creato
$user->createEmailIdentity([...]); // 2. ← qui fallisce
… addGroup …                       // 3. mai eseguito
(new PersonaleModel())->insert()   // 4. mai eseguito
```

Se il passo 2 fallisce resta **un utente Shield senza identità, senza gruppi e senza scheda
dipendente**, che occupa lo username scelto: al secondo tentativo con lo stesso username
fallisce anche `is_unique`, e l'utente si trova bloccato senza capire perché.

**Correzione:** aggiungere `is_unique[auth_identities.secret]` alla regola dell'email e
racchiudere i quattro passi in una transazione (`$db->transStart()` / `transComplete()`).

## 7. Magic link attivo ma posta non configurata

> ◐ **Mitigato in v0.27.2**: `allowMagicLinkLogins` è ora `false`, quindi la pagina di login
> non mostra più una funzione che non funziona. La configurazione SMTP resta da fare prima
> del go-live: finché non c'è, il recupero password è a carico di un amministratore.

**Dove:** `app/Config/Auth.php:184` e `app/Config/Email.php:9-31`

`allowMagicLinkLogins` è `true`, quindi la pagina di login mostra il collegamento per
accedere via email. Ma `Config\Email` è vuoto: `fromEmail` e `fromName` sono stringhe vuote,
il protocollo è `mail`, nessun SMTP configurato, e in `.env` non c'è nessuna chiave di posta.

Chi dimentica la password clicca su quel link e ottiene un errore, oppure — su un server con
sendmail attivo — un messaggio con mittente vuoto che finisce quasi sicuramente nello spam.
L'unico recupero reale è chiedere a un amministratore.

**Correzione:** o si configura l'invio (SMTP in `.env`, mittente su un dominio con posta
attiva), o si mette `allowMagicLinkLogins = false` per non mostrare una funzione che non
funziona. La seconda è una riga e si può fare oggi; la prima è la soluzione vera, da fare
prima del go-live.

## 8. `CURDATE()` e fuso orario del server MySQL

**Dove:** `app/Models/InterventiModel.php:305-311`, `app/Models/AbbonamentiModel.php:113, 134, 158, 203`

Le query che decidono cosa è "in ritardo", "scaduto" o "in arrivo" usano `CURDATE()`, cioè la
data **del server MySQL**. L'applicazione invece vive in `Europe/Rome`
(`Config\App::$appTimezone`).

In sviluppo non si nota, perché MySQL gira sulla stessa macchina con lo stesso fuso. Su una
VPS il default è spesso UTC: in quel caso, fra mezzanotte e le 2 di notte ora italiana,
`CURDATE()` restituisce ancora il giorno precedente, e la barra "Attenzione" del calendario e
lo stato calcolato degli abbonamenti ragionano su un giorno sbagliato.

Il batch notturno non è coinvolto: `AbbonamentiModel::leggiScaduti()` (riga 181) confronta con
`date('Y-m-d')` calcolato in PHP, che è il modo giusto. È un'altra incoerenza fra due punti
del progetto che risolvono lo stesso problema in modi diversi — qui per fortuna nella
direzione buona.

**Correzione:** la più semplice è impostare il fuso sulla connessione, aggiungendo alla
configurazione del database `SET time_zone = '+01:00'` (o `'Europe/Rome'` se le tabelle dei
fusi sono caricate su MySQL). L'alternativa più esplicita è passare la data calcolata in PHP
come parametro al posto di `CURDATE()`. Da verificare comunque sul server di produzione con
un semplice `SELECT NOW(), CURDATE();` appena disponibile.

## 9. Periodi abbonamento sostituiti senza transazione

> ✅ **Chiuso in v0.28.1** — le tre operazioni sono in `transStart()` / `transComplete()`.
> Durante il lavoro è emersa una seconda via allo stesso danno, che la transazione da sola non
> copriva: i periodi non erano validati lato server, e `salvaPeriodi()` scartava in silenzio le
> righe incomplete. Con frequenza vuota — cosa che solo il `required` del `<select>` impediva,
> quindi solo nel browser — i periodi venivano cancellati, nessuno reinserito, e la transazione
> si chiudeva con successo. Aggiunte le regole `periodi.*.*` e rimosso lo scarto silenzioso.

**Dove:** `app/Controllers/AbbonamentiController.php:172-176`

```
$model->update($id, $this->request->getPost());
(new AbbonamentiPeriodiModel())->where('abbonamento_id', $id)->delete();
$this->salvaPeriodi($id, $periodi);
```

I periodi vengono cancellati e ricreati fuori da qualsiasi transazione. Se `salvaPeriodi()`
fallisce a metà, l'abbonamento resta con periodi parziali o del tutto senza — e i periodi
sono ciò che definisce la frequenza delle visite, quindi il contratto diventa inservibile
finché qualcuno non se ne accorge.

Da notare che l'accettazione (`accettaAbbonamento()`, riga 336) è invece scritta bene, con la
transazione per singolo abbonamento e la spiegazione del perché. Stessa incoerenza vista al
punto 4: la soluzione buona esiste già nel file accanto.

**Correzione:** racchiudere le tre operazioni in `transStart()` / `transComplete()`.

## 10. Assenze per malattia visibili a tutti

**Dove:** `app/Controllers/Operativo/CalendarioController.php:236-256`

L'endpoint `eventi` restituisce le assenze del personale a **qualunque utente autenticato**,
con nome, cognome e tipo: `"Rossi Mario — Malattia"`. Anche i promemoria aziendali sono
esposti a tutti allo stesso modo.

Lo stato di salute è un dato personale di categoria particolare. In un'azienda di poche
persone la cosa può essere del tutto accettabile — probabilmente lo sanno già tutti — ma è
una scelta che vale la pena fare consapevolmente, non per default. Il resto del progetto è
attento su questo fronte (la scheda dipendente è protetta da `personale.manage` dalla
v0.24.23): qui il dato esce da una porta laterale.

**Possibile correzione, se decidi che serve:** per chi non è in `ufficio`/`admin`/`developer`,
restituire l'assenza come evento generico ("Non disponibile") senza il tipo. Il calendario
resta utile per la pianificazione e il motivo non circola.

## 11. Font Awesome: ~570 KB per un solo utilizzo

**Dove:** `app/Views/layouts/admin.php:24-25`

Il layout carica **due librerie di icone** su ogni pagina:

| Libreria | Peso su disco | Utilizzi nelle view |
|---|---|---|
| Bootstrap Icons | 220 KB | 570 |
| Font Awesome (CSS + webfonts) | ~570 KB | 1 |

Font Awesome serve solo per le icone dei tipi intervento — il campo `tipi_intervento.icona`
contiene classi `fa-` — e per la loro resa sul calendario. Tutto il resto dell'interfaccia usa
Bootstrap Icons.

Va detto subito che la soluzione ovvia non funziona: il selettore in Impostazioni → Tipi
intervento (`_icona_picker.php`) propone **99 icone**, molte delle quali tematiche e senza
equivalente in Bootstrap Icons (`fa-water-ladder`, `fa-hot-tub`, `fa-faucet-drip`,
`fa-droplet-slash`…). Convertirle una a una significherebbe impoverire il selettore proprio
dove è più utile, cioè sulle piscine.

**Tre strade, in ordine di convenienza:**

1. **Subsetting** — Font Awesome permette di generare un CSS con le sole icone usate. Con 99
   icone su migliaia, il file scende a poche decine di KB e i webfont diventano superflui se
   si passa alle versioni SVG inline. È la strada che conserva tutto e paga poco.
2. **Caricare Font Awesome solo dove serve** — le pagine che mostrano icone di tipo intervento
   sono il calendario, gli elenchi interventi e Impostazioni → Tipi intervento. Spostando i
   due `<link>` in una `section('styles')` si toglierebbe il peso da dashboard, clienti,
   cantieri, abbonamenti e magazzino, cioè dalla maggior parte della navigazione.
3. **Convertire e rimuovere** — praticabile solo accettando un selettore più povero.

La 2 è mezz'ora di lavoro e nessun rischio; la 1 è la soluzione definitiva. Le due si
possono anche combinare.

Nota collegata: `CLAUDE.md` elenca `bootstrap-icons` fra le dipendenze frontend e non nomina
Font Awesome. Qualunque strada scegli, quella sezione va allineata.

## 12. Due rotte puntano a metodi inesistenti

> → **Confluito in `docs/spec/gestione_account_spec.md`** (§7): le due rotte vengono rimosse.
> L'ipotesi avanzata qui sotto — scriverle per creare account senza scheda dipendente — è
> stata scartata: l'account di un futuro cliente nascerà dalla scheda cliente, dove esiste
> già `clienti.user_id`.

**Dove:** `app/Config/Routes.php:50-51`

```
$routes->get('nuovo',  'Impostazioni\UtentiController::creaUtenteApp');
$routes->post('store', 'Impostazioni\UtentiController::storeUtenteApp');
```

Nessuno dei due metodi esiste in `UtentiController`, e nessuna view punta a quelle rotte.
Sono residui: gli account si creano da Anagrafiche → Personale, insieme alla scheda
dipendente. Chi digitasse quell'URL otterrebbe un errore.

**Correzione:** cancellare le due righe. Se invece vuoi che si possa creare un account senza
scheda dipendente (per esempio un utente `cliente` in futuro), allora i due metodi vanno
scritti.

## 13. Accesso ai record non verificato in alcune azioni minori

Tre punti in cui l'identificativo arriva dal client e non viene ricondotto al contesto:

- **`InterventiController::store()`, riga 156-159** — gli `sospesi_ids` inviati dal form
  vengono agganciati all'intervento senza verificare che appartengano al cliente indicato:
  una POST modificata può spostare i materiali sospesi di un altro cliente.
- **`MaterialiController::store()` e `delete()`** — nessun controllo che il materiale
  appartenga all'intervento o al cliente di provenienza, e nessun `accessoConsentito()`.
- **`InterventiController::aggiungiNota()` / `eliminaNota()`, righe 537-579** — chiunque può
  scrivere o cancellare note sul diario di qualunque intervento.

Nessuno di questi è sfruttabile per caso: servono richieste costruite a mano da un utente già
autenticato. Li elenco perché sono la stessa dimenticanza del punto 3 in forma più lieve, e
perché il costo di sistemarli è una condizione `where` in più.

## 14. Mass assignment: `codice` e `user_id` scrivibili dal POST

**Dove:** `app/Models/InterventiModel.php:15-23`, `app/Models/ClientiModel.php:14-23`,
usati con `insert($this->request->getPost())` nei rispettivi controller.

La convenzione del progetto — normalizzazione nel model, controller che passa il POST intero
— è comoda e coerente, ma fa sì che tutto ciò che sta in `$allowedFields` sia scrivibile da
chi costruisce la richiesta. Due campi meritano attenzione:

- **`interventi.codice`**: `normalizza()` lo genera solo se arriva vuoto (riga 76), quindi un
  POST che lo contiene lo impone.
- **`clienti.user_id`**: collega il cliente a un account utente. Oggi è inerte, ma è la
  colonna su cui poggerà il portale clienti: quando quel portale esisterà, poterla impostare
  dal form di modifica cliente significherà poter collegare un cliente al proprio account.

**Correzione:** togliere `codice` da `$allowedFields` di `InterventiModel` (lo scrive già
`normalizza()`, che opera prima del filtro) oppure ignorarlo esplicitamente in
`normalizza()`; e per `clienti.user_id`, escluderlo dal POST del form e valorizzarlo solo dal
codice che gestirà l'associazione.

## 15. Errori non gestiti su record inesistenti

Tre casi in cui un identificativo sbagliato non produce un messaggio ma un errore:

- **`InterventiController::nuovo()`, riga 89-92** — con `?cantieri_note_id=<inesistente>`,
  `find()` restituisce `null` e la riga successiva legge `$cantiereNota['testo']`. Gli altri
  due blocchi analoghi (abbonamento, cantiere) controllano il risultato; questo no.
- **`InterventiController::store()`, riga 141** — `$model->find($id)['codice']` senza
  verificare che l'inserimento sia riuscito. Se `insert()` fallisce (per esempio una chiave
  esterna violata) l'utente vede un errore PHP invece di un messaggio.
- **`CalendarioController::sposta()`, righe 296-323** — `$model->update($id, …)` viene
  eseguito anche quando `find()` non ha trovato nulla, e la risposta è comunque
  `{ok: true}`: il calendario mostra lo spostamento come riuscito quando non è successo
  niente. Manca anche il controllo di stato: si può spostare via POST un intervento
  completato o annullato.

## 16. `defaultGroup` è `ufficio`

> → **Confluito in `docs/spec/gestione_account_spec.md`** (§4): passa a `cliente`, insieme
> alle altre modifiche della matrice dei permessi.

**Dove:** `app/Config/AuthGroups.php:26`

Il gruppo assegnato di default a un utente creato senza gruppi espliciti è `ufficio`, che
nella matrice dei permessi ha quasi tutto (`personale.*`, `abbonamenti.*`, `cantieri.*`,
`magazzino.*`, `clienti.*`, `interventi.*`).

Oggi è innocuo: `allowRegistration` è `false` e il codice assegna sempre i gruppi
esplicitamente. Diventa pericoloso il giorno in cui si aprisse una registrazione, anche solo
per il portale clienti. Un default dovrebbe essere il ruolo meno potente, non il secondo più
potente.

**Correzione:** portarlo a `tecnico`, o meglio a `cliente` (matrice vuota).

## 17. Il sistema `from` è ripetuto in venti punti

Il blocco

```
$from = $this->request->getPost('from');
$dest = ($from && str_starts_with($from, base_url())) ? $from : '<fallback>';
```

compare identico in una ventina di metodi. Finora è sempre stato copiato correttamente, ma è
il tipo di duplicazione in cui prima o poi un `str_starts_with` viene dimenticato — e quel
controllo è ciò che impedisce un redirect verso un sito esterno.

In `MaterialiController::store()` (riga 35) il valore viene per esempio rimesso in query
string **senza** la verifica; il redirect finale è comunque protetto, quindi non c'è un buco,
ma è la dimostrazione che la regola non è applicata in modo uniforme.

**Correzione:** un metodo in `BaseController`, per esempio
`ritornaA(string $fallback): RedirectResponse`, che legge il `from` dal POST, lo valida e
costruisce il redirect. Venti chiamate diventano una riga ciascuna e il controllo di sicurezza
vive in un posto solo. Si sposa con il tema "refactor del sistema from" già annotato fra le
cose da riprendere.

## 18. Nessun backup del database

Non esiste nessuno script di dump, nessuna procedura documentata, nessun riferimento a backup
in `docs/`. Finché i dati sono di test non è un problema; dal primo giorno di uso reale, un
gestionale senza backup è un rischio che non si può correre.

**Proposta:** un comando Spark `php spark db:backup` che esegua `mysqldump` in
`writable/backup/` con la data nel nome e tenga gli ultimi N file, più una riga di cron
notturna sulla VPS accanto a quella del batch abbonamenti. Mezza giornata di lavoro, da fare
prima del go-live e non dopo.

## 19. jQuery 4.0.0 da verificare

`package.json` richiede `jquery: ^4.0.0` e in `node_modules` è installata proprio la 4.0.0.
jQuery 4 rimuove diverse API deprecate e ha una compatibilità ancora in assestamento con i
plugin. Il progetto usa jQuery solo per DataTables 2.3.8, che dovrebbe supportarlo, ma è
esattamente il genere di combinazione che si rompe in silenzio su una funzione poco usata
(responsive, rowgroup, export).

**Proposta:** provare a fondo le tabelle — ricerca, ordinamento, raggruppamento, vista mobile
— e se emergono stranezze scendere a `^3.7`, che è la versione su cui DataTables è più
collaudato. Non è un problema oggi: è una cosa da sapere se un giorno una tabella si comporta
in modo strano.

## 19-bis. Una deroga alla convenzione `cliente_denominazione`

**Dove:** `app/Models/AbbonamentiModel.php:178`

```
->select('abbonamenti.id, c.denominazione, ti.nome, …')
```

`CLAUDE.md` stabilisce che `clienti.denominazione` vada **sempre** aliasata come
`cliente_denominazione`, proprio per evitare che due query che joinano i clienti da entità
diverse producano chiavi differenti. `leggiScaduti()` è l'unico punto che non lo fa (ho
controllato tutti i model): la chiave resta `denominazione` e il batch la legge così.

Anche `ti.nome` andrebbe aliasato, perché in quella riga convive con il nome del cliente e
"nome" da solo non dice a chi appartiene.

Non rompe niente oggi. Lo segnalo perché è una convenzione scritta apposta per non doverci
pensare, e una deroga isolata è il modo in cui le convenzioni si sgretolano.

## 20. Checklist di go-live

Non sono difetti, sono cose che oggi sono giustamente impostate per lo sviluppo e che vanno
cambiate al momento del deploy. Le raccolgo qui perché è comodo averle in un posto solo.

| Cosa | Dove | Valore in produzione |
|---|---|---|
| Ambiente | `.env` | `CI_ENVIRONMENT = production` (già presente commentato) |
| URL base | `.env` | `https://colombini.metesoftware.it/` |
| HTTPS forzato | `Config\App::$forceGlobalSecureRequests` | `true` |
| Cookie sicuro | `Config\Cookie::$secure` | `true` |
| Filtro CSRF | `Config\Filters` | attivo (punto 1) |
| Posta | `Config\Email` + `.env` | SMTP configurato, o magic link disattivato (punto 7) |
| Fuso MySQL | connessione DB | verificato con `SELECT NOW(), CURDATE()` (punto 8) |
| Batch abbonamenti | cron VPS | `php spark batch:abbonamenti-scaduti -force`, notturno |
| Backup | cron VPS | punto 18 |
| Database | — | **svuotato**: nessun record di sviluppo va in produzione |
| Permessi | filesystem | `writable/` scrivibile dall'utente del web server |
| Rewrite | nginx | `index.php` nascosto, `/uploads` servito ma non eseguibile come PHP |

L'ultima riga della tabella è anche la seconda difesa per il punto 2: una regola nginx che
impedisca l'esecuzione di PHP dentro `/uploads` rende innocuo un file caricato per errore.

---

## Quello che invece funziona bene

Vale la pena scriverlo, perché in una lista di difetti si perde di vista il resto.

- **La validazione c'è quasi ovunque**, con le regole raccolte in metodi dedicati
  (`regolaValidazione()`) e i valori ammessi letti dalle costanti dei model: aggiungere uno
  stato non richiede di ricordarsi di aggiornare anche il controller.
- **Le view sono disciplinate sull'escape.** Ho cercato le stampe non protette: quelle che
  restano sono identificatori numerici, conteggi e classi CSS. Dove il dato viene da un
  utente, `esc()` c'è — anche precalcolato in cima al file, come in `personale/show.php`.
- **Nessun `TODO`, nessun `dd()` attivo, nessun `var_dump` dimenticato** in tutto `app/`.
  L'unico residuo è un `//dd(...)` commentato in `cantieri/show.php:327`, che è debug
  intenzionale.
- **Tutte le 47 migrazioni hanno un `down()` reale**, non vuoto.
- **Le query raw sono confinate alle migrazioni** e ai due punti dove servono davvero (il
  contatore atomico con `FOR UPDATE`, l'introspezione delle chiavi esterne): il resto è
  Query Builder.
- **`ImportClientiController`** è un esempio di come si gestisce un upload: validato, fuori
  dalla webroot, con nome generato e pulizia finale.
- **I docblock spiegano il perché**, non il cosa. Diversi commenti in `InterventiModel` e
  `AbbonamentiController` documentano le alternative scartate: è ciò che ha reso possibile
  scrivere questa review senza doverti chiedere niente.

## Come leggerei questa lista

> **Aggiornamento 22 agosto.** I primi tre della lista qui sotto sono fatti (v0.27.2 e
> v0.27.3), e il punto 4 di questo elenco — le voci 5, 6, 7 — è in corso: la 7 è mitigata, la
> 5 e la 6 sono nello spec sulla gestione account. L'ordine con cui proseguire, deciso il 22
> agosto, è: gestione account (questo spec), poi punto 9, punto 4, punto 8, quindi il backup
> del punto 18 e la checklist del punto 20 prima del go-live.

Se dovessi scegliere cosa fare per primo, in ordine:

1. **Punto 1 (CSRF)** — una riga, l'infrastruttura è già pronta, va solo provata.
2. **Punto 3 (`update()` senza controllo tecnico)** — tre righe, chiude un buco reale nella
   policy dei tecnici che il resto del progetto applica già.
3. **Punto 2 (upload logo)** — mezz'ora, elimina l'unico punto da cui si può scrivere un file
   eseguibile.
4. **Punti 5, 6, 7** — piccoli, indipendenti, ognuno evita una situazione in cui qualcuno
   resta bloccato fuori o vede una schermata di errore.
5. **Punto 18 (backup)** e la checklist del punto 20 — prima del go-live, non dopo.
6. Il resto quando capita, con il punto 11 (Font Awesome) come candidato ideale per una
   giornata in cui si ha voglia di un miglioramento visibile e a basso rischio.
