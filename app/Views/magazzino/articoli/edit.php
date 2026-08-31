<?php
/**
 * @var array $articolo   Record articoli
 * @var array $categorie  Righe da CategorieArticoliModel::tutteOrdinate()
 * @var array  $unitaMisura [codice => etichetta]
 * @var string $from        URL di ritorno dopo il salvataggio
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Modifica — <?= esc($articolo['descrizione']) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('magazzino/articoli') ?>">Articoli</a></li>
    <li class="breadcrumb-item active"><?= esc($articolo['descrizione']) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-7">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2"></i><?= esc($articolo['descrizione']) ?>
                </h3>
            </div>

            <form id="form-update"
                  action="<?= base_url('magazzino/articoli/' . $articolo['id'] . '/update') ?>"
                  method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <div class="card-body">

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Codice <span class="text-danger">*</span></label>
                            <input type="text" name="codice" class="form-control"
                                   value="<?= esc(old('codice', $articolo['codice'] ?? '')) ?>"
                                   maxlength="50" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                            <input type="text" name="descrizione" class="form-control"
                                   value="<?= esc(old('descrizione', $articolo['descrizione'])) ?>"
                                   maxlength="255" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Categoria</label>
                            <select name="categoria_id" class="form-select">
                                <option value="">— nessuna —</option>
                                <?php foreach ($categorie as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                            <?= old('categoria_id', $articolo['categoria_id']) == $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unità di misura <span class="text-danger">*</span></label>
                            <select name="unita_misura" class="form-select">
                                <?php foreach ($unitaMisura as $cod => $label): ?>
                                    <option value="<?= $cod ?>"
                                            <?= old('unita_misura', $articolo['unita_misura']) === $cod ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="attivo"
                                       id="attivo" value="1"
                                       <?= old('attivo', $articolo['attivo']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="attivo">Attivo</label>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted section-header mb-3"><i class="bi bi-tag me-1"></i> Prezzi</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Costo (acquisto)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="costo" class="form-control"
                                       value="<?= esc(old('costo', $articolo['costo'] ?? '')) ?>"
                                       min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vendita (listino)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="vendita" class="form-control"
                                       value="<?= esc(old('vendita', $articolo['vendita'] ?? '')) ?>"
                                       min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Giacenza</label>
                            <input type="number" name="giacenza" class="form-control"
                                   value="<?= esc(old('giacenza', $articolo['giacenza'] ?? '')) ?>"
                                   min="0" step="0.01">
                        </div>
                    </div>

                </div>
            </form>

            <div class="card-footer card-azioni">
                <a href="<?= esc($from ?: base_url('magazzino/articoli')) ?>"
                   class="btn btn-sm btn-outline-secondary azione-ritorno">
                    <i class="bi bi-x-lg me-1"></i>Annulla
                </a>
                <form action="<?= base_url('magazzino/articoli/' . $articolo['id'] . '/delete') ?>"
                      method="post" class="azione-distruttiva"
                      data-nome="<?= esc($articolo['descrizione']) ?>"
                      onsubmit="return confirm('Eliminare definitivamente &quot;' + this.dataset.nome + '&quot;?')">
                    <?= csrf_field() ?>
                    <?php if ($from): ?>
                        <input type="hidden" name="from" value="<?= esc($from) ?>">
                    <?php endif ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i>Elimina
                    </button>
                </form>
                <button type="submit" form="form-update" class="btn btn-sm btn-primary azione-primaria">
                    <i class="bi bi-check-lg me-1"></i>Salva
                </button>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
