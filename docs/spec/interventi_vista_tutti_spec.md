# Spec — Vista "Tutti gli interventi" senza filtro di sezione

> Concordata il 01.08.2026, ripresa dal branch mobile UX (§2.7): l'utente ha
> notato che le tre liste separate (Generale/Piscine/Addolcitori) impediscono
> una panoramica completa o la ricerca di un intervento di cui non si conosce
> a priori la categoria.

## Contesto / problema

`InterventiController::index()` filtra sempre per `?sezione=generale|piscine|addolcitori`
(default "generale" se il parametro manca o non è valido). Non esiste oggi alcuna
vista che mostri tutti gli interventi insieme, indipendentemente dalla categoria.

Caso d'uso reale: un cliente chiama per un intervento e il tecnico/ufficio non
sa a memoria se il suo impianto è catalogato come "generale", "piscine" o
"addolcitori" — deve indovinare la sezione giusta prima ancora di poter
cercare.

## Soluzione concordata

**Nessuna modifica al model**: `InterventiModel::elencoCompleto(?string $categoria = null)`
già non applica alcun filtro quando `$categoria` è `null` — il backend per
"Tutti" esiste da sempre, va solo collegato.

**Controller**: `?sezione=` continua a validare solo i tre valori noti
(`generale`/`piscine`/`addolcitori`, tramite `CATEGORIE_LABEL`). La differenza
è nel fallback: quando il parametro manca o non è tra i tre valori validi,
`$sezione` diventa `null` (non più forzato a `'generale'`) e
`elencoCompleto(null)` restituisce tutto. `$sezioneLabel` passa a
"Tutti gli interventi" quando `$sezione` è `null`.

**Accesso dal menu**: il click sulla voce padre "Interventi" nella sidebar
(oggi `href="#"`, serve solo ad aprire/chiudere il sottomenu) diventa un link
vero verso `operativo/interventi` senza query string — naviga alla vista
"Tutti" **e** apre comunque il sottomenu (verificato nel JS di AdminLTE 4: il
listener del treeview chiama sempre `toggle()` sul sottomenu, e salta
`preventDefault()` solo quando l'href cliccato non è `"#"` — quindi con un
href reale il browser naviga normalmente). Le tre sotto-voci (Generici,
Piscine, Addolcitori) restano invariate, con i rispettivi `?sezione=`.

**Evidenziazione nel menu**: `$sezioneCorrente` in `admin.php`, oggi
`getGet('sezione') ?: CATEGORIA_GENERALE`, non deve più avere un fallback su
"generale" — altrimenti la voce "Generici" del sottomenu risulterebbe
erroneamente evidenziata come attiva anche quando si sta guardando "Tutti".
Con `$sezioneCorrente = getGet('sezione')` (senza `?:`), nessuna sotto-voce
risulta attiva quando si è su "Tutti" — solo la voce padre "Interventi"
(già gestita da `$interventiAttivo`, basato sul prefisso dell'URL).

### Perché così

- **Riuso totale del backend esistente**: zero rischio di introdurre una
  query parallela che diverge da `elencoCompleto()` nel tempo.
- **Click sulla voce padre invece di una quarta sotto-voce esplicita
  "Tutti"**: proposta dell'utente, zero click aggiuntivi — chi cerca un
  intervento senza sapere la categoria trova comunque il sottomenu aperto
  sotto per affinare la ricerca in un secondo momento, se vuole. Alternativa
  scartata sotto.
- **Default "Tutti" quando `?sezione=` manca, non più "Generale"**: coerente
  con "nessun filtro finché non scelgo esplicitamente una categoria". Ha
  effetto anche su ~10 punti del codice che linkano a `operativo/interventi`
  senza query string (breadcrumb di `edit.php`/`show.php`, redirect di
  errore "intervento non trovato" in `InterventiController`, box dashboard in
  `dashboard/index.php`) — cambiano comportamento automaticamente, senza
  bisogno di toccarli uno per uno, e il nuovo comportamento è più corretto
  per quei contesti (un breadcrumb generico "Interventi" o un redirect di
  errore non dovrebbero forzare una categoria arbitraria).

## Alternative scartate

- **Quarta voce esplicita "Tutti" nel sottomenu**, accanto a
  Generici/Piscine/Addolcitori: più scopribile/esplicita, ma un click in più
  rispetto a quanto proposto dall'utente. Da riconsiderare in futuro se il
  click sulla voce padre risultasse poco intuitivo nell'uso reale.
- **Endpoint di ricerca separato** (solo ricerca testuale cross-categoria,
  senza una vera vista elenco): scartato — l'utente ha chiesto esplicitamente
  una panoramica, non solo una ricerca puntuale, e DataTables offre già
  ricerca full-text gratuita su qualunque elenco reso in tabella.

## Modifiche, file per file

1. **`app/Controllers/Operativo/InterventiController.php`** — `index()`:
   `$sezione` diventa `null` (invece di ricadere su `CATEGORIA_GENERALE`)
   quando il parametro GET manca o non è tra i valori validi;
   `$sezioneLabel` diventa `'Tutti gli interventi'` in quel caso. Nessuna
   modifica alla chiamata a `elencoCompleto($sezione)`, già compatibile.
   Aggiornare il docblock del metodo (oggi dice esplicitamente che l'assenza
   del parametro "ricade su generale").
2. **`app/Views/layouts/admin.php`** — voce di menu "Interventi": `href="#"`
   → `href="<?= base_url('operativo/interventi') ?>"`; `$sezioneCorrente`
   senza fallback su `CATEGORIA_GENERALE`.
3. **`app/Views/operativo/interventi/index.php`** — nessuna modifica
   strutturale: i controlli esistenti su `$sezione` (`in_array(...)`,
   `$sezione === 'piscine'`, per mostrare i pill "Abbonamenti"/"Aperture"/
   "Chiusure") sono già confronti stretti, `null` non li attiva mai — corretto,
   quei filtri categoria-specifici non hanno senso in una vista mista.
   Aggiornare solo il docblock (`@var string $sezione` → `@var string|null $sezione`).

## Fuori scope

- Badge/colonna che mostri la categoria di ogni riga nella vista "Tutti"
  (oggi la colonna "Tipo" mostra già nome+icona del tipo intervento, che
  distingue implicitamente la categoria) — da valutare solo se in uso reale
  risultasse poco chiaro.
- Salvare/ricordare l'ultima sezione visitata (es. in sessione) per farla
  diventare il nuovo default al posto di "Tutti" — non richiesto.
- Toccare singolarmente i link bare a `operativo/interventi` sparsi nel
  codice (breadcrumb, redirect errore, box dashboard): cambiano
  comportamento automaticamente col nuovo default, nessuna modifica di riga
  necessaria per loro.
