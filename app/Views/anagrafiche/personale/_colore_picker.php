<?php
/**
 * Picker colore profilo riutilizzabile (nuovo/modifica dipendente).
 * Griglia di cerchi cliccabili: ogni cerchio è un radio nascosto, nessun JS.
 *
 * @var array       $pastelli       Palette colori disponibili (hex)
 * @var array       $colori_usati   Colori già assegnati ad altri dipendenti (hex)
 * @var string      $coloreCorrente Colore attualmente selezionato (hex), o stringa vuota
 */
$colori_usati   = $colori_usati ?? [];
$coloreCorrente = $coloreCorrente ?? '';

// Se il colore attuale non è tra i predefiniti (dato legacy), lo mostro come prima scorciatoia
$lista = $pastelli;
if ($coloreCorrente && ! in_array($coloreCorrente, $lista, true)) {
    array_unshift($lista, $coloreCorrente);
}
?>
<label class="form-label">Colore profilo</label>
<div class="colore-grid mt-1">
    <?php foreach ($lista as $hex):
        $taken    = in_array($hex, $colori_usati, true);
        $selected = ($hex === $coloreCorrente);
    ?>
        <label class="colore-swatch<?= $taken ? ' taken' : '' ?>"
               style="background-color:<?= esc($hex) ?>"
               title="<?= $taken ? esc($hex). ' Già assegnato a un altro dipendente' : esc($hex) ?>">
            <input type="radio" name="colore" value="<?= esc($hex) ?>"
                   <?= $selected ? 'checked' : '' ?> <?= $taken ? 'disabled' : '' ?>>
            <i class="bi bi-check-lg"></i>
        </label>
    <?php endforeach ?>
</div>
<span class="text-muted small">Colore con cui il dipendente comparirà nel calendario</span>
