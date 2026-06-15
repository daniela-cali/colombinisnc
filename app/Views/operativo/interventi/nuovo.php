<?php
/**
 * @var array    $clienti    Righe da ClientiModel::elencoCompleto()
 * @var array    $tecnici    Righe da PersonaleModel::elencoPerGruppi(['tecnico'])
 * @var array    $tipi       Righe da TipiInterventoModel::attivi()
 * @var array    $generiLabel [codice => etichetta]
 * @var array    $statiLabel [codice => etichetta]
 * @var int      $cliente_id Pre-selezione da ?cliente_id= (0 se non presente)
 * @var string   $from       URL di ritorno dopo il salvataggio (vuoto = lista interventi)
 */
$this->extend('layouts/admin');

// Durate default indicizzate per tipo_intervento_id
$durateDefault = array_column($tipi, 'durata_default', 'id');
?>
<?= $this->section('title') ?>Nuovo intervento<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('operativo/interventi') ?>">Interventi</a></li>
    <li class="breadcrumb-item active">Nuovo</li>
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
                <h3 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Nuovo intervento</h3>
            </div>
            <form action="<?= base_url('operativo/interventi/store') ?>" method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <div class="card-body">

                    <!-- Cliente e tecnico -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-people me-1"></i> Assegnazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" class="form-select">
                                <option value="">— seleziona —</option>
                                <?php foreach ($clienti as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                            <?= old('cliente_id', $cliente_id) == $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['denominazione']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Tecnico assegnato</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">— nessuno —</option>
                                <?php foreach ($tecnici as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= old('tecnico_id') == $t['id'] ? 'selected' : '' ?>>
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
                                            <?= old('genere', 'normale') === $codice ? 'selected' : '' ?>>
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
                                            <?= old('tipo_intervento_id') == $t['id'] ? 'selected' : '' ?>>
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
                                            <?= old('stato', 'da_pianificare') === $codice ? 'selected' : '' ?>>
                                        <?= esc($etichetta) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="urgenza"
                                       id="urgenza" value="1"
                                       <?= old('urgenza') ? 'checked' : '' ?>>
                                <label class="form-check-label text-danger fw-semibold" for="urgenza">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Urgente
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Date e durata -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-calendar me-1"></i> Pianificazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Data pianificata</label>
                            <input type="date" name="data_pianificata" class="form-control"
                                   value="<?= esc(old('data_pianificata')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data scadenza</label>
                            <input type="date" name="data_scadenza" class="form-control"
                                   value="<?= esc(old('data_scadenza')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durata stimata</label>
                            <div class="input-group">
                                <input type="number" name="durata_stimata" id="durata_stimata"
                                       class="form-control" min="5" max="480" step="5"
                                       value="<?= esc(old('durata_stimata')) ?>"
                                       placeholder="min">
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-sticky me-1"></i> Note</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <textarea name="note" class="form-control" rows="4"><?= esc(old('note')) ?></textarea>
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= esc($from ?: base_url('operativo/interventi')) ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
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
