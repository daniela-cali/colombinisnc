# 3. Modello dati

Il database si chiama `colombinisnc` e contiene 25 tabelle più 3 view di consultazione.
Questo capitolo le descrive per aree funzionali: che cosa rappresenta ogni tabella, come
viene usata e quali campi hanno un comportamento non ovvio.

> I contenuti sono stati verificati interrogando direttamente il database di sviluppo. Dove
> divergono da `docs/schema.html`, la divergenza è segnalata.

## 3.1 Convenzioni valide per tutte le tabelle

**Campi standard.** Ogni tabella ha `created_by`, `updated_by` (`INT UNSIGNED NULL`,
riferiti a `users.id` ma senza foreign key) e `created_at`, `updated_at` (`DATETIME NULL`).
Nel model si attivano con `$useTimestamps = true`; l'autore viene scritto dal metodo
`normalizza()`, che legge l'helper Shield **`user_id()`**. `created_by` si imposta solo
nell'inserimento e non va mai sovrascritto negli update. L'unica eccezione è
`promemoria_dismiss`, che ha il solo `created_at` perché una riga di quella tabella non
viene mai modificata.

> **Attenzione a `session()->get('user_id')`.** Shield salva i dati dell'utente in sessione
> sotto la chiave `user`, non sotto una chiave piatta `user_id`: quella lettura restituisce
> sempre `null`, silenziosamente. È stata la causa di un bug che ha lasciato
> `created_by`/`updated_by` vuoti in tredici model fino alla v0.21.7. Si usa sempre
> `user_id()` o `auth()->id()`.

**Niente `ENUM`.** I flag booleani sono `TINYINT` con default `0` o `1`; gli stati con più
valori sono `VARCHAR` con le costanti dichiarate nel model e un commento sulla colonna che
elenca i valori ammessi. Aggiungere uno stato nuovo richiede quindi solo una costante e una
regola di validazione, non un `ALTER TABLE` — ed è esattamente quello che è successo con
`proposta` e `rifiutata` sugli abbonamenti in v0.26.0.

**Niente flag di cancellazione.** Non esiste `eliminato`: si usa la cancellazione fisica,
preceduta da un controllo applicativo che verifica i record collegati e mostra un messaggio
chiaro. Il campo `attivo` significa "disabilitato temporaneamente", non "cancellato".

**Chiavi esterne.** La regola `ON DELETE` è scelta caso per caso e riflette il significato
del legame: `RESTRICT` dove la cancellazione va impedita finché esistono figli
(un cliente con interventi), `CASCADE` dove i figli non hanno senso da soli (le note di un
cantiere), `SET NULL` dove il legame è un'informazione accessoria (il tecnico preferito di
un cliente).

## 3.2 Autenticazione e configurazione

### users e tabelle `auth_*`

Gestite da Shield, da non modificare a mano. `users` contiene `username`, `active`,
`status`, `last_active` e i timestamp; email e password vivono in `auth_identities`, che
supporta più identità per utente. Le altre tabelle sono `auth_groups_users`,
`auth_permissions_users`, `auth_logins`, `auth_token_logins`, `auth_remember_tokens`.
Tutte hanno la foreign key su `users.id` in `CASCADE`: eliminando un utente sparisce tutto
ciò che lo riguarda.

L'unica colonna aggiunta dal progetto è **`users.ultima_versione_vista`**
(`VARCHAR(20) NULL`): registra l'ultima versione del changelog che l'utente ha visto, ed è
il meccanismo che fa comparire il modal delle novità al primo accesso dopo un rilascio.

I gruppi e i permessi **non stanno nel database**: sono dichiarati in
`app/Config/AuthGroups.php` (capitolo 8).

### settings

Tabella di CodeIgniter Settings: `class`, `key`, `value`, `type`, `context`. Conserva
tutta la configurazione modificabile a runtime, in particolare:

| `class` / `key` | Contenuto |
|---|---|
| `Azienda.sede_*` | nome, indirizzo, CAP, città, telefono, logo, latitudine e longitudine della sede. Sono i dati che intestano i PDF |
| `Azienda.orario_inizio`, `orario_fine`, pausa pranzo | orari aziendali, usati per suggerire l'orario nel modal di pianificazione |
| `Azienda.zona_lng_ovest`, `zona_lng_est` | le due soglie di longitudine che determinano la zona di un cliente |
| `Interventi.seq_<PREFISSO>` | contatore progressivo dei codici intervento, uno per prefisso |
| `Import.clienti_mapping` | l'ultima mappatura colonne usata nell'import dei clienti storici |

