# Numeratori atomici e visibilità dei progressivi

## Contesto

`ClientiModel::generaCodice()` ricavava il prossimo codice interno da `MAX(codice)`, con tre
difetti (punto 4 di `docs/review/2026-08-16-review-progetto.md`):

1. **Ordinamento alfabetico.** Finché tutti i codici hanno la stessa lunghezza funziona, ma
   basta mescolare 3 e 4 cifre perché `INT-020` risulti maggiore di `INT-0021`: il massimo
   trovato resta quello vecchio e il codice generato collide con uno esistente, facendo
   fallire il salvataggio sul vincolo `UNIQUE` con un errore SQL grezzo.
2. **Regressione dopo una cancellazione.** Eliminato l'ultimo cliente, il suo codice viene
   riassegnato al prossimo — e nel frattempo può essere finito su un preventivo.
3. **Nessuna atomicità.** Due salvataggi simultanei ottengono lo stesso numero.

Il progetto conosceva già la soluzione: `InterventiModel::generaCodice()` usa un contatore in
`settings` con `SELECT … FOR UPDATE`. Quel ragionamento non era stato riportato sui clienti,
e i due generatori risolvevano lo stesso problema in due modi diversi.

Manca inoltre qualsiasi visibilità sui progressivi: per sapere a che numero è arrivata una
serie bisogna interrogare a mano la tabella `settings`.

## Decisioni chiave

### 1. Contatore atomico, non padding più largo

La correzione economica — portare il padding a 4 cifre — è stata **scartata**, ed è anzi
l'unica delle opzioni valutate che avrebbe rotto qualcosa di funzionante: con i codici
esistenti a 3 cifre, il primo codice a 4 sarebbe risultato "minore" nell'ordinamento
alfabetico e la funzione avrebbe restituito sempre lo stesso valore, fallendo al secondo
cliente. Per farla funzionare servivano comunque o la normalizzazione dei codici esistenti o
l'ordinamento per la parte numerica: a quel punto il costo pareggia quello della soluzione
giusta, che però risolve anche i difetti 2 e 3.

Il volume non è il problema — `CLI-9999` non arriverà mai in questo progetto — ma il difetto
non dipende dal volume.

### 2. Un solo punto che sa cos'è una sequenza

La logica va in `NumeratoriModel`, che è l'unico posto a conoscere transazione, lock,
incremento e formato. `InterventiModel::generaCodice()` e `ClientiModel::generaCodice()`
restano come API pubbliche — i controller continuano a chiamarle invariate — ma delegano.

L'estrazione non nasce dal principio astratto di non duplicare: nasce dal fatto che la
pagina Impostazioni deve **leggere** gli stessi numeratori che il codice incrementa, e
sarebbe assurdo che lettura e scrittura vivessero in file diversi.

### 3. `SELECT … FOR UPDATE` dentro una transazione

Il lock esclusivo dura fino al `COMMIT`: il secondo salvataggio attende che il primo abbia
incrementato, invece di leggere lo stesso valore. Fuori da una transazione sarebbe inutile —
in autocommit il lock si libera immediatamente.

Il lock vincola solo chi lo richiede: una `SELECT` ordinaria legge lo snapshot e non si
blocca. Per questo il valore va letto così in **ogni** punto che lo incrementa, ed è una
ragione in più per averne uno solo.

### 4. Il prefisso dei clienti passa da `INT-` a `CLI-`

`INT` significava "interno" sui clienti e "intervento" sugli interventi: lo stesso prefisso
per due cose diverse. La conversione costa zero adesso, con l'anagrafica di produzione vuota
dopo la ricostruzione del 26/08, e i venti codici di sviluppo si convertono nella migration.

### 5. I codici non si riassegnano mai

Richiesta esplicita: un codice usato da un cliente poi eliminato resta bruciato. È una
conseguenza gratuita del contatore, che non torna indietro perché non guarda i codici
esistenti. Il caso è rarissimo — un cliente si elimina solo se non ha nulla di collegato — ma
un codice che identifica due soggetti diversi in momenti diversi è confusione che si scopre
mesi dopo, su documenti già stampati.

