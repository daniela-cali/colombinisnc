<?php
/**
 * @var string $title
 * @var array  $cantiere             Da CantieriModel::conCliente() — include cliente_denominazione
 * @var array  $note                 Da CantieriNoteModel::perCantiere() — include autore
 * @var array  $interventi           Da InterventiModel::perCantiere()
 * @var array  $tipiLabel            CantieriModel::TIPI_LABEL
 * @var array  $statiLabel           CantieriModel::STATI_LABEL
 * @var array  $statiBadge           CantieriModel::STATI_BADGE
 * @var array  $interventiStatiLabel InterventiModel::STATI_LABEL
 * @var array  $interventiBadge      InterventiModel::STATI_BADGE
 */
$this->extend('layouts/admin');

$stato     = $cantiere['stato'];
$schedaUrl = base_url('cantieri/' . $cantiere['id']);
$diarioUrl = $schedaUrl . '#sec-diario';
$nuovoInterventoUrl = base_url('operativo/interventi/nuovo?cantiere_id=' . $cantiere['id']
    . '&cliente_id=' . $cantiere['cliente_id']
    . '&from=' . urlencode($schedaUrl . '#sec-interventi'));
?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('cantieri') ?>">Cantieri</a></li>
    <li class="breadcrumb-item active"><?= esc($cantiere['titolo']) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">

    <!-- ── Colonna dettagli ─────────────────────────────────────────────── -->
    <div class="col-md-4">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-bricks me-2"></i>Cantiere
                    <span class="badge <?= $statiBadge[$stato] ?? 'bg-secondary' ?> ms-2">
                        <?= esc($statiLabel[$stato] ?? $stato) ?>
                    </span>
                </h3>
                <div class="card-tools">
                    <a href="<?= base_url('cantieri/' . $cantiere['id'] . '/edit') ?>"
                       class="btn btn-sm" title="Modifica">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Titolo</dt>
                    <dd class="col-7"><?= esc($cantiere['titolo']) ?></dd>

                    <dt class="col-5 text-muted">Cliente</dt>
                    <dd class="col-7">
                        <a href="<?= base_url('anagrafiche/clienti/' . $cantiere['cliente_id'] . '#sec-cantieri') ?>">
                            <?= esc($cantiere['cliente_denominazione']) ?>
                        </a>
                    </dd>

                    <dt class="col-5 text-muted">Tipo</dt>
                    <dd class="col-7"><?= esc($tipiLabel[$cantiere['tipo']] ?? $cantiere['tipo']) ?></dd>

                    <dt class="col-5 text-muted">Inizio</dt>
                    <dd class="col-7"><?= $cantiere['data_inizio'] ? date('d/m/Y', strtotime($cantiere['data_inizio'])) : '—' ?></dd>

                    <dt class="col-5 text-muted">Fine prevista</dt>
                    <dd class="col-7"><?= $cantiere['data_fine_prevista'] ? date('d/m/Y', strtotime($cantiere['data_fine_prevista'])) : '—' ?></dd>

                    <?php if ($cantiere['note']): ?>
                        <dt class="col-5 text-muted">Note</dt>
                        <dd class="col-7"><?= nl2br(esc($cantiere['note'])) ?></dd>
                    <?php endif ?>
                </dl>
            </div>

            <!-- Azioni di stato -->
            <div class="card-footer">
                <div class="d-flex flex-column gap-2">
                    <?php if ($stato !== \App\Models\CantieriModel::STATO_APERTO): ?>
                        <form action="<?= base_url('cantieri/' . $cantiere['id'] . '/stato') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="stato" value="aperto">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-play-circle me-1"></i>Riapri
                            </button>
                        </form>
                    <?php endif ?>
                    <?php if ($stato !== \App\Models\CantieriModel::STATO_SOSPESO): ?>
                        <form action="<?= base_url('cantieri/' . $cantiere['id'] . '/stato') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="stato" value="sospeso">
                            <button type="submit" class="btn btn-warning btn-sm w-100">
                                <i class="bi bi-pause-circle me-1"></i>Sospendi
                            </button>
                        </form>
                    <?php endif ?>
                    <?php if ($stato !== \App\Models\CantieriModel::STATO_CHIUSO): ?>
                        <form action="<?= base_url('cantieri/' . $cantiere['id'] . '/stato') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="stato" value="chiuso">
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-check2-circle me-1"></i>Chiudi
                            </button>
                        </form>
                    <?php endif ?>
                </div>
            </div>

            <!-- Elimina -->
            <div class="card-footer">
                <form action="<?= base_url('cantieri/' . $cantiere['id'] . '/delete') ?>" method="post"
                      onsubmit="return confirm('Eliminare definitivamente il cantiere e il suo diario? L\'operazione non è reversibile.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="from"
                           value="<?= esc(base_url('anagrafiche/clienti/' . $cantiere['cliente_id']) . '#sec-cantieri') ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash me-1"></i>Elimina cantiere
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Colonna interventi + diario ───────────────────────────────────── -->
    <div class="col-md-8">

        <!-- Interventi -->
        <div class="card card-outline card-secondary" id="sec-interventi">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Interventi
                    <span class="badge bg-secondary ms-1"><?= count($interventi) ?></span>
                </h3>
                <div class="card-tools">
                    <a href="<?= $nuovoInterventoUrl ?>" class="btn btn-sm" title="Nuovo intervento">
                        <i class="bi bi-plus-circle me-1"></i>Nuovo intervento
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($interventi)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun intervento collegato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Codice</th>
                                    <th>Tipo</th>
                                    <th>Pianificato</th>
                                    <th class="text-center">Stato</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interventi as $iv): ?>
                                    <tr>
                                        <td class="font-monospace small"><?= esc($iv['codice'] ?? '—') ?></td>
                                        <td><?= esc($iv['tipo_intervento_nome'] ?? '—') ?></td>
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

        <!-- Diario -->
        <div class="card card-outline card-info" id="sec-diario">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-journal-text me-2"></i>Diario
                    <span class="badge bg-secondary ms-1"><?= count($note) ?></span>
                </h3>
            </div>
            <div class="card-body">

                <?php if (! empty($note)): ?>
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($note as $n): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start"
                                title="ID nota: <?= $n['id'] ?>">
                                <div class="me-2">
                                    <span class="badge bg-light text-dark border me-2"><?= esc(date('d/m/Y', strtotime($n['data_nota']))) ?></span>
                                    <span class="text-preline"><?= esc($n['testo']) ?></span>
                                    <?php if (! empty($n['autore'])): ?>
                                        <span class="text-muted small ms-1">— <?= esc($n['autore']) ?></span>
                                    <?php endif ?>
                                </div>
                                <form method="post"
                                      action="<?= base_url('cantieri/note/' . $n['id'] . '/elimina') ?>"
                                      class="d-inline" onsubmit="return confirm('Eliminare questa nota?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="from" value="<?= esc($diarioUrl) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina nota">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-4">Nessuna nota nel diario.</p>
                <?php endif ?>

                <!-- Mini-form aggiunta nota -->
                <form method="post" action="<?= base_url('cantieri/note/aggiungi') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cantiere_id" value="<?= $cantiere['id'] ?>">
                    <input type="hidden" name="from" value="<?= esc($diarioUrl) ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Data</label>
                            <input type="date" name="data_nota" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Nota</label>
                            <input type="text" name="testo" class="form-control form-control-sm"
                                   placeholder="Aggiungi una nota al diario…" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
<?= $this->endSection() ?>
