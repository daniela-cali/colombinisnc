# Spec — Invio delle proposte di abbonamento per email

> Brainstorming dell'08/10/2026, ripreso e concordato il 13/10/2026.

## Contesto / problema

Le proposte di abbonamento si generano in Word dal gestionale (v0.38.0), una alla volta dalla
scheda o in uno zip dall'elenco. Per spedirle oggi bisogna scaricare il Word, salvarlo in PDF,
aprire il programma di posta, scrivere l'email, allegare il file e ricordarsi a chi è già stata
mandata. Il 2027 parte dalle proposte nate dal rinnovo (vedi `CLAUDE.md`, «Il 2027 parte dalle
proposte»): a fine anno saranno decine, e quasi tutte con lo stesso testo.

L'obiettivo è spedire la proposta in PDF dal gestionale, dalla casella dell'operatore che la
manda, e sapere sempre quali proposte sono già partite.

## Soluzione concordata

Dalla scheda di un abbonamento in proposta, oppure dall'elenco per più proposte selezionate,
«Invia per email» apre una finestra con oggetto e testo già compilati e modificabili. Il server
genera il Word, lo converte in PDF con LibreOffice e lo spedisce dalla casella Aruba
dell'operatore, con la sua firma, aggiungendo l'operatore in copia nascosta. L'abbonamento
annota quando e da chi è stata inviata la proposta.

### Decisioni e perché

1. **Ogni operatore spedisce dalla propria casella Aruba.** Il cliente risponde a chi gli ha
   scritto, e la copia firmata torna a quella persona. Come utente SMTP si usa l'email con cui
   l'operatore accede al gestionale; nel profilo l'operatore inserisce solo la password della
   casella.
2. **La password della casella sta nel database, cifrata; la chiave di cifratura nel `.env`.**
   Nessun account nel `.env`, che si modifica solo dal server. La password si cifra con il
   servizio `encrypter` di CodeIgniter, e la chiave (`encryption.key`) non può stare nel
   database: chi ne ottenesse una copia avrebbe insieme le password cifrate e la chiave per
   leggerle. La chiave è una riga sola, uguale per tutti, da impostare una volta per ambiente.
3. **Server e porta SMTP in Impostazioni → Email**, non nel `.env`: sono uguali per tutti,
   non sono segreti, e così si cambiano dall'interfaccia. Valori iniziali `smtps.aruba.it`,
   porta 465, SSL.
4. **Un'email contiene sempre una sola proposta.** L'invio multiplo spedisce una email per
   proposta, in fila.

   **Invio singolo in qualunque stato, multiplo solo per le proposte** (deciso il 14/10).
   Dalla scheda si spedisce anche il documento di un abbonamento già accettato, come già si
   genera il Word in qualunque stato: un cliente può chiedere di nuovo la copia. Il testo
   standard («restituirci firmata la copia per accettazione») lì non ha senso, e l'operatore
   lo corregge nella finestra: niente secondo testo standard finché il caso non si rivela
   frequente. L'invio multiplo resta lo strumento del rinnovo, solo sulle proposte.
5. **Oggetto e testo standard in Impostazioni → Email, modificabili.** Il segnaposto
   `{anno}` diventa l'anno di inizio dell'abbonamento: solo l'anno di inizio anche per un
   abbonamento di più anni. Testo iniziale:

   > **Oggetto:** Proposta di manutenzione in abbonamento {anno}
   >
   > Gentile Cliente,
   > in allegato le inviamo la nostra proposta di manutenzione in abbonamento per il {anno}.
   > Se di suo gradimento, la preghiamo di restituirci firmata la copia per accettazione.
   > Restiamo a disposizione per qualsiasi chiarimento.
   >
   > Cordiali saluti

   «Gentile Cliente» generico, senza il nome: nel Word si usa «Gentile Sig./Sig.ra» perché
   l'anagrafica non conosce il genere, e un'intestazione con la denominazione grezza
   («Gentile Rossi Mario») suonerebbe peggio di una formula neutra.
6. **Nella finestra d'invio l'operatore modifica oggetto e testo.** Un campo per l'oggetto e una
   textarea per il testo, già compilati con l'anno sostituito. Le modifiche valgono solo per
   quell'invio: il testo standard in Impostazioni non cambia. La firma non sta nella textarea:
   si aggiunge in coda all'invio, e nella finestra compare sotto in anteprima, non modificabile,
   perché nessuno la cancelli per sbaglio.
