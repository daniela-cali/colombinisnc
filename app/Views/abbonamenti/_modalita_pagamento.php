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

<script>
// Questo script sta nel corpo della pagina, quindi gira prima che il layout carichi
// Tom Select in fondo: si aspetta DOMContentLoaded.
document.addEventListener('DOMContentLoaded', function () {
    initTomSelect('#modalita-pagamento', {
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
});
</script>
