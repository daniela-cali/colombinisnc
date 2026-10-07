/**
 * Formattazione live di campi valuta.
 *
 * Markup dichiarativo, nessuna inizializzazione manuale nella singola pagina:
 *
 *   <input type="text" data-currency-display="prezzo" inputmode="decimal" placeholder="0,00">
 *   <input type="hidden" name="prezzo" id="prezzo" value="1234.56">
 *
 * Il campo con data-currency-display è quello che l'utente vede e digita, formattato in
 * stile italiano (1.234,56). Il campo puntato dall'attributo (qui "prezzo") è quello che
 * viene davvero inviato al server, sempre in formato "pulito" (1234.56, punto decimale,
 * niente separatore delle migliaia) — quello che si aspetta la regola di validazione
 * decimal di CodeIgniter.
 */
document.querySelectorAll('[data-currency-display]').forEach(function (display) {
    var hidden = document.getElementById(display.dataset.currencyDisplay);
    if (! hidden) return;

    function raw() {
        return display.value.replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, '');
    }

    function sync() {
        hidden.value = raw();
    }

    function format() {
        var val = parseFloat(raw());
        if (! isNaN(val)) {
            display.value = val.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        sync();
    }

    display.addEventListener('input', sync);
    display.addEventListener('blur', format);

    // Il valore precompilato dal server sta nel campo nascosto: va portato nel visibile prima
    // di formattare, altrimenti format() leggerebbe il visibile vuoto e svuoterebbe anche il
    // nascosto, perdendo il prezzo al salvataggio.
    if (display.value === '' && hidden.value !== '') {
        display.value = hidden.value.replace('.', ',');
    }
    format();
});
