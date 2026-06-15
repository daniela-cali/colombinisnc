<?php
/**
 * @var array      $intervento  Record interventi
 * @var array|null $cliente     Record clienti
 * @var array      $clienti     Righe da ClientiModel::elencoCompleto()
 * @var array      $tecnici     Righe da PersonaleModel::elencoPerGruppi(['tecnico'])
 * @var array      $tipi        Righe da TipiInterventoModel::attivi()
 * @var array      $generiLabel [codice => etichetta]
 * @var array      $statiLabel  [codice => etichetta]
 * @var array      $materiali   Righe da InterventiMaterialiModel::perIntervento()
 * @var string     $from        URL di ritorno dopo il salvataggio (vuoto = rimane in edit)
 */
$this->extend('layouts/admin');

$durateDefault = array_column($tipi, 'durata_default', 'id');
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
    <div class="col-lg-9">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i><?= esc($intervento['codice']) ?>
                    <?php if ($intervento['urgenza']): ?>
                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1"
                           data-bs-toggle="tooltip" data-bs-title="Urgente"></i>
                    <?php endif ?>
                    <?php if ($cliente): ?>
                        <span class="text-muted small ms-2">
                            — <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>"
                                 class="text-muted text-decoration-none">
                                <?= esc(\App\Models\ClientiModel::denominazione($cliente)) ?>
                            </a>
                        </span>
                    <?php endif ?>
                </h3>
            </div>

            <!-- Tab nav -->
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab"
                                data-bs-target="#pane-intervento" type="button" role="tab">
                            <i class="bi bi-tools me-1"></i>Intervento
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#pane-materiali" type="button" role="tab">
                            <i class="bi bi-box-seam me-1"></i>Materiali
                            <?php if (count($materiali)): ?>
                                <span class="badge bg-secondary ms-1"><?= count($materiali) ?></span>
                            <?php endif ?>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">

                <!-- TAB: Intervento -->
                <div class="tab-pane fade show active" id="pane-intervento" role="tabpanel">
                    <form id="form-update"
                          action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/update') ?>"
                          method="post">
                        <?= csrf_field() ?>
                        <div class="card-body">
                            <?php if ($from): ?>
                                <input type="hidden" name="from" value="<?= esc($from) ?>">
                            <?php endif ?>

                            <!-- Assegnazione -->
                            <p class="text-muted section-header mb-3"><i class="bi bi-people me-1"></i> Assegnazione</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-7">
                                    <label class="form-label">Cliente</label>
                                    <input type="hidden" name="cliente_id" value="<?= esc($intervento['cliente_id']) ?>">
                                    <p class="form-control-plaintext">
                                        <?php if ($cliente): ?>
                                            <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>">
                                                <?= esc(\App\Models\ClientiModel::denominazione($cliente)) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif ?>
                                    </p>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Tecnico assegnato</label>
                                    <select name="tecnico_id" class="form-select">
                                        <option value="">— nessuno —</option>
                                        <?php foreach ($tecnici as $t): ?>
                                            <option value="<?= $t['id'] ?>"
                                                    <?= old('tecnico_id', $intervento['tecnico_id']) == $t['id'] ? 'selected' : '' ?>>
                                                <?= esc($t['cognome'] . ' ' . $t['nome']) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Classificazione -->
                            <p class="text-muted section-header mb-3"><i class="bi bi-tag me-1"></i> Classificazione</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Genere <span class="text-danger">*</span></label>
                                    <select name="genere" id="genere" class="form-select">
                                        <?php foreach ($generiLabel as $codice => $etichetta): ?>
                                            <option value="<?= $codice ?>"
                                                    <?= old('genere', $intervento['genere']) === $codice ? 'selected' : '' ?>>
                                                <?= esc($etichetta) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo intervento</label>
                                    <select name="tipo_intervento_id" id="tipo_intervento_id" class="form-select">
                                        <option value="">— nessuno —</option>
                                        <?php foreach ($tipi as $t): ?>
                                            <option value="<?= $t['id'] ?>"
                                                    <?= old('tipo_intervento_id', $intervento['tipo_intervento_id']) == $t['id'] ? 'selected' : '' ?>>
                                                <?= esc($t['nome']) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Stato <span class="text-danger">*</span></label>
                                    <select name="stato" class="form-select">
                                        <?php foreach ($statiLabel as $codice => $etichetta): ?>
                                            <option value="<?= $codice ?>"
                                                    <?= old('stato', $intervento['stato']) === $codice ? 'selected' : '' ?>>
                                                <?= esc($etichetta) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="urgenza"
                                               id="urgenza" value="1"
                                               <?= old('urgenza', $intervento['urgenza']) ? 'checked' : '' ?>>
                                        <label class="form-check-label text-danger fw-semibold" for="urgenza">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Urgente
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Pianificazione -->
                            <p class="text-muted section-header mb-3"><i class="bi bi-calendar me-1"></i> Pianificazione</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Data pianificata</label>
                                    <input type="date" name="data_pianificata" class="form-control"
                                           value="<?= esc(old('data_pianificata',
                                               $intervento['data_pianificata']
                                                   ? date('Y-m-d', strtotime($intervento['data_pianificata']))
                                                   : ''
                                           )) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Data scadenza</label>
                                    <input type="date" name="data_scadenza" class="form-control"
                                           value="<?= esc(old('data_scadenza', $intervento['data_scadenza'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Durata stimata</label>
                                    <div class="input-group">
                                        <input type="number" name="durata_stimata" id="durata_stimata"
                                               class="form-control" min="5" max="480" step="5"
                                               value="<?= esc(old('durata_stimata', $intervento['durata_stimata'] ?? '')) ?>"
                                               placeholder="min">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Note -->
                            <p class="text-muted section-header mb-3"><i class="bi bi-sticky me-1"></i> Note</p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <textarea name="note" class="form-control" rows="4"><?= esc(old('note', $intervento['note'] ?? '')) ?></textarea>
                                </div>
                            </div>

                        </div>
                    </form><!-- /form-update — chiuso prima del footer -->

                    <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                        <form action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/delete') ?>"
                              method="post" class="d-inline"
                              onsubmit="return confirm('Eliminare definitivamente l\'intervento <?= esc($intervento['codice']) ?>?')">
                            <?= csrf_field() ?>
                            <?php if ($from): ?>
                                <input type="hidden" name="from" value="<?= esc($from) ?>">
                            <?php endif ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>Elimina
                            </button>
                        </form>
                        <div class="ms-auto d-flex gap-2">
                            <a href="<?= esc($from ?: base_url('operativo/interventi')) ?>"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-lg me-1"></i>Annulla
                            </a>
                            <button type="submit" form="form-update" class="btn btn-primary btn-sm">
                                <i class="bi bi-check-lg me-1"></i>Salva modifiche
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TAB: Materiali -->
                <div class="tab-pane fade" id="pane-materiali" role="tabpanel">
                    <div class="card-body">

                        <?php if (empty($materiali)): ?>
                            <p class="text-muted text-center py-3 mb-3">Nessun materiale registrato per questo intervento.</p>
                        <?php else: ?>
                            <table class="table table-sm align-middle mb-4">
                                <thead class="table-light">
                                    <tr>
                                        <th>Descrizione</th>
                                        <th class="text-center" style="width:80px">Qtà</th>
                                        <th>Note</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materiali as $m): ?>
                                        <tr>
                                            <td><?= esc($m['descrizione']) ?></td>
                                            <td class="text-center"><?= esc($m['quantita']) ?></td>
                                            <td class="text-muted small"><?= esc($m['note'] ?? '') ?></td>
                                            <td class="text-end">
                                                <form action="<?= base_url('operativo/materiali/' . $m['id'] . '/delete') ?>"
                                                      method="post" class="d-inline"
                                                      onsubmit="return confirm('Eliminare questo materiale?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        <?php endif ?>

                        <!-- Form aggiunta materiale -->
                        <p class="text-muted section-header mb-3"><i class="bi bi-plus-circle me-1"></i> Aggiungi materiale</p>
                        <form action="<?= base_url('operativo/materiali/store') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="intervento_id" value="<?= $intervento['id'] ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                                    <input type="text" name="descrizione" class="form-control" maxlength="255" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qtà</label>
                                    <input type="number" name="quantita" class="form-control"
                                           min="1" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Note</label>
                                    <input type="text" name="note" class="form-control" maxlength="255">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-lg me-1"></i>Aggiungi
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>

            </div><!-- /tab-content -->
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var durateDefault = <?= json_encode($durateDefault) ?>;

    document.getElementById('tipo_intervento_id').addEventListener('change', function () {
        var durata = document.getElementById('durata_stimata');
        if (! durata.value && durateDefault[this.value]) {
            durata.value = durateDefault[this.value];
        }
    });
})();
</script>
<?= $this->endSection() ?>