7. **Le proposte di un invio multiplo sono tutte dello stesso anno di inizio.** È un vincolo
   oggettivo, quindi bloccato lato server e non un avviso: se la selezione mescola anni
   diversi la finestra non si apre e il messaggio lo spiega. Così oggetto e testo mostrano un
   anno solo, già sostituito, come nell'invio singolo.
8. **Al massimo 20 proposte per invio multiplo, spedite una alla volta.** Il browser chiede al
   server di spedirne una, aspetta la risposta, aggiorna l'avanzamento e passa alla successiva,
   con una pausa di 2 secondi fra un invio e l'altro. Ogni invio è una richiesta breve (circa
   2–3 secondi fra conversione e SMTP), quindi nessun limite di tempo di PHP, e il ritmo resta
   lontano dai limiti di invio della casella Aruba. Il tetto di 20 è sulla selezione, non sulla
   soglia di Aruba: tiene corta l'attesa davanti allo schermo.
9. **Una proposta che non si può spedire non ferma le altre.** Cliente senza email, proposta
   già inviata, documento che sfora, errore di Aruba: quella proposta si salta con il motivo
   accanto, le altre proseguono, e alla fine compare il riepilogo. Le proposte senza email
   vanno sotto «da spedire per posta».
10. **Una proposta risulta inviata solo se l'email è partita davvero.** `proposta_inviata_at`
    si scrive dopo la risposta positiva del server SMTP, quindi un invio multiplo interrotto si
    riprende senza doppioni: le proposte già partite si saltano.
11. **Reinvio possibile, con conferma.** Dalla scheda, «Invia per email» su una proposta già
    inviata chiede «Già inviata il … da …, inviarla di nuovo?». Nell'invio multiplo le già
    inviate si saltano sempre: un reinvio è un caso singolo.
12. **«Segna come inviata».** Se la proposta va ritoccata a mano, si scarica il Word, si
    sistema, si spedisce dal proprio programma di posta, e nella scheda si usa «Segna come
    inviata»: scrive le stesse due colonne dell'invio, così l'elenco resta affidabile.
13. **Il documento non deve superare le 2 pagine.** Dopo la conversione il server conta le
    pagine del PDF con `pdfinfo`. Entrambi i modelli sono di 2 pagine: proposta e copia da
    restituire. Una proposta con molti periodi od operazioni può sforare, e la copia da
    restituire scivolerebbe su una terza pagina. In quel caso l'invio di quella proposta si
    ferma con «Il documento supera le 2 pagine: scarica il Word, sistemalo e usa Segna come
    inviata». Il controllo vale solo per l'invio, non per i download, dove l'operatore vede il
    documento prima di usarlo.
14. **Copia nascosta all'operatore.** Via SMTP Aruba non salva il messaggio nella cartella
    «Inviata»: la copia nascosta fa da archivio nella casella di chi ha spedito.
