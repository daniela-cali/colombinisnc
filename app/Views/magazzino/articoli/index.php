<?php
/**
 * @var array $articoli  Righe da ArticoliModel::elencoCompleto()
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Articoli<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
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
                    <i class="bi bi-info-circle text-muted ms-2"
                       style="font-size:.85rem; font-weight:normal"
                       data-bs-toggle="tooltip"
                       title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
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
                            <thead>
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
                                    <tr class="<?= $a['attivo'] ? '' : 'text-muted' ?>" title="ID articolo: <?= $a['id'] ?>"><?php // ID utile per debug DB ?>
                                    <!-- 1 Codice -->
                                        <td> 
                                            <a href="<?= base_url('magazzino/articoli/' . $a['id']) ?>"
                                               class="text-decoration-none js-row-open">
                                                <code class="small"><?= esc($a['codice'] ?? '—') ?></code>
                                            </a>
                                        </td>
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
<?= $this->include('partials/datatables_scripts') ?>
<script>
$(function () {
    initTabella('#tabella-articoli', {
        order: [[2, 'asc'], [1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [-1], responsivePriority: 2 },
            { responsivePriority: 1, targets: 1 },
            { responsivePriority: 3, targets: 6 }
        ]
    });
});
</script>
<?= $this->endSection() ?>
