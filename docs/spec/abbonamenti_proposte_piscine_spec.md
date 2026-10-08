# Proposta di abbonamento piscine in Word

## Contesto

La v0.36.0 genera in Word la proposta degli abbonamenti addolcitori
(`abbonamenti_proposte_word_spec.md`). Il motore `DocumentoWord` e la classe
`PropostaAbbonamento` erano pensati per accogliere altri modelli: per le piscine si aggiungono
una voce nella mappa categoria → modello e il file del modello. Il pulsante nella scheda e lo
zip dall'elenco seguono da soli.

Il modello di partenza è `docs/spec/2026 PISCINE MODELLO-PROVA-ABBONAMENTO.docx`. Rispetto a
quello degli addolcitori ha tre differenze, già annotate nel «Fuori scope» dello spec Word:

- la frequenza delle visite è scritta **per periodo**, con le sue date
  («Dal 1 Maggio al 30 Settembre: QUINDICINALE»);
- dice se la **pulizia del fondo** è compresa;
- ha un **secondo prezzo**, quello della pulizia del fondo fatta su richiesta, che nel
  gestionale non esiste.

## Decisioni chiave

### 1. Il template è quello degli addolcitori, adattato alle piscine

Il template di riferimento resta `proposta_addolcitori.docx`, con le correzioni della
decisione 10 dello spec Word: Verdana ovunque, dati aziendali nel piè di pagina fra i due
loghi, «Pag. 1 di 2» allineato, data anche sulla prima copia. Il modello piscine in
`docs/spec/` mostra **che cosa** contiene la proposta delle piscine e come appare. Ha ancora
l'impaginazione precedente a quelle correzioni, quindi non è una base da cui partire.

Il template delle piscine è una copia di quello degli addolcitori in cui cambia **solo il
corpo**. Intestazione e piè di pagina restano quelli già corretti, e le due
proposte hanno lo stesso aspetto. I segnaposto si inseriscono via XML, come per gli addolcitori,
e Daniela controlla poi il risultato in Word.

Dal modello piscine si prendono così come sono:

- la frase iniziale: «…prevede la messa in moto, l'effettuazione di visite periodiche di
  controllo alle apparecchiature.»;
- l'intestazione dell'elenco: «Durante le visite di controllo verranno eseguite le seguenti
  operazioni:»;
- le condizioni di abbonamento, che a differenza degli addolcitori escludono anche i prodotti
  chimici.

Non ci sono le apparecchiature: per le piscine l'impianto è la piscina stessa (spec Word,
decisione 2).

### 2. Una riga per periodo, con le date in numeri e senza anno

Il blocco «FREQUENZA DELLE VISITE» ha una riga per ogni periodo dell'abbonamento, nell'ordine
dei periodi (`abbonamenti_periodi.ordine`):

```
FREQUENZA DELLE VISITE:   Dal 01.01 al 31.03: MENSILE
                          Dal 01.04 al 15.09: SETTIMANALE, con pulizia del fondo
                          Dal 16.09 al 31.10: QUINDICINALE
```

**Ogni periodo sta su una riga sola, pulizia compresa.** Ci si è arrivati per prove in Word,
perché la riga più lunga, «…: QUINDICINALE, con pulizia del fondo», non ci stava:

- con le date in lettere («Dal 1 Aprile al 15 Settembre») misurava circa 14 cm, contro i 10
  della colonna dei valori, che partiva a 7 cm dal margine come negli addolcitori;
- con le date in numeri ci stava accanto a MENSILE ma non a SETTIMANALE;
- con la **colonna dei valori spostata a 6 cm**, come nel modello piscine di partenza, ci sta
  anche con QUINDICINALE.

Scartati, durante le prove:

- «con pulizia del fondo» su una riga sua sotto il periodo: con il rientro staccava troppo la
  riga dal blocco, e senza rientro si leggeva come un periodo a parte;
- l'etichetta su una riga sua e i periodi a tutta larghezza, perché il blocco avrebbe avuto un
  aspetto diverso da DURATA, PREZZO e PAGAMENTO.

