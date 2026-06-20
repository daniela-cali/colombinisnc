<?php
/**
 * @var array $interventi  Righe da InterventiModel::elencoCompleto()
 * @var array $prioritaLabel [codice => etichetta]
 * @var array $statiLabel  [codice => etichetta]
 */
$this->extend('layouts/admin');

$statoBadge = [
    'da_pianificare' => 'secondary',
    'pianificato'    => 'primary',
    'in_corso'       => 'warning text-dark',
    'completato'     => 'success',
    'annullato'      => 'danger',
];
$prioritaBadge = [
    'programmato' => 'primary',
    'normale'     => 'secondary',
    'urgente'     => 'danger',
];
?>
<?= $this->section('title') ?>Interventi<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Interventi</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i>Interventi
                </h3>
                <div class="card-tools ms-auto">
                    <a href="<?= base_url('operativo/interventi/nuovo') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3 d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" data-filtro="aperti">
                        <i class="bi bi-folder2-open me-1"></i>Aperti
                    </button>
                    <button class="btn btn-sm btn-outline-success" data-filtro="completati">
                        <i class="bi bi-check-circle me-1"></i>Completati
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-filtro="annullati">
                        <i class="bi bi-x-circle me-1"></i>Annullati
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" data-filtro="tutti">
                        Tutti (<?= count($interventi) ?>)
                    </button>
                </div>
                <?php if (empty($interventi)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun intervento trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabella-interventi" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Codice</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Stato</th>
                                    <th>Data pianificata</th>
                                    <th>Scadenza</th>
                                    <th>Tecnico</th>
                                    <th class="text-center">Urg.</th>
                                    <th></th>
                                    <th></th><!-- stato raw — nascosto, usato dal filtro -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interventi as $i): ?>
                                    <tr class="<?= $i['urgenza'] ? 'table-danger' : '' ?>" title="ID intervento: <?= $i['id'] ?>"><?php // ID utile per debug DB ?>
                                        <td>
                                            <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>"
                                               class="text-decoration-none">
                                                <code class="small"><?= esc($i['codice']) ?></code>
                                            </a>
                                        </td>
                                        <td title="ID cliente: <?= $i['cliente_id'] ?>">
                                            <a href="<?= base_url('anagrafiche/clienti/' . $i['cliente_id']) ?>"
                                               class="text-body text-decoration-none">
                                                <?= esc($i['cliente_denominazione']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($i['tipo_intervento_nome']): ?>
                                                <?php if ($i['tipo_intervento_icona']): ?>
                                                    <i class="fas <?= esc($i['tipo_intervento_icona']) ?> text-muted me-1 small"></i>
                                                <?php endif ?>
                                                <span class="small"><?= esc($i['tipo_intervento_nome']) ?></span>
                                                <br>
                                            <?php endif ?>
                                            <span class="badge bg-<?= $prioritaBadge[$i['priorita']] ?? 'secondary' ?>">
                                                <?= esc($prioritaLabel[$i['priorita']] ?? $i['priorita']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statoBadge[$i['stato']] ?? 'secondary' ?>">
                                                <?= esc($statiLabel[$i['stato']] ?? $i['stato']) ?>
                                            </span>
                                        </td>
                                        <td data-order="<?= esc($i['data_pianificata'] ?? '') ?>">
                                            <?= $i['data_pianificata']
                                                ? esc(date('d/m/Y H:i', strtotime($i['data_pianificata'])))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <td data-order="<?= esc($i['data_scadenza'] ?? '') ?>">
                                            <?= $i['data_scadenza']
                                                ? esc(date('d/m/Y', strtotime($i['data_scadenza'])))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <td class="text-muted small"><?= esc($i['tecnico_nome'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <?php if ($i['urgenza']): ?>
                                                <i class="bi bi-exclamation-triangle-fill text-danger"
                                                   data-bs-toggle="tooltip" data-bs-title="Urgente"></i>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= base_url('operativo/interventi/' . $i['id'] . '/edit') ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                        <td><?= esc($i['stato']) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script>
$(function () {
    var table = $('#tabella-interventi').DataTable({
        language: {
            search:       'Cerca:',
            lengthMenu:   'Mostra _MENU_ righe',
            info:         'Da _START_ a _END_ di _TOTAL_ record',
            infoEmpty:    'Nessun record',
            infoFiltered: '(filtrati da _MAX_ totali)',
            zeroRecords:  'Nessun risultato trovato',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        },
        orderMulti: true,
        pageLength:  25,
        order:       [[4, 'desc']],
        columnDefs:  [
            { orderable: false, searchable: false, targets: 8 },
            { visible: false, targets: 9 }
        ]
    });

    var filtri = {
        aperti:     { q: '^(da_pianificare|pianificato|in_corso)$', regex: true  },
        completati: { q: '^completato$',                            regex: true  },
        annullati:  { q: '^annullato$',                             regex: true  },
        tutti:      { q: '',                                        regex: false }
    };

    function setFiltro(nome) {
        var f = filtri[nome];
        table.column(9).search(f.q, f.regex, false).draw();
        document.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filtro === nome);
        });
    }

    document.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.addEventListener('click', function () { setFiltro(this.dataset.filtro); });
    });

    setFiltro('aperti');
});
</script>
<?= $this->endSection() ?>
