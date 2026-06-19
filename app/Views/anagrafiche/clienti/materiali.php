<?php
/**
 * @var array $cliente    Record clienti
 * @var array $sospesi    Righe da InterventiMaterialiModel::sospesiPerCliente()
 * @var array $materiali  Righe da InterventiMaterialiModel::perCliente() (con stato_intervento)
 * @var array $statiLabel Map stato intervento → label leggibile
 */
$this->extend('layouts/admin');
$denom = \App\Models\ClientiModel::denominazione($cliente);

$statoBadgeInt = [
    'da_pianificare' => 'bg-secondary',
    'pianificato'    => 'bg-primary',
    'in_corso'       => 'bg-warning text-dark',
    'completato'     => 'bg-success',
    'annullato'      => 'bg-danger',
];
?>
<?= $this->section('title') ?>Materiali — <?= esc($denom) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/clienti') ?>">Clienti</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>"><?= esc($denom) ?></a></li>
    <li class="breadcrumb-item active">Materiali</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="max-width:900px">

    <!-- Header -->
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Scheda cliente
        </a>
        <div class="ms-1">
            <h5 class="mb-0 fw-bold">Materiali — <?= esc($denom) ?></h5>
            <small class="text-muted">
                <?= count($sospesi) ?> da portare &nbsp;·&nbsp; <?= count($materiali) ?> legati a interventi
            </small>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:55px">Qtà</th>
                        <th>Articolo / Descrizione</th>
                        <th>Note</th>
                        <th class="text-center" style="width:110px">Stato mat.</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if (! empty($sospesi)): ?>
                        <tr class="mat-spacer"><td colspan="4"></td></tr>
                        <!-- Intestazione: sospesi -->
                        <tr class="mat-group mat-group-sospesi">
                            <td colspan="4" class="ps-3 pt-3 pb-2 fw-semibold small">
                                <i class="bi bi-clock text-warning me-1"></i>
                                Da portare — non ancora assegnati a un intervento
                                <span class="badge bg-warning text-dark ms-2"><?= count($sospesi) ?></span>
                            </td>
                        </tr>
                        <?php foreach ($sospesi as $s): ?>
                            <tr>
                                <td class="text-center"><?= (int) $s['quantita'] ?></td>
                                <td><?= esc($s['desc_materiale']) ?></td>
                                <td class="text-muted small"><?= esc($s['note'] ?? '') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">Da portare</span>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>

                    <?php
                    $currentInterventoId = -1;
                    foreach ($materiali as $m):
                        if ((int) $m['intervento_id_ref'] !== $currentInterventoId):
                            $currentInterventoId = (int) $m['intervento_id_ref'];
                            $badgeClass = $statoBadgeInt[$m['stato_intervento']] ?? 'bg-secondary'; ?>
                        <tr class="mat-spacer"><td colspan="4"></td></tr>
                        <tr class="mat-group mat-group-intervento">
                            <td colspan="4" class="ps-3 pt-3 pb-2 fw-semibold small">
                                <i class="bi bi-tools me-1 text-muted"></i>
                                <a href="<?= base_url('operativo/interventi/' . $m['intervento_id_ref']) ?>"
                                   class="fw-semibold text-reset text-decoration-none">
                                    <?= esc($m['codice_intervento']) ?>
                                </a>
                                <?php if ($m['data_pianificata']): ?>
                                    <span class="text-muted ms-2 small"><?= date('d/m/Y', strtotime($m['data_pianificata'])) ?></span>
                                <?php endif ?>
                                <span class="badge <?= $badgeClass ?> ms-2" style="font-size:.65rem">
                                    <?= esc($statiLabel[$m['stato_intervento']] ?? $m['stato_intervento']) ?>
                                </span>
                                <a href="<?= base_url('operativo/interventi/' . $m['intervento_id_ref'] . '/edit') ?>"
                                   class="ms-2 text-muted small">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endif ?>
                        <tr>
                            <td class="text-center"><?= (int) $m['quantita'] ?></td>
                            <td><?= esc($m['desc_materiale']) ?></td>
                            <td class="text-muted small"><?= esc($m['note'] ?? '') ?></td>
                            <td class="text-center">
                                <?php if ($m['stato'] === \App\Models\InterventiMaterialiModel::STATO_CONSEGNATO): ?>
                                    <span class="badge bg-success">Consegnato</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Da portare</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <?php if (empty($sospesi) && empty($materiali)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                                Nessun materiale registrato per questo cliente.
                            </td>
                        </tr>
                    <?php endif ?>

                </tbody>
            </table>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
