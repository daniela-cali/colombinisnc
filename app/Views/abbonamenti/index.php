<?php
/**
 * @var string $title
 * @var array  $abbonamenti  Da AbbonamentiModel::elencoConDettagli() — include stato_calcolato, ha_successore, num_periodi, prima_frequenza
 * @var array  $statiLabel   AbbonamentiModel::STATI_LABEL
 * @var array  $statiBadge   AbbonamentiModel::STATI_BADGE
 * @var array  $frequenze    AbbonamentiModel::FREQUENZE_LABEL
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Abbonamenti<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Abbonamenti</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Abbonamenti</h3>
        <div class="card-tools">
            <a href="<?= base_url('abbonamenti/nuovo') ?>" class="btn btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuovo
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($abbonamenti)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun abbonamento presente.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo intervento</th>
                            <th>Frequenza</th>
                            <th>Periodo</th>
                            <th class="text-end">Prezzo</th>
                            <th class="text-center">Stato</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abbonamenti as $a): ?>
                            <tr>
                                <td><?= esc($a['cliente_denominazione']) ?></td>
                                <td><?= esc($a['tipo_nome'] ?? '—') ?></td>
                                <td>
                                    <?php if ($a['num_periodi'] > 1): ?>
                                        <span class="text-muted">Multipla</span>
                                        <small class="text-muted">(<?= (int) $a['num_periodi'] ?> periodi)</small>
                                    <?php else: ?>
                                        <?= esc($frequenze[$a['prima_frequenza']] ?? '—') ?>
                                    <?php endif ?>
                                </td>
                                <td class="text-nowrap">
                                    <?= date('d/m/Y', strtotime($a['data_inizio'])) ?>
                                    –
                                    <?= date('d/m/Y', strtotime($a['data_fine'])) ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <?= $a['prezzo'] !== null ? '€ ' . number_format((float) $a['prezzo'], 2, ',', '.') : '—' ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $statiBadge[$a['stato_calcolato']] ?? 'bg-secondary' ?>">
                                        <?= esc($statiLabel[$a['stato_calcolato']] ?? $a['stato_calcolato']) ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= base_url('abbonamenti/' . $a['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Scheda">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (! $a['ha_successore'] && in_array($a['stato_calcolato'], ['scaduto', 'disdetto'], true)): ?>
                                        <a href="<?= base_url('abbonamenti/' . $a['id'] . '/rinnova') ?>"
                                           class="btn btn-sm btn-outline-primary" title="Rinnova">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </a>
                                    <?php endif ?>
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