La colonna a 6 cm vale per tutte le voci della proposta piscine, che quindi ha i valori 1 cm
più a sinistra di quella degli addolcitori.

**Le righe del blocco sono allineate a sinistra**, mentre il resto del documento è
giustificato. Una riga giustificata che va a capo viene allargata fino al margine: gli spazi
fra le date apparivano più larghi di quelli delle altre righe, e se un giorno una riga non ci
stesse, si vedrebbe chiaramente invece di comparire «stirata».

- **Le date dei periodi sono in numeri e senza anno** («01.04»), più corte del mese in
  lettere. L'anno lo dice già la durata del servizio, e un periodo a cavallo d'anno si legge
  bene lo stesso («Dal 01.10 al 30.04»).
- **La frequenza è in maiuscolo**, presa da `AbbonamentiModel::FREQUENZE_LABEL`, come negli
  addolcitori.
- **Un abbonamento con un periodo solo** ha una riga sola, nella stessa forma con le date:
  il documento ha sempre lo stesso aspetto.

### 3. La pulizia del fondo si scrive solo dove c'è

Nella realtà un abbonamento nasce con o senza pulizia del fondo, e la si scrive solo nei
periodi in cui è compresa:

- **almeno un periodo con pulizia**: quei periodi finiscono con «, con pulizia del fondo».
  Gli altri non scrivono niente: dato che la pulizia è indicata dove c'è, dove
  non è scritta non c'è;
- **nessun periodo con pulizia**: sotto il blocco delle frequenze c'è una riga sola,
  «SENZA PULIZIA DEL FONDO», come nel modello.

Il dato viene da `abbonamenti_periodi.con_pulizia_fondo`.

### 4. Il prezzo della pulizia su richiesta è un parametro unico

In un periodo senza pulizia del fondo il cliente può chiederla lo stesso, e la proposta dice
quanto costerà. La riga c'è **sempre**, sotto il prezzo complessivo:

```
PULIZIA DEL FONDO:              Euro 65,00 + Iva 22% cadauna, su richiesta
```

Il prezzo è uguale per tutti i clienti, quindi è un parametro in **Impostazioni → Parametri**,
nella card Abbonamenti sotto l'aumento al rinnovo: «Prezzo della pulizia del fondo su richiesta
(€)». Si salva nel setting `Azienda.prezzo_pulizia_fondo`, IVA esclusa, e vale
`AbbonamentiModel::PREZZO_PULIZIA_FONDO_DEFAULT` (65) finché nessuno lo salva. È lo stesso
schema della percentuale di rinnovo, quindi in produzione funziona senza configurarlo. Il
salvataggio lo valida come numero non negativo, prima di scrivere gli altri parametri.

«+ Iva 22% cadauna, su richiesta» resta testo fisso nel modello, come «+ Iva 22%» del prezzo
complessivo. **«su richiesta» sta nel valore e non nell'etichetta**: «PULIZIA DEL FONDO su
richiesta:» arrivava fino alla colonna dei valori e toccava il prezzo. Scartato scriverlo in un
carattere più piccolo, che lo avrebbe fatto sembrare una nota secondaria.

### 5. Durata, date, prezzo e pagamento

- **Durata**: «Dal `data_inizio` al `data_fine`», senza «UN ANNO», perché un abbonamento
  piscine spesso non dura un anno (nel modello: dal 01.11.2025 al 31.12.2026).
- **Tutte le date con i punti**: `01.11.2025` nella durata, come `01.04` nei periodi. Il valore
  è comune ai due modelli, quindi cambia anche nella proposta degli addolcitori, che usava
  `01/11/2025`: un formato solo in tutti i documenti. Resta in lettere la data della lettera
  in fondo alla pagina («Ceriale, 08 ottobre 2026»).
- **Prezzo complessivo, modalità di pagamento, destinatario, contatti, data e nome del file**:
  identici agli addolcitori (spec Word, decisioni 4 e 8).

### 6. Cosa blocca la generazione

