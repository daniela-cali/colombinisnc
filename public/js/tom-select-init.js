/**
 * Init condiviso di Tom Select, caricato dal layout insieme alla libreria.
 *
 * Perché esiste: ogni view caricava Tom Select per conto suo e copiava il
 * correttivo del bug #729, che alla fine c'era solo in metà dei punti. Qui il
 * correttivo sta in un punto solo, come i default delle DataTable in
 * datatable-init.js:
 *
 *     var ts = initTomSelect('#sel-materiale', { create: ..., placeholder: '...' });
 *
 * Le tendine del cliente non hanno bisogno di script nella view: basta stamparle
 * con il partial partials/select_cliente, che mette data-cerca-cliente.
 */
(function (window) {
    'use strict';

    /**
     * Crea un Tom Select e applica il correttivo del bug noto
     * github.com/orchidjs/tom-select/issues/729: a ogni apertura del menu chiama
     * focus() senza preventScroll e la pagina salta. La patch riguarda solo i due
     * elementi interni di questa istanza, non il prototipo globale.
     */
    function initTomSelect(selettore, opzioni) {
        var ts = new TomSelect(selettore, opzioni || {});

        [ts.control_input, ts.focus_node].forEach(function (el) {
            if (! el) return;
            var nativeFocus = el.focus.bind(el);
            el.focus = function (options) {
                return nativeFocus(Object.assign({ preventScroll: true }, options));
            };
        });

        return ts;
    }

    /**
     * Tendina del cliente: si cerca per nome o per città, e la città compare sotto
     * il nome per distinguere gli omonimi (Tom Select legge data-citta dall'opzione).
     *
     * maxOptions basso è voluto: su iPhone il menu di Tom Select non scorre col dito
     * (vedi _form_materiale_scripts.php), quindi deve restare abbastanza corto da
     * non dover scorrere. Si scrive qualche lettera e il cliente è fra i primi.
     */
    function initCercaCliente(el) {
        return initTomSelect(el, {
            maxOptions:  8,
            searchField: ['text', 'citta'],
            placeholder: 'Cerca cliente…',
            render: {
                option: function (data, escape) {
                    var citta = data.citta
                        ? '<div class="ts-citta">' + escape(data.citta) + '</div>'
                        : '';
                    return '<div>' + escape(data.text) + citta + '</div>';
                },
                no_results: function () {
                    return '<div class="no-results">Nessun cliente trovato</div>';
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[data-cerca-cliente]').forEach(initCercaCliente);
    });

    window.initTomSelect = initTomSelect;
})(window);