> Questa tabella non compare in `docs/schema.html`, pur essendo tutt'altro che marginale:
> ci vive anche il contatore atomico dei codici intervento (capitolo 5.2).

## 3.3 Anagrafiche

### personale

I dipendenti dell'azienda, tecnici e staff. È deliberatamente **separata da `users`**:
Shield gestisce l'accesso, `personale` gestisce i dati operativi, e il legame è la colonna
nullable `user_id`. La conseguenza pratica è che può esistere un dipendente senza account
(un tecnico che non usa il gestionale) e che l'anagrafica si può modificare senza toccare
Shield.

| Campo | Tipo | Note |
|---|---|---|
| `user_id` | `INT UNSIGNED NULL` | → `users.id`, `SET NULL` |
| `nome`, `cognome` | `VARCHAR(100)` | obbligatori |
| `telefono` | `VARCHAR(20) NULL` | |
| `colore` | `VARCHAR(7) NULL` | esadecimale `#RRGGBB`: identifica il dipendente sul calendario |
| `attivo` | `TINYINT` default `1` | `0` = disabilitato |

Il colore è scelto liberamente dall'utente, quindi il testo sopra di esso viene calcolato
a runtime dall'helper `colore_testo()` per restare leggibile.

Tutti i riferimenti operativi puntano a `personale.id`, mai a `users.id`: vale per
`interventi.tecnico_id`, per `assenze.personale_id` e per `clienti.tecnico_preferito_id`.

### clienti

L'anagrafica, che ospita sia società sia persone fisiche distinte dal campo `tipo`.
Trentaquattro colonne; qui quelle con un comportamento proprio.

| Campo | Tipo | Note |
|---|---|---|
| `codice` | `VARCHAR(15)` UNIQUE | **porta un'informazione, non è un semplice identificativo** — vedi sotto |
| `codice_esterno` | `VARCHAR(50) NULL` | riferimento nel software di contabilità, compilabile a mano |
| `id_external` | `INT NULL` | chiave primaria del software contabile |
| `tipo` | `VARCHAR(20)` default `societa` | `societa` · `persona_fisica` |
| `ragsoc` | `VARCHAR(255) NULL` | valorizzato per le società |
| `nome`, `cognome` | `VARCHAR(100) NULL` | valorizzati per le persone fisiche |
| `denominazione` | `VARCHAR(255)` **generata STORED** | cognome + nome se persona fisica, altrimenti ragione sociale |
| `zona` | `TINYINT NULL` | `-1` Ventimiglia · `0` Ceriale · `1` Savona |
| `distanza_sede` | `DECIMAL(6,2) NULL` | km in linea d'aria dalla sede, ricalcolati a ogni salvataggio |
| `lat`, `lng` | `DECIMAL(10,7) NULL` | |
| `geocoded_at` | `DATETIME NULL` | ultima geocodifica riuscita o correzione manuale del pin |
| `geocodifica_fallita` | `TINYINT` default `0` | `1` = l'ultimo tentativo Nominatim non ha prodotto risultati |
| `nazione` | `VARCHAR(50)` default `ITALIA` | zona di confine: la tendina propone Italia e Francia |
| `tecnico_preferito_id` | `INT UNSIGNED NULL` | → `personale.id`, `SET NULL` |
| `user_id` | `INT UNSIGNED NULL` | → `users.id`, `SET NULL`: predisposizione al portale clienti |
| `attivo` | `TINYINT` default `1` | |

**`clienti.denominazione` è una colonna generata** (`GENERATED ALWAYS AS ... STORED`,
migration `AddDenominazioneToClienti`, v0.26.0). Prima della sua introduzione ogni query
che mostrava il nome di un cliente ripeteva lo stesso `CASE WHEN tipo = 'persona_fisica'`,
in quattro model diversi. Vale una convenzione ferrea: **quando una query la seleziona va
sempre aliasata come `cliente_denominazione`**, sia partendo da un'altra tabella sia
dentro `ClientiModel`.

```php
->select("clienti.*, clienti.denominazione AS cliente_denominazione, ...")
```

