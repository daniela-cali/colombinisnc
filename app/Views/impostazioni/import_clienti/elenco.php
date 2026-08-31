<?php
/**
 * @var list<array<string,mixed>>                        $records   Righe da ClientiAdhocModel::daMigrare()
 * @var array{totale:int, importati:int, da_migrare:int} $contatori
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Clienti da migrare<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni/import-clienti') ?>">Import Clienti</a></li>
    <li class="breadcrumb-item active">Da migrare</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="bi bi-box-arrow-in-right me-2"></i>Clienti da migrare
            <span class="badge text-bg-secondary ms-2"><?= $contatori['da_migrare'] ?></span>
        </h3>
        <div class="card-tools">
            <a href="<?= base_url('impostazioni/import-clienti') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-upload me-1"></i> Carica export
            </a>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($records)): ?>
            <p class="text-muted text-center py-4 mb-0">
                <?php if ($contatori['totale'] === 0): ?>
                    Nessun dato in parcheggio: caricare prima un export.
                <?php else: ?>
                    Tutti i <?= $contatori['totale'] ?> record in parcheggio sono già stati promossi.
                <?php endif ?>
            </p>
        <?php else: ?>
            <table id="tabella-adhoc" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Denominazione</th>
                        <th>Città</th>
                        <th>Prov.</th>
                        <th>P.IVA / C.F.</th>
                        <th>Telefono</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                    <?php $urlPromozione = base_url('anagrafiche/clienti/nuovo?adhoc=' . $r['id']); ?>
                    <tr>
                        <td><code class="text-muted"><?= esc($r['codice']) ?></code></td>
                        <td class="fw-semibold"><?= esc($r['denominazione'] ?? '—') ?></td>
                        <td><?= esc($r['citta'] ?? '—') ?></td>
                        <td class="text-muted small"><?= esc($r['provincia'] ?? '') ?></td>
                        <td class="text-muted small"><?= esc($r['piva'] ?: ($r['cfisc'] ?? '')) ?></td>
                        <td class="text-muted small"><?= esc($r['telefono'] ?? '') ?></td>
                        <td class="text-end">
                            <a href="<?= $urlPromozione ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus me-1"></i> Crea cliente
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script>
$(function () {
    initTabella('#tabella-adhoc', {
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [-1], responsivePriority: 2 },
            { responsivePriority: 1, targets: 1 }
        ]
    });
});
</script>
<?= $this->endSection() ?>
