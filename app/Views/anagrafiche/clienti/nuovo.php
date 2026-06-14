<?php
/**
 * @var array $tecnici  Righe da PersonaleModel::elencoPerGruppi(['tecnico'])
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Nuovo cliente<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/clienti') ?>">Clienti</a></li>
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
                <h3 class="card-title mb-0"><i class="bi bi-person-plus me-2"></i>Nuovo cliente</h3>
            </div>
            <form action="<?= base_url('anagrafiche/clienti/store') ?>" method="post">
                <?= csrf_field() ?>

                <!-- Campi geocodifica — compilati dal JS prima del submit -->
                <input type="hidden" name="lat"                  value="">
                <input type="hidden" name="lng"                  value="">
                <input type="hidden" name="geocoded_at"          value="">
                <input type="hidden" name="geocodifica_fallita"  value="0">

                <div class="card-body">

                    <!-- Tipo cliente -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-tag me-1"></i> Tipo cliente</p>
                    <div class="row g-3 mb-4">
                        <div class="col-auto">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_societa"
                                       value="societa" <?= old('tipo', 'societa') === 'societa' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tipo_societa">Società / Ditta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_persona"
                                       value="persona_fisica" <?= old('tipo') === 'persona_fisica' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tipo_persona">Persona fisica</label>
                            </div>
                        </div>
                    </div>

                    <!-- Anagrafica — campi condizionali per tipo -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-building me-1"></i> Anagrafica</p>
                    <div class="row g-3 mb-4">

                        <div class="col-1">
                            <label class="form-label">Codice <i class="bi bi-info-circle text-muted small" data-bs-toggle="tooltip" data-bs-title="Codice nel software di contabilità esterno"></i></label>
                            <input type="text" name="codice_esterno" class="form-control"
                                   value="<?= esc(old('codice_esterno')) ?>" maxlength="50">
                        </div>

                        <div id="campi-societa" class="col-11">
                            <label class="form-label">Ragione sociale <span class="text-danger">*</span></label>
                            <input type="text" name="ragsoc" class="form-control"
                                   value="<?= esc(old('ragsoc')) ?>">
                        </div>

                        <div id="campi-persona" class="col-8" style="display:none">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Cognome <span class="text-danger">*</span></label>
                                    <input type="text" name="cognome" class="form-control"
                                           value="<?= esc(old('cognome')) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Nome <span class="text-danger">*</span></label>
                                    <input type="text" name="nome" class="form-control"
                                           value="<?= esc(old('nome')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">P.IVA</label>
                            <input type="text" name="piva" class="form-control"
                                   value="<?= esc(old('piva')) ?>" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Codice fiscale</label>
                            <input type="text" name="cfisc" class="form-control"
                                   value="<?= esc(old('cfisc')) ?>" maxlength="16">
                        </div>
                    </div>

                    <!-- Indirizzo -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-geo me-1"></i> Indirizzo operativo</p>
                    <div class="row g-3 mb-1">
                        <div class="col-12">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" name="indirizzo" class="form-control"
                                   value="<?= esc(old('indirizzo')) ?>" placeholder="es. Via Aurelia, 296">
                        </div>
                        <div class="col-3">
                            <label class="form-label">CAP</label>
                            <input type="text" name="cap" class="form-control"
                                   value="<?= esc(old('cap')) ?>" maxlength="10" placeholder="00000">
                        </div>
                        <div class="col-5">
                            <label class="form-label">Città</label>
                            <input type="text" name="citta" class="form-control"
                                   value="<?= esc(old('citta')) ?>">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Prov.</label>
                            <input type="text" name="provincia" class="form-control"
                                   value="<?= esc(old('provincia')) ?>" maxlength="5">
                        </div>
                        <div class="col-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100"
                                    data-geocoder
                                    title="Rileva coordinate dall'indirizzo">
                                <i class="bi bi-geo-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div id="geo-result" class="small mb-3 mt-1"></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nazione</label>
                            <input type="text" name="nazione" class="form-control"
                                   value="<?= esc(old('nazione', 'ITALIA')) ?>">
                        </div>
                    </div>

                    <!-- Contatti -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-telephone me-1"></i> Contatti</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="<?= esc(old('telefono')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= esc(old('email')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contatti aggiuntivi <small class="text-muted">— testo libero</small></label>
                            <textarea name="contatti" class="form-control" rows="3"
                                      placeholder="es. Sig. Rossi (responsabile): 333 000 0000"><?= esc(old('contatti')) ?></textarea>
                        </div>
                    </div>

                    <!-- Gestione -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-sliders me-1"></i> Gestione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Zona</label>
                            <select name="zona" class="form-select">
                                <option value="">— auto da geocodifica —</option>
                                <option value="-1" <?= old('zona') === '-1' ? 'selected' : '' ?>>Ventimiglia (da Andora verso Francia)</option>
                                <option value="0"  <?= old('zona') === '0'  ? 'selected' : '' ?>>Ceriale (da Andora a Loano)</option>
                                <option value="1"  <?= old('zona') === '1'  ? 'selected' : '' ?>>Savona (da Loano in poi)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tecnico preferito</label>
                            <select name="tecnico_preferito_id" class="form-select">
                                <option value="">— nessuno —</option>
                                <?php foreach ($tecnici as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= old('tecnico_preferito_id') == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['cognome'] . ' ' . $t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
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
                    <a href="<?= base_url('anagrafiche/clienti') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-primary ms-auto">
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
<script>
(function () {
    // Mostra i campi giusti in base al tipo selezionato
    function toggleTipo(tipo) {
        document.getElementById('campi-societa').style.display = tipo === 'societa'        ? '' : 'none';
        document.getElementById('campi-persona').style.display = tipo === 'persona_fisica' ? '' : 'none';
    }

    document.querySelectorAll('[name="tipo"]').forEach(function (radio) {
        radio.addEventListener('change', function () { toggleTipo(this.value); });
    });

    // Inizializza al caricamento (gestisce anche il caso old() dopo redirect da errore)
    var tipoAttivo = document.querySelector('[name="tipo"]:checked');
    if (tipoAttivo) toggleTipo(tipoAttivo.value);
})();
</script>
<?= $this->endSection() ?>