Dentro `ClientiModel` questo produce un doppione apparente, perché `clienti.*` porta con
sé anche la colonna grezza: è un effetto collaterale innocuo di `SELECT *`. Il codice
applicativo deve leggere sempre e solo `cliente_denominazione`.

**Il prefisso del codice distingue due popolazioni di clienti.** Un codice numerico
(`2121`, `945`) è l'`ANCODICE` originale del gestionale contabile Ad Hoc, conservato tale e
quale; un codice `INT-xxx` identifica un cliente creato solo dentro questo gestionale e non
presente in contabilità. Il criterio per isolarli è quindi `codice LIKE 'INT-%'`.
`ClientiModel::generaCodice()` cerca il massimo solo fra i codici `INT-`, quindi i codici
numerici legacy convivono senza alterare la numerazione. **Non va normalizzato tutto a
`INT-xxx`**: cancellerebbe l'informazione.

### clienti_adhoc

Il parcheggio dell'anagrafica storica importata da CSV. Non partecipa a nessun flusso
operativo: i record vengono promossi a `clienti` uno alla volta (capitolo 4.4).

Contiene gli stessi campi anagrafici di `clienti` ma **tutti nullable e più larghi**
(`piva` 20 invece di 15, `cfisc` 20 invece di 16, `cap` 15 invece di 10): è dato grezzo,
e un valore fuori misura non deve far fallire l'import ma emergere alla promozione, sotto
gli occhi dell'operatore.

| Campo | Tipo | Note |
|---|---|---|
| `codice` | `VARCHAR(30)` UNIQUE | l'`ANCODICE` di Ad Hoc. L'unicità rende il ri-caricamento un aggiornamento invece di una duplicazione |
| `importato` | `TINYINT` default `0`, indicizzato | resta `1` anche se il cliente promosso viene poi eliminato |
| `cliente_id` | `INT UNSIGNED NULL` | → `clienti.id`, `SET NULL` |
| `imported_at` | `DATETIME NULL` | valorizzato alla promozione |

**Perché sia `importato` sia `cliente_id`**: nel caso normale il flag sarebbe deducibile
dalla presenza della chiave esterna, ma quella chiave è `SET NULL`. Se il cliente promosso
viene cancellato dall'anagrafica, senza il flag la riga tornerebbe nell'elenco "da
migrare" come se non fosse mai stata valutata. Con il flag resta leggibile come *migrato,
poi eliminato*, che è un'informazione diversa da *mai toccato*.

### assenze

Ferie, malattie e permessi del personale, a giornata intera.

| Campo | Tipo | Note |
|---|---|---|
| `personale_id` | `INT UNSIGNED` | → `personale.id`, `CASCADE` |
| `tipo` | `VARCHAR(20)` | `ferie` · `malattia` · `permesso` · `altro` |
| `data_inizio`, `data_fine` | `DATE` | indicizzata la prima |

La chiave esterna punta a `personale` e non a `users`, coerentemente con
`interventi.tecnico_id`: un dipendente senza account può comunque andare in ferie.

## 3.4 Il lavoro sul campo

### tipi_intervento

Il catalogo dei tipi di lavoro, gestito da Impostazioni. Non è una semplice lista di
etichette: da qui dipendono la sezione in cui l'intervento compare, la durata proposta, il
prefisso del codice e il testo che precompila le operazioni di un abbonamento.

| Campo | Tipo | Note |
|---|---|---|
| `codice` | `VARCHAR(50)` UNIQUE | |
| `nome` | `VARCHAR(100)` | |
| `categoria` | `VARCHAR(30)` default `generale` | `generale` · `piscine` · `addolcitori`: pilota la divisione degli interventi per area |
| `icona` | `VARCHAR(50) NULL` | **classe Font Awesome** (`fas ...`) |
| `durata_default` | `INT UNSIGNED` default `60` | minuti, precompila il form |
| `abbonabile` | `TINYINT UNSIGNED` default `0` | `1` = il tipo compare nella tendina degli abbonamenti |
| `prefisso_codice` | `VARCHAR(10) NULL` | tre lettere per i codici da abbonamento (`PIS`, `ADD`); `NULL` = si usa `INT` |
| `ha_pulizia_fondo` | `TINYINT UNSIGNED` default `0` | `1` = il tipo prevede l'opzione pulizia del fondo |
| `operazioni_standard` | `TEXT NULL` | elenco puntato che precompila `abbonamenti.operazioni_incluse` |
| `ordine` | `INT UNSIGNED` default `0`, indicizzato | |

