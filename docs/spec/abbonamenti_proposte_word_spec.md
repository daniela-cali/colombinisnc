# Spec: Abbonamenti — Proposta in Word (fase 2 di 2)

> Segue `abbonamenti_proposte_spec.md` (fase 1, v0.26.0), che ha introdotto lo stato `proposta`, l'accettazione e i campi `operazioni_incluse` e `modalita_pagamento` pensando a questo documento. Decisioni prese nel brainstorming del 06/10/2026.
>
> Costruisce il **motore di generazione Word condiviso** che riuseranno i preventivi (`preventivi_impianti_spec.md` §8.5).

## Contesto

Oggi la proposta di abbonamento si prepara a mano in Word, copiando i dati del cliente e dell'abbonamento in un modello. Il 2027 parte dalle proposte (vedi `CLAUDE.md`, «Il 2027 parte dalle proposte»): a fine anno andranno preparate e spedite decine di proposte, per email ai clienti che ce l'hanno e per posta agli altri, che sono ancora parecchi.

Tutti i dati sono già nel gestionale. Manca il passaggio che li porta nel documento.

I modelli di partenza sono in `docs/spec/`: `2026 IMPIANTI MODELLO-PROVA-ABBONAMENTO.docx` e `2026 PISCINE MODELLO-PROVA-ABBONAMENTO.docx`. Ogni proposta è di **due pagine identiche**: una resta al cliente, l'altra («Copia da restituire firmata al Centro Assistenza») torna firmata. È sempre stato così e resta così.

## Decisioni chiave

### 1. Si parte dal modello IMPIANTI, per gli addolcitori

È il caso più completo: oltre ai dati semplici ha due elenchi a righe variabili, le operazioni e le apparecchiature. Se il motore regge questo, il modello delle piscine è un sottoinsieme e si aggiunge dopo.

Il modello si sceglie dalla **categoria del tipo di intervento** (`tipi_intervento.categoria`): `addolcitori` → modello impianti. Una categoria senza modello non mostra il bottone, quindi oggi piscine e generale non generano niente.

### 2. Apparecchiature installate: testo libero, salvato e obbligatorio

Nuovo campo `abbonamenti.apparecchiature` (TEXT, nullable), **una riga per apparecchiatura** nella forma del modello: «N. 1 ADDOLCITORE».

- **Obbligatorio per gli addolcitori**, con controllo anche lato server: l'apparecchiatura è la ragione stessa dell'abbonamento, e un abbonamento addolcitori senza apparecchiature non esiste. È un vincolo oggettivo, quindi si blocca e non si avvisa soltanto. Per le piscine l'impianto è la piscina stessa, sottintesa: il campo non compare.
- **Nel form compare solo se il tipo scelto è della categoria addolcitori**, con lo stesso meccanismo JS che già precompila `operazioni_incluse` al cambio di `tipo_intervento_id`. Nascondere il campo non lo svuota: se si torna a un tipo addolcitori, il testo è ancora lì.
- **Il rinnovo lo porta all'anno dopo** senza codice in più: `rinnova()` precompila il form con tutto l'abbonamento precedente, e il textarea lo ripresenta. L'obbligo pesa quindi solo alla prima creazione.
- **Nessuna precompilazione dal tipo**, a differenza delle operazioni: le apparecchiature dipendono dal cliente, non dal tipo di abbonamento.

**Niente colonna «in attesa» per gli impianti futuri.** Più avanti, non nel 2027, esisteranno gli impianti dei clienti. Si è deciso di non prevedere oggi un campo che li colleghi, per tre motivi:

- il collegamento futuro non avrà la forma di una colonna. Un abbonamento copre più apparecchiature e un'apparecchiatura passa di abbonamento in abbonamento negli anni: è un molti-a-molti, cioè una tabella `abbonamenti_impianti` che nascerà con gli impianti. Un `impianto_id` aggiunto oggi sarebbe sbagliato già nella forma. È lo stesso ragionamento fatto in fase 1 per `modalita_pagamento` contro una tabella dei pagamenti che non esiste;
- aggiungerla dopo non costa niente e non perde dati;
- il testo libero non diventa inutile quando arriveranno gli impianti, ma cambia ruolo: resta la **fotografia** di cosa diceva la proposta di quell'anno, come la descrizione su una riga di fattura. Con gli impianti si potrà precompilare dagli impianti del cliente. Lo storico non va convertito.

