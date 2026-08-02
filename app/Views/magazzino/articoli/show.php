<?php

/**
 * @var array  $articolo 
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?><?= esc($articolo['codice']) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('magazzino/articoli') ?>">Articoli</a></li>
    <li class="breadcrumb-item active"><?= esc($articolo['codice']) ?></li>
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
                <h3 class="card-title"><?= esc($articolo['descrizione']) ?> </h3>
                <div class="card-tools">
                    <a href="<?= base_url('magazzino/articoli') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Articoli
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <p class="text-muted small mb-1">Codice</p>
                        <span class="fw-semibold"><?= esc($articolo['codice']) ?></span>
                    </div>
                    <div class="col-md-9">
                        <p class="text-muted small mb-1">Descrizione</p>
                        <span class="fw-semibold"><?= esc($articolo['descrizione']) ?></span>
                    </div>
                    <div class="col-md-5">
                        <p class="text-muted small mb-1">Categoria</p>
                        <span><?= $articolo['categoria_label'] ? esc($articolo['categoria_label']) : '<span class="text-muted">—</span>' ?></span>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted small mb-1">Unità di misura</p>
                        <span class="fw-semibold"><?= esc($articolo['unita_misura']) ?></span>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <?php if ($articolo['attivo']): ?>
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger me-1"></i>
                        <?php endif ?>
                        <p class="text-muted small mb-1">Attivo</p>
                    </div>
                    <div>
                        <p class="text-muted section-header mb-3"><i class="bi bi-tag me-1"></i> Prezzi</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <p class="text-muted small mb-1">Costo (acquisto)</p>
                                <span class="fw-semibold prezzo"><?= esc($articolo['costo'] ?? "--") ?></span>
                            </div>
                            <div class="col-md-4">
                                <p class="text-muted small mb-1">Vendita (listino)</p>
                                <span class="fw-semibold prezzo"><?= esc($articolo['vendita'] ?? "--") ?></span>
                            </div>
                            <div class="col-md-4">
                                <p class="text-muted small mb-1">Giacenza</p>
                                <span class="fw-semibold"><?= esc($articolo['giacenza'] ?? "--") ?></span>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>