> `docs/schema.html` documenta `icona` come classe Bootstrap Icons. È un residuo: il fix
> della v0.24.14 ha stabilito che sono classi Font Awesome, dopo che le icone non erano mai
> comparse sul calendario proprio a causa del prefisso sbagliato.

### interventi

La tabella centrale. Ventisei colonne, di cui molte accumulate nel tempo.

| Campo | Tipo | Note |
|---|---|---|
| `codice` | `VARCHAR(20)` UNIQUE | `INT-0001`, oppure il prefisso del tipo per le occorrenze da abbonamento, `EXT-` per le visite extra |
| `cliente_id` | `INT UNSIGNED` | → `clienti.id`, `RESTRICT` |
| `tecnico_id` | `INT UNSIGNED NULL` | → `personale.id`, `SET NULL` |
| `descrizione` | `VARCHAR(255) NULL` | precompilata dal tipo scelto, sempre modificabile |
| `priorita` | `VARCHAR(30)` default `normale` | `abbonamento` · `normale` · `urgente` |
| `tipo_intervento_id` | `INT UNSIGNED NULL` | → `tipi_intervento.id`, `SET NULL` |
| `stato` | `VARCHAR(30)` default `da_pianificare`, indicizzato | `da_pianificare` · `pianificato` · `in_corso` · `completato` · `annullato` · `sospeso` |
| `data_pianificata` | `DATETIME NULL`, indicizzata | quando è fissato l'appuntamento |
| `data_inizio_lavoro` | `DATETIME NULL` | scritta dal bottone "Inizio lavoro" |
| `data_completamento` | `DATETIME NULL` | scritta alla chiusura |
| `data_scadenza` | `DATE NULL` | entro quando deve essere eseguito |
| `durata_stimata` | `INT UNSIGNED NULL` | minuti; se assente vale il default del tipo |
| `urgenza` | `TINYINT(1)` default `0` | flag indipendente da `priorita` |
| `abbonamento_id` | `INT UNSIGNED NULL` | → `abbonamenti.id`, `RESTRICT` |
| `cantiere_id` | `INT UNSIGNED NULL` | → `cantieri.id`, `RESTRICT` |
| `extra` | `TINYINT(1)` default `0` | `1` = visita fuori dal piano dell'abbonamento |
| `pulizia_fondo` | `TINYINT(1)` default `0` | ereditata dal periodo, modificabile alla chiusura |
| `apertura`, `chiusura` | `TINYINT(1)` default `0` | fase stagionale della piscina, mutuamente esclusive |
| `impianto_id` | `INT UNSIGNED NULL` | segnaposto senza chiave esterna, riservato a una futura tabella `impianti` |

> `data_inizio_lavoro` e `data_completamento` (v0.24.29) non sono documentate in
> `docs/schema.html`. Servono a tracciare i tempi reali di lavorazione, in vista di un
> calcolo futuro della durata media per tipo.

Le relazioni verso abbonamento e cantiere sono **una-a-molti dirette**, senza tabelle
pivot: un intervento appartiene a un solo abbonamento e a un solo cantiere. Entrambe sono
`RESTRICT`, quindi finché esistono interventi collegati il contratto o il cantiere non si
possono eliminare.

`apertura` e `chiusura` non possono essere entrambe attive: il metodo `normalizza()` fa
rispettare il vincolo, e in caso di conflitto lascia vincere l'apertura.

### interventi_materiali

I materiali portati o da portare. Una tabella che serve due scopi, distinti da un solo
campo.

| Campo | Tipo | Note |
|---|---|---|
| `cliente_id` | `INT UNSIGNED` | → `clienti.id`, `CASCADE`. **Sempre valorizzato** |
| `intervento_id` | `INT UNSIGNED NULL` | → `interventi.id`, `CASCADE`. **`NULL` = materiale sospeso** |
| `articolo_id` | `INT UNSIGNED NULL` | → `articoli.id`, `SET NULL`. `NULL` = descrizione libera fuori catalogo |
| `descrizione` | `VARCHAR(255)` | copiata dall'articolo al salvataggio, oppure scritta a mano |
| `quantita` | `INT UNSIGNED` default `1` | |
| `note` | `VARCHAR(255) NULL` | dove finisce il riferimento `[Da INT-0042]` quando il materiale torna sospeso |
| `stato` | `VARCHAR(20)` default `da_portare` | `da_portare` · `consegnato` |