### 6. Prefisso parametrico

Ogni prefisso ha la sua riga (`seq_CLI`, `seq_INT`, `seq_PIS`, `seq_ADD`), creata al primo
utilizzo. Serve già agli interventi, che derivano il prefisso dal tipo, e servirà ai clienti
il giorno in cui una serie separata avesse senso.

### 7. Padding a 4 cifre per tutti

Con il contatore la lunghezza non incide più sulla correttezza — il prossimo numero non si
ricava ordinando stringhe — ma un formato unico evita elenchi con codici di due fogge.

**Non è un tetto.** `str_pad()` riempie fino a quattro cifre e non tronca: dopo `PIS-9999`
arriva `PIS-10000` e la serie prosegue. La cosa non è teorica per gli interventi, dove un
abbonamento settimanale produce una cinquantina di visite l'anno per cliente e la serie di un
tipo supera le 9999 nel giro di pochi anni; semplicemente, quando accadrà, i codici avranno
una cifra in più. `interventi.codice` è `varchar(20)`, con margine abbondante.

È la differenza rispetto al vecchio generatore dei clienti, dove la lunghezza decideva la
correttezza: mescolando 3 e 4 cifre il massimo trovato era quello sbagliato.

### 8. La pagina Impostazioni è in sola lettura

Mostra classe, prefisso, ultimo numero usato, prossimo codice e data dell'ultimo utilizzo
(`settings.updated_at`), che dice a colpo d'occhio quali serie sono vive.

Niente modifica dall'interfaccia. Il caso reale — riallineare un contatore dopo un import
massivo — è raro e lo esegue direttamente sul database l'unica persona in grado di farlo. Un
form di modifica introdurrebbe il rischio opposto: un numero abbassato per errore produce
codici duplicati e salvataggi che falliscono. Di conseguenza **non serve nessun log di chi ha
cambiato cosa**: non esiste un'azione applicativa da tracciare.

Permesso `impostazioni.manage`, come il resto della sezione. Se un giorno l'ufficio dovesse
consultarli, la risposta è promuovere quella persona, non allargare la matrice dei permessi.

### 9. Eliminata la riga `Interventi/progressivo`

Creata da `SeedProgressivoInterventi` a giugno e mai letta da nessuna parte: il codice usa
solo le righe `seq_*`. Finché era invisibile non dava fastidio; in una pagina che elenca i
numeratori comparirebbe accanto a quelli veri con un valore privo di significato.

### 10. I clienti potenziali sono un flag, non un prefisso

Emersa discutendo i numeratori: marcare i potenziali con un codice `POT-xxx` sembra comodo
perché si vede a colpo d'occhio, ma codifica **uno stato che cambia dentro un identificatore
che non deve cambiare**. Quando il potenziale accetta restano due strade, entrambe cattive:
cambiargli il codice, e allora il preventivo già inviato porta un riferimento che non esiste
più; oppure lasciarglielo, e allora il prefisso mente.

Il flag `clienti.potenziale` sposta l'informazione dove le cose cambiano. In elenco un badge
si vede più di un prefisso, ed è filtrabile senza `LIKE 'POT-%'` sparsi nelle query.

Questa migration aggiunge solo la colonna: la parte applicativa è fuori scope.

### 11. Il prefisso appartiene al tipo, non all'origine

Emersa collaudando la pagina: un intervento di tipo *Consegna Sale*, con prefisso `SAL`
configurato, riceveva `INT-0516`. `InterventiModel::normalizza()` usava il prefisso del tipo
**solo** per gli interventi generati da un abbonamento, com'era nato il campo — il commento di
`tipi_intervento.prefisso_codice` diceva "per codici interventi da abbonamento".

