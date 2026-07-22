# Spec — Copertura automatica e validata dei periodi di frequenza negli abbonamenti

> Emersa testando `abbonamenti_scadenze_duplicate_spec.md`: verificando il fix lì descritto è emerso che la convenzione di inserimento istintiva (periodi **non sovrapposti**, senza giorno di confine condiviso) è quella corretta e priva di bug — non richiede nessun meccanismo compensativo in generazione. Questa spec porta quella convenzione nel form: aiuti automatici per rispettarla, e un blocco esplicito se non viene rispettata.

## 1. Contesto

Il widget dei periodi (`app/Views/abbonamenti/_form_periodi.php`) permetteva di inserire liberamente le date di ogni periodo, con un default fisso (01/01–31/12 dell'anno corrente) scollegato dalla Data inizio/Data fine dell'abbonamento. Due problemi pratici:

1. Per abbonamenti che non coprono l'anno intero (es. contratti con frequenza che cambia a metà anno, indipendentemente dal fatto che l'abbonamento stesso sia annuale o stagionale — non è una discriminante di durata, ma di frequenza variabile), il default costringeva a riscrivere le date da zero su ogni riga.
2. Niente impediva che il primo periodo non partisse dalla Data inizio abbonamento, o che l'ultimo non arrivasse alla Data fine abbonamento — lasciando porzioni di calendario senza nessun periodo a definirne la frequenza, quindi senza generazione di scadenze per quella porzione.

## 2. Soluzione

### 2.1 Prima riga: eredita sempre la Data inizio abbonamento

Con un solo periodo, la sua Data inizio *deve* coincidere con quella dell'abbonamento — non è una scelta, qualunque altro valore lascerebbe calendario scoperto. Per questo la riga 0 resta sincronizzata in automatico e permanentemente con il campo Data inizio dell'abbonamento (listener `input` su quel campo, aggiorna sempre la prima riga in ordine nel DOM — non per indice, per restare corretta anche se altre righe vengono rimosse).

### 2.2 Data fine: mai auto-compilata, nemmeno sulla prima riga

Qui la scelta è deliberatamente **asimmetrica** rispetto al punto precedente. Si era considerato di sincronizzare live anche la Data fine della prima riga con la Data fine abbonamento (comodo finché esiste un solo periodo), ma è stato scartato: a differenza dell'inizio, la fine di un periodo è quasi sempre una scelta reale dell'utente (potrebbe voler spezzare il periodo più avanti), e un valore auto-compilato può restare inosservato se dimenticato, silenziosamente sbagliato — la stessa famiglia di bug della spec sulle scadenze duplicate, spostata dalla generazione all'inserimento dati. Ogni Data fine, su ogni riga, nasce sempre vuota; l'attributo HTML `required` già presente forza una scelta consapevole prima del salvataggio.

### 2.3 Bottone "Aggiungi periodo": propone il giorno successivo

Alla pressione, calcola la Data fine attuale dell'ultima riga esistente e propone il giorno successivo come Data inizio della nuova riga (Data fine sempre vuota, per il punto 2.2). Se la Data fine dell'ultima riga è ancora vuota, non c'è nulla da cui calcolare: la nuova riga nasce con Data inizio vuota anch'essa, spingendo implicitamente l'utente a completare prima la riga precedente.

**Bug del fuso orario incontrato e corretto durante l'implementazione**: il calcolo iniziale costruiva la data con `new Date(dataFine + 'T00:00:00')` (mezzanotte locale) ma la formattava con `.toISOString().slice(0, 10)`, che converte sempre in UTC. Con fuso orario italiano (UTC+1/+2), mezzanotte locale del giorno successivo corrisponde ancora al giorno precedente in UTC — il risultato tornava identico alla data di partenza invece di essere +1 giorno. Corretto leggendo `getFullYear()`/`getMonth()`/`getDate()` (sempre locali, mai convertiti) invece di passare per `toISOString()`.

Nessun aggancio "a catena" tra periodi successivi: il calcolo avviene solo al click, una tantum. Se l'utente modifica in seguito la Data fine di un periodo intermedio, i periodi successivi già creati **non** si aggiornano da soli (valutato e scartato: complicherebbe il codice per un caso d'uso che l'utente non ha giudicato necessario).

### 2.4 Blocco al salvataggio se manca copertura, lato browser e lato server

Verifica su due livelli, entrambi confrontando **stringhe** `Y-m-d` (ordinamento cronologico corretto senza bisogno di `DateTime`):

- **Browser** (`_form_periodi.php`, listener su `submit` del form): se la Data inizio della prima riga non coincide con la Data inizio abbonamento, o la Data fine dell'ultima riga non coincide con la Data fine abbonamento, blocca l'invio (`preventDefault()`) e mostra un `alert()` che indica quale dei due lati non combacia.
- **Server** (`AbbonamentiController::periodiCoprono()`, richiamato da `store()` e `update()`): stessa verifica con `min()`/`max()` sulle date dei periodi ricevuti via POST, filtrando i campi vuoti. Necessaria perché il controllo lato browser è solo una comodità UX, aggirabile (JS disabilitato, POST diretto) — l'unica garanzia reale di coerenza sul DB è server-side.

## 3. Riepilogo modifiche

1. **`app/Views/abbonamenti/_form_periodi.php`**: rimosso il default fisso 01/01–31/12; aggiunti riferimenti `abbInizio`/`abbFine` ai campi abbonamento; funzione di sincronizzazione permanente della Data inizio della prima riga; calcolo "giorno successivo" (con fix fuso orario) sul bottone "Aggiungi periodo"; listener `submit` con verifica di copertura completa.
2. **`app/Controllers/AbbonamentiController.php`**: nuovo metodo privato `periodiCoprono()`; richiamato in `store()` e `update()` con lo stesso pattern di errore già usato per "Aggiungere almeno un periodo di frequenza".
3. Nessuna migration, nessuna modifica al model `AbbonamentiPeriodiModel`.

## 4. Verifica

Testato manualmente: creazione nuovo abbonamento con periodi non sovrapposti (prima riga eredita la Data inizio, bottone propone correttamente il giorno successivo dopo il fix del fuso orario); tentativo di salvataggio con copertura incompleta bloccato dall'alert lato browser.

## 5. Fuori scope

- Sincronizzazione "a catena" tra periodi intermedi quando si modifica una Data fine già usata come riferimento da un periodo successivo — valutato, scartato per assenza di caso d'uso reale (vedi §2.3).
- Blocco/lock del campo Data inizio della prima riga (oggi è solo pre-compilato, resta modificabile a mano dall'utente) — il controllo al salvataggio (§2.4) intercetta comunque un'eventuale incoerenza finale.
