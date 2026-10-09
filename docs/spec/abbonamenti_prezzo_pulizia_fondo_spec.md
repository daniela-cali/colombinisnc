# Spec: Abbonamenti — Prezzo della pulizia del fondo per abbonamento

> Decisioni prese nel brainstorming del 09/10/2026. Si appoggia al prezzo del rinnovo della
> v0.36.1 (`AbbonamentiModel::prezzoRinnovo()`) e alla proposta Word delle piscine della
> v0.38.0 (`abbonamenti_proposte_piscine_spec.md`).

## Contesto

La proposta delle piscine scrive sempre la riga «PULIZIA DEL FONDO: Euro … + Iva 22%
cadauna, su richiesta». Il prezzo oggi è **uno solo per tutti**: sta in Impostazioni →
Parametri (`Azienda.prezzo_pulizia_fondo`, 65 € finché non lo si salva) e la proposta lo
legge da lì al momento della generazione.

Ne vengono due problemi:
- non si può fare un prezzo diverso a un cliente;
- se il parametro cambia a metà anno, rigenerando una proposta già spedita esce il prezzo
  nuovo e non quello concordato.

Il gestionale non usa quel prezzo per nessun conto: le pulizie eseguite non si sommano e non
c'è fatturazione. È solo un importo scritto nella proposta.

## Decisioni chiave

### 1. Il prezzo sta sull'abbonamento

Colonna nuova `abbonamenti.prezzo_pulizia_fondo`, `DECIMAL(10,2) NULL`, come `prezzo`. La
proposta Word legge quella e non più il parametro. Il prezzo concordato resta scritto
sull'abbonamento, quindi una proposta rigenerata dice sempre la stessa cifra.

### 2. Il parametro diventa il listino

`Azienda.prezzo_pulizia_fondo` resta in Impostazioni → Parametri, con l'etichetta «Prezzo di
listino della pulizia del fondo». Serve solo a **precompilare il campo** nel nuovo
abbonamento. Conta soprattutto al go-live, quando tutto il 2026 si carica con «Nuovo
abbonamento»; dopo serve per i clienti nuovi.

### 3. Il campo compare solo per i tipi con la pulizia

Visibile e obbligatorio quando il tipo ha `ha_pulizia_fondo = 1` (oggi Piscine e Test),
nascosto altrimenti. Segue lo stesso interruttore JavaScript che già mostra la colonna
«Pulizia fondo» dei periodi (`aggiornaPulizia()` / `setPuliziaFondo()`).

Se si cambia il tipo da uno con pulizia a uno senza, il valore rimasto **non si azzera**: lo
legge solo la proposta delle piscine, quindi un importo dimenticato su un addolcitore non va
da nessuna parte.

### 4. Campo vuoto: si blocca

Il campo è sempre precompilato, quindi vuoto vuol dire che l'operatore l'ha cancellato per
errore. Si blocca in due punti:
- **al salvataggio**: regola `required|decimal|greater_than_equal_to[0]` quando il tipo ha la
  pulizia. L'errore si vede subito, non quando si genera il Word;
- **alla generazione della proposta**: `PropostaAbbonamento` lancia «Manca il prezzo della
  pulizia del fondo», come già fa per il prezzo principale. Copre gli abbonamenti salvati
  prima di questa versione.

### 5. Il rinnovo copia la pulizia senza aumentarla

Il prezzo della pulizia **non aumenta mai in automatico**: il rinnovo lo copia così com'è,
65 € resta 65 €. La percentuale di Parametri vale solo per il prezzo principale.

Non serve codice: `rinnova()` parte già da `array_merge($precedente, [...])`, quindi la
colonna nuova passa all'anno dopo da sola. Basta **non** aggiungerla fra i campi che il
rinnovo ricalcola.

Quando si decide un prezzo nuovo, si cambia a mano: il listino in Parametri per gli
abbonamenti nuovi, il campo nel form per quelli rinnovati.

### 6. Dati esistenti: un UPDATE a mano, fuori dal repository

La migration aggiunge solo la colonna, che deve arrivare anche in produzione. Per gli
abbonamenti di prova in sviluppo basta un UPDATE lanciato a mano, che non entra nelle
migration:

```sql
UPDATE abbonamenti a
JOIN tipi_intervento t ON t.id = a.tipo_intervento_id
SET a.prezzo_pulizia_fondo = 65
WHERE t.ha_pulizia_fondo = 1;
```

In produzione il database è vuoto, quindi non c'è niente da aggiornare.

## Alternative scartate

- **Lasciare solo il parametro globale**: non permette prezzi per cliente e cambia il prezzo
  delle proposte già spedite.
- **Default ricavato dagli abbonamenti dell'anno** (il prezzo più frequente fra le piscine
  attive), al posto del listino: è un calcolo nascosto che nessuno sa spiegare quando esce un
  numero strano.
- **Aumento della pulizia nel rinnovo**, con arrotondamento all'euro e un pulsante in
  Parametri per aumentare il listino della stessa percentuale (con un blocco contro il doppio
  clic). Discusso e poi scartato: è un prezzo «su richiesta» e non segue l'aumento annuale.

## Riepilogo modifiche file per file

- **Migration** `AddPrezzoPuliziaFondoToAbbonamenti`: colonna `prezzo_pulizia_fondo
  DECIMAL(10,2) NULL` dopo `prezzo`.
- **`AbbonamentiModel`**:
  - `prezzo_pulizia_fondo` negli `$allowedFields`;
  - `prezzoPuliziaFondo()` resta e restituisce il listino;
  - `normalizza()` porta `''` a `NULL`, come per `prezzo`.
- **`AbbonamentiController`**:
  - `nuovo()` precompila il campo dal listino;
  - `rinnova()` non cambia: la colonna passa con `array_merge($precedente, ...)`;
  - `regolaValidazione()` lo rende obbligatorio quando il tipo ha `ha_pulizia_fondo`.
- **`abbonamenti/nuovo.php`, `edit.php`**: il campo accanto al prezzo, con
  `currency-input.js`, mostrato e nascosto da `aggiornaPulizia()`.
- **`abbonamenti/show.php`**: il prezzo della pulizia accanto al prezzo, solo per i tipi con
  la pulizia.
- **`PropostaAbbonamento`**: legge `$abbonamento['prezzo_pulizia_fondo']`; se è nullo per le
  piscine lancia l'eccezione.
- **`impostazioni/parametri.php`**: etichetta «Prezzo di listino della pulizia del fondo»,
  con una riga di aiuto: precompila i nuovi abbonamenti, non cambia quelli esistenti.
- **`help/abbonamenti.php`**: il paragrafo sulla pulizia dice che il prezzo sta
  sull'abbonamento, parte dal listino e il rinnovo lo copia senza aumentarlo.
- **`docs/schema.html`**: la colonna nuova e la riga nel log delle modifiche DB.

## Fuori scope

- **Prezzo orario della pulizia.** Si può aggiungere dopo senza rifare niente: una colonna
  per l'unità (`cadauna` / `oraria`, default `cadauna`) e la parola «cadauna» del modello
  Word trasformata in segnaposto, via XML come gli altri. Diventerebbe un lavoro più grosso
  solo se il gestionale dovesse conteggiare le ore delle pulizie eseguite, cosa che oggi non
  fa nemmeno per quelle a forfait.
- **Prezzo della pulizia nella proposta degli addolcitori**: quel modello non ha la pulizia.
