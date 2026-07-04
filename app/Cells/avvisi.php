<?php
/**
 * @var array $oggi
 * @var array $prossimi
 * @var int   $badge
 */
$vuoto = $oggi === [] && $prossimi === [];

// Rende una voce del dropdown: link al calendario aperto sul giorno del promemoria.
$voce = static function (array $p): string {
    $giorno = date('Y-m-d', strtotime($p['data_ora_inizio']));
    $quando = date('d/m H:i', strtotime($p['data_ora_inizio']));

    $letto = ! empty($p['letto']) ? ' <i class="bi bi-check-circle-fill text-success" title="Già letto"></i>' : '';

    return '<li><a class="dropdown-item" href="' . base_url('operativo/calendario') . '?data=' . $giorno . '">'
        . '<span class="d-block small text-muted"><i class="bi bi-calendar-event me-1"></i>' . $quando . '</span>'
        . '<span class="d-block">' . esc($p['titolo']) . $letto . '</span>'
        . '</a></li>';
};
?>
<li class="nav-item dropdown">
    <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" title="Avvisi in arrivo">
        <i class="bi bi-bell"></i>
        <?php if ($badge > 0): ?>
        <span class="position-absolute badge-avvisi badge rounded-pill bg-danger">
            <?= $badge ?><span class="visually-hidden"> avvisi</span>
        </span>
        <?php endif ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-end dropdown-avvisi shadow">
        <li><h6 class="dropdown-header"><i class="bi bi-bell me-1"></i>Avvisi in arrivo</h6></li>

        <?php if ($vuoto): ?>
        <li><span class="dropdown-item-text text-muted small">Nessun avviso in arrivo</span></li>
        <?php endif ?>

        <?php if ($oggi !== []): ?>
        <li><h6 class="dropdown-header text-uppercase small text-secondary">Oggi</h6></li>
        <?php foreach ($oggi as $p) echo $voce($p) ?>
        <?php endif ?>

        <?php if ($prossimi !== []): ?>
            <?php if ($oggi !== []): ?>
            <li><hr class="dropdown-divider"></li>
            <?php endif ?>
        <li><h6 class="dropdown-header text-uppercase small text-secondary">Prossimi giorni</h6></li>
        <?php foreach ($prossimi as $p) echo $voce($p) ?>
        <?php endif ?>
    </ul>
</li>
