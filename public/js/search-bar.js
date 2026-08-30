(function () {
    'use strict';

    /**
     * Filtri generici per tabelle DataTables — pulsanti "pill" o voci di un dropdown, stesso meccanismo.
     *
     * Il contenitore dei bottoni deve avere:
     *   data-pill-tabella   id della <table> già inizializzata come DataTable
     *   data-pill-filtri    oggetto JSON: { nomeFiltro: { col, q, regex, col2, q2, regex2 } }
     *                       col/q/regex descrivono la colonna nascosta da cercare;
     *                       col2/q2/regex2 sono opzionali, per un secondo filtro
     *                       da applicare insieme al primo (es. escludere un sotto-caso).
     *                       Un filtro senza "col" (es. {}) azzera soltanto le colonne
     *                       — è il classico "Tutti".
     *   data-pill-gruppo    (opzionale) nome con cui la tendina viene ricordata. Serve quando in
     *                       una pagina ci sono più tendine con la stessa etichetta su tabelle
     *                       diverse (la scheda cliente ne ha tre chiamate "Stato"): senza un nome
     *                       distinto si sovrascriverebbero la memoria a vicenda. In mancanza si
     *                       usa l'id della tabella.
     *
     * Ogni bottone pill ha solo [data-filtro="nomeFiltro"], che deve corrispondere
     * a una chiave dell'oggetto data-pill-filtri del proprio contenitore.
     *
     * Uso opzionale come dropdown: se il contenitore [data-pill-tabella] include uno
     * <span class="filtro-label">, il suo testo viene aggiornato con l'etichetta del
     * bottone cliccato (utile per un bottone dropdown-toggle che mostra il filtro attivo).
     *
     * I filtri scelti si ricordano in sessionStorage, cioè finché la scheda del browser resta
     * aperta: si torna alla lista dopo aver aperto una scheda o salvato un form e la si ritrova
     * come la si era filtrata, mentre il giorno dopo si riparte dai default. La chiave comprende
     * la pagina con la sua query string, così le tre sezioni degli interventi (?sezione=piscine,
     * generale, addolcitori) ricordano ciascuna la propria combinazione.
     *
     * All'apertura la pagina chiama filtriIniziali('id-tabella') subito dopo aver creato la
     * DataTable: applica i filtri ricordati e, dove non ce ne sono, quelli marcati [data-default].
     */

    function gruppoDi(contenitore) {
        return contenitore.dataset.pillGruppo || contenitore.dataset.pillTabella;
    }

    function chiaveDi(contenitore) {
        return 'filtri:' + window.location.pathname + window.location.search + ':' + gruppoDi(contenitore);
    }

    /* sessionStorage può non essere disponibile (navigazione privata, cookie bloccati): in quel
       caso i filtri semplicemente non si ricordano, senza far fallire il resto della pagina. */
    function ricorda(contenitore, nomeFiltro) {
        try {
            sessionStorage.setItem(chiaveDi(contenitore), nomeFiltro);
        } catch (e) { /* memoria non disponibile: si prosegue senza */ }
    }

    function ricordato(contenitore) {
        try {
            return sessionStorage.getItem(chiaveDi(contenitore));
        } catch (e) {
            return null;
        }
    }

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

        var label = container.querySelector('.filtro-label');
        if (label) label.textContent = btn.textContent.trim();

        ricorda(container, btn.dataset.filtro);
    });

    /**
     * Attiva i filtri iniziali di tutte le tendine di una tabella: quelli ricordati dalla
     * sessione, altrimenti i [data-default]. Da chiamare dopo aver creato la DataTable.
     *
     * Un valore ricordato che non corrisponde a nessuna voce viene ignorato e si ricade sul
     * default: una tendina che nel frattempo ha perso quella voce non lascia la pagina senza filtro.
     */
    window.filtriIniziali = function (idTabella) {
        document.querySelectorAll('[data-pill-tabella="' + idTabella + '"]').forEach(function (contenitore) {
            var scelto = ricordato(contenitore);
            var btn    = null;

            if (scelto) {
                btn = contenitore.querySelector('[data-filtro="' + CSS.escape(scelto) + '"]');
            }

            btn = btn || contenitore.querySelector('[data-default]');
            if (btn) btn.click();
        });
    };

})();