La chiave di lettura è che `intervento_id` è nullable dalla v0.11.0: un materiale con
quel campo vuoto è **sospeso**, cioè un promemoria legato al cliente e non ancora a una
visita. Agganciarlo a un intervento non crea una riga nuova, sposta quella esistente.

> Nel database `descrizione` è `NOT NULL`, mentre `docs/schema.html` la documenta come
> nullable. Il model la valorizza sempre, copiandola dall'articolo quando l'utente sceglie
> dal catalogo (`InterventiMaterialiModel::normalizza()`).

### interventi_note

Il diario datato di un intervento: `intervento_id` (`CASCADE`), `data_nota` (`DATE`, che
il model imposta a oggi se lasciata vuota) e `testo`. L'autore si legge da `created_by`,
con una join su `personale` per il nome.

Esiste perché il campo `note` singolo dell'intervento veniva sovrascritto a ogni
aggiornamento. Il caso d'uso tipico sono le aperture piscina, che durano più visite:
*15.06 vuotata → 20.06 in riempimento → 24.06 avviata*. Il vecchio campo `note` resta
accanto al diario, da valutare se renderlo ridondante.

## 3.5 Contratti e cantieri

### abbonamenti

Il contratto di manutenzione ricorrente.

| Campo | Tipo | Note |
|---|---|---|
| `cliente_id` | `INT UNSIGNED`, indicizzato | → `clienti.id`, `RESTRICT` |
| `tipo_intervento_id` | `INT UNSIGNED`, indicizzato | → `tipi_intervento.id`, `RESTRICT`: stesso tipo per tutte le visite generate |
| `abbonamento_precedente_id` | `INT UNSIGNED NULL` **UNIQUE** | → `abbonamenti.id`, `SET NULL`: la catena dei rinnovi |
| `data_inizio`, `data_fine` | `DATE` | tipicamente 01/01 e 31/12; `data_fine` è indicizzata |
| `durata_mesi` | `INT UNSIGNED NULL` | calcolata dal model, non passata dal controller |
| `prezzo` | `DECIMAL(10,2) NULL` | totale del contratto, **non** per singola visita |
| `operazioni_incluse` | `TEXT NULL` | precompilato da `tipi_intervento.operazioni_standard`, poi libero |
| `modalita_pagamento` | `VARCHAR(255) NULL` | testo libero: gli accordi sono specifici del cliente |
| `stato` | `VARCHAR(20)` default `proposta`, indicizzato | `proposta` · `attivo` · `sospeso` · `scaduto` · `disdetto` · `rifiutata` |

L'indice **UNIQUE** su `abbonamento_precedente_id` (v0.26.0) impone a livello di database
che un abbonamento abbia al massimo un rinnovo: la catena storica resta lineare e si può
ricostruire con una CTE ricorsiva di MySQL 8.

### abbonamenti_periodi

I sotto-periodi di un abbonamento, ciascuno con la propria frequenza.

| Campo | Tipo | Note |
|---|---|---|
| `abbonamento_id` | `INT UNSIGNED`, indicizzato | → `abbonamenti.id`, `CASCADE` |
| `data_inizio`, `data_fine` | `DATE` | i periodi devono coprire l'intero arco del contratto |
| `frequenza` | `VARCHAR(20)` | `settimanale` · `quindicinale` · `mensile` · `bimestrale` · `trimestrale` · `semestrale` · `annuale` |
| `con_pulizia_fondo` | `TINYINT UNSIGNED` default `0` | opzione valida per quel periodo |
| `ordine` | `INT UNSIGNED` default `1` | ordine di visualizzazione e di generazione |

Esistono perché la frequenza può cambiare dentro lo stesso contratto annuale: una piscina
si visita ogni due settimane in primavera e ogni settimana in estate. Un abbonamento
semplice ha un unico periodo che coincide con le date del contratto. Il perché di questa
struttura, e l'alternativa scartata, sono al capitolo 6.2.

### cantieri

