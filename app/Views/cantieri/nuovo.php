<?php
/**
 * @var string      $title
 * @var array|null  $cantiere    null per nuovo (presente per riuso old())
 * @var array|null  $cliente     Preselezionato da ?cliente_id=; null = mostra select
 * @var array       $clienti     Elenco completo per il select
 * @var array       $tipi        Righe da TipiInterventoModel::attivi() — per il tipo intervento predefinito
 * @var array       $tipiLabel   CantieriModel::TIPI_LABEL
 * @var array       $statiLabel  CantieriModel::STATI_LABEL
 * @var string|null $from        URL di ritorno dopo salvataggio
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('cantieri') ?>">Cantieri</a></li>
    <li class="breadcrumb-item active"><?= esc($title) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-bricks me-2"></i><?= esc($title) ?>
                </h3>
            </div>
            <form action="<?= base_url('cantieri/store') ?>" method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>

                <!-- Campi geocodifica — compilati dal JS prima del submit -->
                <input type="hidden" name="lat"                 value="">
                <input type="hidden" name="lng"                 value="">
                <input type="hidden" name="geocoded_at"         value="">
                <input type="hidden" name="geocodifica_fallita" value="0">

                <div class="card-body">

                    <!-- Cliente -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Cliente</p>
                    <div class="row g-3 mb-4">
                        <?php if ($cliente): ?>
                            <div class="col-12">
                                <input type="hidden" name="cliente_id" value="<?= (int) $cliente['id'] ?>">
                                <input type="text" class="form-control" readonly
                                       value="<?= esc($cliente['tipo'] === 'persona_fisica'
                                           ? trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''))
                                           : $cliente['ragsoc']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="col-12">
                                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select name="cliente_id" class="form-select">
                                    <option value="">— seleziona —</option>
                                    <?php foreach ($clienti as $c): ?>
                                        <option value="<?= $c['id'] ?>"
                                                <?= old('cliente_id') == $c['id'] ? 'selected' : '' ?>>
                                            <?= esc($c['tipo'] === 'persona_fisica'
                                                ? trim(($c['cognome'] ?? '') . ' ' . ($c['nome'] ?? ''))
                                                : $c['ragsoc']) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        <?php endif ?>
                    </div>

                    <!-- Dati cantiere -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-bricks me-1"></i> Cantiere</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Titolo <span class="text-danger">*</span></label>
                            <input type="text" name="titolo" class="form-control" maxlength="150"
                                   placeholder="Es. Piscina interna/Esterna"
                                   value="<?= esc(old('titolo')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select">
                                <?php foreach ($tipiLabel as $val => $label): ?>
                                    <option value="<?= esc($val) ?>"
                                            <?= old('tipo', 'nuova_costruzione') === $val ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stato <span class="text-danger">*</span></label>
                            <select name="stato" class="form-select">
                                <?php foreach ($statiLabel as $val => $label): ?>
                                    <option value="<?= esc($val) ?>"
                                            <?= old('stato', 'aperto') === $val ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo intervento predefinito</label>
                            <select name="tipo_intervento_id" class="form-select">
                                <option value="">— nessuno —</option>
                                <?php foreach ($tipi as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= old('tipo_intervento_id') == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <small class="text-muted">Pre-compila il tipo nei nuovi interventi del cantiere.</small>
                        </div>
                    </div>

                    <!-- Luogo e referente -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-geo-alt me-1"></i> Luogo e referente</p>
                    <div class="row g-3 mb-1">
                        <div class="col-md-6">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" name="indirizzo" class="form-control"
                                   value="<?= esc(old('indirizzo')) ?>"
                                   placeholder="Indirizzo cantiere, se diverso da anagrafica cliente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Città</label>
                            <input type="text" name="citta" class="form-control"
                                   value="<?= esc(old('citta')) ?>"
                                   placeholder="Città cantiere, se diversa da anagrafica cliente">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100"
                                    data-geocoder
                                    title="Rileva coordinate dall'indirizzo">
                                <i class="bi bi-geo-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div id="geo-result" class="small mb-3 mt-1"></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Referente</label>
                            <input type="text" name="referente_nome" class="form-control" maxlength="150"
                                   value="<?= esc(old('referente_nome')) ?>"
                                   placeholder="es. Rossi (capo cantiere)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono referente</label>
                            <input type="tel" name="referente_telefono" class="form-control" maxlength="50"
                                   value="<?= esc(old('referente_telefono')) ?>"
                                   placeholder="339 1234567">
                        </div>
                    </div>

                    <!-- Periodo -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-calendar-range me-1"></i> Periodo</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Data inizio</label>
                            <input type="date" name="data_inizio" class="form-control"
                                   value="<?= esc(old('data_inizio')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data fine prevista</label>
                            <input type="date" name="data_fine_prevista" class="form-control"
                                   value="<?= esc(old('data_fine_prevista')) ?>">
                        </div>
                    </div>

                    <!-- Note -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-card-text me-1"></i> Note generali</p>
                    <div class="row g-3 mb-2">
                        <div class="col-12">
                            <textarea name="note" class="form-control" rows="3"
                                      placeholder="Note generali del cantiere (distinte dal diario)"><?= esc(old('note')) ?></textarea>
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= esc($from ?: base_url('cantieri')) ?>" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm ms-auto">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/geocoding.js') ?>"></script>
<?= $this->endSection() ?>
