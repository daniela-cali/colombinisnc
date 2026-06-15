<?php
/**
 * @var array $categorie   Righe da CategorieArticoliModel::tutteOrdinate()
 * @var array  $unitaMisura [codice => etichetta]
 * @var string $from        URL di ritorno dopo il salvataggio
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Nuovo articolo<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('magazzino/articoli') ?>">Articoli</a></li>
    <li class="breadcrumb-item active">Nuovo</li>
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
                <h3 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Nuovo articolo</h3>
                <div class="card-tools">
                    <a href="<?= esc($from ?: base_url('magazzino/articoli')) ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>Annulla
                    </a>
                </div>
            </div>
            <form action="<?= base_url('magazzino/articoli/store') ?>" method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <div class="card-body">

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Codice <span class="text-danger">*</span></label>
                            <input type="text" name="codice" class="form-control"
                                   value="<?= esc(old('codice')) ?>" maxlength="50" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                            <input type="text" name="descrizione" class="form-control"
                                   value="<?= esc(old('descrizione')) ?>" maxlength="255" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Categoria</label>
                            <select name="categoria_id" class="form-select">
                                <option value="">— nessuna —</option>
                                <?php foreach ($categorie as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                            <?= old('categoria_id') == $c['id'] ? 'selected' : '' ?>>
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
                                            <?= old('unita_misura', 'pz') === $cod ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>

                    <p class="text-muted section-header mb-3"><i class="bi bi-tag me-1"></i> Prezzi</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Costo (acquisto)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="costo" class="form-control"
                                       value="<?= esc(old('costo')) ?>"
                                       min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vendita (listino)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="vendita" class="form-control"
                                       value="<?= esc(old('vendita')) ?>"
                                       min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Giacenza iniziale</label>
                            <input type="number" name="giacenza" class="form-control"
                                   value="<?= esc(old('giacenza', 0)) ?>"
                                   min="0" step="0.01">
                        </div>
                    </div>

                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
