# Spec — Gestione account, ruoli e profilo

> Da leggere insieme a `docs/review/2026-08-16-review-progetto.md`, di cui questo spec chiude i punti 5, 6, 12 e 16, più un buco di sicurezza che la review non aveva individuato. Per il contesto architetturale generale vedere `docs/ANALISI.md`.

## 1. Contesto e problema di partenza

Oggi **tre** controller scrivono sullo stesso account Shield, con lo stesso codice copiato parola per parola:

| Blocco | `UtentiController` | `PersonaleController` | `ProfiloController` |
|---|---|---|---|
| Aggiornamento email identity | 72-77 | 212-217 | 81-86 |
| Diff dei gruppi (remove + add) | 79-88 | 219-229 | — (per scelta) |
| Cambio password | 90-94 | 231-235 | 88-91 |

Tre copie che devono restare uguali per sempre, senza che niente lo garantisca. È da questa duplicazione che nascono i problemi qui sotto.

### 1.1 Un utente `ufficio` può promuoversi ad amministratore

È il problema più grave e **non compare nella review**.

Le rotte `anagrafiche/personale/*` sono protette da `permission:personale.manage` (`Routes.php:60`), permesso che nella matrice appartiene anche a `ufficio`. Ma la whitelist `$gruppi` di `PersonaleController` (righe 15-20) include `admin` e `developer`. Un utente ufficio apre la propria scheda dipendente, spunta "Amministratore", salva, e ottiene `impostazioni.*`.

Non è un'ipotesi teorica come gli altri punti della review: è raggiungibile **dall'interfaccia normale**, senza costruire richieste a mano. È la stessa classe di problema chiusa nella v0.24.23, dove si è protetto *l'accesso alla sezione* ma non *quali ruoli si possono assegnare da dentro*.

La causa strutturale è la wildcard in `AuthGroups::PERMESSI_UFFICIO`: con `personale.*`, qualunque permesso nuovo chiamato `personale.qualcosa` finisce automaticamente anche a ufficio. Finché resta, la matrice **non è in grado di esprimere** la distinzione fra "gestire i dipendenti" e "nominare amministratori".

Un secondo difetto amplifica il primo: nel ciclo che rimuove i gruppi (riga 222) manca il filtro `isset($this->gruppi[$g])` che invece esiste in quello che li aggiunge (riga 226). Conseguenza: un ufficio che salva la scheda di un amministratore **gli toglie il ruolo**, solo perché quel checkbox non era nel suo form.

### 1.2 Nessuna guardia su sé stessi

Né `UtentiController` né `PersonaleController` confrontano l'utente su cui si sta agendo con quello collegato:

- si può eliminare il proprio account mentre lo si sta usando (`deleteUtenteApp()` riga 112, `PersonaleController::delete()` righe 254-256, che elimina anche l'account Shield);
- si può togliere a sé stessi il ruolo amministrativo, restando fuori dalle Impostazioni.

Nel caso peggiore si recupera solo agendo sul database.

### 1.3 Creazione dipendente senza transazione (punto 6 della review)

`PersonaleController::store()` esegue quattro passi non atomici: utente, identità email, gruppi, scheda personale. Se il secondo fallisce — e fallisce, perché l'email **non ha `is_unique`** mentre `auth_identities` ha un vincolo `UNIQUE (type, secret)` — resta un utente Shield senza identità, senza gruppi e senza scheda, che però occupa lo username scelto. Al secondo tentativo l'utente sbatte contro `is_unique[users.username]` senza capire perché.

### 1.4 Due sezioni per la stessa persona

Lo stato del database di sviluppo:

| | |
|---|---|
| Utenti totali | 11 |
| Schede personale | 10 |
| Utenti senza scheda personale | 1 — l'account `admin` del seeder |
| Schede personale senza utente | 0 |

Anagrafiche → Personale e Impostazioni → Utenti gestiscono quindi **le stesse dieci persone**, più un account di servizio. La separazione fra `personale` e `users` nel database è corretta e va mantenuta — Shield ha le sue tabelle e non ci si mette dentro il colore del calendario — ma nell'interfaccia produce due pagine che fanno lo stesso lavoro: doppia superficie da proteggere e nessun vantaggio per chi le usa.

Dopo la sostituzione del seeder (§6) **nessun nuovo account nasce senza scheda personale**. L'orfano esistente non sparisce però da solo — sostituire il seeder cambia solo ciò che accade da lì in avanti — ed è stato rimosso a mano dal database di sviluppo: senza scheda non era raggiungibile da Anagrafiche → Personale, e da Impostazioni → Utenti gli account si sospendono soltanto. Gli account senza scheda torneranno a esistere solo con il portale clienti.

## 2. Soluzione: tre sedi con responsabilità distinte

Il principio: **un solo punto di scrittura per ogni cosa**, e i permessi che decidono chi ci arriva.

### 2.1 Anagrafiche → Personale — la persona

Resta la sede della scheda dipendente. Il blocco "Account di accesso" (email, ruoli, password) è visibile e modificabile **solo a chi ha `personale.account`**, cioè admin e developer. Un utente `ufficio` vede e modifica solo l'anagrafica — nome, cognome, telefono, colore — e le assenze.

Le view non vanno riscritte: `nuovo.php` ed `edit.php` iterano già su `$gruppi` ricevuto dal controller, quindi basta che il controller passi l'elenco filtrato perché i checkbox spariscano da soli. Il blocco password/email va invece racchiuso in una condizione sul permesso.

**Creare un dipendente** richiede `personale.account`, perché creare una persona significa oggi creare anche il suo account. Ufficio non crea più dipendenti: modifica quelli esistenti e gestisce le assenze.

### 2.2 Impostazioni → Utenti — la vista d'insieme, in sola lettura

Smette di essere un secondo form di modifica e diventa una pagina di consultazione per admin e developer: elenco di **tutti** gli account con username, email, ruoli, stato attivo e link alla scheda di provenienza.

Risponde a una domanda che l'elenco del personale non sa rispondere — *chi ha accesso all'applicazione, con quali ruoli, e chi è ancora attivo* — dato che lì non compaiono né l'email, né lo stato dell'account, né i gruppi. Ed è l'unico posto da cui vedere gli account **senza scheda personale**: nessuno dopo la sostituzione del seeder, tutti quelli dei clienti quando ci sarà il portale.

Resta qui la sola azione che non appartiene a una scheda persona: **sospendere e riaprire l'accesso**.

#### Due schede: Personale e Clienti

L'elenco è diviso in due schede, perché i due tipi di account sono cose diverse: una persona che lavora qui e un contatto esterno. La regola di smistamento è volutamente **esaustiva** — va fra i clienti chi ha un `clienti.user_id` collegato *oppure* appartiene al gruppo `cliente`, e in Personale finisce tutto il resto, compreso un account senza nessuna scheda. Nessun account può cadere fuori da entrambe: sarebbe la contraddizione esatta del motivo per cui questa pagina esiste.

La colonna del nome si adatta da sola alla riga: denominazione con link alla scheda cliente per gli account cliente, nominativo con link alla scheda dipendente per gli altri. Le due schede condividono quindi lo stesso partial `_tabella_utenti.php` invece di essere due copie della stessa tabella.

**DataTables solo sulla scheda Clienti**, con il pattern già in uso in `clienti/index.php`: gli account del personale sono una manciata e non hanno bisogno di ricerca e paginazione, quelli dei clienti sì. L'asimmetria è motivata dai dati, non da distrazione.

Un dettaglio da non perdere: la scheda Clienti è nascosta al caricamento, e dentro un `display: none` ogni misura vale zero. DT fisserebbe le larghezze delle colonne su quegli zeri e l'intestazione resterebbe disallineata dal corpo; per questo si richiama `columns.adjust()` su `shown.bs.tab`. Oggi il difetto non si vedrebbe — senza account cliente la tabella non viene nemmeno creata — e ricomparirebbe al primo account cliente, quando nessuno starebbe più pensando a questa pagina.

*Rimandato:* dopo aver sospeso un account cliente si torna sulla scheda Personale, perché il redirect non porta con sé quale scheda era aperta. Si risolve con l'anchor `#pane-clienti` e l'attivazione del tab da hash già documentata in `CLAUDE.md`; non vale scriverlo finché gli account cliente sono zero. Quando il portale esisterà, la scheda cliente avrà un proprio blocco account e il pulsante di sospensione potrà stare anche lì — le due sedi non si escludono, rispondono a domande diverse: *chi ha accesso* contro *questo cliente ha accesso*.

#### Un account non si elimina mai

Gli account non hanno un comando di eliminazione, in nessuna delle due sezioni. La ragione la dicono le foreign key: `interventi.tecnico_id` punta a `personale` con `ON DELETE SET NULL` e `assenze.personale_id` con `ON DELETE CASCADE`, quindi eliminare chi ha lavorato azzererebbe il tecnico su tutto il suo storico e ne cancellerebbe il diario delle assenze — in silenzio, dopo una sola conferma del browser. In un gestionale lo storico vale più di un elenco ordinato.

L'unica eccezione è il record **mai usato**: l'errore di inserimento, l'account di prova. Lì l'eliminazione non distrugge niente e risolve un problema reale, perché `users.username` e `auth_identities.secret` hanno un vincolo `UNIQUE` e un account sospeso li occupa per sempre: senza poter eliminare, uno username sbagliato per un errore di battitura resterebbe bruciato e la persona vera dovrebbe chiamarsi `mario2`.

La regola diventa una frase sola — **si elimina solo ciò che non è mai stato usato** — e la fa rispettare il codice, non la memoria di chi la usa: `PersonaleController::delete()` conta interventi e assenze e rifiuta indicando la sospensione come alternativa.

Il caso di un ex dipendente che chiedesse la cancellazione dei propri dati **non** si risolve con questo comando: richiede l'anonimizzazione della scheda, perché gli interventi vanno comunque conservati. Fuori scope, da affrontare se e quando si porrà.

#### Sospendere si fa con `ban()`, non con `active`

Il campo `users.active` **non impedisce l'accesso** in questo progetto. `isActivated()` (Shield, `Traits/Activatable.php:22`) è scritto come `! $this->shouldActivate() || $this->active`: quando non è configurata un'azione di registrazione — e in `Config\Auth` `$actions['register']` è `null` — il primo termine è già vero e il valore di `active` non viene mai guardato. Una disattivazione basata su quel campo sarebbe una funzione che sembra funzionare e non fa nulla, esattamente come il magic link del punto 7 della review.

Si usa quindi `ban()` / `unban()`: `isBanned()` è la prima cosa che il filtro `SessionAuth` controlla (riga 62), e disconnette subito mostrando il messaggio impostato al momento della sospensione.

#### La sospensione toglie dalle assegnazioni

`PersonaleModel::elencoPerGruppi()` alimenta tutte le tendine di scelta del tecnico — calendario, nuovo intervento, viaggio, scheda cliente — e non filtrava lo stato dell'account: un tecnico sospeso avrebbe continuato a comparire fra gli assegnabili. Ora esclude chi è sospeso.

Gli interventi **già** assegnati restano invece intatti e visibili: la riassegnazione è una decisione dell'operatore, non un effetto collaterale. Per questo la sospensione, quando la persona ha interventi ancora aperti, non viene bloccata ma **avvisa** con l'elenco di quelli da riassegnare.

#### Il lavoro da riassegnare finisce nella card dei conflitti

Il solo avviso al momento della sospensione non basta: è un messaggio flash, compare una volta e chi chiude la pagina non lo rivede più. Gli interventi pianificati di un tecnico sospeso entrano quindi anche nella card **"Interventi in conflitto"** della dashboard, quella nata per le assenze inserite dopo la pianificazione (`conflitti_assenze_interventi_spec.md`). È lo stesso identico problema — un fatto registrato *dopo* invalida una pianificazione già fatta — e la card lo ripropone a ogni accesso finché esiste, che è esattamente ciò che l'avviso non sa fare.

I due casi convivono nella stessa card con un'etichetta colorata che li distingue: il tipo di assenza in giallo, «Account sospeso» in rosso. Un intervento che ricade in entrambi compare una volta sola come sospensione, perché è il fatto più forte: l'assenza finisce da sola a fine ferie, la sospensione no.

*Alternativa scartata:* riassegnare automaticamente a "non assegnato" tutti gli interventi aperti al momento della sospensione. Rende sì lo stato del database immediatamente coerente, ma è **irreversibile** — sospendi e riattivi si annullano a vicenda, la perdita di `tecnico_id` no — ed è riga per riga la stessa scrittura che `ON DELETE SET NULL` farebbe da sola: farla partire da un click invece che da una foreign key non la rende meno distruttiva. Resta comunque disponibile come scelta esplicita dell'operatore, che dalla card apre l'intervento e mette «— nessuno —».

#### Il tecnico sospeso deve restare nelle tendine dove è già assegnato

Escludere i sospesi da `elencoPerGruppi()` ha un effetto collaterale che va corretto nello stesso momento, altrimenti il rimedio è peggio del male: i `<select>` di **modifica** — tecnico dell'intervento, tecnico preferito del cliente — sono costruiti da quella stessa lista. Un record già assegnato a un sospeso si aprirebbe con il select su «— nessuno —», e il primo salvataggio di una qualunque altra modifica azzererebbe `tecnico_id` **in silenzio**, senza un messaggio.

Il caso peggiore non è nemmeno l'intervento aperto: è quello **completato**, che resta modificabile (solo gli annullati sono bloccati) e che deve restare attribuito a chi l'ha fatto per sempre. Si perderebbe lo storico proprio dalla funzione scritta per proteggerlo.

Per questo `elencoPerGruppi()` accetta un secondo parametro `$includiId` che riammette il singolo dipendente già assegnato, marcato «(sospeso)» nella tendina. Le chiamate senza quel parametro — calendario, nuovo intervento, foglio viaggio — continuano a escludere i sospesi come previsto. Il calendario non ha bisogno di modifiche: usa la lista solo per i pulsanti-filtro, mentre gli eventi arrivano dal proprio endpoint, quindi gli interventi di un sospeso restano visibili sotto «Tutti».

*Perché la sola lettura e non la fusione totale:* far sparire questa sezione renderebbe invisibili dall'interfaccia gli account non legati a un dipendente. Oggi sarebbe una perdita piccola — dopo il nuovo seeder non ce ne sono — ma il portale clienti ne creerà molti, e in quel momento la sezione andrebbe ricostruita da zero. Tenerla in sola lettura costa una view e conserva il posto dove andranno.

*Cosa si perde:* la possibilità di cambiare una password al volo da Impostazioni. Chi deve farlo passa dalla scheda del dipendente; il diretto interessato dal proprio profilo.

### 2.3 Profilo — il self-service, per chiunque

`ProfiloController` esiste già e fa la cosa giusta nel modo giusto: risolve il dipendente da `user_id()` lato server e mai da un id in POST o URL, quindi nessun altro profilo è raggiungibile da lì. Non tocca i gruppi, per scelta già documentata nel suo docblock.

**Una sola modifica**, che serve al portale clienti: oggi il metodo si interrompe con un errore se l'utente non ha una scheda personale (riga 17 e riga 44). Un account cliente non ne avrà mai una. Va reso capace di funzionare **anche senza scheda**: in quel caso mostra e salva solo email e password, saltando il blocco anagrafico.

Questo è ciò che rende il profilo la sede naturale del self-service futuro dei clienti (email, password e in prospettiva l'indirizzo), senza dover costruire una sezione separata per loro.

## 3. Il codice condiviso: `UserModel`

Le operazioni sull'account si spostano una volta sola in `app/Models/UserModel.php`, che già estende quello di Shield e ospita `tuttiConGruppi()`. È coerente con la regola del progetto: la logica sui dati sta nel model, il controller passa la request.

```php
public function creaAccount(array $dati, array $assegnabili): User
public function aggiornaAccount(User $user, array $dati, array $assegnabili): void
```

`$assegnabili` è l'elenco dei gruppi che chi sta salvando ha il diritto di toccare. Dentro il metodo il diff si applica **solo** a quell'insieme, sia in aggiunta sia in rimozione: i gruppi fuori elenco non vengono né aggiunti né tolti. La regola di sicurezza che oggi manca vive così in un punto solo e vale automaticamente per ogni chiamante, presente o futuro.

`creaAccount()` è anche il posto in cui mettere la **transazione** del punto 6: utente, identità e gruppi diventano atomici. La transazione che comprende anche la scheda `personale` resta nel controller, che è l'unico a sapere che le due cose vanno insieme.

### Come si comportano le transazioni annidate (verificato su `BaseConnection`)

`transStart()` dentro un'altra transazione non ne apre una seconda: incrementa un contatore (riga 1012). Il `transComplete()` interno non committa e non annulla nulla finché la profondità è maggiore di 1 — decrementa e basta. Il commit o il rollback fisico avviene **solo** all'uscita più esterna, quindi un fallimento dentro `creaAccount()` annulla anche l'inserimento della scheda personale, che è il comportamento voluto.

Il flag che rende vero tutto questo è `transStrict`, che vale `true` per default (riga 317) e che il progetto non modifica. Se venisse portato a `false`, il `transComplete()` interno **azzererebbe lo stato di fallimento** (riga 984) e quello esterno finirebbe per committare: si salverebbe la scheda personale con un account rotto, in silenzio. Vale per ogni transazione annidata del progetto, non solo per questa.

Da sapere anche: dentro una transazione CodeIgniter **non lancia** l'eccezione sulla query fallita nemmeno con `DBDebug` attivo (righe 836-843), a meno di chiedere `transException(true)`. La query ritorna `false`, il codice prosegue e il fallimento si legge da `transStatus()`. È il motivo per cui il controllo esplicito dopo `transComplete()` serve davvero.

I chiamanti diventano due — `PersonaleController` e `ProfiloController` — perché `UtentiController` non scrive più sull'account.

### Cosa non viene estratto

Le guardie "non su te stesso" restano scritte nei metodi in cui servono. Sono una condizione e un redirect con un messaggio diverso ogni volta ("non puoi eliminare il tuo account", "non puoi rimuovere il tuo ruolo"): nasconderle dietro un helper le renderebbe meno leggibili, non meno duplicate.

## 4. I permessi: la regola vive nella matrice

In `app/Config/AuthGroups.php`:

- `PERMESSI_UFFICIO`: `'personale.*'` diventa `'personale.manage'`. È la modifica che rende la matrice capace di esprimere la policy: ufficio ha solo ciò che gli è scritto accanto e non eredita più i permessi futuri.
- Due permessi nuovi in `$permissions`:
  - `personale.account` — «Può creare account, assegnare ruoli e cambiare le password altrui»
  - `personale.elimina` — «Può eliminare dipendenti e account»
- `PERMESSI_ADMIN` resta `'personale.*'`: admin e developer li ereditano entrambi senza toccare la matrice.
- `defaultGroup` passa da `ufficio` a `cliente` (punto 16 della review): il gruppo assegnato per default dev'essere il meno potente, non il secondo più potente. Oggi è inerte perché `allowRegistration` è `false`, ma diventa rilevante il giorno in cui si apre la registrazione del portale clienti.

In `app/Config/Routes.php`: filtro `permission:personale.elimina` sulle rotte `delete`. Chi non ha il permesso non arriva al metodo — nessuna riga di controller.

Chi legge `AuthGroups.php` vede la policy per intero senza aprire un controller.

## 5. Le guardie su sé stessi

Tre controlli che la matrice non può esprimere, perché dipendono da *chi sei tu* e non dal ruolo:

1. **Non elimini la tua scheda** — rifiuto secco, sempre, anche in presenza di altri amministratori: eliminerebbe anche l'account con cui si sta lavorando.
2. **Non sospendi il tuo accesso** — stessa ragione, dall'altra sezione.
3. **Non ti togli il ruolo amministrativo** — il blocco scatta solo se i gruppi richiesti non contengono **né** `admin` **né** `developer`, così un admin può ancora passare a developer se lo vuole.

### Perché non serve contare gli amministratori superstiti

La review proponeva di rifiutare ogni operazione che lasciasse zero utenti nel gruppo `admin`. Con le regole di questo spec quel conteggio diventa **strutturalmente impossibile da violare**: ogni operazione sugli account la compie un amministratore, che non può né declassare né cancellare sé stesso. Codice che non viene scritto.

Nel conteggio, comunque, `admin` e `developer` andrebbero considerati insieme: nella matrice hanno la stessa identica riga (`PERMESSI_ADMIN`), quindi finché resta un developer nessuno è chiuso fuori.

## 6. Seeder: da `admin` di servizio all'utente reale

`AdminSeeder.php` crea un account `admin` generico, che è l'unico utente senza scheda personale. Va sostituito con un seeder che crea l'account reale della sviluppatrice — che è **dipendente interna** dell'azienda, quindi con la sua scheda in `personale` come chiunque altro.

| | |
|---|---|
| Username | `Daniela` — con l'iniziale maiuscola, come tutti gli altri account |
| Email | `daniela@colombini-snc.it` (dominio aziendale, non quello del gestionale) |
| Gruppi | `admin`, `developer`, `ufficio` |
| Scheda personale | sì — Daniela Calì |
| Password | letta da `.env`, **mai** scritta in chiaro in un file versionato |

Tutta l'identità sta nel `.env`, non solo la password: `admin.username`, `admin.email`, `admin.password`, `admin.nome`, `admin.cognome`, nella convenzione a namespace puntato già usata dal blocco `database.default.*`. Al go-live si valorizza tutto in un posto solo sulla VPS, senza modificare un file versionato.

**I gruppi restano invece nel codice**, elencati in una costante del seeder. Non sono l'identità della persona ma i suoi permessi, e la policy di questo progetto vive in `AuthGroups` — è la coerenza che il §4 esiste per ottenere. Un elenco di gruppi in un file non versionato sarebbe anche un invito al refuso: `develper` produrrebbe in silenzio un account senza accesso, e nessuno saprebbe perché.

Ha due effetti utili: sparisce un account di servizio con password nota e prevedibile, e ogni utente del sistema ha una persona dietro.

`.env` è in `.gitignore` (riga 44), quindi i valori reali non entrano mai nel repository: in sviluppo stanno nel `.env` locale, al go-live si valorizzano sulla VPS prima di eseguire il seeder.

Se una chiave manca o è vuota il seeder **si ferma** elencando quali, invece di ripiegare su un valore di scorta. Un ripiego generico ricreerebbe esattamente l'account con password prevedibile che questo seeder esiste per eliminare, solo sotto un altro nome — e nessuno si accorgerebbe di averlo. Su una macchina appena clonata `db:seed` quindi fallisce: fallisce però *spiegando cosa mettere nel `.env`*, che è più utile di un account fantasma.

Il file va salvato in **UTF-8**: in ANSI «Calì» diventa «CalÃ¬» e finisce così nell'anagrafica.

Il gruppo `ufficio` è ridondante rispetto ai permessi — `admin` ha già tutto ciò che ha `ufficio` — ma è corretto tenerlo: descrive il ruolo aziendale della persona, e alcuni controlli nel codice ragionano per appartenenza al gruppo e non per permesso (per esempio `puoGestireAssenze` in `PersonaleController:65`).

### Il seeder dev'essere idempotente

Nel database di sviluppo l'account `Daniela` **esiste già**, con la sua scheda personale. Il seeder deve quindi verificare la presenza dell'utente prima di crearlo e, se c'è, limitarsi ad allineare gruppi e scheda invece di inserire un doppione — che fallirebbe comunque sul vincolo `UNIQUE` di `users.username`.

Non è una precauzione teorica: al go-live il database sarà svuotato e il seeder creerà l'account da zero, mentre in sviluppo verrà eseguito su dati già presenti. Deve funzionare in entrambi gli scenari.

Sulla riesecuzione aggiunge i gruppi mancanti e crea la scheda se non c'è, ma **non tocca email e password**: allinearle al `.env` significherebbe cambiare in silenzio le credenziali di chi sta lavorando, che non è ciò che ci si aspetta da un comando lanciato per assicurarsi che l'account esista.

**L'account viene riconosciuto dallo username**, che è quindi la sua identità. Cambiare `admin.username` nel `.env` e rilanciare il seeder non rinomina niente: crea un secondo account. È il tipo di cosa che si scopre solo dopo averla fatta, per questo sta scritta anche nel docblock del seeder.

### Maiuscole e minuscole: nessuna differenza

Verificato sul database: `users.username` e `auth_identities.secret` hanno collation `utf8mb4_general_ci`, dove `_ci` significa *case insensitive*. Il confronto `'daniela' = 'Daniela'` è vero, quindi il login funziona con qualunque combinazione di maiuscole e l'unicità impedisce di creare due account che differiscono solo per quelle. Il valore resta memorizzato come è stato scritto ed è così che appare nell'interfaccia.

## 7. Le due rotte orfane (punto 12 della review)

`Routes.php:50-51` espone `utenti-app/nuovo` e `utenti-app/store` verso due metodi che **non esistono** in `UtentiController`. Sono quasi certamente un residuo del progetto precedente, che gestiva i clienti come utenti dell'applicazione.

Vanno **rimosse**. La creazione di un account non parte da qui: per un dipendente avviene dalla scheda personale, e per un futuro cliente avverrà dalla scheda cliente, dove esiste già la colonna `clienti.user_id` che li collega.

## 8. Riepilogo delle modifiche

1. **`app/Config/AuthGroups.php`** — `PERMESSI_UFFICIO` da wildcard a elenco esplicito (tolte tutte, non solo `personale.*`); nuovi permessi `personale.account` e `personale.elimina`; `defaultGroup` a `cliente`.
2. **`app/Config/Routes.php`** — `permission:personale.account` su `nuovo` e `store` del personale, `permission:personale.elimina` sulla sua `delete`; nuova rotta `utenti-app/(:num)/stato`; rimosse le due rotte orfane e quelle di modifica ed eliminazione degli utenti.
3. **`app/Models/UserModel.php`** — `creaAccount()` e `aggiornaAccount()`, con il filtro sui gruppi assegnabili applicato sia in aggiunta sia in rimozione e la transazione sulla creazione; `tuttiConGruppi()` restituisce anche email, `personale_id`, `cliente_id` con la denominazione (per lo smistamento fra le due schede) e `status` al posto di `active` (è `status` a dire chi è sospeso: mostrare `active` indicherebbe "attivo" anche un utente bannato).
4. **`app/Models/InterventiModel.php`** — `contaPerTecnico()` (tutti, per il blocco all'eliminazione: lì serve solo un numero dentro un messaggio, e possono essere centinaia); `apertiPerTecnico()` (non completati, per l'avviso alla sospensione) che restituisce le **righe** e non un conteggio, così il numero è `count()` e l'elenco diventa i link su cui riassegnare — una query sola, com'era già l'uso in `DashboardController::caricaDatiUfficio()`; `inConflittoPerSospensione()` e `inConflitto()`, che unisce i due tipi di conflitto per la card della dashboard; `inConflittoConAssenze()` guadagna il campo `motivo`.
5. **`app/Models/AssenzeModel.php`** — `contaPerPersonale()`, per il blocco all'eliminazione.
6. **`app/Models/PersonaleModel.php`** — `elencoPerGruppi()` esclude gli account sospesi dalle tendine di assegnazione, con il parametro `$includiId` che riammette il dipendente già assegnato nei form di modifica (§2.2) e `status` nel select perché la view lo marchi.
7. **`app/Controllers/Anagrafiche/PersonaleController.php`** — usa il model; il blocco account è tutto-o-niente sul permesso `personale.account`, comprese le regole di validazione; transazione su `store()`; `is_unique` sull'email in creazione e in modifica; guardia sull'auto-declassamento; `delete()` rifiuta chi ha interventi o assenze.
8. **`app/Controllers/Impostazioni/UtentiController.php`** — perde `editUtenteApp()`, `updateUtenteApp()` e `deleteUtenteApp()`; resta l'elenco arricchito più `cambiaStatoUtenteApp()`, il cui avviso elenca gli interventi da riassegnare come link (primi cinque, poi «e altri N»: un flash lungo una pagina nasconde l'avviso invece di darlo).

    Fuori da questi file, per la stessa ragione del §2.2: `DashboardController::caricaDatiUfficio()` chiama `inConflitto()` al posto di `inConflittoConAssenze()`; `dashboard/index.php` distingue i due motivi con un badge colorato; `Operativo\InterventiController::edit()` e `Anagrafiche\ClientiController::edit()` passano `$includiId` a `elencoPerGruppi()`, e le rispettive view marcano «(sospeso)» l'assegnato.
9. **`app/Controllers/ProfiloController.php`** — usa il model con elenco di gruppi vuoto; funziona anche senza scheda personale; `is_unique` sull'email.
10. **View** — `impostazioni/utenti_app.php` da elenco con link di modifica a vista d'insieme (username, nome con link alla scheda, email, gruppi, stato) con sospendi/riattiva, nessun pulsante sulla propria riga, divisa nelle due schede Personale e Clienti che condividono il nuovo partial `impostazioni/_tabella_utenti.php`, con DataTables sulla sola scheda Clienti; eliminate `impostazioni/edit_utente_app.php` e `impostazioni/__crea_utente_app.php`, quest'ultima la view delle due rotte orfane del punto 12; in `anagrafiche/personale/edit.php` il blocco "Account di accesso" condizionato a `personale.account` e il pulsante Elimina a `personale.elimina`; in `anagrafiche/personale/index.php` il pulsante "Nuovo dipendente" condizionato a `personale.account`; `profilo/index.php` con la parte anagrafica condizionata alla presenza della scheda.

    In `anagrafiche/personale/nuovo.php` **non** serve nessuna condizione, contrariamente a quanto previsto qui in origine: la rotta ha già il filtro `permission:personale.account`, quindi chi non ha il permesso non raggiunge mai la pagina e l'`if` non potrebbe mai scattare.

    Aggiornate anche le due guide `app/Views/help/utenti.php` e `app/Views/help/personale.php`, che descrivevano funzioni ora inesistenti — modifica dell'account da Impostazioni, eliminazione degli account, «togli i gruppi per revocare l'accesso» — cioè esattamente le pratiche che questo spec sostituisce con la sospensione. La creazione degli account cliente dalla scheda cliente **non** viene anticipata nelle guide: si documenta quando la funzione esisterà (§9).
11. **Seeder** — `AdminSeeder` sostituito (§6).

## 9. Fuori scope

- **Assenze con granularità oraria.** Le assenze sono oggi per giornata intera, mentre la realtà quotidiana è spesso "Mario non c'è il pomeriggio". Si risolve con un orario di inizio e fine sull'assenza, ma tocca il calendario, il controllo dei conflitti (`conflitti_assenze_interventi_spec.md`) e la scelta del tecnico disponibile: è una feature a sé.
- **Inserimento delle proprie assenze da parte del dipendente.** Il Profilo è la sede naturale, ma va deciso se serve un'approvazione e come si comporta il calendario nel frattempo. Da affrontare insieme al punto precedente.
- **Portale clienti.** Questo spec si limita a non ostacolarlo: il Profilo funzionerà senza scheda personale e la vista Utenti mostrerà anche gli account non legati a un dipendente. Creazione dell'account dalla scheda cliente, permessi del gruppo `cliente` e dati modificabili sono da progettare a parte.
- **Recupero password autonomo.** Dipende dalla configurazione SMTP (punto 7 della review): finché la posta non è attiva, il ripristino resta a carico di un amministratore.
- **Fusione delle tabelle `personale` e `users`.** Mai: sono due cose diverse che restano separate nel database. Questo spec unifica l'interfaccia e il codice, non lo schema.