Ma i prefissi vengono configurati anche su tipi non abbonabili (`SAL`, `FIL`, `OSM`), e con
quella regola non avrebbero mai prodotto un codice: campi compilati che non facevano niente.

La regola diventa quindi: **se il tipo ha un prefisso, lo usano tutti i suoi interventi**, da
abbonamento o inseriti a mano. Il codice dice che lavoro è, che è l'informazione utile su un
foglio di lavoro; `INT` resta per i tipi senza prefisso e per gli interventi senza tipo.

**Le visite extra restano `EXT`** e vincono sul tipo: non sono una categoria di lavoro ma una
prestazione fuori dalle scadenze previste, tipicamente da fatturare a parte, e quella
distinzione conta più del tipo di impianto su cui si interviene.

Gli interventi già emessi non vengono rinumerati: i loro codici sono in calendario e sui
documenti.

## Alternative scartate

- **Padding a 4 cifre senza contatore** — vedi decisione 1: rompe subito e lascia in piedi
  gli altri due difetti.
- **Lasciare tutto com'è.** Difendibile: oggi funziona, `CLI-999` è irraggiungibile e il
  riuso di un codice dopo una cancellazione è quasi sempre innocuo, perché si elimina solo
  un cliente appena inserito per errore. Scartata perché il momento è insolitamente
  favorevole — con la produzione vuota il contatore parte da zero e non c'è niente da
  allineare, mentre farlo a anagrafica caricata costerebbe di più.
- **Tabella `numeratori` dedicata** invece di `settings`. Più esplicita, ma introduce una
  tabella per tenere due righe quando esiste già il posto dove il progetto tiene i contatori,
  con il pattern collaudato.
- **Prefisso `POT-` per i clienti potenziali** — vedi decisione 10.
- **Modifica dei numeratori dall'interfaccia, con log delle variazioni** — vedi decisione 8.

## Riepilogo modifiche file per file

1. `app/Database/Migrations/…_ContatoreCodiciClienti.php` (nuova) — converte `INT-xxx` in
   `CLI-00xx`, crea `settings(class='Clienti', key='seq_CLI')` inizializzata al massimo
   esistente (`0` su database vuoto), elimina la riga morta `Interventi/progressivo`.
2. `app/Database/Migrations/…_AddPotenzialeToClienti.php` (nuova) — colonna `potenziale`
   `TINYINT NOT NULL DEFAULT 0` dopo `attivo`.
3. `app/Models/NumeratoriModel.php` (nuovo) — `prossimo()` con transazione e `FOR UPDATE`,
   `elenco()` per la pagina Impostazioni.
4. `app/Models/ClientiModel.php` e `app/Models/InterventiModel.php` — `generaCodice()`
   delega a `NumeratoriModel`, firme invariate.
5. `app/Controllers/Impostazioni/NumeratoriController.php` (nuovo) — `index()` in sola
   lettura.
6. `app/Views/impostazioni/numeratori.php` (nuova) e voce nella pagina Impostazioni.
7. `app/Config/Routes.php` — rotta nel gruppo `impostazioni`, sotto
   `permission:impostazioni.manage`.
8. `app/Views/help/impostazioni.php` — cosa sono i numeratori e perché non si modificano da
   qui.
9. `CHANGELOG.md`, `docs/ANALISI.md` §7.1, `docs/schema.html` e il punto 4 della review.

## Fuori scope

- **Interfaccia dei clienti potenziali** — badge in elenco, filtro, esclusione dalle tendine,
  conversione a cliente. Qui nasce solo la colonna; il resto ha decisioni sue (cosa succede
  a un potenziale che rifiuta, se va cancellato o marcato) e merita un brainstorming.
- **Modifica dei numeratori dall'interfaccia** — decisione 8.
- **Riallineamento automatico dopo un import** — il caso si presenta di rado e si risolve
  con un `UPDATE` diretto.
- **Serie separate per anno** (`CLI-2027-0001`): nessuno le ha chieste, e la numerazione
  contabile dei clienti importati non le usa.
