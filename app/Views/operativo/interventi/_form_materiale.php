<?php
/**
 * @var int|null $interventoId        Vuoto/null se il materiale nasce sospeso (non legato a un intervento)
 * @var int      $clienteId
 * @var string   $from                URL di ritorno dopo il salvataggio (vuoto = fallback del controller)
 * @var array    $articoliPerCat      Da ArticoliModel::perCategoria() [cat_id => ['nome'=>..,'articoli'=>[..]]]
 * @var bool     $riapriStepMateriali Se vero, aggiunge un materiale e riapre subito il modal per il successivo
 */
$riapriStepMateriali = $riapriStepMateriali ?? false;
$from                = $from ?? '';
$articoliPerCat      = $articoliPerCat ?? [];
?>
<form action="<?= base_url('operativo/materiali/store') ?>" method="post" id="form-materiale">
    <?= csrf_field() ?>
    <?php if (! empty($interventoId)): ?>
        <input type="hidden" name="intervento_id" value="<?= $interventoId ?>">
    <?php endif ?>
    <input type="hidden" name="cliente_id"   value="<?= $clienteId ?>">
    <input type="hidden" name="articolo_id"  id="h-articolo-id">
    <input type="hidden" name="descrizione"  id="h-descrizione">
    <?php if ($from): ?>
        <input type="hidden" name="from" value="<?= esc($from) ?>">
    <?php endif ?>
    <?php if ($riapriStepMateriali): ?>
        <input type="hidden" name="riapri_step_materiali" value="1">
    <?php endif ?>
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small">Articolo / Descrizione <span class="text-danger">*</span></label>
            <select id="sel-materiale" placeholder="Cerca articolo o digita descrizione libera…">
                <option value=""></option>
                <optgroup label="Altro">
                    <option value="__libero__">Descrizione libera…</option>
                </optgroup>
                <?php foreach ($articoliPerCat as $cat): ?>
                    <optgroup label="<?= esc($cat['nome']) ?>">
                        <?php foreach ($cat['articoli'] as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= esc($a['descrizione']) ?></option>
                        <?php endforeach ?>
                    </optgroup>
                <?php endforeach ?>
            </select>
            <!-- Mostrato solo su mobile (vedi _form_materiale_scripts.php), quando si sceglie "Descrizione libera…". -->
            <input type="text" id="input-descrizione-libera" class="form-control form-control-sm d-none mt-1"
                   placeholder="Scrivi una descrizione…" maxlength="255">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Qtà</label>
            <input type="number" name="quantita" class="form-control form-control-sm"
                   min="1" value="1" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Note</label>
            <input type="text" name="note" class="form-control form-control-sm" maxlength="255">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-plus-lg me-1"></i>Aggiungi
            </button>
        </div>
    </div>
</form>