La convenzione «una riga per apparecchiatura» serve anche al futuro: uno script potrà proporre l'abbinamento fra le righe e gli impianti, come aiuto e non in automatico.

### 3. Operazioni: già coperte dalla fase 1

`tipi_intervento.operazioni_standard` e `abbonamenti.operazioni_incluse` esistono dalla v0.26.0. Ogni riga del testo diventa una voce dell'elenco nel documento, quindi nel campo **non vanno scritti simboli a inizio riga** («-», «°»): il simbolo lo mette il modello, altrimenti nel documento comparirebbe due volte.

Lavoro di dati, nessun codice: inserire nelle Operazioni standard del tipo Addolcitori le 7 righe del modello impianti, prima in sviluppo per le prove e poi in produzione.

### 4. Contenuto del documento

| Nel modello | Da dove arriva |
|---|---|
| «Stim. Sig.» | **«Gentile Sig./Sig.ra»** per le persone fisiche, **«Spett.le»** per società e condomini (`clienti.tipo`) |
| MARIO ROSSI | persona fisica: **nome e cognome**, nell'ordine del modello (la colonna `denominazione` ha invece cognome e nome); società: ragione sociale |
| indirizzo, CAP, città | **indirizzo del cliente**, non del luogo dell'impianto: è dove riceve la posta |
| «Tel …» e «Posta elettronica: …» | `clienti.telefono` e `clienti.email`: **i contatti che esistono si indicano**, e una riga senza dato sparisce invece di restare un'etichetta vuota |
| frase iniziale | fissa nel modello: «…prevede l'effettuazione di visite periodiche di controllo alle apparecchiature installate.» |
| FREQUENZA DELLE VISITE | etichetta della frequenza del periodo, in maiuscolo («BIMESTRALE») |
| Apparecchiature installate | `abbonamenti.apparecchiature`, una voce per riga |
| operazioni | `abbonamenti.operazioni_incluse`, una voce per riga |
| DURATA DEL SERVIZIO | «UN ANNO» fisso nel modello, poi «dal `data_inizio` al `data_fine`» in formato `01/06/2026` |
| PREZZO COMPLESSIVO | `abbonamenti.prezzo` in formato italiano («500,00»); «Euro … + Iva 22%» fisso nel modello |
| PAGAMENTO | `abbonamenti.modalita_pagamento`; l'etichetta è testo fisso del modello e si sceglie in Word |
| condizioni di abbonamento | testo fisso nel modello |
| «Ceriale, 03 dicembre 2025» | **data di generazione**, scritta per intero come da sempre: giorno a due cifre, mese in lettere, anno. Compare solo in fondo alla seconda pagina, accanto a «Per accettazione, il Cliente» |
| intestazione e piè di pagina | testo fisso del modello: dati Colombini, «Pag. 1 di 2», titolo, la nota Culligan/Grundfos. La seconda pagina ha in più «Copia da restituire firmata al centro assistenza» |

Motivazioni delle scelte meno ovvie:

- **«Gentile Sig./Sig.ra» e non il genere.** L'anagrafica non conosce il genere e l'import da Ad Hoc non lo porta. Un campo «titolo» andrebbe compilato a mano su centinaia di schede, e ricavarlo dal nome sbaglia su nomi come Andrea o Celeste: un errore di genere in una lettera pesa più di una formula neutra. Il documento esce in Word, quindi un caso particolare si ritocca a mano prima di spedirlo. Il genere in anagrafica si potrà aggiungere più avanti senza toccare il motore.
- **«UN ANNO» e non «UN ANNO SOLARE».** Esistono abbonamenti annuali non allineati all'anno solare, per esempio dal 01/06/2026 al 31/05/2027. Le date dicono già tutto: riconoscere il caso solare non serve. Il rinnovo sposta entrambe le date di un anno esatto, quindi regge questi casi.
- **Una sola frequenza.** Oggi gli abbonamenti addolcitori hanno sempre un solo periodo. Se un giorno ne avessero più d'uno con frequenze diverse, il documento le elenca separate da « / » nell'ordine dei periodi: non si blocca la generazione per un caso che oggi non esiste.
- **IVA al 22% fissa nel modello**, perché è sempre quella.

