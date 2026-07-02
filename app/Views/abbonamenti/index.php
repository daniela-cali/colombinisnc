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

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Abbonamenti</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Abbonamenti
            <i class="bi bi-info-circle text-muted ms-2"
               style="font-size:.85rem; font-weight:normal"
               data-bs-toggle="tooltip"
               title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
        </h3>
        <div class="card-tools ms-auto">
            <a href="<?= base_url('abbonamenti/nuovo') ?>" class="btn btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuovo
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($abbonamenti)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun abbonamento presente.</p>
        <?php else: ?>
            <div class="mb-3 filtri-scroll">
                <button class="btn btn-sm btn-outline-success" data-filtro="attivo">
                    <i class="bi bi-check-circle me-1"></i>Attivi
                </button>
                <button class="btn btn-sm btn-outline-warning" data-filtro="sospeso">
                    <i class="bi bi-pause-circle me-1"></i>Sospesi
                </button>
                <button class="btn btn-sm btn-outline-secondary" data-filtro="scaduto">
                    <i class="bi bi-clock-history me-1"></i>Scaduti
                </button>
                <button class="btn btn-sm btn-outline-danger" data-filtro="disdetto">
                    <i class="bi bi-x-circle me-1"></i>Disdetti
                </button>
                <button class="btn btn-sm btn-outline-primary" data-filtro="tutti">
                    Tutti (<?= count($abbonamenti) ?>)
                </button>
            </div>
            <div class="table-responsive">
                <table id="tabella-abbonamenti" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px">Rif.</th>
                            <th>Cliente</th>
                            <th>Tipo intervento</th>
                            <th>Frequenza</th>
                            <th class="text-center">Periodo</th>
                            <th class="text-center">Prezzo</th>
                            <th class="text-center">Stato</th>
                            <th style="width:100px"></th>
                            <th></th><!-- 8 Filter stato_calcolato -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abbonamenti as $a): ?>
                            <tr>
                                <!-- 1 Rif. -->
                                <td data-order="<?= (int) $a['id'] ?>">
                                    <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="text-decoration-none js-row-open">
                                        <code class="small">#<?= (int) $a['id'] ?></code>
                                    </a>
                                </td>
                                <!-- 2 Cliente -->
                                <td>
                                    <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="text-body text-decoration-none">
                                        <?= esc($a['cliente_denominazione']) ?>
                                    </a>
                                </td>
                                <!-- 3 Tipo intervento -->
                                <td><?= esc($a['tipo_nome'] ?? '—') ?></td>
                                <!-- 4 Frequenza -->
                                <td>
                                    <?php if ($a['num_periodi'] > 1): ?>
                                        Multipla
                                        <small class="text-muted">(<?= (int) $a['num_periodi'] ?> periodi)</small>
                                    <?php else: ?>
                                        <?= esc($frequenze[$a['prima_frequenza']] ?? '—') ?>
                                    <?php endif ?>
                                </td>
                                <!-- 5 Periodo -->
                                <td class="text-center text-nowrap" data-order="<?= esc($a['data_inizio']) ?>">
                                    <?= date('d/m/Y', strtotime($a['data_inizio'])) ?>
                                    –
                                    <?= date('d/m/Y', strtotime($a['data_fine'])) ?>
                                </td>
                                <!-- 6 Prezzo -->
                                <td class="text-end text-nowrap" data-order="<?= $a['prezzo'] !== null ? (float) $a['prezzo'] : -1 ?>">
                                    <?= $a['prezzo'] !== null ? '€ ' . number_format((float) $a['prezzo'], 2, ',', '.') : '—' ?>
                                </td>
                                <!-- 7 Stato -->
                                <td class="text-center">
                                    <span class="badge <?= $statiBadge[$a['stato_calcolato']] ?? 'bg-secondary' ?>">
                                        <?= esc($statiLabel[$a['stato_calcolato']] ?? $a['stato_calcolato']) ?>
                                    </span>
                                </td>
                                <!-- 8 Azioni -->
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
                                <!-- 9 Filter stato_calcolato -->
                                <td><?= esc($a['stato_calcolato']) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script>
$(function () {

    var table = new DataTable('#tabella-abbonamenti', {
        language: {
            search:       'Cerca:',
            lengthMenu:   'Mostra _MENU_ righe',
            info:         'Da _START_ a _END_ di _TOTAL_ record',
            infoEmpty:    'Nessun record',
            infoFiltered: '(filtrati da _MAX_ totali)',
            zeroRecords:  'Nessun risultato trovato',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        },
        responsive: true,
        orderMulti: true, // già attivo di default in DataTables (Shift+clic ordina su più colonne)
        pageLength:  25,
        order:       [[0, 'desc']],
        columnDefs: [
            { name: 'rif',       targets: 0, searchable: false, responsivePriority: 2 },
            { name: 'cliente',   targets: 1, responsivePriority: 1 },
            { name: 'tipo',      targets: 2 },
            { name: 'frequenza', targets: 3 },
            { name: 'periodo',   targets: 4, searchable: false },
            { name: 'prezzo',    targets: 5, searchable: false },
            { name: 'stato',     targets: 6, searchable: false, orderable: false, responsivePriority: 3 },
            { name: 'azioni',    targets: 7, searchable: false, orderable: false, responsivePriority: 2 },
            { name: 'filter_stato', targets: 8, searchable: true, orderable: false, visible: false }
        ]
    });

    // Filtri pill: cercano lo stato_calcolato nella colonna nascosta 8
    var filtri = {
        attivo:   '^attivo$',
        sospeso:  '^sospeso$',
        scaduto:  '^scaduto$',
        disdetto: '^disdetto$',
        tutti:    ''
    };

    function setFiltro(nome) {
        table.column(8).search(filtri[nome], filtri[nome] !== '', false).draw();
        document.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filtro === nome);
        });
    }

    document.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.addEventListener('click', function () { setFiltro(this.dataset.filtro); });
    });

    setFiltro('attivo');
});
</script>
<?= $this->endSection() ?>
