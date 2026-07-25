<?php
/**
 * Init del mini-form materiali per _form_materiale.php.
 * Incluso dentro section('scripts') con: <?= $this->include('operativo/interventi/_form_materiale_scripts') ?>
 * Va incluso solo nelle pagine che includono anche _form_materiale.php.
 *
 * Su mobile (<768px) il <select> resta nativo invece di inizializzare TomSelect: il dropdown
 * custom di TomSelect ha un bug di scroll touch su WebKit iOS (confermato con diagnostica dal
 * vivo — dropdown-content non scrolla, la pagina sotto sì — e riscontrato anche come problema
 * noto/mai risolto in Select2, libreria analoga). Il nativo scrolla sempre correttamente perché
 * disegnato dal sistema operativo. Si perde la ricerca-mentre-scrivi su mobile, non la
 * possibilità di descrizione libera: l'opzione "Descrizione libera…" nel select rivela un
 * campo di testo dedicato. Su desktop resta invariato TomSelect con ricerca e creazione al volo.
 */
?>
<script src="<?= base_url('assets/vendor/tom-select/tom-select.complete.min.js') ?>"></script>
<script>
(function () {
    var selEl        = document.getElementById('sel-materiale');
    var form         = document.getElementById('form-materiale');
    var hArticolo     = document.getElementById('h-articolo-id');
    var hDescrizione  = document.getElementById('h-descrizione');
    var isMobile      = window.matchMedia('(max-width: 767.98px)').matches;

    if (isMobile) {
        var inputLibero = document.getElementById('input-descrizione-libera');

        selEl.addEventListener('change', function () {
            var libero = selEl.value === '__libero__';
            inputLibero.classList.toggle('d-none', ! libero);
            if (libero) {
                inputLibero.focus();
            }
        });

        form.addEventListener('submit', function (e) {
            if (selEl.value === '__libero__') {
                var testo = inputLibero.value.trim().toUpperCase();
                if (! testo) { e.preventDefault(); alert('Scrivi una descrizione.'); return; }
                hArticolo.value    = '';
                hDescrizione.value = testo;
            } else if (selEl.value) {
                hArticolo.value    = selEl.value;
                hDescrizione.value = '';
            } else {
                e.preventDefault();
                alert('Seleziona un articolo o scegli "Descrizione libera…".');
            }
        });

        return;
    }

    // --- Desktop: TomSelect con ricerca-mentre-scrivi + creazione al volo ---
    // L'opzione "__libero__" serve solo al fallback mobile sopra: su desktop la creazione di
    // testo libero è già gestita nativamente da TomSelect digitando nel campo.
    var optgroupLibero = selEl.querySelector('option[value="__libero__"]');
    if (optgroupLibero) {
        (optgroupLibero.closest('optgroup') || optgroupLibero).remove();
    }

    var ts = new TomSelect(selEl, {
        wrapperClass: 'ts-wrapper ts-upper',
        create: function (input) {
            var v = input.trim().toUpperCase();
            return { value: v, text: v };
        },
        createOnBlur: true,
        placeholder: 'Cerca articolo o digita descrizione libera…',
        allowEmptyOption: true,
        createFilter: function (input) { return input.trim().length > 0; }
    });

    // Bug noto TomSelect (github.com/orchidjs/tom-select/issues/729): ad ogni apertura del
    // dropdown richiama focus() senza preventScroll sui suoi elementi interni — patch mirata
    // solo a questi due elementi, non al prototipo globale.
    [ts.control_input, ts.focus_node].forEach(function (el) {
        if (! el) return;
        var nativeFocus = el.focus.bind(el);
        el.focus = function (options) {
            return nativeFocus(Object.assign({ preventScroll: true }, options));
        };
    });

    form.addEventListener('submit', function (e) {
        var val = ts.getValue();
        if (! val) { e.preventDefault(); alert('Seleziona un articolo o digita una descrizione.'); return; }
        if (/^\d+$/.test(val)) {
            hArticolo.value    = val;
            hDescrizione.value = '';
        } else {
            hArticolo.value    = '';
            hDescrizione.value = val;
        }
    });
})();
</script>