**Le due copie stanno ciascuna in una pagina.** Il modello è una sezione sola: le due copie sono separate da un'interruzione di pagina, e la seconda pagina riconosce di essere «la copia da restituire» solo perché usa un'intestazione diversa dalla prima. Se gli elenchi sono lunghi, la prima copia sborda sulla seconda pagina e l'intestazione finisce sopra il testo sbagliato. Con 2 apparecchiature e 7 operazioni ci sta. In fase di prova va generata anche una proposta con elenchi lunghi, per sapere dove sta il limite. Il codice non può accorgersene, perché l'impaginazione la calcola Word quando apre il file.

### 5. Generazione al volo, nessun file sul server del gestionale

Il documento si genera al momento e si scarica. Il gestionale non conserva il file: l'archivio è quello dell'ufficio, sul server locale di Colombini, dove il file si salva dopo averlo scaricato. **I dati restano sempre nel database**, che è la fonte: il documento si può rigenerare quando serve, per esempio dopo aver corretto il prezzo.

Il documento che vale davvero è la copia firmata che torna dal cliente. Per questo non serve tenere traccia delle versioni generate.

**Si può generare in qualunque stato**, non solo in `proposta`: serve anche a ristampare la proposta di un abbonamento già attivo, se il cliente l'ha persa. Il titolo «PROPOSTA DI MANUTENZIONE» resta corretto: è il documento che il cliente ha firmato.

### 6. Data dell'ultima generazione

Nuova colonna `abbonamenti.proposta_generata_at` (DATETIME, nullable), aggiornata a ogni generazione. A fine anno, con decine di proposte, serve vedere a colpo d'occhio quali sono già state preparate. È il tracciamento leggero previsto in `CLAUDE.md`: una colonna, non una tabella di log.

- **Non tocca `updated_at` né `updated_by`.** Generare un documento non modifica l'abbonamento: un metodo del model aggiorna solo questa colonna, passando dal builder senza i timestamp automatici. Il motivo va scritto nel docblock, perché altrove nel progetto si passa sempre dal model.
- **Al rinnovo non passa all'abbonamento nuovo.** `store()` salva solo ciò che arriva dal form, e la colonna non è un campo del form: la proposta rinnovata parte senza data, giustamente.
- Nella scheda si mostra «Proposta generata il …». Nell'elenco, sulle righe in stato proposta, c'è un **bottone Word** fra le azioni che scarica la proposta e dice anche se è già stata generata: **pieno** sì, **contornato** no, con la data nel tooltip. Nella prima versione era un'icona accanto al badge dello stato: in prova si è rivelata poco intuitiva, perché compariva solo dopo e non diceva cosa significasse.

### 7. Una alla volta o tutte insieme

- **Singola**: bottone «Proposta Word» nel `card-tools` della scheda abbonamento (`btn btn-sm btn-outline-secondary` con `bi-file-earmark-word`, come gli strumenti che portano altrove; vedi `CLAUDE.md`, «Azioni nelle card»).
- **Multipla**: nell'elenco le caselle di selezione esistono già, solo sulle righe `proposta`, per «Accetta selezionati». Accanto si aggiunge «Scarica proposte», che invia **la stessa selezione** a un'altra azione con l'attributo `formaction` del bottone: nessun secondo form e nessuna seconda serie di caselle. Il risultato è **uno zip con un file per proposta**:
  - per l'email, si allega il file di quel cliente, già pronto e con il nome giusto;
  - per la posta, si estrae lo zip, si selezionano tutti i file e con tasto destro → Stampa Windows li stampa in sequenza.

  Scartato un unico Word con tutte le proposte di seguito: comodo per stampare, ma PhpWord non sa unire documenti in modo pulito, e per l'email andrebbe comunque diviso.

  **Il form della selezione multipla non racchiude più la tabella.** Prima la tabella stava dentro `form-accetta-multiplo`, e i form Accetta/Rifiuta di ogni riga risultavano annidati, cosa che l'HTML non ammette: il parser scarta il primo form interno, e l'Accetta della prima proposta inviava la selezione multipla. Il difetto c'era dalla v0.26.0 ed è emerso aggiungendo un secondo bottone allo stesso form. Ora il form è vuoto e sta fuori dalla tabella; caselle e bottoni gli si collegano con l'attributo `form`. La conferma «Accettare le proposte selezionate?» passa dall'`onsubmit` del form al bottone, altrimenti sarebbe comparsa anche scaricando lo zip.