Il progetto che raggruppa più interventi per un cliente.

| Campo | Tipo | Note |
|---|---|---|
| `cliente_id` | `INT UNSIGNED`, indicizzato | → `clienti.id`, `RESTRICT` |
| `titolo` | `VARCHAR(150)` | |
| `indirizzo`, `citta` | `VARCHAR NULL` | **`NULL` = valgono quelli del cliente** |
| `referente_nome` | `VARCHAR(150) NULL` | nome ed eventuale ruolo del referente operativo |
| `referente_telefono` | `VARCHAR(50) NULL` | isolato per il link `tel:`, senza ripiego sul telefono del cliente |
| `lat`, `lng`, `geocoded_at`, `geocodifica_fallita` | | posizione propria; `NULL` = quella del cliente |
| `tipo` | `VARCHAR(30)` default `nuova_costruzione` | `nuova_costruzione` · `ristrutturazione` · `manutenzione_straordinaria` |
| `tipo_intervento_id` | `INT UNSIGNED NULL` | → `tipi_intervento.id`, `SET NULL`: tipo predefinito per i nuovi interventi |
| `stato` | `VARCHAR(20)` default `aperto`, indicizzato | `aperto` · `sospeso` · `chiuso` |
| `data_inizio`, `data_fine_prevista` | `DATE NULL` | |
| `note` | `TEXT NULL` | note generali, distinte dal diario |

Il ripiego sui dati del cliente quando i campi sono `NULL` mantiene il caso comune senza
attrito — condomini e privati, dove il cantiere è a casa del cliente — e copre i casi reali
in cui non coincidono: un intermediario che segue piscine per conto di terzi, o un cliente
con due proprietà diverse.

### cantieri_note

Il diario del cantiere: stessa struttura di `interventi_note`, con `cantiere_id` in
`CASCADE`. Note e interventi sono **fratelli figli del cantiere, non collegati fra loro**:
le note sono prosa libera, e quando un appunto diventa lavoro concreto si crea un
intervento e lo si aggancia al cantiere.

## 3.6 Magazzino

### categorie_articoli

`nome` e `ordine`. Le categorie iniziali sono Prodotti (cloro, sale, antialghe),
Attrezzature (retini, kit di analisi), Apparecchiature (addolcitori) e Ricambi.

### articoli

Il catalogo. È un'anagrafica, non una gestione di magazzino: la colonna `giacenza` esiste
ma nessun flusso la movimenta.

| Campo | Tipo | Note |
|---|---|---|
| `codice` | `VARCHAR(50) NULL` | |
| `descrizione` | `VARCHAR(255)` | salvata sempre in maiuscolo |
| `categoria_id` | `INT UNSIGNED NULL` | → `categorie_articoli.id`, `SET NULL` |
| `unita_misura` | `VARCHAR(20)` default `pz` | `pz` · `kg` · `lt` · `m2` · `m` · `conf` |
| `costo`, `vendita` | `DECIMAL(10,2) NULL` | prezzo di acquisto e di listino |
| `giacenza` | `DECIMAL(10,2)` default `0` | mostrata come intero; nessun carico/scarico automatico |
| `attivo` | `TINYINT(1)` default `1` | |

## 3.7 Promemoria

### promemoria

Eventi aziendali ad hoc, gestiti dall'ufficio dal Calendario, dove compaiono in viola e
in sola lettura per i tecnici. Campi: `titolo`, `data_ora_inizio` (indicizzata),
`data_ora_fine` (se assente il model la imposta a un'ora dopo l'inizio) e `note`.

### promemoria_dismiss

Registra quali promemoria di giornata un utente ha già chiuso nel modal di apertura.
Chiave esterna verso `promemoria` e verso `users`, entrambe `CASCADE`, con un indice
**UNIQUE** sulla coppia `(promemoria_id, utente_id)`.

La tabella esiste per una ragione precisa: salvare quel "letto" nel browser lo avrebbe
legato al singolo dispositivo, mentre lo stesso utente apre il gestionale dal PC
dell'ufficio e dal telefono.

## 3.8 View di consultazione

Tre view di sola lettura, non usate da alcun codice applicativo: servono per le
interrogazioni manuali con un client SQL.

