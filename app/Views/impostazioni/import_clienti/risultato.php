<?php
/**
 * @var array{totale:int, inseriti:int, aggiornati:int, saltati:int} $esito
 * @var array{totale:int, importati:int, da_migrare:int}             $contatori
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Import Clienti — Risultato<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni/import-clienti') ?>">Import Clienti</a></li>
    <li class="breadcrumb-item active">Risultato</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-check-circle me-2"></i>Import completato</h3>
            </div>
            <div class="card-body">

                <div class="row text-center">
                    <div class="col-3 border-end">
                        <div class="fs-4 fw-semibold"><?= $esito['totale'] ?></div>
                        <div class="text-muted small text-uppercase">Elaborate</div>
                    </div>
                    <div class="col-3 border-end">
                        <div class="fs-4 fw-semibold text-success"><?= $esito['inseriti'] ?></div>
                        <div class="text-muted small text-uppercase">Nuove</div>
                    </div>
                    <div class="col-3 border-end">
                        <div class="fs-4 fw-semibold text-primary"><?= $esito['aggiornati'] ?></div>
                        <div class="text-muted small text-uppercase">Aggiornate</div>
                    </div>
                    <div class="col-3">
                        <div class="fs-4 fw-semibold <?= $esito['saltati'] > 0 ? 'text-warning' : 'text-muted' ?>">
                            <?= $esito['saltati'] ?>
                        </div>
                        <div class="text-muted small text-uppercase">Saltate</div>
                    </div>
                </div>

                <?php if ($esito['saltati'] > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Le righe saltate non avevano il campo <strong>Codice</strong> valorizzato:
                        senza codice il record non è identificabile e non potrebbe essere aggiornato
                        a un caricamento successivo.
                    </div>
                <?php endif ?>

                <?php if ($esito['aggiornati'] > 0): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Le righe aggiornate erano già in parcheggio con lo stesso codice: i dati
                        anagrafici sono stati riscritti. I clienti già creati non sono stati toccati.
                    </div>
                <?php endif ?>

            </div>
            <div class="card-footer card-azioni">
                <a href="<?= base_url('impostazioni/import-clienti') ?>"
                   class="btn btn-sm btn-outline-secondary azione-ritorno">
                    <i class="bi bi-arrow-repeat me-1"></i>Nuovo import
                </a>
                <a href="<?= base_url('impostazioni/import-clienti/elenco') ?>"
                   class="btn btn-sm btn-primary azione-primaria">
                    <i class="bi bi-list-ul me-1"></i>Vai all'elenco (<?= $contatori['da_migrare'] ?> da migrare)
                </a>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