- **Le proposte che non si possono generare** non bloccano le altre: una categoria senza modello, oggi le piscine, o un dato mancante (decisione 11). Finiscono in un file `NON GENERATE.txt` dentro lo zip, con il motivo per ciascuna. Una risposta che scarica un file non può portare con sé un messaggio flash.

### 8. Nome del file

Criterio provvisorio, **da confermare**: `<DENOMINAZIONE> <ANNO> <TIPO>.docx`, per esempio `ROSSI MARIO 2027 ADDOLCITORI.docx`. Se nello zip un nome si ripete (stesso cliente, anno e tipo: una proposta rifiutata e rifatta) le successive prendono « (2)», « (3)», confrontando i nomi senza distinguere maiuscole e minuscole, come fa Windows.

- **L'anno è quello di inizio del servizio**, non quello di generazione: le proposte del 2027 si preparano a fine 2026.
- **Il tipo serve a distinguere i doppioni**: un cliente con due abbonamenti nello stesso anno, piscina e addolcitore, produrrebbe due file con lo stesso nome, e nello zip il secondo sovrascriverebbe il primo.
- **I caratteri vietati da Windows** (`/ \ : * ? " < > |`) diventano un trattino: «IDRO 2000 S/N» → «IDRO 2000 S-N».

Lo zip si chiama `Proposte abbonamento <data>.zip`.

### 9. Il motore Word condiviso

Una classe in `app/Libraries/` sopra il `TemplateProcessor` di PhpWord. Fa **solo ciò che serve alle proposte**: apre il modello, sostituisce i segnaposto, ripete gli elenchi, toglie i blocchi vuoti e restituisce il contenuto del file. Le schede impianto dei preventivi, cioè blocchi ripetuti con più campi dentro, le aggiungeranno i preventivi quando serviranno, sapendo che forma devono avere: disegnarle oggi vorrebbe dire indovinare.

Punti tecnici da non perdere:

- **Escape dell'output attivo** (`Settings::setOutputEscapingEnabled(true)`). Di default PhpWord inserisce i valori così come sono, quindi una ragione sociale con «&» («ROSSI & BIANCHI») produrrebbe un `.docx` che Word non apre.
- **I blocchi si scrivono `${nome}` … `${/nome}`**, ciascun marcatore in un paragrafo suo, con un nome diverso da quello della riga che contiene: `${operazioni}` / `- ${operazione}` / `${/operazioni}`. Le righe facoltative usano lo stesso meccanismo con una voce o nessuna: `${riga_telefono}` / `Tel ${telefono}` / `${/riga_telefono}`.
- **Le due pagine hanno gli stessi elenchi.** I segnaposto semplici si sostituiscono in tutte le occorrenze. Un blocco PhpWord lo elabora una volta per chiamata (le copie identiche insieme), quindi il motore ripete l'operazione finché il blocco è presente, con un tetto ai passaggi.
- **Escape fatto dal motore nei blocchi.** `cloneBlock()` inserisce i valori delle righe ripetute senza escape, anche con l'opzione attiva: lo fa `DocumentoWord::elenco()`.
- **Un segnaposto rimasto è un errore.** Prima di restituire il file il motore verifica che non resti nessun `${...}`, e in caso contrario si ferma elencandoli: un nome sbagliato nel codice o un segnaposto aggiunto al modello non producono un documento con `${prezzo}` scritto in mezzo.
- **Nessun file temporaneo che resti in giro.** PhpWord lavora su una copia del modello nella cartella temporanea di sistema, non in `writable/`, le cui sottocartelle hanno altri scopi. Il motore la salva, ne legge il contenuto e la cancella, anche quando poi segnala un errore, e la risposta parte con `download($nome, $contenuto)`. Lo stesso vale per lo zip. Un `.docx` pesa circa 80 KB: anche cinquanta proposte stanno comodamente in memoria.

### 10. I modelli nel repository, preparati da Claude

