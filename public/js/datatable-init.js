/**
 * Init condiviso delle DataTable del gestionale.
 *
 * Perché esiste: le dieci tabelle del gestionale ripetevano le stesse quindici
 * righe di configurazione (traduzioni, paginazione, numero di righe), quindi
 * ogni decisione comune andava applicata a mano in altrettanti file — e bastava
 * dimenticarne uno perché una tabella si comportasse diversamente dalle altre.
 * Qui la regola sta in un punto solo.
 *
 * Nella view resta solo ciò che è davvero specifico di quella tabella:
 * `columnDefs`, `order`, e i messaggi che nominano l'entità
 * (`emptyTable`, `zeroRecords`).
 *
 *     var table = initTabella('#tabella-cantieri', {
 *         order: [[0, 'desc']],
 *         columnDefs: [ ... ],
 *         language: { emptyTable: 'Nessun cantiere registrato.' }
 *     });
 *
 * ATTENZIONE alla fusione dei default: `Object.assign` è superficiale, quindi
 * passare `language: { emptyTable: '...' }` cancellerebbe l'intero blocco delle
 * traduzioni e la tabella tornerebbe in inglese — senza nessun errore, il che
 * lo renderebbe difficile da notare. Per questo `language` viene fuso chiave per
 * chiave, e dentro di esso anche i due oggetti annidati `paginate` e
 * `lengthLabels`.
 */
(function (window) {
    'use strict';

    /* In DataTables 2 l'etichetta di -1 arriva da `lengthLabels`, non dal
       `lengthMenu`: per i valori noti il default inglese del bundle scavalca
       l'array parallelo che si usava in DataTables 1.x. */
    var LINGUA = {
        search:       'Cerca:',
        lengthMenu:   'Mostra _MENU_ righe',
        info:         'Da _START_ a _END_ di _TOTAL_ record',
        infoEmpty:    'Nessun record',
        infoFiltered: '(filtrati da _MAX_ totali)',
        zeroRecords:  'Nessun risultato trovato',
        emptyTable:   'Nessun record',
        paginate:     { first: '«', last: '»', next: '›', previous: '‹' },
        lengthLabels: { '-1': 'Tutti' }
    };

    var OPZIONI = {
        responsive: true,
        orderMulti: true,          // già attivo di default (Shift+clic ordina su più colonne)
        lengthMenu: [10, 25, 50, 100, -1],  // -1 = nessuna paginazione, etichetta in LINGUA.lengthLabels
        /* 25 è il default degli elenchi. Una view che ne vuole meno passa il suo
           `pageLength`, ma deve essere un valore presente nel menu qui sopra:
           altrimenti la tendina mostra un numero che non contiene. */
        pageLength: 25
    };

    /**
     * Crea una DataTable con i default del gestionale.
     *
     * @param {string|Object} selettore  selettore della tabella ('#tabella-cantieri')
     *                                   oppure l'oggetto jQuery già risolto
     * @param {Object} [opzioni]  solo ciò che differisce dai default
     * @returns {Object} l'API DataTables, per i filtri e il resto
     */
    window.initTabella = function (selettore, opzioni) {
        opzioni = opzioni || {};

        var lingua = opzioni.language || {};
        var config = Object.assign({}, OPZIONI, opzioni);

        config.language              = Object.assign({}, LINGUA, lingua);
        config.language.paginate     = Object.assign({}, LINGUA.paginate, lingua.paginate || {});
        config.language.lengthLabels = Object.assign({}, LINGUA.lengthLabels, lingua.lengthLabels || {});

        return $(selettore).DataTable(config);
    };
})(window);
