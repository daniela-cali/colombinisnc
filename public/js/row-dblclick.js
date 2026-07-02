/**
 * Doppio click su riga di tabella → apre la scheda collegata.
 *
 * Pattern riutilizzabile: in ogni <tr> si marca il link "canonico" della riga
 * con la classe `js-row-open`. Il doppio click su una zona neutra della riga
 * naviga verso quel link, senza duplicare l'URL (che è già sull'<a>).
 *
 *   Interventi   → js-row-open sull'<a> del codice   (scheda intervento)
 *   Clienti      → js-row-open sull'<a> del nome      (scheda cliente)
 *   Abbonamenti… → js-row-open sul link principale della riga
 *
 * L'handler è delegato su `document`, quindi:
 *  - vale per tutte le tabelle senza inizializzazioni per-pagina;
 *  - sopravvive ai redraw di DataTables (filtri, ordinamento, paginazione),
 *    che rigenerano le <tr> a ogni draw().
 *
 * I click singoli restano intatti: se il doppio click finisce dentro un
 * elemento interattivo (<a>, <button>, input…), l'handler si ferma e lascia
 * agire quell'elemento (es. doppio click sul nome cliente → scheda cliente).
 *
 * Su mobile è un no-op: il doppio tap è riservato allo zoom, il tap singolo
 * apre i link come sempre. Pattern puramente additivo, nessuna regressione.
 */
(function () {
    'use strict';

    document.addEventListener('dblclick', function (e) {
        // Se il bersaglio è un elemento interattivo, lascialo lavorare.
        if (e.target.closest('a, button, input, textarea, select, label')) {
            return;
        }

        const row = e.target.closest('tr');
        if (!row) {
            return;
        }

        const link = row.querySelector('a.js-row-open');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href) {
            return;
        }

        // Evita che resti selezionato il testo sotto il cursore.
        const sel = window.getSelection();
        if (sel) {
            sel.removeAllRanges();
        }

        // Ctrl/Cmd → nuova scheda, come un vero link.
        if (e.ctrlKey || e.metaKey) {
            window.open(href, '_blank');
        } else {
            window.location.href = href;
        }
    });
})();
