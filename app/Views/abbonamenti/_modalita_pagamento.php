<?php
/**
 * Campo «Modalità di pagamento» dei form nuovo e modifica: tendina con le frasi standard,
 * dove l'operatore può anche scriverne una sua. Si salva la frase, non un codice.
 *
 * @var string|null $modalita Frase salvata sull'abbonamento (modifica, rinnovo); null nel nuovo
 */
$opzioni = \App\Models\AbbonamentiModel::MODALITA_PAGAMENTO_STANDARD;
$valore  = (string) old('modalita_pagamento', $modalita ?? $opzioni[0], false);

// Una frase personalizzata già salvata diventa una voce in più, solo in questa pagina:
// senza, la tendina non la mostrerebbe e al salvataggio andrebbe persa.
if ($valore !== '' && ! in_array($valore, $opzioni, true)) {
    $opzioni[] = $valore;
}
?>
<label class="form-label" for="modalita-pagamento">Modalità di pagamento <span class="text-danger">*</span></label>
<select name="modalita_pagamento" id="modalita-pagamento">
    <?php foreach ($opzioni as $frase): ?>
        <option value="<?= esc($frase) ?>" <?= $frase === $valore ? 'selected' : '' ?>><?= esc($frase) ?></option>
    <?php endforeach ?>
</select>

<link rel="stylesheet" href="<?= asset_url('assets/vendor/tom-select/tom-select.bootstrap5.min.css') ?>">
<script src="<?= asset_url('assets/vendor/tom-select/tom-select.complete.min.js') ?>"></script>
<script>
(function () {
    var ts = new TomSelect('#modalita-pagamento', {
        create: true,
        createOnBlur: true,
        createFilter: function (input) { return input.trim().length > 0; },
        render: {
            option_create: function (data, escape) {
                return '<div class="create">Usa «' + escape(data.input) + '»</div>';
            },
            no_results: null
        }
    });

    // Bug noto TomSelect (github.com/orchidjs/tom-select/issues/729), come nei materiali degli
    // interventi: a ogni apertura chiama focus() senza preventScroll e la pagina salta.
    [ts.control_input, ts.focus_node].forEach(function (el) {
        if (! el) return;
        var nativeFocus = el.focus.bind(el);
        el.focus = function (options) {
            return nativeFocus(Object.assign({ preventScroll: true }, options));
        };
    });
})();
</script>
