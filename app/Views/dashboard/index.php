<?php
/**
 * @var bool       $isAdmin
 * @var bool       $isUfficio
 * @var bool       $isTecnico
 * @var int        $countOggi
 * @var array      $urgenti
 * @var array      $abbonamenti
 * @var array      $mieiOggi
 * @var array      $mieiUrgenti
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if ($isAdmin || $isUfficio): ?>
<!-- Sezione operativa: visibile ad admin e ufficio -->
<div class="row g-3 mb-3">

    <!-- Card: interventi pianificati oggi -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                <div class="display-3 fw-bold <?= $countOggi > 0 ? 'text-primary' : 'text-muted' ?>">
                    <?= $countOggi ?>
                </div>
                <div class="text-muted mt-1">
                    interventi pianificati oggi
                </div>
            </div>
            <div class="card-footer text-center small">
                <a href="<?= base_url('operativo/calendario') ?>">
                    <i class="bi bi-calendar3 me-1"></i>Calendario
                </a>
                <span class="text-muted mx-2">·</span>
                <a href="<?= base_url('operativo/viaggi?data=' . date('Y-m-d')) ?>">
                    <i class="bi bi-map me-1"></i>Foglio di viaggio
                </a>
            </div>
        </div>
    </div>

    <!-- Card: urgenti non pianificati -->
    <div class="col-md-8">
        <?php $numUrgenti = count($urgenti); ?>
        <div class="card card-outline <?= $numUrgenti > 0 ? 'card-danger' : 'card-success' ?> h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-exclamation-triangle<?= $numUrgenti > 0 ? '-fill text-danger' : ' text-success' ?> me-1"></i>
                    Urgenti non pianificati
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numUrgenti > 0 ? 'bg-danger' : 'bg-success' ?> fs-6">
                        <?= $numUrgenti ?>
                    </span>
                </div>
            </div>
            <?php if ($urgenti): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($urgenti as $u): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <a href="<?= base_url('operativo/interventi/' . $u['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= esc($u['cliente_denominazione']) ?>
                            </a>
                            <small class="text-muted ms-1"><?= esc($u['citta']) ?></small>
                        </div>
                        <span class="text-muted small"><?= esc($u['tipo'] ?? '—') ?></span>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessun urgente in attesa
            </div>
            <?php endif ?>
            <div class="card-footer text-end">
                <a href="<?= base_url('operativo/interventi') ?>" class="small">
                    Vedi tutti gli interventi <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

</div>

<?php if ($isUfficio): ?>
<!-- Card: abbonamenti in scadenza (solo gruppo ufficio) -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-calendar-x me-1"></i>
                    Abbonamenti in scadenza — prossimi 30 giorni
                </h3>
                <div class="card-tools">
                    <span class="badge <?= count($abbonamenti) > 0 ? 'bg-warning text-dark' : 'bg-success' ?>">
                        <?= count($abbonamenti) ?>
                    </span>
                </div>
            </div>
            <?php if ($abbonamenti): ?>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Scadenza</th>
                            <th class="text-end">Giorni rimasti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abbonamenti as $a):
                            $giorni = (int) $a['giorni_rimasti'];
                            $badgeClass = $giorni <= 7 ? 'bg-danger' : ($giorni <= 15 ? 'bg-warning text-dark' : 'bg-secondary');
                        ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('abbonamenti/' . $a['id']) ?>">
                                    <?= esc($a['cliente_denominazione']) ?>
                                </a>
                            </td>
                            <td><?= esc($a['tipo'] ?? '—') ?></td>
                            <td><?= date('d/m/Y', strtotime($a['data_fine'])) ?></td>
                            <td class="text-end">
                                <span class="badge <?= $badgeClass ?>"><?= $giorni ?> gg</span>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessun abbonamento in scadenza nei prossimi 30 giorni
            </div>
            <?php endif ?>
            <div class="card-footer text-end">
                <a href="<?= base_url('abbonamenti') ?>" class="small">
                    Tutti gli abbonamenti <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<?php endif ?>

<?php if ($isTecnico): ?>
<?php if ($isAdmin || $isUfficio): ?>
<hr class="my-2">
<p class="text-muted text-uppercase fw-semibold small mb-3">I miei interventi</p>
<?php endif ?>

<!-- Sezione tecnico: interventi propri -->
<div class="row g-3">

    <!-- Card: i miei interventi oggi -->
    <div class="col-md-8">
        <div class="card card-outline card-info h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-person-check me-1"></i>
                    I miei interventi oggi
                </h3>
                <div class="card-tools">
                    <span class="badge bg-info"><?= count($mieiOggi) ?></span>
                </div>
            </div>
            <?php if ($mieiOggi): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($mieiOggi as $i): ?>
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted me-2 small"><?= date('H:i', strtotime($i['data_pianificata'])) ?></span>
                                <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>" class="fw-semibold text-decoration-none">
                                    <?= esc($i['cliente_denominazione']) ?>
                                </a>
                            </div>
                            <span class="text-muted small"><?= esc($i['tipo'] ?? '—') ?></span>
                        </div>
                        <div class="text-muted small ms-5">
                            <?= esc($i['indirizzo']) ?>, <?= esc($i['citta']) ?>
                        </div>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-calendar-check me-1"></i>
                Nessun intervento pianificato per oggi
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Card: i miei urgenti -->
    <div class="col-md-4">
        <?php $numMieiUrgenti = count($mieiUrgenti); ?>
        <div class="card card-outline <?= $numMieiUrgenti > 0 ? 'card-danger' : 'card-success' ?> h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-exclamation-triangle<?= $numMieiUrgenti > 0 ? '-fill text-danger' : ' text-success' ?> me-1"></i>
                    I miei urgenti
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numMieiUrgenti > 0 ? 'bg-danger' : 'bg-success' ?>">
                        <?= $numMieiUrgenti ?>
                    </span>
                </div>
            </div>
            <?php if ($mieiUrgenti): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($mieiUrgenti as $u): ?>
                    <li class="list-group-item py-2">
                        <a href="<?= base_url('operativo/interventi/' . $u['id']) ?>" class="fw-semibold text-decoration-none d-block">
                            <?= esc($u['cliente_denominazione']) ?>
                        </a>
                        <small class="text-muted"><?= esc($u['tipo'] ?? '—') ?> · <?= esc($u['citta']) ?></small>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessun urgente
            </div>
            <?php endif ?>
        </div>
    </div>

</div>
<?php endif ?>

<?php if (! $isAdmin && ! $isUfficio && ! $isTecnico): ?>
<div class="alert alert-secondary">
    Benvenuto nel gestionale Colombini SNC.
</div>
<?php endif ?>

<?= $this->endSection() ?>
