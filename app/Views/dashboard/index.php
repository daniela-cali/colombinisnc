<?php
/**
 * @var bool       $isAdmin
 * @var bool       $isUfficio
 * @var bool       $isTecnico
 * @var int        $countOggi
 * @var array      $interventiOggi
 * @var array      $urgenti
 * @var array      $abbonamenti
 * @var array      $assentiOggi
 * @var array      $conflitti
 * @var array      $tipiAssenzaLabel
 * @var array      $mieiOggi
 * @var array      $mieiUrgenti
 * @var array{oggi: array, prossimi: array} $promemoria
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if ($isAdmin || $isUfficio): ?>
<?php
$numUrgenti = count($urgenti);
$numAbb     = count($abbonamenti);
$numAssenti = count($assentiOggi);
$numConflitti = count($conflitti);

$promOggi = $promemoria['oggi'];
$promDopo = $promemoria['prossimi'];
$numProm  = count($promOggi) + count($promDopo);

// Rende una voce promemoria: titolo (link al calendario sul giorno) + ora a destra + note.
$voceProm = static function (array $p, string $classe): string {
    $giorno = date('Y-m-d', strtotime($p['data_ora_inizio']));
    $letto  = ! empty($p['letto']) ? ' <i class="bi bi-check-circle-fill text-success" title="Già letto"></i> ' : '';
    $html   = '<li class="list-group-item py-2 ' . $classe . '">'
        . '<div class="d-flex justify-content-between align-items-start">'
        . '<a href="' . base_url('operativo/calendario?data=' . $giorno) . '" class="fw-semibold text-decoration-none">' . esc($p['titolo']) . '</a>'
        . '<small class="text-muted text-nowrap ms-2">' . $letto . date('d/m H:i', strtotime($p['data_ora_inizio'])) . '</small>'
        . '</div>';
    if (! empty($p['note'])) {
        $html .= '<small class="text-muted d-block">' . esc($p['note']) . '</small>';
    }

    return $html . '</li>';
};

$capOggi      = array_slice($promOggi, 0, 5);
$capDopo      = array_slice($promDopo, 0, 5);
$mostratiProm = count($capOggi) + count($capDopo);
?>

<!-- Riga contatori: info-box compatti, un link alla pagina relativa -->
<div class="row info-box-row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-3">

    <div class="col">
        <a href="<?= base_url('operativo/calendario') ?>" class="info-box text-reset text-decoration-none h-100">
            <span class="info-box-icon text-bg-primary"><i class="bi bi-tools"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Interventi oggi</span>
                <span class="info-box-number"><?= $countOggi ?></span>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="<?= base_url('operativo/interventi') ?>" class="info-box text-reset text-decoration-none h-100">
            <span class="info-box-icon <?= $numUrgenti > 0 ? 'text-bg-danger' : 'text-bg-success' ?>"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Urgenti non pianificati</span>
                <span class="info-box-number"><?= $numUrgenti ?></span>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="<?= base_url('anagrafiche/personale') ?>" class="info-box text-reset text-decoration-none h-100">
            <span class="info-box-icon <?= $numAssenti > 0 ? 'bg-assenza' : 'text-bg-success' ?>"><i class="bi bi-person-x"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Assenti oggi</span>
                <span class="info-box-number"><?= $numAssenti ?></span>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="<?= base_url('operativo/calendario') ?>" class="info-box text-reset text-decoration-none h-100">
            <span class="info-box-icon bg-promemoria"><i class="bi bi-bell"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Promemoria in arrivo</span>
                <span class="info-box-number"><?= $numProm ?></span>
            </div>
        </a>
    </div>

    <?php if ($isUfficio): ?>
    <div class="col">
        <a href="<?= base_url('abbonamenti') ?>" class="info-box text-reset text-decoration-none h-100">
            <span class="info-box-icon <?= $numAbb > 0 ? 'text-bg-warning' : 'text-bg-success' ?>"><i class="bi bi-calendar-x"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Abbonamenti in scadenza</span>
                <span class="info-box-number"><?= $numAbb ?></span>
            </div>
        </a>
    </div>
    <?php endif ?>

</div>

<!-- Riga: interventi di oggi (elenco completo) -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-calendar-day me-1"></i>
                    Interventi di oggi
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $countOggi > 0 ? 'bg-primary' : 'bg-secondary' ?>"><?= $countOggi ?></span>
                </div>
            </div>
            <?php if ($interventiOggi): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($interventiOggi as $i): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <span class="text-muted me-2 small"><?= date('H:i', strtotime($i['data_pianificata'])) ?></span>
                            <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= esc($i['cliente_denominazione']) ?>
                            </a>
                            <small class="text-muted ms-1"><?= esc($i['citta']) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small"><?= esc($i['tipo'] ?? '—') ?></span>
                            <?php if (! empty($i['tecnico'])): ?>
                            <small class="text-muted d-block"><i class="bi bi-person me-1"></i><?= esc($i['tecnico']) ?></small>
                            <?php endif ?>
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
            <div class="card-footer text-end">
                <a href="<?= base_url('operativo/viaggi?data=' . date('Y-m-d')) ?>" class="small">
                    <i class="bi bi-map me-1"></i>Foglio di viaggio
                </a>
                <span class="text-muted mx-2">·</span>
                <a href="<?= base_url('operativo/calendario') ?>" class="small">
                    <?php if ($countOggi > count($interventiOggi)): ?>e altri <?= $countOggi - count($interventiOggi) ?> · <?php endif ?>Calendario <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Riga liste: card outline uniformi -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-3">

    <!-- Card: urgenti non pianificati -->
    <div class="col">
        <div class="card card-outline <?= $numUrgenti > 0 ? 'card-danger' : 'card-success' ?> h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-exclamation-triangle<?= $numUrgenti > 0 ? '-fill text-danger' : ' text-success' ?> me-1"></i>
                    Urgenti non pianificati
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numUrgenti > 0 ? 'bg-danger' : 'bg-success' ?>"><?= $numUrgenti ?></span>
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

    <!-- Card: assenze di oggi -->
    <div class="col">
        <div class="card card-outline <?= $numAssenti > 0 ? 'card-assenza' : 'card-success' ?> h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-person-x me-1"></i>
                    Assenze di oggi
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numAssenti > 0 ? 'bg-assenza' : 'bg-success' ?>"><?= $numAssenti ?></span>
                </div>
            </div>
            <?php if ($assentiOggi): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($assentiOggi as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <a href="<?= base_url('anagrafiche/personale/' . $a['personale_id']) ?>" class="fw-semibold text-decoration-none">
                            <?= esc(trim($a['personale_cognome'] . ' ' . $a['personale_nome'])) ?>
                        </a>
                        <span class="text-muted small"><?= esc($tipiAssenzaLabel[$a['tipo']] ?? ucfirst($a['tipo'])) ?></span>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessuna assenza oggi
            </div>
            <?php endif ?>
            <div class="card-footer text-end">
                <a href="<?= base_url('anagrafiche/personale') ?>" class="small">
                    Vedi il personale <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card: interventi in conflitto con un'assenza inserita a posteriori -->
    <div class="col">
        <div class="card card-outline <?= $numConflitti > 0 ? 'card-danger' : 'card-success' ?> h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-exclamation-octagon<?= $numConflitti > 0 ? '-fill text-danger' : ' text-success' ?> me-1"></i>
                    Interventi in conflitto
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numConflitti > 0 ? 'bg-danger' : 'bg-success' ?>"><?= $numConflitti ?></span>
                </div>
            </div>
            <?php if ($conflitti): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($conflitti as $c): ?>
                    <li class="list-group-item py-2">
                        <a href="<?= base_url('operativo/interventi/' . $c['id'] . '/edit') ?>" class="fw-semibold text-decoration-none d-block">
                            <?= esc($c['cliente_denominazione']) ?>
                        </a>
                        <small class="text-muted">
                            <?= esc($c['tecnico']) ?> · <?= esc($tipiAssenzaLabel[$c['assenza_tipo']] ?? ucfirst($c['assenza_tipo'])) ?>
                            · <?= date('d/m/Y', strtotime($c['data_pianificata'])) ?>
                        </small>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessun conflitto
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Card: promemoria in arrivo -->
    <div class="col">
        <div class="card card-outline card-promemoria h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bell me-1"></i>
                    Promemoria in arrivo
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numProm > 0 ? 'bg-secondary' : 'bg-success' ?>"><?= $numProm ?></span>
                </div>
            </div>
            <?php if ($numProm): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if ($capOggi): ?>
                    <li class="list-group-item py-1 bg-body-tertiary"><small class="text-uppercase text-secondary fw-semibold">Oggi</small></li>
                    <?php foreach ($capOggi as $p) echo $voceProm($p, 'prom-oggi') ?>
                    <?php endif ?>
                    <?php if ($capDopo): ?>
                    <li class="list-group-item py-1 bg-body-tertiary"><small class="text-uppercase text-secondary fw-semibold">Prossimi giorni</small></li>
                    <?php foreach ($capDopo as $p) echo $voceProm($p, 'prom-prossimi') ?>
                    <?php endif ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessun promemoria in arrivo
            </div>
            <?php endif ?>
            <div class="card-footer text-end">
                <a href="<?= base_url('operativo/calendario') ?>" class="small">
                    <?php if ($numProm > $mostratiProm): ?>e altri <?= $numProm - $mostratiProm ?> · <?php endif ?>Vai al calendario <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <?php if ($isUfficio): ?>
    <!-- Card: abbonamenti in scadenza (solo gruppo ufficio) -->
    <div class="col">
        <div class="card card-warning card-outline h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-calendar-x me-1"></i>
                    Abbonamenti in scadenza — 30 giorni
                </h3>
                <div class="card-tools">
                    <span class="badge <?= $numAbb > 0 ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $numAbb ?></span>
                </div>
            </div>
            <?php if ($abbonamenti): ?>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($abbonamenti as $a):
                        $giorni = (int) $a['giorni_rimasti'];
                        $badgeClass = $giorni <= 7 ? 'bg-danger' : ($giorni <= 15 ? 'bg-warning text-dark' : 'bg-secondary');
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= esc($a['cliente_denominazione']) ?>
                            </a>
                            <small class="text-muted d-block"><?= esc($a['tipo'] ?? '—') ?> · <?= date('d/m/Y', strtotime($a['data_fine'])) ?></small>
                        </div>
                        <span class="badge <?= $badgeClass ?>"><?= $giorni ?> gg</span>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Nessun abbonamento in scadenza
            </div>
            <?php endif ?>
            <div class="card-footer text-end">
                <a href="<?= base_url('abbonamenti') ?>" class="small">
                    Tutti gli abbonamenti <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif ?>

</div>
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