I modelli stanno in **`app/Templates/word/`**, versionati con il codice: cambiano di rado, e un modello aggiornato va in produzione con il deploy, come tutto il resto. Scartato caricarli da una pagina delle impostazioni: una pagina da costruire per un'operazione che capita forse una volta l'anno.

**I segnaposto li inserisce Claude**, su una copia del modello di Daniela. Scritto in Word, `${cliente}` viene spesso spezzato in più pezzi invisibili dal controllo ortografico o dalle revisioni, e PhpWord non lo riconosce più. È l'errore più comune con questa libreria, ed è silenzioso: nel documento resta scritto `${cliente}`. Si parte dal modello impianti che Daniela ha aggiornato il 06/10/2026, con intestazione e piè di pagina veri e le due copie già allineate, e si cambia soltanto «apparecchiature Culligan» in «apparecchiature installate». Poi Daniela apre il file in Word e controlla che l'aspetto sia identico al suo. Il modello resta un normale `.docx`, modificabile in Word per lo stile e per i testi fissi: basta non riscrivere a mano i segnaposto.

Il controllo visivo ha portato altre correzioni al modello, fatte nello stesso passaggio:

- tutto in **Verdana**: le intestazioni ereditavano Aptos dal tema e la riga della firma lo aveva esplicito;
- **dati aziendali in basso**: in alto restano logo, titolo e numero di pagina; ragione sociale («Colombini S.n.c. di Colombini Giorgio, Flavia e Paolo»), indirizzo, contatti e dati fiscali vanno nel piè di pagina, centrati fra i due loghi, su cinque righe a 7–8 pt perché lì lo spazio è di circa 13 cm;
- **piè di pagina**: tolta la frase su Culligan/Grundfos e una copia invisibile del logo Grundfos ancorata fuori pagina; il logo Save Water, una PNG con due versioni affiancate, è ritagliato sulla sola blu e messo alla stessa distanza dal bordo a cui Grundfos sta dall'altro;
- **intestazioni**: «Pag. 1 di 2» della prima pagina cadeva sul logo per via della tabulazione centrale dello stile, ora è allineato a destra come sulla seconda; sulla seconda pagina «Copia da restituire firmata al centro assistenza» va su una riga sua, a 9 pt, sotto il titolo;
- **la data anche sulla prima copia**, quella che resta al cliente: sotto la linea che chiude le condizioni, come sulla seconda, senza la firma;
- **spazio per la seconda copia**, che con queste aggiunte finiva in una terza pagina: tolte le righe vuote in fondo ai piè di pagina, rimaste da quando i loghi erano agganciati a paragrafi vuoti, e le righe vuote fra logo e titolo della seconda pagina rese uguali a quelle della prima (erano 40 pt contro 28). Provato con un abbonamento a tre apparecchiature.

Lo script che ha preparato il modello è servito una volta e non è nel repository: un modello rifatto da zero in Word andrà ripreparato allo stesso modo.

### 11. Senza prezzo, operazioni e apparecchiature la proposta non si genera

Una proposta senza prezzo non è una proposta. Il controllo sta sul server, nell'azione di generazione: la scheda risponde con un messaggio d'errore, e nello zip la proposta finisce in `NON GENERATE.txt`. Il prezzo **non** diventa obbligatorio nel form dell'abbonamento: si può salvare una proposta in lavorazione e completarla prima di generarla.

Lo stesso vale per **operazioni incluse** e **apparecchiature**, decisione presa in prova. Gli abbonamenti creati prima che le operazioni standard degli addolcitori fossero compilate hanno il campo vuoto, e il documento sarebbe uscito con l'intestazione dell'elenco e niente sotto. Scartato il ripiego sul testo standard del tipo: il documento direbbe una cosa diversa da quella salvata sull'abbonamento. Le apparecchiature le impone già il form, ma non agli abbonamenti creati prima che il campo esistesse.

La modalità di pagamento invece può mancare: la riga resta con il valore vuoto, da completare in Word se serve.

## Alternative scartate

