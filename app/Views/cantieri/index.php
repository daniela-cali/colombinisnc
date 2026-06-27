<?php
/**
 * @var string $title
 * @var array  $cantieri    Da CantieriModel::elencoCompleto() — include cliente_denominazione
 * @var array  $tipiLabel   CantieriModel::TIPI_LABEL
 * @var array  $statiLabel  CantieriModel::STATI_LABEL
 * @var array  $statiBadge  CantieriModel::STATI_BADGE
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Cantieri<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Cantieri</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title mb-0"><i class="bi bi-bricks me-2"></i>Cantieri</h3>
        <div class="card-tools">
            <a href="<?= base_url('cantieri/nuovo') ?>" class="btn btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuovo
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cantieri)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun cantiere presente.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Titolo</th>
                            <th>Tipo</th>
                            <th>Periodo</th>
                            <th class="text-center">Stato</th>
                            <th style="width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cantieri as $c): ?>
                            <tr>
                                <td><?= esc($c['cliente_denominazione']) ?></td>
                                <td><?= esc($c['titolo']) ?></td>
                                <td><?= esc($tipiLabel[$c['tipo']] ?? $c['tipo']) ?></td>
                                <td class="text-nowrap">
                                    <?= $c['data_inizio'] ? date('d/m/Y', strtotime($c['data_inizio'])) : '—' ?>
                                    <?php if ($c['data_fine_prevista']): ?>
                                        – <?= date('d/m/Y', strtotime($c['data_fine_prevista'])) ?>
                                    <?php endif ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $statiBadge[$c['stato']] ?? 'bg-secondary' ?>">
                                        <?= esc($statiLabel[$c['stato']] ?? $c['stato']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Scheda">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
