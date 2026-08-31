<?php
/**
 * @var array{totale:int, importati:int, da_migrare:int} $contatori
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Import Clienti<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item active">Import Clienti</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <?php if ($contatori['totale'] > 0): ?>
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Avanzamento migrazione</h3>
                    <div class="card-tools">
                        <a href="<?= base_url('impostazioni/import-clienti/elenco') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-list-ul me-1"></i> Vedi elenco
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $percentuale = (int) round($contatori['importati'] / max($contatori['totale'], 1) * 100);
                    ?>
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="fs-4 fw-semibold"><?= $contatori['totale'] ?></div>
                            <div class="text-muted small text-uppercase">In parcheggio</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-4 fw-semibold text-success"><?= $contatori['importati'] ?></div>
                            <div class="text-muted small text-uppercase">Già promossi</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-4 fw-semibold text-primary"><?= $contatori['da_migrare'] ?></div>
                            <div class="text-muted small text-uppercase">Da migrare</div>
                        </div>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Avanzamento migrazione"
                         aria-valuenow="<?= $percentuale ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?= $percentuale ?>%">
                            <?= $percentuale ?>%
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-filetype-csv me-2"></i>Carica export clienti</h3>
            </div>
            <form method="post" action="<?= base_url('impostazioni/import-clienti/analizza') ?>"
                  enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="card-body">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        Il file può avere qualsiasi struttura: nel passo successivo si associa ogni
                        colonna al campo corrispondente. Separatori riconosciuti <code>;</code> e
                        <code>,</code>, codifica UTF-8 o ISO-8859-1.
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">
                            File CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control"
                               accept=".csv,.txt" required>
                        <div class="form-text">Dimensione massima 10&nbsp;MB.</div>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-arrow-repeat me-1"></i>
                        I dati non finiscono direttamente in anagrafica: restano in un
                        <strong>parcheggio</strong> da cui si creano i clienti uno alla volta.
                        Ricaricare lo stesso export <strong>aggiorna</strong> le righe già presenti
                        (stesso codice) senza duplicarle, e senza toccare i clienti già creati.
                    </div>

                </div>
                <div class="card-footer card-azioni">
                    <a href="<?= base_url('impostazioni') ?>"
                       class="btn btn-sm btn-outline-secondary azione-ritorno">
                        <i class="bi bi-x-lg me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary azione-primaria">
                        <i class="bi bi-arrow-right me-1"></i>Carica e continua
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
