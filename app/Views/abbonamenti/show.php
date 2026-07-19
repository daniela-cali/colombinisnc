<?php
/**
 * @var string $title
 * @var array  $abbonamento          Da AbbonamentiModel::trovaConDettagli() — include stato_calcolato, ha_successore
 * @var array  $periodi              Da AbbonamentiPeriodiModel::perAbbonamento()
 * @var array  $interventi           Interventi figli: id, codice, data_scadenza, data_pianificata, stato
 * @var array  $statiLabel           AbbonamentiModel::STATI_LABEL
 * @var array  $statiBadge           AbbonamentiModel::STATI_BADGE
 * @var array  $frequenze            AbbonamentiModel::FREQUENZE_LABEL
 * @var array  $interventiStatiLabel InterventiModel::STATI_LABEL
 * @var array  $interventiBadge      InterventiModel::STATI_BADGE
 */
$this->extend('layouts/admin');

$stato = $abbonamento['stato_calcolato'];
?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('abbonamenti') ?>">Abbonamenti</a></li>
    <li class="breadcrumb-item active">Abbonamento</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">

    <!-- ── Colonna dettagli ─────────────────────────────────────────────── -->
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Abbonamento
                    <span class="badge <?= $statiBadge[$stato] ?? 'bg-secondary' ?> ms-2">
                        <?= esc($statiLabel[$stato] ?? $stato) ?>
                    </span>
                </h3>
                <div class="card-tools">
                    <a href="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/edit') ?>"
                       class="btn btn-sm" title="Modifica">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Cliente</dt>
                    <dd class="col-7"><?= esc($abbonamento['cliente_denominazione']) ?></dd>

                    <dt class="col-5 text-muted">Tipo</dt>
                    <dd class="col-7"><?= esc($abbonamento['tipo_nome'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">Inizio</dt>
                    <dd class="col-7"><?= date('d/m/Y', strtotime($abbonamento['data_inizio'])) ?></dd>

                    <dt class="col-5 text-muted">Fine</dt>
                    <dd class="col-7"><?= date('d/m/Y', strtotime($abbonamento['data_fine'])) ?></dd>

                    <dt class="col-5 text-muted">Prezzo</dt>
                    <dd class="col-7">
                        <?= $abbonamento['prezzo'] !== null
                            ? '€ ' . number_format((float) $abbonamento['prezzo'], 2, ',', '.')
                            : '—' ?>
                    </dd>

                    <?php if ($abbonamento['abbonamento_precedente_id']): ?>
                        <dt class="col-5 text-muted">Rinnovo di</dt>
                        <dd class="col-7">
                            <a href="<?= base_url('abbonamenti/' . $abbonamento['abbonamento_precedente_id']) ?>">
                                #<?= (int) $abbonamento['abbonamento_precedente_id'] ?>
                            </a>
                        </dd>
                    <?php endif ?>

                    <?php if ($abbonamento['note']): ?>
                        <dt class="col-5 text-muted">Note</dt>
                        <dd class="col-7"><?= nl2br(esc($abbonamento['note'])) ?></dd>
                    <?php endif ?>
                </dl>

                <?php if (! empty($periodi)): ?>
                    <hr class="my-3">
                    <p class="text-muted small fw-semibold mb-2"><i class="bi bi-calendar3 me-1"></i>Periodi di frequenza</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Da</th>
                                    <th>A</th>
                                    <th>Frequenza</th>
                                    <th class="text-center">Pul. fondo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periodi as $p): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= date('d/m/Y', strtotime($p['data_inizio'])) ?></td>
                                        <td class="text-nowrap"><?= date('d/m/Y', strtotime($p['data_fine'])) ?></td>
                                        <td><?= esc($frequenze[$p['frequenza']] ?? $p['frequenza']) ?></td>
                                        <td class="text-center">
                                            <?= $p['con_pulizia_fondo'] ? '<i class="bi bi-check-lg text-success"></i>' : '<span class="text-muted">—</span>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>

            <!-- Azioni di stato -->
            <?php if (in_array($stato, ['attivo', 'sospeso'], true)): ?>
                <div class="card-footer">
                    <div class="d-flex flex-column gap-2">

                        <?php if ($stato === 'attivo'): ?>
                            <form action="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/stato') ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="stato" value="sospeso">
                                <button type="submit" class="btn btn-warning btn-sm w-100"
                                        onclick="return confirm('Sospendere l\'abbonamento? Gli interventi futuri da pianificare verranno sospesi.')">
                                    <i class="bi bi-pause-circle me-1"></i>Sospendi
                                </button>
                            </form>
                        <?php endif ?>

                        <?php if ($stato === 'sospeso'): ?>
                            <button type="button" class="btn btn-success btn-sm w-100"
                                    data-bs-toggle="modal" data-bs-target="#modal-riattiva">
                                <i class="bi bi-play-circle me-1"></i>Riattiva
                            </button>
                        <?php endif ?>

                        <form action="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/stato') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="stato" value="disdetto">
                            <button type="submit" class="btn btn-danger btn-sm w-100"
                                    onclick="return confirm('Disdire l\'abbonamento? Gli interventi futuri verranno annullati. L\'operazione non è reversibile.')">
                                <i class="bi bi-x-circle me-1"></i>Disdici
                            </button>
                        </form>

                    </div>
                </div>
            <?php endif ?>

            <!-- Rinnova -->
            <?php if (! $abbonamento['ha_successore'] && in_array($stato, ['scaduto', 'disdetto'], true)): ?>
                <div class="card-footer">
                    <a href="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/rinnova') ?>"
                       class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-arrow-repeat me-1"></i>Rinnova abbonamento
                    </a>
                </div>
            <?php endif ?>

        </div>
    </div>

    <!-- ── Colonna interventi ────────────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Interventi
                    <span class="badge bg-secondary ms-1"><?= count($interventi) ?></span>
                </h3>
                <?php if ($stato === 'attivo'): ?>
                    <div class="card-tools">
                        <a href="<?= base_url('operativo/interventi/nuovo?abbonamento_id=' . $abbonamento['id'] . '&cliente_id=' . $abbonamento['cliente_id'] . '&extra=1&from=' . urlencode(base_url('abbonamenti/' . $abbonamento['id']))) ?>"
                           class="btn btn-sm" title="Nuova visita extra">
                            <i class="bi bi-plus-circle me-1"></i>Visita extra
                        </a>
                    </div>
                <?php endif ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($interventi)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun intervento associato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Codice</th>
                                    <th>Scadenza</th>
                                    <th>Pianificato</th>
                                    <th class="text-center">Stato</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interventi as $iv): ?>
                                    <tr>
                                        <td class="font-monospace small">
                                            <?= esc($iv['codice'] ?? '—') ?>
                                            <?php if ($iv['extra']): ?>
                                                <span class="badge bg-warning text-dark ms-1">Extra</span>
                                            <?php endif ?>
                                        </td>
                                        <td><?= $iv['data_scadenza'] ? date('d/m/Y', strtotime($iv['data_scadenza'])) : '—' ?></td>
                                        <td><?= $iv['data_pianificata'] ? date('d/m/Y', strtotime($iv['data_pianificata'])) : '—' ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $interventiBadge[$iv['stato']] ?? 'bg-secondary' ?>">
                                                <?= esc($interventiStatiLabel[$iv['stato']] ?? $iv['stato']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('operativo/interventi/' . $iv['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Scheda">
                                                <i class="bi bi-eye"></i>
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

<!-- Modal riattiva abbonamento (sospeso → attivo) -->
<?php if ($stato === 'sospeso'): ?>
<div class="modal fade" id="modal-riattiva" tabindex="-1" aria-labelledby="modal-riattiva-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-riattiva-label">
                    <i class="bi bi-play-circle me-2"></i>Riattiva abbonamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>L'abbonamento verrà riportato ad <strong>Attivo</strong>.</p>
                <p class="mb-0">Cosa fare con gli interventi attualmente <em>sospesi</em>?</p>
            </div>
            <div class="modal-footer flex-column align-items-stretch gap-2">

                <form action="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/stato') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="stato" value="attivo">
                    <input type="hidden" name="ripristina" value="1">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Ripristina gli interventi sospesi
                    </button>
                </form>

                <form action="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/stato') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="stato" value="attivo">
                    <input type="hidden" name="ripristina" value="0">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x me-1"></i>Riattiva senza ripristinare
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
