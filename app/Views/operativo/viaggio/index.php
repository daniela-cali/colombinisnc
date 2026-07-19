<?php
/**
 * @var string    $data
 * @var string    $dataPrecedente
 * @var string    $dataSuccessiva
 * @var array[]   $perZona
 * @var array     $zoneLabel
 * @var int       $totale
 * @var array[]   $materialiPerIntervento
 * @var array[]   $tecnici
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Viaggi<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Viaggi</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/viaggio.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$zonaStyleMap = [
    -1       => ['bg' => 'warning',   'text' => 'dark'],
     0       => ['bg' => 'success',   'text' => 'white'],
     1       => ['bg' => 'primary',   'text' => 'white'],
    'nessuna'=> ['bg' => 'secondary', 'text' => 'white'],
];

$giorni   = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
$mesi     = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$ts       = strtotime($data);
$dataLabel = $giorni[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mesi[(int) date('n', $ts)] . ' ' . date('Y', $ts);
?>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title mb-0">
                    <i class="bi bi-truck me-2"></i>Fogli di viaggio
                </h3>
                <span class="text-muted small print-subtitle">
                    <?= esc($dataLabel) ?> &middot; <?= $totale ?> interventi pianificati
                </span>

                <div class="card-tools ms-auto no-print d-flex flex-wrap align-items-center gap-2">
                    <a href="<?= base_url('operativo/viaggi?data=' . $dataPrecedente) ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <form method="get" action="<?= base_url('operativo/viaggi') ?>" class="d-flex align-items-center">
                        <input type="date" name="data" class="form-control form-control-sm" value="<?= esc($data) ?>"
                               onchange="this.form.submit()">
                    </form>
                    <a href="<?= base_url('operativo/viaggi?data=' . $dataSuccessiva) ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('operativo/viaggi') ?>" class="btn btn-outline-primary btn-sm">
                        Oggi
                    </a>
                    <a href="<?= base_url('operativo/viaggi/pdf?data=' . esc($data)) ?>"
                       target="_blank"
                       id="btn-pdf-viaggio"
                       class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Stampa
                    </button>
                </div>
            </div>

            <div class="card-body">

                <?php if (! empty($tecnici)): ?>
                <div class="d-flex flex-wrap gap-1 mb-3 no-print">
                    <button type="button" class="btn btn-sm btn-secondary btn-filtro-tecnico active" data-id="">
                        <i class="bi bi-people me-1"></i>Tutti
                    </button>
                    <?php foreach ($tecnici as $t): ?>
                    <button type="button" class="btn btn-sm btn-filtro-tecnico"
                            data-id="<?= $t['id'] ?>"
                            style="background:<?= esc($t['colore'] ?: '#6c757d') ?>;border-color:<?= esc($t['colore'] ?: '#6c757d') ?>;color:<?= colore_testo($t['colore'] ?: '#6c757d') ?>;">
                        <?= esc(trim($t['cognome'] . ' ' . $t['nome'])) ?>
                    </button>
                    <?php endforeach ?>
                </div>
                <?php endif ?>

                <?php if (empty($perZona)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-calendar-x d-block mb-2" style="font-size:2.5rem;opacity:.4;"></i>
                    Nessun intervento pianificato per questo giorno.
                </div>
                <?php else: ?>

                <?php foreach ($perZona as $zonaKey => $interventi):
                    $zonaNome  = $zoneLabel[$zonaKey] ?? 'Senza zona';
                    $zonaStyle = $zonaStyleMap[$zonaKey] ?? ['bg' => 'secondary', 'text' => 'white'];
                ?>
                <div class="zona-block mb-4">

                    <!-- Header zona -->
                    <div class="zona-header bg-<?= $zonaStyle['bg'] ?> text-<?= $zonaStyle['text'] ?> px-3 py-2 mb-0 d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            <i class="bi bi-geo-alt me-1"></i>Zona <?= esc($zonaNome) ?>
                        </span>
                        <span class="badge bg-black bg-opacity-25 zona-count-badge"><?= count($interventi) ?> interventi</span>
                    </div>

                    <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 viaggio-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px">Ora</th>
                                <th>Cliente</th>
                                <th>Indirizzo</th>
                                <th>Tipo / Descrizione</th>
                                <th style="width:120px">Tecnico</th>
                                <th style="width:130px" class="no-print">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($interventi as $i): ?>
                        <tr data-tecnico-id="<?= (int) ($i['tecnico_id'] ?? 0) ?>"
                            class="<?= $i['urgenza'] ? 'table-danger' : '' ?>">
                            <td class="fw-bold text-nowrap">
                                <?= date('H:i', strtotime($i['data_pianificata'])) ?>
                                <?php if ($i['urgenza']): ?>
                                    <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Urgente"></i>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= esc($i['cliente_denominazione'] ?? '—') ?></div>
                                <?php if ($i['citta']): ?>
                                <div class="text-muted small"><?= esc($i['citta']) ?></div>
                                <?php endif ?>
                            </td>
                            <td class="text-muted small"><?= esc($i['indirizzo'] ?? '—') ?></td>
                            <td>
                                <?php if ($i['tipo_nome']): ?>
                                <div class="small fw-semibold">
                                    <?php if ($i['tipo_icona']): ?>
                                        <i class="fas <?= esc($i['tipo_icona']) ?> me-1 text-muted"></i>
                                    <?php endif ?>
                                    <?= esc($i['tipo_nome']) ?>
                                </div>
                                <?php endif ?>
                                <?php if ($i['descrizione']): ?>
                                <div class="small text-muted"><?= esc($i['descrizione']) ?></div>
                                <?php endif ?>
                                <?php if (!empty($materialiPerIntervento[$i['id']])): ?>
                                <div class="mt-1 pt-1 border-top border-warning">
                                    <span class="small text-warning-emphasis fw-semibold">
                                        <i class="bi bi-box-seam me-1"></i>Da portare:
                                    </span>
                                    <ul class="mb-0 ps-3 small text-muted">
                                        <?php foreach ($materialiPerIntervento[$i['id']] as $m): ?>
                                        <li>
                                            <?= esc($m['quantita']) ?>× <?= esc($m['descrizione']) ?>
                                            <?php if ($m['note']): ?>
                                                <em>(<?= esc($m['note']) ?>)</em>
                                            <?php endif ?>
                                        </li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                                <?php endif ?>
                            </td>
                            <td class="small"><?= esc($i['tecnico_nome'] ?: '—') ?></td>
                            <td class="no-print">
                                <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>"
                                   class="btn btn-xs btn-outline-secondary" title="Scheda">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= base_url('operativo/interventi/' . $i['id'] . '/edit?from=' . urlencode(base_url('operativo/viaggi?data=' . $data))) ?>"
                                   class="btn btn-xs btn-outline-primary" title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                    </div>

                </div>
                <?php endforeach ?>
                <?php endif ?>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
var btnPdf     = document.getElementById('btn-pdf-viaggio');
var pdfBaseUrl = btnPdf ? btnPdf.getAttribute('href') : '';

document.querySelectorAll('.btn-filtro-tecnico').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var filtro = this.dataset.id;

        if (btnPdf) {
            btnPdf.href = filtro ? (pdfBaseUrl + '&tecnico_id=' + encodeURIComponent(filtro)) : pdfBaseUrl;
        }

        document.querySelectorAll('.btn-filtro-tecnico').forEach(function (b) {
            b.classList.toggle('active', b === btn);
            if (b.dataset.id === '') {
                b.classList.toggle('btn-secondary', b === btn);
                b.classList.toggle('btn-outline-secondary', b !== btn);
            } else {
                b.style.opacity = (b === btn || filtro === '') ? '1' : '.4';
            }
        });

        document.querySelectorAll('.zona-block').forEach(function (blocco) {
            var visibili = 0;
            blocco.querySelectorAll('tbody tr').forEach(function (tr) {
                var match = filtro === '' || tr.dataset.tecnicoId === filtro;
                tr.classList.toggle('d-none', !match);
                if (match) visibili++;
            });
            blocco.classList.toggle('d-none', visibili === 0);
            var badge = blocco.querySelector('.zona-count-badge');
            if (badge) badge.textContent = visibili + ' interventi';
        });
    });
});
</script>
<?= $this->endSection() ?>
