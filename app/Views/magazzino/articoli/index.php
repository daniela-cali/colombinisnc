<?php
/**
 * @var array $articoli  Righe da ArticoliModel::elencoCompleto()
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Articoli<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Articoli</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-boxes me-2"></i>Articoli
                </h3>
                <div class="card-tools ms-auto">
                    <a href="<?= base_url('magazzino/articoli/nuovo') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo articolo
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($articoli)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun articolo in catalogo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabella-articoli" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Codice</th>
                                    <th>Descrizione</th>
                                    <th>Categoria</th>
                                    <th class="text-center">U.M.</th>
                                    <th class="text-end">Costo</th>
                                    <th class="text-end">Vendita</th>
                                    <th class="text-end">Giacenza</th>
                                    <th class="text-center">Attivo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articoli as $a): ?>
                                    <tr class="<?= $a['attivo'] ? '' : 'text-muted' ?>">
                                        <td class="small text-muted"><?= esc($a['codice'] ?? '—') ?></td>
                                        <td class="fw-semibold"><?= esc($a['descrizione']) ?></td>
                                        <td class="small text-muted"><?= esc($a['categoria_nome'] ?? '—') ?></td>
                                        <td class="text-center small"><?= esc($a['unita_misura']) ?></td>
                                        <td class="text-end small">
                                            <?= $a['costo'] !== null ? '€ ' . number_format($a['costo'], 2, ',', '.') : '—' ?>
                                        </td>
                                        <td class="text-end small">
                                            <?= $a['vendita'] !== null ? '€ ' . number_format($a['vendita'], 2, ',', '.') : '—' ?>
                                        </td>
                                        <td class="text-end small">
                                            <?= $a['giacenza'] !== null ? (int) $a['giacenza'] : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($a['attivo']): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= base_url('magazzino/articoli/' . $a['id'] . '/edit') ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
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
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script>
$(function () {
    $('#tabella-articoli').DataTable({
        language: {
            search:       'Cerca:',
            lengthMenu:   'Mostra _MENU_ righe',
            info:         'Da _START_ a _END_ di _TOTAL_ record',
            infoEmpty:    'Nessun record',
            infoFiltered: '(filtrati da _MAX_ totali)',
            zeroRecords:  'Nessun risultato trovato',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        },
        pageLength: 25,
        order: [[2, 'asc'], [1, 'asc']],
        columnDefs: [{ orderable: false, targets: [-1] }]
    });
});
</script>
<?= $this->endSection() ?>