- **Salvare il documento sul server del gestionale**: l'archivio vive sul server dell'ufficio, e i dati per rigenerarlo sono nel database. Tenere il file anche qui significherebbe gestire versioni e cancellazioni senza un bisogno reale.
- **PDF invece di Word**: il Word si ritocca prima di spedirlo, per esempio il «Gentile Sig./Sig.ra» per un cliente di lunga data, e la conversione automatica richiederebbe LibreOffice sul server. Per l'email il PDF si ottiene da Word con «Salva come PDF». Stessa decisione presa per i preventivi (§8.3).
- **Colonna di collegamento agli impianti futuri** e **genere in anagrafica**: vedi decisioni 2 e 4.
- **Un unico Word con tutte le proposte**: vedi decisione 7.

## Riepilogo modifiche file per file

1. `composer.json` — `phpoffice/phpword`. Richiede l'estensione `zip`, presente sul server, che serve anche allo zip delle proposte. In produzione arriva con il `composer install` della sequenza di deploy.
2. `app/Database/Migrations/...AddApparecchiatureToAbbonamenti.php` (nuova) — `apparecchiature` (TEXT, nullable) e `proposta_generata_at` (DATETIME, nullable) su `abbonamenti`.
3. `app/Models/AbbonamentiModel.php` — `apparecchiature` in `$allowedFields`; metodo che segna la generazione senza toccare `updated_at`/`updated_by`.
4. `app/Libraries/` — il motore Word (decisione 9) e la classe che compone i dati della proposta: destinatario, date, prezzo, nome del file (decisioni 4 e 8). La mappa categoria → modello vive lì, così aggiungere le piscine significa aggiungere una voce e un file.
5. `app/Templates/word/proposta_addolcitori.docx` (nuovo) — il modello impianti con i segnaposto.
6. `app/Controllers/AbbonamentiController.php` — `regolaValidazione()` rende obbligatorio `apparecchiature` quando il tipo è della categoria addolcitori; nuove azioni per la proposta singola e per lo zip.
7. `app/Config/Routes.php` — due rotte nel gruppo `abbonamenti`, sotto `permission:abbonamenti.manage`: la generazione scrive sull'abbonamento.
8. `app/Views/abbonamenti/nuovo.php` ed `edit.php` — textarea `apparecchiature`, visibile solo per la categoria addolcitori.
9. `app/Views/abbonamenti/show.php` — bottone «Proposta Word» se la categoria ha un modello; apparecchiature e data dell'ultima generazione fra i dati.
10. `app/Views/abbonamenti/index.php` — bottone «Scarica proposte» accanto ad «Accetta selezionati»; bottone Word sulle righe in proposta; form della selezione multipla fuori dalla tabella (decisione 7).
11. `app/Helpers/validazione_helper.php` — non toccato: la regola delle apparecchiature ha un messaggio suo.
12. `app/Views/help/abbonamenti.php` — come si genera la proposta e come si scrivono operazioni e apparecchiature, una riga per voce, senza simboli.
13. `docs/` (schema del database e log delle modifiche), `CHANGELOG.md`, `docs/ANALISI.md` §7.1 — a chiusura, come da convenzione.

## Fuori scope

- **Modello piscine**: un capitolo a sé, da affrontare più avanti con lo stesso motore. Dal modello reale si vede già che sarà più complesso: più periodi scritti con le loro date («Dal 1 Maggio al 30 Settembre: QUINDICINALE»), «SENZA PULIZIA DEL FONDO» (`abbonamenti_periodi.con_pulizia_fondo`), una durata che non è un anno (dal 01.11.2025 al 31.12.2026) e un secondo prezzo, quello della pulizia del fondo su richiesta a intervento, che nel gestionale non esiste.
- **Le eccezioni degli abbonamenti impianti**, che ci saranno: si analizzano quando capitano. Il documento esce in Word, quindi un caso fuori dallo schema si ritocca a mano.
- **Amministratori di condominio** come destinatari, con il loro indirizzo: nella prima versione la proposta va al condominio. Voce «Referente per le comunicazioni del cliente» in `docs/backlog.md`.
- **Genere del cliente in anagrafica**, per scrivere «Sig.» o «Sig.ra» invece di «Sig./Sig.ra».
- **Impianti dei clienti** e il loro collegamento agli abbonamenti: non nel 2027.
- **Invio automatico per email** delle proposte.
- **Conversione automatica in PDF.**
- **Blocchi ripetuti con più campi** (schede impianto dei preventivi): li aggiungono i preventivi.

## Da confermare

- **Il criterio del nome del file** (decisione 8).
