# Spec — Telefono del referente cantiere come campo strutturato

> Segue e corregge `docs/spec/cantieri_luogo_referente_spec.md` (v0.24.28, 25.07.2026).
> Concordata il 26.07.2026 durante il lavoro sull'UX mobile tecnici (`mobile_ux_spec.md`).

## Contesto / problema

`cantieri.referente` è oggi un unico campo testo libero ("Manuel (custode) 339
1234567"): nome, ruolo e telefono mescolati nella stessa stringa. Nella pratica il
tecnico sul cantiere chiama spesso il referente, non il cliente (che può essere un
amministratore, un intermediario, un proprietario irreperibile) — è la stessa esigenza
di un tap-to-call già prevista per il cliente in `mobile_ux_spec.md` §2.1, ma il
numero non è isolabile da un campo libero senza un parsing fragile.

## Soluzione concordata

Spacchettare `referente` in due colonne nullable su `cantieri`:

| Campo | Tipo | Significato |
|---|---|---|
| `referente_nome` | VARCHAR(150) NULL | Nome ed eventuale ruolo (rinominata da `referente`, stesso contenuto testuale) |
| `referente_telefono` | VARCHAR(50) NULL | Numero isolato, stesso tipo/lunghezza di `clienti.telefono` |

### Perché così

- **Rename, non drop+add**: i cantieri di test già compilati con `referente` non
  perdono il testo (nome/ruolo) al momento della migration — il telefono eventualmente
  già scritto dentro quel testo va ricopiato a mano nel nuovo campo, ma non serve
  cancellare nulla.
- **Nessun fallback sul telefono del cliente** quando `referente_telefono` è NULL: a
  differenza di `indirizzo`/`lat`/`lng` (dove NULL = "vale quello del cliente", perché
  spesso coincide), il referente esiste apposta per essere una persona diversa dal
  cliente. Un fallback silenzioso chiamerebbe il cliente pensando di chiamare il
  referente — comportamento sbagliato, meglio non mostrare il bottone.
- **Resta un solo referente per cantiere**, non una tabella `cantieri_referenti`: la
  spec precedente aveva già scartato i referenti multipli strutturati per lo stesso
  motivo (80% dei cantieri ne ha uno solo, passare a tabella in futuro è una
  migrazione semplice se emerge il bisogno).

## Alternative scartate

- **Regex per estrarre il telefono dal testo libero esistente**: fragile — il campo
  contiene anche note libere ("disponibile solo la mattina"), un'estrazione
  automatica romperebbe facilmente e produrrebbe link `tel:` sbagliati.
- **Fallback su `clienti.telefono`** se il referente non ha telefono proprio: scartato,
  vedi sopra — chiamerebbe la persona sbagliata.

## Modifiche, file per file

1. **Migration** `SplitReferenteCantieri` — `modifyColumn` per rinominare
   `referente` → `referente_nome` (stesso tipo/lunghezza), `addColumn` per
   `referente_telefono VARCHAR(50) NULL` subito dopo.
2. **`app/Models/CantieriModel.php`** — `$allowedFields`: `referente` →
   `referente_nome`, `referente_telefono`.
3. **`app/Controllers/CantieriController.php`** — `regolaValidazione()`: la regola
   `referente` diventa due regole (`permit_empty|max_length[150]` e
   `permit_empty|max_length[50]`).
4. **`app/Views/cantieri/nuovo.php`, `edit.php`** — un input "Referente" (nome/ruolo)
   e un input "Telefono referente" (`type="tel"`) al posto dell'unico campo.
5. **`app/Views/cantieri/show.php`** — `referente_nome` come testo, `referente_telefono`
   come link `tel:` (stesso pattern già usato per `clienti.telefono` in
   `clienti/show.php`).
6. **`app/Views/operativo/interventi/show.php`** — quando l'intervento ha un
   `cantiere_id` e il cantiere ha `referente_telefono`, bottone "Chiama referente"
   accanto al "Chiama" del cliente (parte dell'implementazione di `mobile_ux_spec.md`
   §2.1, stesso giro di modifiche).
7. **`docs/schema.html`** — riga `referente` sostituita dalle due nuove colonne, nota
   di versione.

## Fuori scope

- Referenti multipli strutturati (tabella dedicata) — resta un solo referente per
  cantiere, come nella spec precedente.
- Fallback automatico su un altro numero se `referente_telefono` è NULL.
- Referente/amministratore per comunicazioni formali a livello cliente (tema distinto,
  vedi nota separata in `cantieri_luogo_referente_spec.md` § Fuori scope).
