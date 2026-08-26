<?php
/**
 * @var string $title
 * @var array  $numeratori Da NumeratoriModel::elenco() — class, prefisso, descrizione,
 *                         ultimo, ultimo_codice, prossimo, updated_at
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item active">Numeratori</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="bi bi-123 me-2"></i>Numeratori
        </h3>
    </div>

    <div class="card-body">
        <p class="text-muted">
            Ogni serie di codici ha il suo contatore. Il numero cresce sempre e non torna mai
            indietro: un codice assegnato resta speso anche se quel cliente o quell'intervento
            viene poi eliminato, così lo stesso codice non finisce mai su due documenti diversi.
        </p>

        <?php if (empty($numeratori)): ?>
            <p class="text-muted text-center py-4 mb-0">
                Nessun numeratore ancora in uso: le serie nascono con il primo codice generato.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Serie</th>
                            <th>Prefisso</th>
                            <th class="text-end">Assegnati</th>
                            <th>Ultimo codice</th>
                            <th>Prossimo codice</th>
                            <th>Ultimo utilizzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($numeratori as $n): ?>
                            <tr>
                                <td>
                                    <?= esc($n['class']) ?>
                                    <?php if ($n['descrizione']): ?>
                                        <div class="small text-muted"><?= esc($n['descrizione']) ?></div>
                                    <?php endif ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= esc($n['prefisso']) ?></span></td>
                                <td class="text-end"><?= esc($n['ultimo']) ?></td>
                                <td class="text-nowrap">
                                    <?= $n['ultimo_codice'] ? esc($n['ultimo_codice']) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td class="text-nowrap fw-semibold"><?= esc($n['prossimo']) ?></td>
                                <td class="text-nowrap text-muted">
                                    <?= $n['updated_at'] ? date('d/m/Y H:i', strtotime($n['updated_at'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>

    <div class="card-footer bg-warning text-dark small">
        <i class="bi bi-info-circle me-1"></i>
        I numeratori non si modificano da qui. Se una serie va riallineata — per esempio dopo
        un caricamento massivo di dati — l'intervento si fa direttamente sul database.
    </div>
</div>
<?= $this->endSection() ?>
