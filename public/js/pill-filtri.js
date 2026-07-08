(function () {
    'use strict';

    /**
     * Filtri "pill" generici per tabelle DataTables.
     *
     * Il contenitore dei bottoni deve avere:
     *   data-pill-tabella   id della <table> già inizializzata come DataTable
     *   data-pill-filtri    oggetto JSON: { nomeFiltro: { col, q, regex, col2, q2, regex2 } }
     *                       col/q/regex descrivono la colonna nascosta da cercare;
     *                       col2/q2/regex2 sono opzionali, per un secondo filtro
     *                       da applicare insieme al primo (es. escludere un sotto-caso).
     *                       Un filtro senza "col" (es. {}) azzera soltanto le colonne
     *                       — è il classico "Tutti".
     *
     * Ogni bottone pill ha solo [data-filtro="nomeFiltro"], che deve corrispondere
     * a una chiave dell'oggetto data-pill-filtri del proprio contenitore.
     *
     * Il filtro di default all'apertura pagina si ottiene marcando un bottone con
     * [data-default] e simulando un click su di esso subito dopo aver creato la
     * DataTable, nello script della singola pagina.
     */

    document.addEventListener('click', function (e) {

        var btn = e.target.closest('[data-filtro]');
        if (! btn) return;

        var container = btn.closest('[data-pill-tabella]');
        if (! container) return;

        var filtri = JSON.parse(container.dataset.pillFiltri);
        var table  = $('#' + container.dataset.pillTabella).DataTable();

        // Azzera tutte le colonne usate da uno qualsiasi dei filtri del gruppo
        Object.values(filtri).forEach(function (f) {
            if (f.col)  table.column(f.col).search('', false, false);
            if (f.col2) table.column(f.col2).search('', false, false);
        });

        // Applica il filtro del pill cliccato
        var f = filtri[btn.dataset.filtro] || {};
        if (f.col) {
            table.column(f.col).search(f.q || '', !! f.regex, false);
        }
        if (f.col2) {
            table.column(f.col2).search(f.q2 || '', !! f.regex2, false);
        }
        table.draw();

        container.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b === btn);
        });
    });

})();