| Vista | Contenuto |
|---|---|
| `v_abbonamenti_clienti` | un abbonamento per riga, con i dati del cliente, il tipo e lo stato calcolato |
| `v_abbonamenti_clienti_interventi` | come sopra, con le visite generate in `LEFT JOIN` (include quindi anche gli abbonamenti senza visite) |
| `v_interventi_clienti` | tutti gli interventi con cliente, tipo, tecnico e cantiere |

Sono state ricreate in una migration separata quando è nata `clienti.denominazione`:
modificare quella originale avrebbe rotto una `migrate` da zero, perché sarebbe stata
eseguita prima che la colonna esistesse.

## 3.9 Integrità referenziale

Il quadro completo delle regole `ON DELETE`, verificato sul database.

| Tabella | Colonna | Riferimento | ON DELETE |
|---|---|---|---|
| `personale` | `user_id` | `users.id` | SET NULL |
| `clienti` | `user_id` | `users.id` | SET NULL |
| `clienti` | `tecnico_preferito_id` | `personale.id` | SET NULL |
| `clienti_adhoc` | `cliente_id` | `clienti.id` | SET NULL |
| `assenze` | `personale_id` | `personale.id` | CASCADE |
| `interventi` | `cliente_id` | `clienti.id` | RESTRICT |
| `interventi` | `tecnico_id` | `personale.id` | SET NULL |
| `interventi` | `tipo_intervento_id` | `tipi_intervento.id` | SET NULL |
| `interventi` | `abbonamento_id` | `abbonamenti.id` | RESTRICT |
| `interventi` | `cantiere_id` | `cantieri.id` | RESTRICT |
| `interventi_materiali` | `cliente_id` | `clienti.id` | CASCADE |
| `interventi_materiali` | `intervento_id` | `interventi.id` | CASCADE |
| `interventi_materiali` | `articolo_id` | `articoli.id` | SET NULL |
| `interventi_note` | `intervento_id` | `interventi.id` | CASCADE |
| `abbonamenti` | `cliente_id` | `clienti.id` | RESTRICT |
| `abbonamenti` | `tipo_intervento_id` | `tipi_intervento.id` | RESTRICT |
| `abbonamenti` | `abbonamento_precedente_id` | `abbonamenti.id` | SET NULL |
| `abbonamenti_periodi` | `abbonamento_id` | `abbonamenti.id` | CASCADE |
| `cantieri` | `cliente_id` | `clienti.id` | RESTRICT |
| `cantieri` | `tipo_intervento_id` | `tipi_intervento.id` | SET NULL |
| `cantieri_note` | `cantiere_id` | `cantieri.id` | CASCADE |
| `promemoria_dismiss` | `promemoria_id` | `promemoria.id` | CASCADE |
| `promemoria_dismiss` | `utente_id` | `users.id` | CASCADE |
| `articoli` | `categoria_id` | `categorie_articoli.id` | SET NULL |

### Un bug da ricordare: gli argomenti di `addForeignKey()`

Fino alla v0.27.0 tre di queste chiavi erano `CASCADE` invece di `SET NULL`:
`clienti.tecnico_preferito_id`, `clienti.user_id` e `personale.user_id`. La firma di
`addForeignKey()` vuole **`$onUpdate` prima di `$onDelete`**, e gli argomenti erano
invertiti.

Non era un difetto formale: **eliminare un dipendente cancellava a cascata tutti i clienti
che lo avevano come tecnico preferito**, ed eliminare un utente cancellava il cliente e la
scheda dipendente collegati. `docs/schema.html` documentava già il comportamento corretto —
era il database a divergere dalla documentazione, non il contrario. Corretto con una
migration per tabella, entrambe reversibili.

### Cancellazione di un cliente

Le chiavi `RESTRICT` fanno sì che un cliente con interventi, cantieri o abbonamenti non
possa essere eliminato. Perché l'utente non veda un'eccezione grezza del database,
`ClientiModel::relazioniBloccanti()` scopre **da `information_schema`** quali tabelle hanno
una chiave `RESTRICT` o `NO ACTION` verso `clienti.id`, conta i record collegati e produce
un messaggio leggibile del tipo *"22 in interventi, 1 in abbonamenti"*.

La scelta di interrogare `information_schema` invece di scrivere l'elenco a mano è
deliberata: quando in futuro nascerà una tabella `impianti` o `preventivi` con una chiave
verso i clienti, il controllo la includerà da solo.
