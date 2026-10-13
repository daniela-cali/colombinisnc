<?php
/**
 * Tendina di scelta del cliente con ricerca mentre si scrive. L'attributo
 * data-cerca-cliente la fa trasformare da public/js/tom-select-init.js; la città
 * viaggia in data-citta e compare sotto il nome nel menu.
 *
 * Si rende con view('partials/select_cliente', [...]), non con $this->include().
 *
 * @var array           $clienti     Righe da ClientiModel::perTendina()
 * @var int|string|null $selezionato Cliente preselezionato se il form non torna da un errore
 */
$scelto = old('cliente_id', $selezionato ?? '');
?>
<select name="cliente_id" class="form-select" data-cerca-cliente>
    <option value="">— seleziona —</option>
    <?php foreach ($clienti as $c): ?>
        <option value="<?= $c['id'] ?>" data-citta="<?= esc($c['citta'] ?? '') ?>"
                <?= $scelto == $c['id'] ? 'selected' : '' ?>>
            <?= esc($c['cliente_denominazione']) ?>
        </option>
    <?php endforeach ?>
</select>
