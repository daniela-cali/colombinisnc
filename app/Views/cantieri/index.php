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

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Cantieri</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-bricks me-2"></i>Cantieri
            <i class="bi bi-info-circle text-muted ms-2"
               style="font-size:.85rem; font-weight:normal"
               data-bs-toggle="tooltip"
               title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
        </h3>
        <div class="card-tools ms-auto">
            <a href="<?= base_url('cantieri/nuovo') ?>" class="btn btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuovo
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($cantieri)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun cantiere presente.</p>
        <?php else: ?>
            <div class="mb-3 filtri-scroll">
                <button class="btn btn-sm btn-outline-success" data-filtro="aperto">
                    <i class="bi bi-unlock me-1"></i>Aperti
                </button>
                <button class="btn btn-sm btn-outline-warning" data-filtro="sospeso">
                    <i class="bi bi-pause-circle me-1"></i>Sospesi
                </button>
                <button class="btn btn-sm btn-outline-secondary" data-filtro="chiuso">
                    <i class="bi bi-lock me-1"></i>Chiusi
                </button>
                <button class="btn btn-sm btn-outline-primary" data-filtro="tutti">
                    Tutti (<?= count($cantieri) ?>)
                </button>
            </div>
            <div class="table-responsive">
                <table id="tabella-cantieri" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px">Rif.</th>
                            <th>Cliente</th>
                            <th>Titolo</th>
                            <th>Tipo</th>
                            <th class="text-center">Periodo</th>
                            <th class="text-center">Stato</th>
                            <th style="width:60px"></th>
                            <th></th><!-- 7 Filter stato -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cantieri as $c): ?>
                            <tr>
                                <!-- 1 Rif. -->
                                <td data-order="<?= (int) $c['id'] ?>">
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>" class="text-decoration-none js-row-open">
                                        <code class="small">#<?= (int) $c['id'] ?></code>
                                    </a>
                                </td>
                                <!-- 2 Cliente -->
                                <td>
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>" class="text-body text-decoration-none">
                                        <?= esc($c['cliente_denominazione']) ?>
                                    </a>
                                </td>
                                <!-- 3 Titolo -->
                                <td><?= esc($c['titolo']) ?></td>
                                <!-- 4 Tipo -->
                                <td>
                                    <?= esc($tipiLabel[$c['tipo']] ?? $c['tipo']) ?>
                                    <?php if (! empty($c['note'])): ?>
                                        <br><small class="text-muted"><?= esc(mb_strimwidth($c['note'], 0, 60, '…')) ?></small>
                                    <?php endif ?>
                                </td>
                                <!-- 5 Periodo -->
                                <td class="text-center text-nowrap" data-order="<?= esc($c['data_inizio'] ?? '') ?>">
                                    <?= $c['data_inizio'] ? date('d/m/Y', strtotime($c['data_inizio'])) : '—' ?>
                                    <?php if ($c['data_fine_prevista']): ?>
                                        – <?= date('d/m/Y', strtotime($c['data_fine_prevista'])) ?>
                                    <?php endif ?>
                                </td>
                                <!-- 6 Stato -->
                                <td class="text-center">
                                    <span class="badge <?= $statiBadge[$c['stato']] ?? 'bg-secondary' ?>">
                                        <?= esc($statiLabel[$c['stato']] ?? $c['stato']) ?>
                                    </span>
                                </td>
                                <!-- 7 Azioni -->
                                <td class="text-end">
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Scheda">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <!-- 8 Filter stato -->
                                <td><?= esc($c['stato']) ?></td>
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

    var table = new DataTable('#tabella-cantieri', {
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
            { name: 'rif',      targets: 0, searchable: false, responsivePriority: 2 },
            { name: 'cliente',  targets: 1, responsivePriority: 1 },
            { name: 'titolo',   targets: 2 },
            { name: 'tipo',     targets: 3 },
            { name: 'periodo',  targets: 4, searchable: false },
            { name: 'stato',    targets: 5, searchable: false, orderable: false, responsivePriority: 3 },
            { name: 'azioni',   targets: 6, searchable: false, orderable: false, responsivePriority: 2 },
            { name: 'filter_stato', targets: 7, searchable: true, orderable: false, visible: false }
        ]
    });

    // Filtri pill: cercano lo stato nella colonna nascosta 7
    var filtri = {
        aperto:  '^aperto$',
        sospeso: '^sospeso$',
        chiuso:  '^chiuso$',
        tutti:   ''
    };

    function setFiltro(nome) {
        table.column(7).search(filtri[nome], filtri[nome] !== '', false).draw();
        document.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filtro === nome);
        });
    }

    document.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.addEventListener('click', function () { setFiltro(this.dataset.filtro); });
    });

    setFiltro('aperto');
});
</script>
<?= $this->endSection() ?>
