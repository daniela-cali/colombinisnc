<?php
/**
 * @var array $clienti  Righe da ClientiModel::elencoCompleto() — include denominazione e tecnico_preferito_nome
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Clienti<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Clienti</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i>Clienti
                </h3>
                <div class="card-tools ms-auto">
                    <a href="<?= base_url('anagrafiche/clienti/nuovo') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo cliente
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($clienti)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun cliente trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabella-clienti" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Codice</th>
                                    <th>Denominazione</th>
                                    <th>Tipo</th>
                                    <th>Città</th>
                                    <th class="text-center">Zona</th>
                                    <th class="text-end">Km sede</th>
                                    <th>Tecnico pref.</th>
                                    <th class="text-center">Attivo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clienti as $c): ?>
                                    <tr>
                                        <td class="text-muted small">
                                            <?= esc($c['codice']) ?>
                                            <?php if ($c['codice_esterno']): ?>
                                                <br><span class="text-muted" style="font-size:.75rem"><?= esc($c['codice_esterno']) ?></span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('anagrafiche/clienti/' . $c['id'] . '/edit') ?>"
                                               class="text-body fw-semibold text-decoration-none">
                                                <?= esc($c['denominazione']) ?>
                                            </a>
                                        </td>
                                        <td class="text-center text-muted">
                                            <?php if ($c['tipo'] === 'societa'): ?>
                                                <i class="bi bi-building" title="Società / Ditta"></i>
                                            <?php else: ?>
                                                <i class="bi bi-person" title="Persona fisica"></i>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-muted"><?= esc($c['citta'] ?? '—') ?></td>
                                        <td class="text-center" data-order="<?= $c['zona'] ?? 999 ?>">
                                            <?php if (isset($c['zona']) && $c['zona'] !== null): ?>
                                                <?php
                                                $zoneLabel  = \App\Models\ClientiModel::ZONE_LABEL[$c['zona']] ?? '?';
                                                $zoneBadge  = [-1 => 'warning text-dark', 0 => 'success', 1 => 'primary'][$c['zona']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $zoneBadge ?>"><?= esc($zoneLabel) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end text-muted small" data-order="<?= $c['distanza_sede'] ?? 999999 ?>">
                                            <?= $c['distanza_sede'] !== null ? esc($c['distanza_sede']) . ' km' : '—' ?>
                                        </td>
                                        <td class="text-muted small"><?= esc($c['tecnico_preferito_nome'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <?php if ($c['attivo']): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= base_url('anagrafiche/clienti/' . $c['id'] . '/edit') ?>"
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
    $('#tabella-clienti').DataTable({
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
        columnDefs: [
            { orderable: false, targets: [-1] }
        ]
    });
});
</script>
<?= $this->endSection() ?>
