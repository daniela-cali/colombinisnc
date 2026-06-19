<?php
/**
 * @var array      $intervento  Record interventi
 * @var array|null $cliente     Record clienti
 * @var array|null $tecnico     Record personale
 * @var array|null $tipo        Record tipi_intervento
 * @var array      $prioritaLabel [codice => etichetta]
 * @var array      $statiLabel  [codice => etichetta]
 * @var array      $materiali   Righe da InterventiMaterialiModel::perIntervento()
 */
$this->extend('layouts/admin');

$statoBadge = [
    'da_pianificare' => 'secondary',
    'pianificato'    => 'primary',
    'in_corso'       => 'warning text-dark',
    'completato'     => 'success',
    'annullato'      => 'danger',
];
?>
<?= $this->section('title') ?><?= esc($intervento['codice']) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('operativo/interventi') ?>">Interventi</a></li>
    <li class="breadcrumb-item active"><?= esc($intervento['codice']) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i><?= esc($intervento['codice']) ?>
                    <?php if ($intervento['urgenza']): ?>
                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1"
                           data-bs-toggle="tooltip" data-bs-title="Urgente"></i>
                    <?php endif ?>
                </h3>
            </div>

            <div class="card-body">

                <!-- Cliente e tecnico -->
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <p class="text-muted small mb-1">Cliente</p>
                        <?php if ($cliente): ?>
                            <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>" class="fw-semibold">
                                <?= esc(\App\Models\ClientiModel::denominazione($cliente)) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif ?>
                    </div>
                    <div class="col-md-5">
                        <p class="text-muted small mb-1">Tecnico</p>
                        <span><?= $tecnico ? esc($tecnico['cognome'] . ' ' . $tecnico['nome']) : '<span class="text-muted">—</span>' ?></span>
                    </div>
                </div>

                <!-- Tipo, stato, urgenza -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <p class="text-muted small mb-1">Tipo intervento</p>
                        <span><?= $tipo ? esc($tipo['nome']) : '<span class="text-muted">—</span>' ?></span>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted small mb-1">Priorità</p>
                        <span><?= esc($prioritaLabel[$intervento['priorita']] ?? $intervento['priorita']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted small mb-1">Stato</p>
                        <span class="badge bg-<?= $statoBadge[$intervento['stato']] ?? 'secondary' ?>">
                            <?= esc($statiLabel[$intervento['stato']] ?? $intervento['stato']) ?>
                        </span>
                    </div>
                    <div class="col-md-2">
                        <p class="text-muted small mb-1">Urgenza</p>
                        <?php if ($intervento['urgenza']): ?>
                            <span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sì</span>
                        <?php else: ?>
                            <span class="text-muted">No</span>
                        <?php endif ?>
                    </div>
                </div>

                <!-- Date -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <p class="text-muted small mb-1">Data pianificata</p>
                        <span><?= $intervento['data_pianificata']
                            ? esc(date('d/m/Y', strtotime($intervento['data_pianificata'])))
                            : '<span class="text-muted">Da pianificare</span>' ?></span>
                    </div>
                    <div class="col-md-4">
                        <p class="text-muted small mb-1">Scadenza</p>
                        <span><?= $intervento['data_scadenza']
                            ? esc(date('d/m/Y', strtotime($intervento['data_scadenza'])))
                            : '<span class="text-muted">—</span>' ?></span>
                    </div>
                    <div class="col-md-4">
                        <p class="text-muted small mb-1">Durata stimata</p>
                        <span><?= $intervento['durata_stimata']
                            ? esc($intervento['durata_stimata']) . ' min'
                            : '<span class="text-muted">—</span>' ?></span>
                    </div>
                </div>

                <!-- Note -->
                <?php if ($intervento['note']): ?>
                    <p class="text-muted small mb-1">Note</p>
                    <p class="text-preline mb-0"><?= esc($intervento['note']) ?></p>
                <?php endif ?>

                <?php if (! empty($materiali)): ?>
                    <hr class="my-4">

                    <p class="text-muted section-header mb-3"><i class="bi bi-box-seam me-1"></i> Materiali</p>
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Articolo / Descrizione</th>
                                <th class="text-center" style="width:60px">Qtà</th>
                                <th>Note</th>
                                <th class="text-center" style="width:110px">Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiali as $m): ?>
                                <tr title="ID materiale: <?= $m['id'] ?>"><?php // ID utile per debug DB ?>
                                    <td><?= esc($m['desc_materiale']) ?></td>
                                    <td class="text-center"><?= (int) $m['quantita'] ?></td>
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
                        </tbody>
                    </table>
                <?php endif ?>

            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <?php if ($cliente): ?>
                    <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '#sec-interventi') ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Scheda cliente
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('operativo/interventi') ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Interventi
                    </a>
                <?php endif ?>
                <div class="d-flex gap-2 ms-auto">
                    <?php if (! in_array($intervento['stato'], ['completato', 'annullato'])): ?>
                        <button type="button" class="btn btn-sm btn-success"
                                data-bs-toggle="modal" data-bs-target="#modal-chiudi">
                            <i class="bi bi-check-circle me-1"></i>Chiudi intervento
                        </button>
                    <?php endif ?>
                    <?php
                        $editFrom = $cliente
                            ? base_url('anagrafiche/clienti/' . $cliente['id'] . '#sec-interventi')
                            : base_url('operativo/interventi');
                    ?>
                    <a href="<?= base_url('operativo/interventi/' . $intervento['id'] . '/edit?from=' . urlencode($editFrom)) ?>"
                       class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i>Modifica
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: chiudi intervento -->
<div class="modal fade" id="modal-chiudi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Chiudi intervento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    Confermi la chiusura dell'intervento
                    <strong><?= esc($intervento['codice']) ?></strong>?
                </p>
                <?php if (! empty($materiali)): ?>
                    <p class="mt-3 mb-0">Hai consegnato i materiali al cliente?</p>
                <?php endif ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <?php if (! empty($materiali)): ?>
                    <form method="post"
                          action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/chiudi') ?>"
                          class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="materiali_consegnati" value="0">
                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-x-circle me-1"></i>No, non portati
                        </button>
                    </form>
                    <form method="post"
                          action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/chiudi') ?>"
                          class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="materiali_consegnati" value="1">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Sì, consegnati
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post"
                          action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/chiudi') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Chiudi
                        </button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