Come per gli addolcitori (spec Word, decisione 11), la proposta non si genera senza
**prezzo**, **operazioni incluse** o **modalità di pagamento**. Le apparecchiature non si
controllano, perché il modello non le ha. Il controllo delle apparecchiature diventa quindi
specifico della categoria addolcitori.

### 7. Operazioni standard del tipo Piscine: lavoro di dati

Oggi le Operazioni standard del tipo Piscine iniziano con «Intervento di apertura piscina…» e
non corrispondono alle sei righe del modello. Come per gli addolcitori (spec Word, decisione
3) le si allinea a mano, prima in sviluppo per le prove e poi in produzione: è un dato, non
codice. Gli abbonamenti già creati conservano le loro operazioni.

## Alternative scartate

- **«senza pulizia del fondo» su ogni periodo che non ce l'ha**: con un periodo con pulizia
  e due senza, la ripetizione confonde invece di chiarire. Quello che non è scritto non c'è.
- **Il prezzo della pulizia su ogni abbonamento**: è uguale per tutti, quindi andrebbe ricopiato
  e aggiornato su centinaia di righe.
- **Il prezzo della pulizia come testo fisso nel modello Word**: per cambiarlo servirebbe
  modificare il modello e fare un deploy.

## Riepilogo modifiche file per file

1. `app/Templates/word/proposta_piscine.docx` (nuovo): intestazione e piè di pagina di
   `proposta_addolcitori.docx`, corpo del modello piscine, con i segnaposto. Il blocco dei
   periodi e la riga «SENZA PULIZIA DEL FONDO» sono elenchi `${blocco}` del motore. Il
   secondo si svuota quando c'è almeno un periodo con pulizia. Colonna dei valori a 6 cm e
   righe dei periodi allineate a sinistra (decisione 2).
2. `app/Libraries/PropostaAbbonamento.php`: voce `piscine` nella mappa dei modelli; righe dei
   periodi e regola della pulizia (decisioni 2 e 3); prezzo della pulizia; controllo delle
   apparecchiature solo per gli addolcitori; valori comuni alle due categorie in un punto solo;
   date della durata con i punti anche per gli addolcitori (decisione 5).
3. `app/Models/AbbonamentiModel.php`: costante `PREZZO_PULIZIA_FONDO_DEFAULT` e metodo statico
   `prezzoPuliziaFondo()`, come `percentualeRinnovo()`.
4. `app/Controllers/Impostazioni/GeneraleController.php`: validazione e salvataggio di
   `prezzo_pulizia_fondo` in `salvaParametri()`.
5. `app/Views/impostazioni/parametri.php`: il campo nella card Abbonamenti.
6. `app/Helpers/validazione_helper.php`: etichetta di `prezzo_pulizia_fondo`, se quella
   automatica non va bene.
7. `app/Views/help/abbonamenti.php`: la proposta si genera anche per le piscine; come si legge
   la pulizia del fondo.
8. `CHANGELOG.md`, `docs/ANALISI.md` §7.1, `docs/backlog.md` (si toglie la voce): a chiusura.

Nessuna migration: i periodi e il flag della pulizia esistono già, e il prezzo è un setting.

## Prove

- Un abbonamento con un periodo solo, senza pulizia.
- Uno con tre periodi e la pulizia in quello centrale.
- Uno con tutti i periodi con pulizia.
- **Il caso più lungo**, quattro periodi e tutte le operazioni: le due copie devono stare
  ciascuna nella sua pagina (spec Word, decisione 4). Il codice non può accorgersene, va
  guardato in Word.
- Lo zip con proposte piscine e addolcitori insieme.

## Fuori scope

- **Il controllo dei periodi contigui o sovrapposti** (per esempio uno che finisce il 31/07 e
  il successivo che inizia lo stesso giorno): il documento scrive le date salvate.
  Un controllo, se serve, sta nel form dei periodi.
- **La proposta del tipo Generale**: nessun modello.
- **«messa in moto» variabile**: è nel testo fisso. Un abbonamento che non la prevede si
  ritocca in Word.