15. **La firma è quella HTML che l'operatore ha già**, incollata nel profilo come codice HTML.
    L'anteprima nel profilo e nella finestra d'invio sta in un `<iframe sandbox>`, perché un
    codice incollato non possa eseguire script nella pagina del gestionale. Le immagini
    incorporate come `data:` si trasformano all'invio in allegati inline con `cid:`, perché
    molti programmi di posta non mostrano le immagini `data:`. Verificato sulla firma vera:
    due immagini `data:` (logo Colombini in PNG da ~62 KB, logo Grundfos in JPEG da ~13 KB),
    nessun riferimento a indirizzi web o file locali.

    **La firma si carica come file `.html`**, non si incolla in una textarea: le firme in uso
    sono documenti HTML completi da ~106 KB, quasi tutti di base64. Al salvataggio si tiene il
    contenuto fra i commenti `INIZIO FIRMA` e `FINE FIRMA` se ci sono, altrimenti quello di
    `<body>`, e si tolgono i commenti (le istruzioni per Gmail e Outlook non devono finire
    nell'email).
16. **PDF anche da scaricare.** Accanto a «Proposta Word» nella scheda c'è «Proposta PDF»;
    accanto a «Scarica Word» nell'elenco c'è «Scarica PDF», per stampare in un colpo le
    proposte da mandare per posta. Usano la stessa conversione dell'invio.

    **Lo zip dei PDF si compone nel browser, a lotti di 20, senza un tetto** (deciso il 14/10).
    Misurata su questo server la conversione costa ~0,6 secondi a proposta (20 in 12 s, 60 in
    35 s): una sola richiesta reggerebbe ~90 proposte prima dei 60 secondi dopo cui Nginx la
    interrompe, e i documenti 2026 erano 124. Lo script chiede un lotto alla volta
    (`POST abbonamenti/proposte-pdf/lotto`, PDF in base64 nel JSON), mostra l'avanzamento,
    rende unici i nomi fra un lotto e l'altro e compone lo zip con fflate (`level: 0`: i PDF
    sono già compressi). Ogni lotto passa da un solo avvio di LibreOffice. Scartate due
    alternative: alzare il timeout di Nginx (un paio di minuti senza segni di vita, e una
    modifica al server) e lotti preparati in una cartella sul server (file che restano se
    l'operatore abbandona). Lo zip dei Word resta in una sola richiesta: senza LibreOffice è
    veloce anche con centinaia di proposte.
17. **La conversione non lascia file sul server**, come `DocumentoWord`: Word e PDF passano da
    una cartella temporanea che si cancella alla fine, anche in caso di errore. Ogni
    conversione usa un profilo LibreOffice proprio (`-env:UserInstallation` nella cartella
    temporanea): due conversioni contemporanee con lo stesso profilo si bloccano a vicenda.
18. **La classe `Email` di CodeIgniter**, già nel framework: SMTP, allegati e immagini inline
    con `setAttachmentCID()`. Nessuna libreria nuova.

### Selezione nell'elenco (decisa il 14/10/2026)

Fino alla v0.41 la casella c'era solo sulle righe in proposta e su quelle da rinnovare, e la
prima spunta decideva il «tipo» della selezione, disattivando le altre caselle: un
comportamento che non si spiegava da sé, e che impediva di scaricare i PDF degli abbonamenti
già attivi. Ora **ogni riga ha la sua casella, e sono i pulsanti a dire cosa si può fare con la
selezione**:

| Pulsante | Si accende se la selezione contiene… |
|---|---|
| Scarica Word / Scarica PDF | qualunque abbonamento |
| Invia per email | solo proposte, tutte dello stesso anno, fino al massimo delle Impostazioni |
| Accetta selezionati | solo proposte |
| Rinnova selezionati | almeno un abbonamento rinnovabile: rinnova solo quelli |

**Rinnova fa eccezione**: il rinnovo di fine anno si fa con «Seleziona tutto» sugli attivi,
che comprendono quelli già rinnovati nei giorni precedenti; se bastasse uno di loro a spegnere
il pulsante, andrebbero tolti a mano. Rinnova quindi lavora sui rinnovabili della selezione,
il contatore dice quanti sono e il tooltip spiega che gli altri si saltano. La coda del rinnovo
già scartava da sola chi non è rinnovabile.

**Perché un pulsante è spento lo dice il suo tooltip**, che a pulsante attivo descrive cosa
fa. Un pulsante `disabled` non riceve il passaggio del mouse, quindi il tooltip sta su uno
`span` che lo avvolge (`.tooltip-pulsante`, con `pointer-events: none` sul pulsante spento),
come indica Bootstrap. Provata prima una riga di riepilogo sopra la tabella con i motivi: non
si notava, e il motivo si cerca sul pulsante. La riga resta solo per il numero dei
selezionati. Il server ricontrolla comunque ogni azione (`accettaAbbonamento()` rifiuta ciò che non è in
proposta, il rinnovo ricontrolla `rinnovabile()`).

### Scelte di dettaglio (confermate il 14/10/2026)

- **Mittente**: «Colombini S.n.c. - Nome Cognome» dell'operatore, con il suo indirizzo. Il nome
  dell'azienda viene prima: il cliente riconosce chi scrive anche dall'elenco dei messaggi.
- **Nome dell'allegato**: quello vero del documento, lo stesso del Word con `.pdf` al posto di
  `.docx` (`PropostaAbbonamento::nomeFile()`): «CLIENTE ANNO TIPO.pdf». Il tipo distingue due
  abbonamenti dello stesso cliente nello stesso anno.
- **Email in HTML con alternativa in testo semplice**: il testo dell'operatore si converte con
  escape e a capo, poi segue la firma. La versione testuale serve ai programmi che non mostrano
  l'HTML e abbassa il rischio spam.
- **Prova della casella nel profilo**: «Invia un'email di prova a me stesso», con l'errore di
  Aruba in chiaro se la password è sbagliata. Senza password salvata, «Invia per email» è
  disattivato e il suo tooltip rimanda al profilo.
- **Nell'elenco** le proposte già inviate si distinguono dall'icona dell'invio piena, come oggi
  quella del Word generato.

## Strutture dati

```php
// abbonamenti — accanto a proposta_generata_at
'proposta_inviata_at' => ['type' => 'DATETIME', 'null' => true],
'proposta_inviata_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],

// personale_caselle_email — tabella nuova, una riga per dipendente che invia
'personale_id'  => ['type' => 'INT', 'unsigned' => true],    // UNIQUE, FK → personale.id, CASCADE
'smtp_password' => ['type' => 'TEXT', 'null' => true],       // cifrata, base64
'firma_email'   => ['type' => 'MEDIUMTEXT', 'null' => true], // ~106 KB: TEXT si ferma a 64 KB
// + created_by, updated_by, created_at, updated_at
```

In una tabella a parte collegata uno a uno alla scheda, decisa in fase di migration dopo aver
scartato le altre due collocazioni:

- **non su `users`**: Shield legge la riga a ogni richiesta per sapere chi è loggato, e ogni
  pagina caricherebbe ~106 KB di immagini e la password cifrata senza usarle. In più si
  aggiungerebbero colonne alla tabella di Shield, che oggi ha solo `ultima_versione_vista`;
- **non su `personale`**: si legge con `personale.*` in molti punti — `perUtente()` dal layout
  a ogni pagina, `elencoPerGruppi()` per le tendine dei tecnici, `find()` in sei controller. Si
  potrebbero escludere le due colonne query per query, ma sarebbe una convenzione da
  ricordare a ogni query nuova, che si dimentica senza errori.

Nella tabella separata la firma e la password si leggono solo dove servono, il profilo e
l'invio, e la tabella ha un suo model: nessun rischio che un form del personale le scriva. Chi
invia è sempre un dipendente, mai un account cliente del portale. L'email usata come utente
SMTP resta quella di login, letta dall'account collegato (`personale.user_id`).

Impostazioni (libreria Settings, come gli altri parametri): server SMTP, porta, oggetto e testo
standard della proposta.

Preparazione della finestra, per una proposta o per venti, con risposta JSON. I vincoli
oggettivi (casella non configurata, più di 20, una riga non in proposta in un invio multiplo,
anni diversi) bloccano con `ok: false`; la lista è informativa, le righe senza email o già
inviate compaiono con il loro stato:

```
POST abbonamenti/invio/prepara    ids[]
→ { "ok": true, "anno": "2027", "oggetto": "…", "testo": "…", "firma": "<table>…</table>",
    "proposte": [ { "id": 70, "cliente": "…", "email": "info@…", "inviata": null },
                  { "id": 67, "cliente": "…", "email": null, "inviata": "12/11/2026 da …" } ] }
→ { "ok": false, "motivo": "Le proposte selezionate sono di anni diversi: 2026 e 2027." }
```

Invio, uno per proposta, con risposta JSON:

```
POST abbonamenti/{id}/invia    oggetto, testo, anno, reinvio (0/1)
→ { "ok": true }
→ { "ok": false, "motivo": "Il cliente non ha un indirizzo email", "per_posta": true }
```

«Segna come inviata» è un form normale: `POST abbonamenti/{id}/segna-inviata`.

`anno` è quello mostrato nella finestra: il server rifiuta la proposta se il suo anno di inizio
è diverso, così il vincolo della decisione 7 regge anche se la pagina è rimasta aperta mentre
qualcuno modificava un abbonamento.

## Alternative scartate

- **PDF con PhpWord e dompdf**: provato l'08/10, perde intestazioni, loghi e tabulazioni.
- **Una casella unica aziendale** per tutti gli invii: le risposte con la copia firmata
  finirebbero in un posto solo, staccate da chi segue il cliente.
- **Account SMTP nel `.env`**: si modificherebbero solo dal server, e ogni operatore nuovo
  richiederebbe un intervento lì.
- **Invio a blocchi da 5 o 10**: un invio per richiesta, in fila, dà lo stesso ritmo lento con
  un avanzamento preciso e senza richieste lunghe.
- **Un messaggio aggiuntivo separato dal testo**: più rigido che rendere modificabile il testo
  intero.
- **Firma costruita dal gestionale** con logo e dati aziendali: gli operatori hanno già una
  firma HTML.

## Modifiche file per file

1. **Due migration**, una per argomento: `AddPropostaInviataToAbbonamenti` (la traccia
   dell'invio) e `CreatePersonaleCaselleEmail` (casella e firma, che serviranno anche ai
   preventivi).
2. **`app/Libraries/ConvertitorePdf.php`** (nuovo): uno o più Word → PDF con LibreOffice in una
   cartella temporanea, profilo proprio, conteggio delle pagine con `pdfinfo`, pulizia finale.
3. **`app/Libraries/EmailOperatore.php`** (nuovo): costruisce la `Email` di CodeIgniter con
   server e porta dalle Impostazioni e la casella dell'utente loggato, aggiunge la firma con le
   immagini in `cid:`, la copia nascosta e l'alternativa testuale. Generica: servirà identica
   per i preventivi.
4. **`app/Libraries/PropostaAbbonamento.php`**: oggetto e testo standard con l'anno sostituito.
5. **`app/Models/AbbonamentiModel.php`**: `segnaPropostaInviata()`, le colonne nuove dove
   servono nelle query dell'elenco e della scheda.
6. **`app/Models/PersonaleCaselleEmailModel.php`** (nuovo): lettura e scrittura della
   password cifrata e della firma per un dipendente.
7. **`app/Controllers/AbbonamentiController.php`**: `propostaPdf()`, zip dei PDF accanto a
   `proposteWord()` (con la parte comune estratta), `preparaInvio()` (controlla la selezione:
   stato, anno unico, massimo 20, e restituisce oggetto e testo), `invia()`,
   `segnaInviata()`.
8. **`app/Controllers/ProfiloController.php`** e **`app/Views/profilo/index.php`**: card
   «Posta» con password della casella, firma con anteprima e prova d'invio.
9. **`app/Controllers/Impostazioni/EmailController.php`** e **`app/Views/impostazioni/email.php`**
   (nuovi): pagina «Email» con server, porta, cifratura, nome nel mittente, oggetto e testo della
   proposta; prende il posto della scheda «Notifiche Email — In costruzione» in Impostazioni.
10. **`app/Config/Routes.php`**: le rotte nuove nel gruppo `abbonamenti` e nel profilo.
11. **View degli abbonamenti**: `show.php` (Proposta PDF, Invia per email, Segna come inviata,
    «Inviata il … da …»), `index.php` (Scarica PDF, Invia selezionate, icona dell'invio),
    partial nuovo `_invio_email.php` con la finestra, condivisa dai due punti.
12. **`public/js/invio-proposte.js`** (nuovo): finestra, invio in fila, avanzamento e
    riepilogo, uguale per una proposta e per venti.
13. **`app/Views/help/abbonamenti.php`**: invio, reinvio, Segna come inviata, limiti.
14. **Documentazione**: schema del database in `docs/`, la sezione «Documenti Word» di
    `CLAUDE.md` per la conversione in PDF, `CHANGELOG.md`, §7.1 di `ANALISI.md`.

## Deploy in produzione

- Pacchetti, come in sviluppo: `libreoffice-writer-nogui`, `ttf-mscorefonts-installer`
  (richiede `contrib` in `debian.sources`), `poppler-utils`.
- `encryption.key` nel `.env` di produzione, generata lì (`php spark key:generate`), diversa da
  quella di sviluppo.
- `php spark migrate --all`.
- In Impostazioni → Email: controllare server, porta, oggetto e testo.
- Ogni operatore: password della casella e firma nel profilo, poi l'email di prova.

## Fuori scope

- Salvare il messaggio nella cartella «Inviata» via IMAP: c'è la copia nascosta.
- Sapere se il cliente ha aperto l'email, e promemoria automatici per le proposte senza
  risposta.
- Un editor Word nel gestionale (Collabora Online, OnlyOffice): si valuta quando si vede
  quanto spesso le proposte vanno ritoccate prima dell'invio.
- Invio dei preventivi: arriverà con i preventivi, riusando `EmailOperatore` e
  `ConvertitorePdf`.
- Il recupero password via email di Shield (review, punto 7): usa la posta del sistema, non le
  caselle degli operatori, e si configura a parte.
